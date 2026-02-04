<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_dep = isset($_REQUEST['id_dep']) ? $_REQUEST['id_dep'] : false;
$condicion = '';
if ($id_dep!='0') {

    $condicion=' AND Departamento='.$id_dep;
}
$query = 'SELECT Id_Punto_Dispensacion AS value, Nombre AS label FROM Punto_Dispensacion WHERE Estado != "Inactivo" '.$condicion.' ORDER BY Nombre';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

echo json_encode($resultados);

?>