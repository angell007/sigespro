<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
include_once('../class/class.mipres.php');
include_once('../class/class.php_mailer.php');

$queryObj = new QueryBaseDatos();

$mipres= new Mipres();
    
$consulta= $mipres->ConsultaProgramacion("20200214175017479224");

$consulta2= $mipres->ConsultaEntrega("20200214175017479224");

var_dump($consulta);
var_dump($consulta2);

//echo json_encode($direccionamientos,true);

?>