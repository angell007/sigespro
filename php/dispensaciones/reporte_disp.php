<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= ' AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
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
		$condicion .= " AND PA.Id_Paciente=$_REQUEST[pac]";
	
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
COUNT(*) AS Total
FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D
ON PD.Id_Dispensacion = D.Id_Dispensacion
LEFT JOIN Positiva_Data PDA ON PDA.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Producto P
On P.Id_Producto = PD.Id_Producto
INNER JOIN (SELECT Nombres, Apellidos, Identificacion_Funcionario FROM Funcionario) F 
ON F.Identificacion_Funcionario = D.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion PDI
ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN (SELECT Id_Paciente, Nit, EPS, Tipo_Documento, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Genero, Id_Regimen, Cod_Municipio_Dane FROM Paciente) PA
ON PA.Id_Paciente = D.Numero_Documento
INNER JOIN Departamento DP
ON PDI.Departamento=DP.Id_Departamento
WHERE D.Estado != "Anulada"
'.$condicion;


$oCon= new consulta();
$oCon->setQuery($query);
$dispensaciones= $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $dispensaciones["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
} 

$query = 'SELECT PDA.numeroAutorizacion, 
D.Codigo, 
D.Observaciones,
D.Fecha_Actual, 
"" as Numero_Factura, 
"" as Fecha_Factura, 
PA.Nit as Identif_Tercero, 
PA.EPS as Nombre_Tercero, 
PD.Cum, 
P.Nombre_Comercial, 
CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) as Nombre, 
P.Embalaje, 
P.Laboratorio_Generico, 
-- (SELECT CONCAT_WS(" ",POO.Id_Proveedor, POO.Nombre) FROM Proveedor POO WHERE POO.Nombre LIKE CONCAT("%",SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(P.Laboratorio_Generico, "FRANCO", "FRANCOL"), " LABINCO", "LABINCO"), "S.A.S ", ""), "  ", " "), " DE ", " "), "COLOMBIA ", ""), "INTERNACIONAL ", ""), "LABORATORIO ", ""), "AMERICAN ", ""), "-", " "), "LABORATORIOS ", ""), " ", 1),"%")  LIMIT 1  ) AS Nit_Proveedor_Generico, 
P.Laboratorio_Comercial, 
-- (SELECT CONCAT_WS(" ",POO.Id_Proveedor, POO.Nombre) FROM Proveedor POO WHERE POO.Nombre LIKE CONCAT("%",SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(P.Laboratorio_Comercial, "FRANCO", "FRANCOL"), " LABINCO", "LABINCO"), "S.A.S ", ""), "  ", " "), " DE ", " "), "COLOMBIA ", ""), "INTERNACIONAL ", ""), "LABORATORIO ", ""), "AMERICAN ", ""), "-", " "), "LABORATORIOS ", ""), " ", 1),"%") LIMIT 1  ) AS Nit_Proveedor_Comercial, 
PD.Cantidad_Formulada, 
PD.Cantidad_Entregada, 
(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
    COALESCE(PD.Costo,(SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = PD.Id_Producto),0) AS Costo,
CONCAT_WS(" ",F.Nombres,F.Apellidos) as Funcionario_Digita, 
PDI.Nombre  as Punto_Dispensacion, 
PA.Tipo_Documento, 
PA.Id_Paciente, 
CONCAT_WS(" ",PA.Primer_Nombre, PA.Segundo_Nombre, PA.Primer_Apellido, PA.Segundo_Apellido) as Nombre_Paciente, 
PA.Genero as Genero_Paciente, 
(SELECT R.Nombre FROM Regimen R WHERE R.Id_Regimen = PA.Id_Regimen) as Regimen_Paciente,
DP.Codigo as Departamento, 
PA.Cod_Municipio_Dane as Ciudad, "" as Codigo_DX,
 D.Tipo, 
 D.Doctor, 
 D.IPS, 
 PA.EPS as EPS_Paciente, 
 PD.Numero_Autorizacion, 
 PD.Fecha_Autorizacion, 
 PD.Numero_Prescripcion, 
 D.Fecha_Formula, 
 D.Fecha_Actual as Fecha_Entrega, 
 D.Cuota as Cuota_Moderadora, 
 "" as Cuota_Recuperacion, 
 D.Causal_No_Pago, 
 D.Estado_Dispensacion
FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion
LEFT JOIN Positiva_Data PDA ON PDA.Id_Dispensacion = D.Id_Dispensacion

INNER JOIN Producto P
On P.Id_Producto = PD.Id_Producto
INNER JOIN (SELECT Nombres, Apellidos, Identificacion_Funcionario FROM Funcionario) F 
ON F.Identificacion_Funcionario = D.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion PDI
ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN (SELECT Id_Paciente, Nit, EPS, Tipo_Documento, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Genero, Id_Regimen, Cod_Municipio_Dane FROM Paciente) PA
ON PA.Id_Paciente = D.Numero_Documento
INNER JOIN Departamento DP
ON PDI.Departamento=DP.Id_Departamento
WHERE D.Estado != "Anulada"
'.$condicion . ' LIMIT '.$limit.','.$tamPag;



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$dispensaciones= $oCon->getData();
unset($oCon);

$resultado['dispensaciones'] = $dispensaciones;
$resultado['numReg'] = $numReg;

echo json_encode($resultado);

?>