<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['prod']) && $_REQUEST['prod'] != "") {
    $condicion .= " WHERE (P.Principio_Activo LIKE '%$_REQUEST[prod]%' OR P.Presentacion LIKE '%$_REQUEST[prod]%' OR P.Concentracion LIKE '%$_REQUEST[prod]%' OR P.Nombre_Comercial LIKE '%$_REQUEST[prod]%')";
}

if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
    if ($condicion != "") {
        $condicion .= " AND PR.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
    } else {
        $condicion .= "WHERE PR.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
    }
}

$query='SELECT COUNT(*) AS Total FROM Precio_Regulado PR INNER JOIN Producto P ON PR.Codigo_Cum = P.Codigo_Cum ' .$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

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

$query = 'SELECT P.Nombre_Comercial,PR.Precio AS PrecioNuevo, CONCAT_WS(" ", P.Principio_Activo,P.Cantidad,P.Unidad_Medida, P.Presentacion, P.Concentracion) AS Nombre_General, PR.*, "true" as Editar 
          FROM Precio_Regulado PR 
          INNER JOIN Producto P ON PR.Codigo_Cum = P.Codigo_Cum '.$condicion.' LIMIT ' . $limit . ',' . $tamPag;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$regulados['Regulados'] = $oCon->getData();
unset($oCon);   

$regulados['numReg'] = $numReg;


echo json_encode($regulados);

?>