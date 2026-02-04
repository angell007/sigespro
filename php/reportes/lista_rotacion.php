<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : ''; 
$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= ' AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
	$condicionbodega .= ' AND DATE(R.Fecha) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	$condicion .= " AND DP.Id_Departamento=$_REQUEST[dep]";
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	$condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[pto]";
}
if (isset($_REQUEST['bod']) && $_REQUEST['bod'] != "") {
	$condicionbodega .= " AND R.Id_Origen=$_REQUEST[bod] AND R.Tipo_Origen='Bodega'";
}
if (isset($_REQUEST['prod']) && $_REQUEST['prod'] != "") {
	$condicion .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[prod]%'";
	$condicionbodega .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[prod]%'";
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
	$condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
	$condicionbodega .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
}
if (isset($_REQUEST['labc']) && $_REQUEST['labc'] != "") {
	$condicion .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[labc]%'";
	$condicionbodega .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[labc]%'";
}
if (isset($_REQUEST['labg']) && $_REQUEST['labg'] != "") {
	$condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[labg]%'";
	$condicionbodega .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[labg]%'";
}

if($tipo=='Punto'){
    $query = 'SELECT *
FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D
ON PD.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Producto P
On P.Id_Producto = PD.Id_Producto
INNER JOIN Punto_Dispensacion PDI
ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN Departamento DP
ON PDI.Departamento=DP.Id_Departamento
WHERE D.Estado != "Anulada"
'.$condicion.' GROUP BY PD.Id_Producto';

}elseif ($tipo=='Bodega') {
    $query = 'SELECT *
    FROM Producto_Remision PD
    INNER JOIN Remision R
    ON PD.Id_Remision = R.Id_Remision
    INNER JOIN Producto P
    On P.Id_Producto = PD.Id_Producto   
    WHERE R.Estado != "Anulada"
    '.$condicionbodega.' GROUP BY PD.Id_Producto';

}
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = count($dispensaciones); 
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

if($tipo=='Punto'){
    $query = 'SELECT P.Codigo_Cum, P.Nombre_Comercial, 
CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) as Nombre, 
P.Embalaje, P.Laboratorio_Generico, P.Laboratorio_Comercial, SUM(PD.Cantidad_Formulada) AS Cantidad_Rotada, 
PDI.Nombre  as Punto_Dispensacion
FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D
ON PD.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Producto P
On P.Id_Producto = PD.Id_Producto
INNER JOIN Punto_Dispensacion PDI
ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN Departamento DP
ON PDI.Departamento=DP.Id_Departamento
WHERE D.Estado != "Anulada"
'.$condicion.' GROUP BY PD.Id_Producto ORDER BY Cantidad_Rotada DESC LIMIT '.$limit.','.$tamPag;

}elseif ($tipo=='Bodega') {
    $query = 'SELECT P.Codigo_Cum, P.Nombre_Comercial, 
    CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) as Nombre, 
    P.Embalaje, P.Laboratorio_Generico, P.Laboratorio_Comercial, SUM(PD.Cantidad) AS Cantidad_Rotada
    FROM Producto_Remision PD
    INNER JOIN Remision R
    ON PD.Id_Remision = R.Id_Remision
    INNER JOIN Producto P
    On P.Id_Producto = PD.Id_Producto   
    WHERE R.Estado != "Anulada"
    '.$condicionbodega.' GROUP BY PD.Id_Producto  ORDER BY Cantidad_Rotada DESC LIMIT '.$limit.','.$tamPag;

}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['dispensaciones']= $oCon->getData();
unset($oCon);
$resultado['numReg'] = $numReg;

echo json_encode($resultado);

?>