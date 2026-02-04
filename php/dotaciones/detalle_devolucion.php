<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );


$query = 'SELECT D.*, CONCAT(R.Nombres," ",R.Apellidos) as Recibe, CONCAT(E.Nombres," ",E.Apellidos) as Entrega
            FROM Dotacion D
            INNER JOIN Funcionario E ON E.Identificacion_Funcionario = D.Identificacion_Funcionario
            INNER JOIN Funcionario R ON R.Identificacion_Funcionario = D.Funcionario_Recibe
            WHERE D.Id_Dotacion = '.$id.'
';

$oCon = new consulta();
$oCon->setQuery($query);
$response["Datos"] = $oCon->getData();
unset($oCon);

$prods=explode("|",$response["Datos"]["Productos"]);
$productos=[];
$i=-1;
foreach($prods as $prod){ $i++;
    $p=explode(" x ",$prod);
    $productos[$i]["Cantidad"] = trim($p[0]);
    $productos[$i]["Nombre"]   = trim($p[1]);
    $productos[$i]["Baja"]=false;
}
$response["Productos"]=$productos;


echo json_encode($response);