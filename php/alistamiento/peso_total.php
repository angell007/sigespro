<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

$archivo=fopen("../bodega/balanza2.txt","r");
$peso=fgets($archivo);
fclose($archivo);
$resultado["Peso"]=((float)($peso))*1000;

echo json_encode($resultado);
?>