<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$condicion = '';

if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
    $condicion .= " AND PP.Cum LIKE '%$_REQUEST[cum]%'";
}

if (isset($_REQUEST['prod']) && $_REQUEST['prod'] != "") {
    $condicion .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[prod]%'";
}

$query='SELECT COUNT(*) AS Total FROM Producto_NoPos PP 
INNER JOIN Producto P ON PP.Cum  = P.Codigo_Cum 
WHERE PP.Id_Lista_Producto_Nopos = '.$id.$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);
// var_dump($total);exit;

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $total["Total"]; 
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

$query= 'SELECT PP.*, IFNULL(PP.Cum_Homologo, "No tiene Homologo") as Cum_Homologo,
         IFNULL(PP.Precio_Homologo, 0) as Precio_Homologo , P.Nombre_Comercial, 
         CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida, " ") as Nombre_Producto, 
         IFNULL(
            (SELECT PR.Nombre_Comercial FROM Producto PR 
            WHERE PR.Codigo_Cum=PP.Cum_Homologo), "Sin Homologo") as Nombre_Comercial_Homologo,
            (SELECT CONCAT(PR.Principio_Activo," ",PR.Presentacion," ",PR.Concentracion," ", PR.Cantidad," ", PR.Unidad_Medida, " ") 
         FROM Producto PR WHERE PR.Codigo_Cum=PP.Cum_Homologo ) as Nombre_Homologo  
         FROM Producto_NoPos PP 
         INNER JOIN Producto P ON PP.Cum=P.Codigo_Cum  
         WHERE PP.Id_Lista_Producto_Nopos='.$id.$condicion.' LIMIT ' . $limit . ',' . $tamPag;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$regulados['Productos'] = $oCon->getData();
unset($oCon);   

$regulados['numReg'] = $numReg;


echo json_encode($regulados);



?>