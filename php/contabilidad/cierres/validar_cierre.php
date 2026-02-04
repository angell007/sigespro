<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','510M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$editar = false;

if ($datos) {

    $datos = json_decode($datos, true);
    $data = getInfoCierre($datos);

    if ($data) {
        if ($data['Tipo_Cierre'] == $datos['Tipo_Cierre']) {
            $response['mensaje'] = "Ya este proceso está registrado.";
            $response['titulo'] = "Ooops!";
            $response['codigo'] = "error";
        } else {
            $response['codigo'] = "success";
        }
        
    } else {
        $response['codigo'] = "success";
    }
    
   
} else {
    $response['mensaje'] = "Ha ocurrido un error inesperado al procesar la información.";
    $response['titulo'] = "Ooops!";
    $response['codigo'] = "error";
}

echo json_encode($response);

function getInfoCierre($datos) {
    $query = '';
    if ($datos['Tipo_Cierre'] == 'Mes') {
        $query = "SELECT * FROM Cierre_Contable WHERE Mes = '$datos[Mes]' AND Anio = '$datos[Anio]' AND Estado != 'Anulado'";
    } else {
        $query = "SELECT * FROM Cierre_Contable WHERE Anio = '$datos[Anio]' AND Tipo_Cierre = '$datos[Tipo_Cierre]' AND Estado != 'Anulado'";
    }
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $response = $oCon->getData();
    unset($oCon);

    return $response;
}

?>