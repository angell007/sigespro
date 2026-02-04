<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta2.php');

$DBApiDian = "sigesproph_apidian";
$mod = 'Resolucion';
$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');

$datos = (array) json_decode($datos);
$type_document = 1;
switch ($datos["Codigo"]) {
    case 'NC':
        $type_document = 4;
        break;
    case 'CN':
        $type_document = 7;
        break;
    case 'NE':
        $type_document = 7;
        break;
    case 'CNN':
        $type_document = 8;
        break;
    default:
        $type_document = 1;
        break;
}




if (isset($datos["id"]) && $datos["id"] != "") {
    $oItem = new complex($mod, "Id_" . $mod, $datos["id"]);
} else {
    
    $query = "INSERT INTO resolutions ( `company_id`, `type_document_id`, `prefix`, `resolution`, `resolution_date`, `technical_key`, `from`, `to`, `date_from`, `date_to`, `created_at`, `updated_at`) VALUES ('1', '$type_document', '$datos[Codigo]', '$datos[Resolucion]',  '$datos[Fecha_Inicio]', '$datos[Clave_Tecnica]', '$datos[Numero_Inicial]', '$datos[Numero_Final]', '$datos[Fecha_Inicio]', '$datos[Fecha_Fin]',  CURRENT_DATE(),  CURRENT_DATE())";
    $con = new consulta2();
    $con->setQuery($query);
    $con->getData2($DBApiDian);
    $id = $con ->getID();
    unset($con);
    
    crearCarpetas($id);
    $oItem = new complex($mod, "Id_" . $mod);
    $oItem->resolution_id = $id;
}

foreach ($datos as $index => $value) {
    $oItem->$index = $value;
}
$oItem->save();
$id_resolucion = (isset($datos["id"]) && $datos["id"] != "") ? $oItem->Id_Resolucion : $oItem->getId();
unset($oItem);



if ($id_resolucion) {
    $resultado['title'] = "Exito!";
    $resultado['mensaje'] = "Se ha registrado la Resoluci¨®n correctamente.";
    $resultado['tipo'] = "success";
} else {
    http_response_code(501);
    $resultado['title'] = "Error!";
    $resultado['mensaje'] = "Ha ocurrido un error de conexi¨®n, si el problema persiste por favor comuniquese con soporte tecnico.";
    $resultado['tipo'] = "error";
}

$oLista = new lista($mod);
$lista = $oLista->getlist();
unset($oLista);

foreach ($lista as $key => $value) {
    $value = array_map('utf8_encode', $value);
    $lista[$key] = $value;
}

$resultado['Lista'] = $lista;

echo json_encode($resultado);

function crearCarpetas($resolution_id, $type_document = 1)
{
    $rutas = [];
    if ($type_document == 1) {
        $rutas = [
            $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $resolution_id,
            '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolution_id,
            '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolution_id,
            '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/zip/1/' . $resolution_id,
        ];
    }
    foreach ($rutas as $ruta) {
        mkdir($ruta, 0777, true);
    }
}
