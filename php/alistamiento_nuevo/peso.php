<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

$archivo=fopen("../bodega/balanza1.txt","r");
$peso=fgets($archivo);
fclose($archivo);
$resultado["Peso"]=(INT)str_replace(".","",str_replace("00.","",$peso));

echo json_encode($resultado);
?>