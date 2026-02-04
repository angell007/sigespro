<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-type:application/json');

include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.contabilizar.php';
require_once '../../class/class.php_mailer.php';

require_once '../../class/class.qr.php';

$dian_response = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
// $id_documento = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$dian_response = json_decode($dian_response, true);

try {
    $respuesta_dian = isset($dian_response['Json']) ? $dian_response['Json'] : $dian_response;
    $respuesta = GenerarFactura($respuesta_dian);
    echo json_encode($respuesta);
} catch (\Throwable $th) {
    http_response_code(403);
    echo json_encode($e->getMessage());
}

function GenerarFactura($respuesta_dian)
{
    $cuds = $respuesta_dian['cuds'];

    $error = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]['ErrorMessage'];
    $errores_dian = isset($error) ? $error : [];

    if (count($errores_dian) > 0) {
        $errores_dian = implode(" - ", $errores_dian);
    } else {
        $errores_dian = "";}
    if (strpos($errores_dian, "procesado anteriormente") !== false) {
        $aplication_response = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"];
        $aplication_response = base64_decode($aplication_response);
        $cuds = ObtenerCudsRespuesta($aplication_response);
    }

    $documento = validarCuds($cuds);
    
    if ($documento) {
        $qr = GetQr($cuds);
        $estado = "true";

        $respuesta_dian['Respuesta'] = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["StatusMessage"];
        if ($estado == "true") {

            $query = "UPDATE Documento_No_Obligados SET Cuds = '$cuds', Codigo_Qr= '$qr', Procesada= '$estado' Where Codigo = '$documento[Codigo]'";
            $oItem = new consulta;
            $oItem->setQuery($query);

            $oItem->getData();
            unset($oItem);

            $respuesta["Documento"] =  $documento['Codigo'];
            $respuesta["Procesada"] = $respuesta_dian['Respuesta'];
            return ($respuesta);
        }
    }
    $respuesta["Procesada"] = "No Procesada, cuds no existe $cuds";
    return ($respuesta);
}
function GetQr($cufe)
{
    $qr = 'https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/' . $cufe;
    $qr = generarqrFE($qr);

    return ($qr);
}

function validarCuds($cufe)
{
    $cufe = trim($cufe);

    $url = "https://catalogo-vpfe.dian.gov.co/document/ShowDocumentToPublic/$cufe";
    $url = "https://catalogo-vpfe.dian.gov.co/Document/Details?trackId=$cufe";
    
    

    $cc = curl_init($url);
    curl_setopt($cc, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)
    curl_setopt($cc, CURLOPT_RETURNTRANSFER, 1);
    $url_content = curl_exec($cc);
        // echo ($url_content); exit;

    if (curl_errno($cc)) {
        return false;
    }
    curl_close($cc);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($url_content, 1);
    libxml_clear_errors();
    $a = $dom->getElementById('html-gdoc');

    $respuesta = [];
    $array = explode("\n", str_replace(["\r", ""], '', $a->nodeValue));

    $array = array_map('trim', $array);
    foreach ($array as $key => $value) {
        if ($value == "") {
            unset($array[$key]);
        }
    }
    $array = array_values($array);
    foreach ($array as $key => $value) {
        if ($value == "CUFE:") {
            $respuesta['CUFE'] = $array[$key + 1];
        } else if (strpos($value, 'Folio') !== false) {
            $valores = explode('Folio:', $value);
            $folio = trim($valores[1]);
            $valores = explode('Serie:', $valores[0]);
            $prefijo = count($valores) > 1 ? trim($valores[1]) : '';
            $respuesta['Factura']['Prefijo'] = $prefijo;
            $respuesta['Factura']['Consecutivo'] = $folio;
            $respuesta['Codigo'] = "$prefijo$folio";
        } else if (strpos($value, 'EMISOR') !== false) {
            $nit = trim(explode('NIT:', $array[$key + 1])[1]);
            $nombre = trim(explode('Nombre:', $array[$key + 2])[1]);
            $respuesta['Proveedor']['NIT'] = $nit;
            $respuesta['Proveedor']['Nombre'] = $nombre;
        } else if (strpos($value, 'RECEPTOR') !== false) {
            $nit = trim(explode('NIT:', $array[$key + 1])[1]);
            $nombre = trim(explode('Nombre:', $array[$key + 2])[1]);
            $respuesta['Cliente']['NIT'] = $nit;
            $respuesta['Cliente']['Nombre'] = $nombre;
        }
    }

    return $respuesta;

}

function ObtenerCudsRespuesta($aplication_response)
{
    preg_match('/<cbc:UUID schemeName="CUDS-SHA384">(.*?)<\/cbc:UUID/is', $aplication_response, $coincidencias3);
    $respuesta = $coincidencias3[1];

    return $respuesta;
}
