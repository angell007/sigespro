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

/* require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php'; */
// header( "Content-type: application/json");
// echo "Reporte no disponible para descarga"; exit;



$permiso = permiso();
// $objPHPExcel = new PHPExcel;

$condicion =[] ;

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	array_push($condicion ,'DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"');
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
} elseif(isset($_REQUEST['pend']) && $_REQUEST['pend'] == "Si")  {
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

$condicion = "WHERE ".implode(" AND ", $condicion); 

$query =
"SELECT 

D.Codigo,
D.Fecha_Actual AS 'Fecha Solicitud',
IFNULL((CASE WHEN D.Id_Tipo_Servicio = 7 THEN (FacCap.Codigo)ELSE (Fac.Codigo)END),'No Facturada') AS 'Numero Factura',
IFNULL((CASE WHEN D.Id_Tipo_Servicio = 7 THEN (FacCap.Fecha_Documento)ELSE (Fac.Fecha_Documento)END),'No Facturada') AS 'Fecha Factura', 
PA.Nit AS 'Ident. Tercero',
PA.EPS AS 'Nombre Tercero',
PD.Cum,
P.Nombre_Comercial as 'Nombre Comercial',
CONCAT_WS(' ',P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) AS Nombre,
P.Embalaje,
P.Laboratorio_Generico as 'Laboratorio Generico',
P.Laboratorio_Comercial as 'Laboratorio Comercial',
PD.Lote,
COALESCE(IV.Fecha_Vencimiento,	Inv.Fecha_Vencimiento, 0) AS 'Fecha Vencimiento',
PD.Cantidad_Formulada as 'Cantidad Formulada',
PD.Cantidad_Entregada as 'Cantidad Entregada',
(PD.Cantidad_Formulada - PD.Cantidad_Entregada) AS 'Cantidad Pendiente',
IF(PD.Generico = 1, 'Generico', NULL) AS Generico,
COALESCE((IF(PD.Costo = 0, NULL, PD.Costo)),(CtP.Costo_Promedio),0) AS Costo,
IFNULL(ROUND(UC.Precio, 2), 0)  AS Ultimo_Costo,
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
PA.Cod_Municipio_Dane AS Ciudad,
D.CIE AS Codigo_DX,
SERV.Nombre AS Tipo,
TServ.Nombre AS 'Tipo Servicio',
D.Doctor,
D.IPS,
PA.EPS AS 'EPS Paciente',
PD.Numero_Autorizacion as 'Numero Autorizacion',
PD.Fecha_Autorizacion as 'Fecha Autorizacion',
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
		Inventario_Nuevo Inv ON Inv.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
	LEFT JOIN 
		Inventario_Viejo IV ON IV.Id_Inventario = PD.Id_Inventario
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
	LEFT JOIN
		Costo_Promedio CtP ON CtP.Id_Producto = P.Id_Producto
	INNER JOIN
		(SELECT Nombres, Apellidos, Identificacion_Funcionario, Ver_Costo FROM Funcionario) F ON F.Identificacion_Funcionario = D.Identificacion_Funcionario
	INNER JOIN
		Punto_Dispensacion PDI ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
	INNER JOIN
		(SELECT R.Nombre AS 'Regimen', P.Id_Paciente,P.Nit,P.EPS,P.Tipo_Documento,P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido,P.Genero,P.Id_Regimen,P.Cod_Municipio_Dane,
		P.Direccion, P.Telefono	FROM Paciente P LEFT JOIN Regimen R ON P.Id_Regimen= R.Id_Regimen ) PA ON PA.Id_Paciente = D.Numero_Documento
	INNER JOIN
		Departamento DP ON PDI.Departamento = DP.Id_Departamento
	LEFT JOIN
		(SELECT A.Id_Auditoria, A.Estado, CONCAT_WS(' ', CONCAT(F.Identificacion_Funcionario, ' -'), F.Nombres, F.Apellidos) AS Funcionario_Preauditoria,
		A.Id_Dispensacion FROM Auditoria A LEFT JOIN Funcionario F ON F.Identificacion_Funcionario = A.Funcionario_Preauditoria) A ON A.Id_Dispensacion = D.Id_Dispensacion
	LEFT JOIN 
		(Select ACT.Fecha, ACT.Id_Dispensacion From Actividades_Dispensacion ACT Where ACT.Detalle LIKE '%Se entrego la dispensacion pendiente%' group by ACT.Id_Dispensacion) Act On Act.Id_Dispensacion=D.Id_Dispensacion
	LEFT JOIN
		(SELECT PAR.Precio, PAR.Id_Producto FROM  Producto_Acta_Recepcion PAR WHERE PAR.Id_Producto_Acta_Recepcion IN (SELECT MAX(PAR.Id_Producto_Acta_Recepcion) FROM Producto_Acta_Recepcion PAR GROUP BY PAR.Id_Producto)) UC ON UC.Id_Producto = PD.Id_Producto 
	LEFT JOIN 
		Producto_Factura PF ON PF.Id_Producto_Dispensacion= PD.Id_Producto_Dispensacion AND PF.Id_Factura = Fac.Id_Factura
 
$condicion 
ORDER BY Fecha_Actual Asc";


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);
$i=0;
foreach ($dispensaciones as $disp ) {
	if(!$permiso){
		unset($disp['Costo']);
		unset($disp['Ultimo_Costo']);
	}
	$dispensaciones[$i]=$disp;
	$i++;
}
echo json_encode($dispensaciones);
exit;
/* 
$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Dispensacion');
 */

$contenido = '<table border="1"><tr>';


$contenido .= '<td>Codigo</td>';
$contenido .= '<td>Fecha_Solicitud</td>';
$contenido .= '<td>Numero_Factura</td>';
$contenido .= '<td>Fecha_Factura</td>';
$contenido .= '<td>Identif_Tercero</td>';
$contenido .= '<td>Nombre_Tercero</td>';
$contenido .= '<td>Cum</td>';
$contenido .= '<td>Nombre_Comercial</td>';
$contenido .= '<td>Nombre</td>';
$contenido .= '<td>Embalaje</td>';
$contenido .= '<td>Laboratorio_Generico</td>';
$contenido .= '<td>Laboratorio_Comercial</td>';
$contenido .= '<td>Lote</td>'; 
$contenido .= '<td>Fecha_Vencimiento</td>';
$contenido .= '<td>Cantidad_Formulada</td>';
$contenido .= '<td>Cantidad_Entregada</td>';
$contenido .= '<td>Cantidad_Pendiente</td>';
$contenido .= '<td>Generico</td>';
if($permiso){
	$contenido .= '<td>Costo</td>';
	$contenido .= '<td>Ultimo Costo</td>';
}
$contenido .= '<td>Precio Venta</td>';

$contenido .= '<td>Funcionario_Digita</td>';
$contenido .= '<td>Punto_Dispensacion</td>';
$contenido .= '<td>Tipo_Documento</td>';
$contenido .= '<td>Id_Paciente</td>';
$contenido .= '<td>Nombre_Paciente</td>';
$contenido .= '<td>Genero_Paciente</td>';
$contenido .= '<td>Regimen_Paciente</td>';
$contenido .= '<td>Departamento</td>';
$contenido .= '<td>Codigo_Departamento</td>';
$contenido .= '<td>Ciudad</td>';
$contenido .= '<td>Codigo_DX</td>';
$contenido .= '<td>Tipo</td>';
$contenido .= '<td>Tipo Servicio</td>';
$contenido .= '<td>Doctor</td>';
$contenido .= '<td>IPS</td>';
$contenido .= '<td>EPS_Paciente</td>';
$contenido .= '<td>Numero_Autorizacion</td>';
$contenido .= '<td>Fecha_Autorizacion</td>';
$contenido .= '<td>Numero_Prescripcion</td>';
$contenido .= '<td>Fecha_Formula</td>';
$contenido .= '<td>Fecha_Entrega</td>';
$contenido .= '<td>Cuota_Moderadora</td>';
$contenido .= '<td>Cuota_Recuperacion</td>';
$contenido .= '<td>Causal_No_Pago</td>';
$contenido .= '<td>Auditada?</td>';
$contenido .= '<td>Funcionario Auditor</td>';
$contenido .= '<td>Estado_Dispensacion</td>';
$contenido .= '<td>Telefono</td>';
$contenido .= '<td>Direccion</td>';
$contenido .= '<td>Estado Auditoria</td>';
$contenido .= '<td>Soportes</td>';

$contenido .= '</tr>';


/* $objSheet->getStyle('A1:AM1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:AM1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:AM1')->getFont()->setBold(true);
$objSheet->getStyle('A1:AM1')->getFont()->getColor()->setARGB('FFFFFFFF'); */

$dis = '';
$j=1;
foreach($dispensaciones as $disp){ $j++;

	// continue;
	$contenido .= '<tr>';
	
	$contenido .= '<td>' . $disp["Codigo"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Actual"] . '</td>';
	$contenido .= '<td>' . $disp["Numero_Factura"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Factura"] . '</td>';
	$contenido .= '<td>' . $disp["Identif_Tercero"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre_Tercero"] . '</td>';
	$contenido .= '<td>' . $disp["Cum"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre_Comercial"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre"] . '</td>';
	$contenido .= '<td>' . $disp["Embalaje"] . '</td>';
	$contenido .= '<td>' . $disp["Laboratorio_Generico"] . '</td>';
	$contenido .= '<td>' . $disp["Laboratorio_Comercial"] . '</td>';
	$contenido .= '<td>' . $disp["Lote"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Vencimiento"] . '</td>';
	$contenido .= '<td>' . $disp["Cantidad_Formulada"] . '</td>';
	$contenido .= '<td>' . $disp["Cantidad_Entregada"] . '</td>';
	$contenido .= '<td>' . $disp["Cantidad_Pendiente"] . '</td>';
	$contenido .= '<td>' . $disp["Generico"] . '</td>';
	if($permiso){
		$contenido .= '<td>' . number_format($disp["Costo"],2,",","") . '</td>';
		$contenido .= '<td>' . number_format($disp["Ultimo_Costo"],2,",","") . '</td>';
	}
	$contenido .= '<td>' . $disp["Precio_Venta"] . '</td>';
	
	$contenido .= '<td>' . $disp["Funcionario_Digita"] . '</td>';
	$contenido .= '<td>' . $disp["Punto_Dispensacion"] . '</td>';
	$contenido .= '<td>' . $disp["Tipo_Documento"] . '</td>';
	$contenido .= '<td>' . $disp["Id_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Nombre_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Genero_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Regimen_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Departamento"] . '</td>';
	$contenido .= '<td>' . $disp["Codigo_Departamento"] . '</td>';
	$contenido .= '<td>' . $disp["Ciudad"] . '</td>';
	$contenido .= '<td>' . $disp["Codigo_DX"] . '</td>';
	$contenido .= '<td>' . $disp["Tipo"] . '</td>';
	$contenido .= '<td>' . $disp["Tipo_Servicio"] . '</td>';
	$contenido .= '<td>' . $disp["Doctor"] . '</td>';
	$contenido .= '<td>' . $disp["IPS"] . '</td>';
	$contenido .= '<td>' . $disp["EPS_Paciente"] . '</td>';
	$contenido .= '<td>' . $disp["Numero_Autorizacion"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Autorizacion"] . '</td>';
	$contenido .= '<td>' . $disp["Numero_Prescripcion"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Formula"] . '</td>';
	$contenido .= '<td>' . $disp["Fecha_Entrega"] . '</td>';
	if ($dis == '' || $dis != $disp['Id_Dispensacion']) {
		$contenido .= '<td>' . $disp["Cuota_Moderadora"] . '</td>';
		$dis = $disp['Id_Dispensacion'];
	} else {
		$contenido .= '<td> 0 </td>';
	}
	
	$contenido .= '<td>' . $disp["Cuota_Recuperacion"] . '</td>';
	$contenido .= '<td>' . $disp["Causal_No_Pago"] . '</td>';
	$contenido .= '<td>' . $disp["Auditada"] . '</td>';
	$contenido .= '<td>' . $disp["Funcionario_Preauditoria"] . '</td>';
	$contenido .= '<td>' . $disp["Estado_Dispensacion"] . '</td>';
	$contenido .= '<td>' . $disp["Telefono"] . '</td>';
	$contenido .= '<td>' . $disp["Direccion"] . '</td>';
	$contenido .= '<td>' . $disp["Estado_Auditoria"] . '</td>';
	$contenido .= '<td>' . $disp["Soporte"] . '</td></tr>';

	
}

$contenido .= '</table>';

// header("Content-type:application/json");
// echo json_encode($dispensaciones); exit;

/* $objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getStyle('A1:AM'.$j)->getAlignment()->setWrapText(true); */



// header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
header('Content-Disposition: attachment;filename="Reporte_Dispensacion.xls"');
header('Cache-Control: max-age=0'); 
echo $contenido;

function permiso(){
	$identificacion_funcionario = $_SESSION["user"];
	if($identificacion_funcionario==''){
		$identificacion_funcionario=$_REQUEST['funcionario'];
	}
	
	$query = 'SELECT Ver_Costo FROM Funcionario WHERE Ver_Costo="Si" AND Identificacion_Funcionario='.$identificacion_funcionario; 
	$oCon= new consulta();
	$oCon->setQuery($query);
	$permisos = $oCon->getData();
	unset($oCon);

	$status = false; // Sin permisos

	if ($permisos) {
		$status = true;
	}

	return $status;
}

?>