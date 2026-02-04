<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );

$condicion = '';
 
/*$oLista = new lista($mod);
$lista= $oLista->getlist();
unset($oLista);*/
 

if($mod === 'Cliente'){
    $condicion = " WHERE Estado != 'Inactivo'";
}elseif($mod === 'Resolucion'){
    $condicion = "  ORDER BY Id_Resolucion DESC";
}
// elseif($mod === 'Categorias_Memorando'){
//     $condicion = " WHERE Estado != 'Desactivado'";
//}

//$query='SELECT * FROM '.$mod.$condicion;
$query = 'SELECT Identificacion_Funcionario, CONCAT(Nombres," ",Apellidos) as Nombre  FROM Funcionario  ';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
echo json_encode($resultado);


?>