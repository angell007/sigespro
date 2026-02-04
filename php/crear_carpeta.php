<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$query = 'SELECT Identificacion_Funcionario
From Funcionario';
           
           

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$funcionarios = $oCon->getData();
unset($oCon);

foreach ($funcionarios as  $value) {
    if (!file_exists( $MY_FILE.'DOCUMENTOS/'.$value["Identificacion_Funcionario"])) {
        mkdir($MY_FILE.'DOCUMENTOS/'.$value["Identificacion_Funcionario"], 0777, true);
    }
}



          
?>