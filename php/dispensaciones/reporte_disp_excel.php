<?php
ini_set('memory_limit', '2048M');
set_time_limit(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");




$permiso = permiso();
$campos_costos = "COALESCE((IF(PD.Costo = 0, NULL, PD.Costo)),(CtP.Costo_Promedio),0) AS Costo, IFNULL(ROUND(UC.Precio, 2), 0)  AS Ultimo_Costo,";
$tablas_costos = "LEFT JOIN
Costo_Promedio CtP ON CtP.Id_Producto = P.Id_Producto
LEFT JOIN
(SELECT PAR.Precio, PAR.Id_Producto FROM  Producto_Acta_Recepcion PAR WHERE PAR.Id_Producto_Acta_Recepcion IN (SELECT MAX(PAR.Id_Producto_Acta_Recepcion) FROM Producto_Acta_Recepcion PAR GROUP BY PAR.Id_Producto)) UC ON UC.Id_Producto = PD.Id_Producto 
";
if (!$permiso) {

	$campos_costos = "-- ";
	$tablas_costos = '-- ';
}
$condicion = [];

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	array_push($condicion, 'DATE(D.Fecha_Actual) BETWEEN "' . $fecha_inicio . '" AND "' . $fecha_fin . '"');
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	array_push($condicion, "DP.Id_Departamento=$_REQUEST[dep]");
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	array_push($condicion, "D.Id_Punto_Dispensacion=$_REQUEST[pto]");
}
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	array_push($condicion, "D.Identificacion_Funcionario=$_REQUEST[func]");
}
if (isset($_REQUEST['pac']) && $_REQUEST['pac'] != "") {
	array_push($condicion, "D.Numero_Documento=$_REQUEST[pac]");
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
	array_push($condicion, "D.Id_Tipo_Servicio='$_REQUEST[tipo]'");
}

if (isset($_REQUEST['pend']) && $_REQUEST['pend'] == "No") {
	array_push($condicion, "PD.Cantidad_Formulada=PD.Cantidad_Entregada");
} elseif (isset($_REQUEST['pend']) && $_REQUEST['pend'] == "Si") {
	array_push($condicion, "PD.Cantidad_Formulada>PD.Cantidad_Entregada");
}
if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
	array_push($condicion, "D.Codigo='$_REQUEST[dis]'");
}

if (isset($_REQUEST['cte']) && $_REQUEST['cte'] != "") {
	array_push($condicion, "PA.Nit='$_REQUEST[cte]'");
}
if (isset($_REQUEST['prod']) && $_REQUEST['prod'] != "") {
	array_push($condicion, "P.Nombre_Comercial LIKE '%$_REQUEST[prod]%'");
}
if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != "") {
	array_push($condicion, "PA.Nit LIKE '%$_REQUEST[nit]%'");
}
if (isset($_REQUEST['estado_facturacion']) && $_REQUEST['estado_facturacion'] != "") {
	array_push($condicion, "D.Estado_Facturacion LIKE '%$_REQUEST[estado_facturacion]%'");
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
	array_push($condicion, "P.Codigo_Cum LIKE '%$_REQUEST[cum]%'");
}
if (isset($_REQUEST['servicio']) && $_REQUEST['servicio'] != "") {
	array_push($condicion, "D.Id_Servicio=$_REQUEST[servicio] ");
}
if (isset($_REQUEST['estado_disp']) && $_REQUEST['estado_disp'] != "") {
	array_push($condicion, "D.Estado_Dispensacion LIKE '%$_REQUEST[estado_disp]%'");
}
array_push($condicion, 'D.Estado != "Anulada"');

$condicion = "WHERE " . implode(" AND ", $condicion);

$query = "SELECT 
	D.Codigo,
	D.Fecha_Actual AS 'Fecha Solicitud',
	IFNULL((CASE WHEN D.Id_Tipo_Servicio = 7 THEN (FacCap.Codigo)ELSE (Fac.Codigo)END),'No Facturada') AS 'Numero Factura',
	IFNULL((CASE WHEN D.Id_Tipo_Servicio = 7 THEN (FacCap.Fecha_Documento)ELSE (Fac.Fecha_Documento)END),'No Facturada') AS 'Fecha Factura',
	PA.Nit AS 'Ident. Tercero',
	PA.EPS AS 'Nombre Tercero',
	P.Codigo_Cum as Cum,
	P.Nombre_Comercial as 'Nombre Comercial',
	CONCAT_WS(' ',P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) AS Nombre,
	P.Embalaje,
	P.Laboratorio_Generico as 'Laboratorio Generico',
	P.Laboratorio_Comercial as 'Laboratorio Comercial',
	PD.Lote,
	COALESCE(Inv.Fecha_Vencimiento, 0) AS 'Fecha Vencimiento',
	PD.Cantidad_Formulada as 'Cantidad Formulada',
	PD.Cantidad_Entregada as 'Cantidad Entregada',
	(PD.Cantidad_Formulada - PD.Cantidad_Entregada) AS 'Cantidad Pendiente',
	IF(PD.Generico = 1, 'Generico', NULL) AS Generico,
	$campos_costos
	PF.Precio AS Precio_Venta,
	CONCAT_WS(' ',CONCAT(F.Identificacion_Funcionario, ' -'),F.Nombres,F.Apellidos) AS Funcionario_Digita,
	PDI.Nombre AS 'Punto Dispensacion',
	PA.Tipo_Documento as 'Tipo Documento',
	PA.Id_Paciente as 'Id. Paciente',
	CONCAT_WS(' - ',PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido) AS 'Nombre Paciente',
	PA.Genero AS 'Genero Paciente',
	PA.Regimen AS 'Regimen Paciente',
	DP.Nombre AS Departamento,
	DP.Codigo AS 'Codigo Departamento',
	Mun.Nombre AS Ciudad,
	D.CIE AS Codigo_DX,
	SERV.Nombre AS Tipo,
	TServ.Nombre AS 'Tipo Servicio',
	D.Doctor,
	D.IPS,
	PA.EPS AS 'EPS Paciente',
	POS.numeroAutorizacion as 'Numero Autorizacion',
	DATE(POS.fechaHoraAutorizacion) as 'Fecha Autorizacion',
	PD.Numero_Prescripcion as 'Numero Prescripcion',
	D.Fecha_Formula as 'Fecha Formula',
	COALESCE(Act.Fecha, PD.Fecha_Carga, D.Fecha_Actual)as 'Fecha Entrega',
	D.Cuota AS 'Cuota Moderadora',
	'' AS 'Cuota Recuperacion',
	D.Causal_No_Pago as 'Causal No Pago',
	IF(A.Id_Auditoria IS NULL, 'No', 'Si') AS 'Auditada?',
	IF(A.Id_Auditoria IS NULL,'', A.Funcionario_Preauditoria) AS 'Funcionario Auditor',
	D.Estado_Dispensacion as 'Estado Autorizacion',
	PA.Telefono,
	PA.Direccion,

	IFNULL((CASE WHEN(A.Estado = 'Aceptar' OR A.Estado = 'Auditado' OR A.Estado = 'Auditada') THEN 'Auditada'
			WHEN A.Estado = 'Anulada' THEN 'Anulada'
			WHEN (A.Estado = 'Pre Auditada' OR A.Estado = 'Rechazar' OR A.Estado = 'Con Observacion') THEN 'Sin Auditar'
		END),'Sin auditar') AS 'Estado Auditoria',

	IF(D.Firma_Reclamante != '' OR D.Acta_Entrega IS NOT NULL,'Si','No') AS Soporte

	FROM
			Producto_Dispensacion PD
		INNER JOIN
			Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion
		LEFT JOIN
			Positiva_Data POS ON POS.Id_Dispensacion = D.Id_Dispensacion
		LEFT JOIN 
			Inventario_Nuevo Inv ON Inv.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
		INNER JOIN
			Servicio SERV ON SERV.Id_Servicio = D.Id_Servicio
		INNER JOIN
			Tipo_Servicio TServ ON TServ.Id_Tipo_Servicio = D.Id_Tipo_Servicio
		LEFT JOIN
			Factura_Capita FacCap ON FacCap.Id_Factura_Capita = D.Id_Factura
		LEFT JOIN
			Factura Fac ON Fac.Id_Factura = D.Id_Factura
		INNER JOIN
			Producto P ON P.Id_Producto = PD.Id_Producto
		$tablas_costos
		INNER JOIN
			(SELECT Nombres, Apellidos, Identificacion_Funcionario, Ver_Costo FROM Funcionario) F ON F.Identificacion_Funcionario = D.Identificacion_Funcionario
		INNER JOIN
			Punto_Dispensacion PDI ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
		INNER JOIN
			(SELECT R.Nombre AS 'Regimen', P.Id_Paciente,P.Nit,P.EPS,P.Tipo_Documento,P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido,P.Genero,P.Id_Regimen,P.Codigo_Municipio,
			P.Direccion, P.Telefono	FROM Paciente P LEFT JOIN Regimen R ON P.Id_Regimen= R.Id_Regimen ) PA ON PA.Id_Paciente = D.Numero_Documento
		INNER JOIN
			Departamento DP ON PDI.Departamento = DP.Id_Departamento
		Left JOIN
			Municipio Mun ON Mun.Codigo = PA.Codigo_Municipio
		LEFT JOIN
			(SELECT A.Id_Auditoria, A.Estado, CONCAT_WS(' ', CONCAT(F.Identificacion_Funcionario, ' -'), F.Nombres, F.Apellidos) AS Funcionario_Preauditoria,
			A.Id_Dispensacion FROM Auditoria A LEFT JOIN Funcionario F ON F.Identificacion_Funcionario = A.Funcionario_Preauditoria) A ON A.Id_Dispensacion = D.Id_Dispensacion
		LEFT JOIN 
			(Select ACT.Fecha, ACT.Id_Dispensacion From Actividades_Dispensacion ACT Where ACT.Detalle LIKE '%Se entrego la dispensacion pendiente%' group by ACT.Id_Dispensacion) Act On Act.Id_Dispensacion=D.Id_Dispensacion
		LEFT JOIN 
			Producto_Factura PF ON PF.Id_Producto_Dispensacion= PD.Id_Producto_Dispensacion AND PF.Id_Factura = Fac.Id_Factura
	
	$condicion 
	ORDER BY Fecha_Actual Asc";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones = $oCon->getData();
unset($oCon);

echo json_encode($dispensaciones);
exit;

function permiso()
{
	$identificacion_funcionario = $_SESSION["user"];
	if ($identificacion_funcionario == '') {
		$identificacion_funcionario = $_REQUEST['funcionario'];
	}

	$query = 'SELECT Ver_Costo FROM Funcionario WHERE Ver_Costo="Si" AND Identificacion_Funcionario=' . $identificacion_funcionario;
	$oCon = new consulta();
	$oCon->setQuery($query);
	$permisos = $oCon->getData();
	unset($oCon);

	$status = false; // Sin permisos

	if ($permisos) {
		$status = true;
	}

	return $status;
}
