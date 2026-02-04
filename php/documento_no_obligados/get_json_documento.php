<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.documento_soporte_electronico.php';

$fecha = $_REQUEST['fecha'] ? $_REQUEST['fecha'] : '2024-09-01';
$min = $_REQUEST['fmin'] ? $_REQUEST['fmin'] : null;

if ($min) {
    $cond = " AND Id_Documento_No_Obligados > $min ";
}



$query = "SELECT * FROM Documento_No_Obligados WHERE Procesada is null and Fecha_Documento >= '$fecha 00:00:00' $cond
    Order by Id_Documento_No_Obligados ASC";
$oItem = new consulta();
$oItem->setQuery($query);
$document = $oItem->getData();
if (count($document) == 0) {
    http_response_code(406);
}
if ($document['Id_Documento_No_Obligados']) {
    try {
        if (contarCodigo('Documento_No_Obligados', $document['Codigo']) == '1') {
            $documento_electronico = new DocumentoElectronico($document['Id_Documento_No_Obligados'], $document['Id_Resolucion']);
            $json = $documento_electronico->getJson();
            // echo '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $json["resolution_id"] . '/CUDS' . $json['file'] . '.xml'; exit;
            unlink('/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $json["resolution_id"] . '/CUDS' . $json['file'] . '.xml');
        }
        //code...
    } catch (\Throwable $th) {
        print_r($th);
        exit;
    }
} else {
    http_response_code(411);
    $resp['Mensaje'] = 'No existe';
    echo json_encode($resp);
}

$Json['Id'] = $document['Id_Documento_No_Obligados'];
$Json['Documento'] = $json;
echo json_encode($Json);

function contarCodigo($tipo, $codigo)
{
    $query = "SELECT COUNT(Id_$tipo) as Total 
    FROM $tipo 
    WHERE Codigo LIKE '$codigo'";

    // echo $query; exit;
    $oCon = new consulta();
    $oCon->setQuery($query);
    return $oCon->getData()['Total'];
}
