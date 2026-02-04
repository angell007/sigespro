<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$condicion = '';


if (isset($_REQUEST['Tipo']) && $_REQUEST['Tipo'] != "") {
        $condicion .= " WHERE T.Tipo_Turno='$_REQUEST[Tipo]'";
}

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
   
    $condicion .= " AND T.Nombre LIKE '%$_REQUEST[nom]%'";

}

$query = 'SELECT T.* FROM Turno T '.$condicion;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);



####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = count($total); 
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

$query = 'SELECT T.* FROM Turno T '.$condicion.' LIMIT '.$limit.','.$tamPag; 


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Turnos'] = $oCon->getData();
unset($oCon);


$resultado['numReg'] = $numReg;
echo json_encode($resultado);
?>