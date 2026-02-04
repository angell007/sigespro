<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../../class/class.http_response.php');
include_once('../../../class/class.consulta.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
if ($id) {
    $query = "select RD.*, DEP.Nombre AS Nombre_Departamento from radicacion RD  
        INNER JOIN Departamento DEP ON RD.Departamento = DEP.Id_Departamento
        where RD.id=$id
    ";

    $oCon = new consulta();
    
    $datos["host"]="localhost";
    $datos["db"]="prohsa_radicaciones";
    $datos["user"]="prohsa";
    $datos["pass"]="Proh2019*";
    
    
    $oCon->setQuery($query);
    $res = $oCon->getData2($datos["host"],$datos["user"],$datos["pass"],$datos["db"]);

    if ($res) {
        $response['tipo'] = "success";
        $response['data'] = $res;
        echo json_encode($response);
        exit;
    } else {
        $response['tipo'] = "error";
        $response['mensaje'] = "No se econtraron coincidencias en nuestra base de datos";
        echo json_encode($response);
    }
}
