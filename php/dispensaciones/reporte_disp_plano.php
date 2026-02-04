<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','2048M');
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

header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte Dispensacion.csv"');


// $objPHPExcel = new PHPExcel;
$permiso = permiso();

$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	$condicion .= " AND DP.Id_Departamento=$_REQUEST[dep]";
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	$condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[pto]";
}
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	$condicion .= " AND D.Identificacion_Funcionario=$_REQUEST[func]";
}
if (isset($_REQUEST['pac']) && $_REQUEST['pac'] != "") {
	$condicion .= " AND D.Numero_Documento=$_REQUEST[pac]";
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
	$condicion .= " AND D.Id_Tipo_Servicio='$_REQUEST[tipo]'";

}

if (isset($_REQUEST['pend']) && $_REQUEST['pend'] == "No") {
	$condicion .= " AND PD.Cantidad_Formulada=PD.Cantidad_Entregada";
} elseif(isset($_REQUEST['pend']) && $_REQUEST['pend'] == "Si")  {
	$condicion .= " AND PD.Cantidad_Formulada>PD.Cantidad_Entregada";
}
if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
	$condicion .= " AND D.Codigo='$_REQUEST[dis]'";
}

if (isset($_REQUEST['cte']) && $_REQUEST['cte'] != "") {
	$condicion .= " AND PA.Nit='$_REQUEST[cte]'";
}
if (isset($_REQUEST['prod']) && $_REQUEST['prod'] != "") {
	$condicion .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[prod]%'";
}
if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != "") {
	$condicion .= " AND PA.Nit LIKE '%$_REQUEST[nit]%'";
}
if (isset($_REQUEST['estado_facturacion']) && $_REQUEST['estado_facturacion'] != "") {
	$condicion .= " AND D.Estado_Facturacion LIKE '%$_REQUEST[estado_facturacion]%'";
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
	$condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
}
if (isset($_REQUEST['servicio']) && $_REQUEST['servicio'] != "") {
	$condicion .= " AND D.Id_Servicio=$_REQUEST[servicio] ";
}
if (isset($_REQUEST['estado_disp']) && $_REQUEST['estado_disp'] != "") {
	$condicion .= " AND D.Estado_Dispensacion LIKE '%$_REQUEST[estado_disp]%'";
}



$query = 'SELECT 
D.Id_Dispensacion,
D.Codigo, 
D.Fecha_Actual,
IFNULL((
	CASE
		WHEN D.Id_Tipo_Servicio = 7 THEN (SELECT Codigo FROM Factura_Capita WHERE Id_Factura_Capita = D.Id_Factura)
		ELSE (SELECT Codigo FROM Factura WHERE Id_Factura = D.Id_Factura)
	END
),"No Facturada") as Numero_Factura, 
IFNULL((
	CASE
		WHEN D.Id_Tipo_Servicio = 7 THEN (SELECT Fecha_Documento FROM Factura_Capita WHERE Id_Factura_Capita = D.Id_Factura)
		ELSE (SELECT Fecha_Documento FROM Factura WHERE Id_Factura = D.Id_Factura)
	END
),"No Facturada") as Fecha_Factura, 
PA.Nit as Identif_Tercero, 
PA.EPS as Nombre_Tercero, 
PD.Cum, 
P.Nombre_Comercial, 
CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) as Nombre, 
P.Embalaje, 
P.Laboratorio_Generico, 
P.Laboratorio_Comercial, 
PD.Cantidad_Formulada, 
PD.Cantidad_Entregada,PD.Lote,

COALESCE(PD.Costo,(SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = PD.Id_Producto),0) AS Costo,

COALESCE((SELECT Fecha_Vencimiento FROM Inventario_Viejo  WHERE Id_Inventario = PD.Id_Inventario),
            (SELECT Fecha_Vencimiento FROM Inventario_Nuevo  WHERE Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo),0) AS Fecha_Vencimiento,


(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente, 

IFNULL(
    (SELECT ROUND(Precio,2) FROM Producto_Acta_Recepcion WHERE Id_Producto = PD.Id_Producto ORDER BY Id_Producto_Acta_Recepcion DESC LIMIT 1),
    0) as Ultimo_Costo, 
CONCAT_WS(" ",CONCAT(F.Identificacion_Funcionario," -"),F.Nombres,F.Apellidos) as Funcionario_Digita, 
PDI.Nombre  as Punto_Dispensacion, 
PA.Tipo_Documento, 
PA.Id_Paciente, 
CONCAT_WS(" - ",PA.Primer_Nombre, PA.Segundo_Nombre, PA.Primer_Apellido, PA.Segundo_Apellido) as Nombre_Paciente, 
PA.Genero as Genero_Paciente, 
(SELECT R.Nombre FROM Regimen R WHERE R.Id_Regimen = PA.Id_Regimen) as Regimen_Paciente,
DP.Codigo as Codigo_Departamento, DP.Nombre as Departamento,
PA.Cod_Municipio_Dane as Ciudad, D.CIE as Codigo_DX,
 (SELECT Nombre FROM Servicio where Id_Servicio=D.Id_Servicio) as Tipo,
 (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = D.Id_Tipo_Servicio) AS Tipo_Servicio, 
 D.Doctor, 
 D.IPS, 
 PA.EPS as EPS_Paciente, 
 PA.Direccion, PA.Telefono,
 PD.Numero_Autorizacion, 
 PD.Fecha_Autorizacion, 
 PD.Numero_Prescripcion, 
 D.Fecha_Formula, 
 /* CAMBIO CARLOS IFNULL(PD.Fecha_Carga,D.Fecha_Actual) as Fecha_Entrega, */
 COALESCE( (SELECT A.Fecha FROM Actividades_Dispensacion A WHERE A.Id_Dispensacion = D.Id_Dispensacion AND A.Detalle LIKE "%Se entrego la dispensacion pendiente%" ORDER BY DATE(A.Fecha) DESC LIMIT 1), PD.Fecha_Carga,D.Fecha_Actual ) AS Fecha_Entrega,

 D.Cuota as Cuota_Moderadora, 
 "" as Cuota_Recuperacion, 
 D.Causal_No_Pago, 
 D.Estado_Dispensacion,
 IF(A.Id_Auditoria IS NULL, "No", "Si") AS Auditada,
 IF(A.Id_Auditoria IS NULL, "", A.Funcionario_Preauditoria) AS Funcionario_Preauditoria,
 IFNULL((CASE WHEN (A.Estado = "Aceptar"  OR A.Estado="Auditado" OR A.Estado="Auditada") THEN "Auditada"
 WHEN A.Estado = "Anulada"  THEN "Anulada"
 WHEN (A.Estado = "Pre Auditada" OR A.Estado="Rechazar" OR  A.Estado="Con Observacion") THEN "Sin Auditar"
  END) , "Sin auditar") AS Estado_Auditoria, IF(D.Firma_Reclamante!="" OR D.Acta_Entrega IS NOT NULL, "Si", "No" ) as Soporte
FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D
ON PD.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Producto P
On P.Id_Producto = PD.Id_Producto
INNER JOIN (SELECT Nombres, Apellidos, Identificacion_Funcionario FROM Funcionario) F 
ON F.Identificacion_Funcionario = D.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion PDI
ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN (SELECT Id_Paciente, Nit, EPS, Tipo_Documento, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Genero, Id_Regimen, Cod_Municipio_Dane,Direccion, Telefono  FROM Paciente) PA
ON PA.Id_Paciente = D.Numero_Documento
INNER JOIN Departamento DP
ON PDI.Departamento=DP.Id_Departamento
LEFT JOIN (SELECT A.Id_Auditoria, A.Estado, CONCAT_WS(" ",CONCAT(F.Identificacion_Funcionario," -"),F.Nombres,F.Apellidos) AS Funcionario_Preauditoria, A.Id_Dispensacion FROM Auditoria A INNER JOIN Funcionario F ON F.Identificacion_Funcionario = A.Funcionario_Preauditoria ) A
ON A.Id_Dispensacion = D.Id_Dispensacion
WHERE D.Estado != "Anulada"
'.$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);

echo "Codigo;Fecha_Solicitud;Numero_Factura;Fecha_Factura;Identif_Tercero;Nombre_Tercero;Cum;Nombre_Comercial;Nombre;Embalaje;Laboratorio_Generico;Laboratorio_Comercial;Lote;Fecha_Vencimiento;Cantidad_Formulada;Cantidad_Entregada;Cantidad_Pendiente;Costo;Ultimo_Costo;Funcionario_Digita;Punto_Dispensacion;Tipo_Documento;Id_Paciente;Nombre_Paciente;Genero_Paciente;Regimen_Paciente;Departamento;Codigo_Departamento;Ciudad;Codigo_DX;Tipo;Tipo_Servicio;Doctor;IPS;EPS_Paciente;Numero_Autorizacion;Fecha_Autorizacion;Numero_Prescripcion;Fecha_Formula;Fecha_Entrega;Cuota_Moderadora;Cuota_Recuperacion;Causal_No_Pago;Auditada?;Funcionario_Auditor;Estado_Dispensacion;Telefono;Direccion;Estado_Auditoria;Soporte Acta\r\n";

$contenido = '';

$dis = '';
$j=1;
foreach($dispensaciones as $disp){ $j++;

	$contenido .= "\"$disp[Codigo]\";";
	$contenido .= "\"$disp[Fecha_Actual]\";";
	$contenido .= "\"$disp[Numero_Factura]\";";
	$contenido .= "\"$disp[Fecha_Factura]\";";
	$contenido .= "\"$disp[Identif_Tercero]\";";
	$contenido .= "\"$disp[Nombre_Tercero]\";";
	$contenido .= "\"$disp[Cum]\";";
	$contenido .= "\"$disp[Nombre_Comercial]\";";
	$contenido .= "\"$disp[Nombre]\";";
	$contenido .= "\"$disp[Embalaje]\";";
	$contenido .= "\"$disp[Laboratorio_Generico]\";";
	$contenido .= "\"$disp[Laboratorio_Comercial]\";";
	$contenido .= "\"$disp[Lote]\";";
	$contenido .= "\"$disp[Fecha_Vencimiento]\";";
	$contenido .= "\"$disp[Cantidad_Formulada]\";";
	$contenido .= "\"$disp[Cantidad_Entregada]\";";
	$contenido .= "\"$disp[Cantidad_Pendiente]\";";
	if($permiso){
		$contenido .= "\"".number_format($disp["Costo"],0,"","") . "\";";
		$contenido .= "\"".number_format($disp["Ultimo_Costo"],0,"","") . "\";";
	}else{
		$contenido .= number_format(0,0,"","") . ';';
	}

	$contenido .= "\"$disp[Funcionario_Digita]\";";
	$contenido .= "\"$disp[Punto_Dispensacion]\";";
	$contenido .= "\"$disp[Tipo_Documento]\";";
	$contenido .= "\"$disp[Id_Paciente]\";";
	$contenido .= "\"$disp[Nombre_Paciente]\";";
	$contenido .= "\"$disp[Genero_Paciente]\";";
	$contenido .= "\"$disp[Regimen_Paciente]\";";
	$contenido .= "\"$disp[Departamento]\";";
	$contenido .= "\"$disp[Codigo_Departamento]\";";
	$contenido .= "\"$disp[Ciudad]\";";
	$contenido .= "\"$disp[Codigo_DX]\";";
	$contenido .= "\"$disp[Tipo]\";";
	$contenido .= "\"$disp[Tipo_Servicio]\";";
	$contenido .= "\"".trim($disp["Doctor"]). "\";";
	$contenido .= "\"".trim($disp["IPS"]). "\";";
	$contenido .= "\"".trim($disp["EPS_Paciente"]). "\";";
	$contenido .= "\"".trim($disp["Numero_Autorizacion"]). "\";";
	$contenido .= "\"".$disp["Fecha_Autorizacion"] . "\";";
	$contenido .= "\"".trim($disp["Numero_Prescripcion"]) . "\";";
	$contenido .= "\"$disp[Fecha_Formula]\";";
	$contenido .= "\"$disp[Fecha_Entrega] \";";
	if ($dis == '' || $dis != $disp['Id_Dispensacion']) {
		$cuota = $disp["Cuota_Moderadora"] != '' ? $disp["Cuota_Moderadora"] : 0;
		$contenido .= "\"".number_format($cuota,0,"","") . "\";";
		$dis = $disp['Id_Dispensacion'];
	} else {
		$contenido .= "\"0\";";
	}
	
	$contenido .= "\"$disp[Cuota_Recuperacion]\";";
	$contenido .= "\"$disp[Causal_No_Pago]\";";
	$contenido .= "\"$disp[Auditada]\";";
	$contenido .= "\"$disp[Funcionario_Preauditoria]\";";
	$contenido .= "\"$disp[Estado_Dispensacion]\";";
	$contenido .= "\"$disp[Telefono]\";";
	$contenido .= "\"$disp[Direccion]\";";
	$contenido .= "\"$disp[Estado_Auditoria] \";";
	$contenido .= "\"$disp[Soporte] \"\r\n";

	
}

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