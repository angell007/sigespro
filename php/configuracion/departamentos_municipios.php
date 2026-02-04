<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_dep = isset($_REQUEST['id_dep']) ? $_REQUEST['id_dep'] : false;

if($id_dep=='' || $id_dep == null){
    $query = 'SELECT D.Id_Departamento, D.Nombre FROM Departamento D ORDER BY D.Nombre ASC ';

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);
}else if ($id_dep!=''){

    $query = "SELECT Id_Municipio AS value, Nombre AS label, Codigo AS Codigo_Municipio FROM Municipio WHERE Id_Departamento=$id_dep ORDER BY Nombre";

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);
}


echo json_encode($resultado);


?>