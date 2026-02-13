<?php
//error_reporting(-1);
//ini_set('error_reporting', E_ALL);

ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.facturacion_electronica.php');
include_once('../../class/class.nota_credito_electronica.php');

// Obtener los parámetros de la solicitud
$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : (isset($_REQUEST['Tipo_Factura']) ? $_REQUEST['Tipo_Factura'] : "Factura_Administrativa");
$reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : null);
$id_factura = isset($_REQUEST['id_factura']) ? $_REQUEST['id_factura'] : (isset($_REQUEST['Id_Factura']) ? $_REQUEST['Id_Factura'] : null);
$reprocesar = isset($_REQUEST['reprocesar']) ? filter_var($_REQUEST['reprocesar'], FILTER_VALIDATE_BOOLEAN) : false;

if (strpos($tipo, "Factura") !== false) {

    $na = 'fv';

    // Crear una nueva instancia de FacturaElectronica con los parámetros proporcionados
    $fe = new FacturaElectronica($tipo, $id_factura, $reso);
    $datos = $fe->GenerarFactura();

    // Procesar la respuesta de DIAN
    $aplication_response = $datos["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"] ?? null;
    $aplication_response = $aplication_response ? base64_decode($aplication_response) : null;

    // Obtener datos de la factura
    $oCon = new complex($tipo, "Id_$tipo", $id_factura);
    $factura = $oCon->getData();
    unset($oCon);

    // Obtener la resolución usando el ID de resolución proporcionado en la solicitud
    $oCon = new complex("Resolucion", "Id_Resolucion", $reso);
    $resolucion = $oCon->getData();
    unset($oCon);

    // Si no hay respuesta inmediata, intentar obtenerla de un archivo AD guardado previamente
    if (empty($aplication_response)) {
        $aplication_response = obtenerApplicationResponseDesdeArchivo($resolucion["resolution_id"] ?? null, $factura["Codigo"] ?? null);
    }

    // Obtener configuración de la empresa (debe cargarse antes de usar getNombre())
    $oItem = new complex("Configuracion", "Id_Configuracion", 1);
    $configuracion = $oItem->getData();
    unset($oItem);

    // Para facturas FENP: si aún no hay ApplicationResponse, consultar GetStatus usando el CUFE como trackId
    if (empty($aplication_response) && !empty($factura["Codigo"]) && strpos($factura["Codigo"], "FENP") === 0 && !empty($factura["Cufe"])) {
        $aplication_response = obtenerApplicationResponseDesdeDIAN($factura["Cufe"]);
    }

    if ($tipo == "Factura_Administrativa") {
        $cliente = getTercero($factura);
    } else {
        $cliente = getCliente($factura);
    }

    // Generar la URL del XML de la factura
    $xml_invoice = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolucion["resolution_id"] . '/fv' . getNombre() . '.xml';

    $xml_factura = @file_get_contents($xml_invoice);
    if ($xml_factura === false) {
        $xml_info = null;
        if (!empty($datos["Json"]["ruta"])) {
            $xml_info = obtenerXmlDesdeRuta($datos["Json"]["ruta"]);
            $xml_factura = $xml_info['xml'] ?? null;
        }
        if (!$xml_factura) {
            http_response_code(404);
            echo json_encode([
                'error' => 'XML no encontrado.',
                'xml_url' => $xml_invoice,
                'xml_origen' => $xml_info['origen'] ?? null,
                'respuesta_dian' => $datos["Json"] ?? null,
            ]);
            exit;
        }
    }

    //echo $xml_invoice; exit;

    // Leer y mostrar el archivo XML de la factura
    $xml_file_path = getXml($aplication_response, $factura['Cufe'], $xml_factura, $cliente);

    // Descartar cualquier salida previa antes de enviar headers de descarga
    ob_end_clean();
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . basename($xml_file_path) . '"');

    // Leer y enviar el archivo XML
    readfile($xml_file_path);
} else {

    $na = 'nc';

    $fe = new NotaCreditoElectronica($tipo, $id_factura, $reso);
    $datos = null;
    $aplication_response = null;

    //var_dump($aplication_response);
    //exit;

    // Obtener datos de la factura
    $oCon = new complex($tipo, "Id_$tipo", $id_factura);
    $factura = $oCon->getData();
    unset($oCon);

    // Obtener la resolución usando el ID de resolución proporcionado en la solicitud
    $oCon = new complex("Resolucion", "Id_Resolucion", $reso);
    $resolucion = $oCon->getData();
    unset($oCon);

    // Obtener configuración de la empresa
    $oItem = new complex("Configuracion", "Id_Configuracion", 1);
    $configuracion = $oItem->getData();
    unset($oItem);


    $factura_origen = obtenerFacturaOrigenNota($tipo, $factura);
    $cliente = obtenerClienteNotaCredito($tipo, $factura, $factura_origen);




    $nombre_archivo = getNombre();
    $nombre_alt = getNombreConFecha($factura['Fecha'] ?? null);
    $nombres_busqueda = array_filter(array_unique([$nombre_archivo, $nombre_alt]));
    $resoluciones_busqueda = obtenerResolutionIds($resolucion["resolution_id"]);
    $xml_info = obtenerXmlNotaCredito($resoluciones_busqueda, $nombres_busqueda);
    if (!$xml_info['xml'] && $reprocesar) {
        $datos = $fe->GenerarNotaConHost('https://api-dian.sigesproph.com.co');
        $aplication_response = $datos["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"] ?? $aplication_response;
        $aplication_response = $aplication_response ? base64_decode($aplication_response) : null;
        $xml_info = obtenerXmlNotaCredito($resoluciones_busqueda, $nombres_busqueda);
        if (!$xml_info['xml'] && !empty($datos["Json"]["ruta"])) {
            $xml_info = obtenerXmlDesdeRuta($datos["Json"]["ruta"]);
        }
    }
    if (!$xml_info['xml']) {
        http_response_code(404);
        echo json_encode([
            'error' => 'XML no encontrado.',
            'origen' => $xml_info['origen'],
            'respuesta_dian' => $datos["Json"] ?? null,
        ]);
        exit;
    }
    $xml_factura = $xml_info['xml'];

    // Leer y mostrar el archivo XML de la factura
    $xml_file_path = getXml($aplication_response, $factura['Cude'], $xml_factura, $cliente);

    // Descartar cualquier salida previa antes de enviar headers de descarga
    ob_end_clean();
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . basename($xml_file_path) . '"');

    // Leer y enviar el archivo XML
    readfile($xml_file_path);
}
// Función para generar el XML de la respuesta
function getXml($aplication_response, $cufe, $xml_factura, $cliente)
{
    global $resolucion, $configuracion, $factura, $tipo, $na;

    $aplication_response = $aplication_response ?: '';
    preg_match('/IssueDate>(.*?)<\/cbc:IssueDate/is', $aplication_response, $coincidencias);
    $fecha = $coincidencias[1] ?? null;
    preg_match('/IssueTime>(.*?)<\/cbc:IssueTime/is', $aplication_response, $coincidencias2);
    $hora = $coincidencias2[1] ?? null;
    preg_match('/ResponseCode>(.*?)<\/cbc:ResponseCode/is', $aplication_response, $coincidencias3);
    $respuesta = $coincidencias3[1] ?? null;
    $fecha_src = $factura['Fecha'] ?? ($factura['Fecha_Documento'] ?? date('Y-m-d H:i:s'));
    if (!$fecha) {
        $fecha = date('Y-m-d', strtotime($fecha_src));
    }
    if (!$hora) {
        $hora = date('H:i:s', strtotime($fecha_src));
    }
    if (!$respuesta) {
        $respuesta = '0';
    }

    $name_file = getNombre();


    $num = explode("-", $configuracion["NIT"]);
    $nit = str_replace(".", "", $num[0]);
    $dv = $num[1];

    $xml = '<?xml version="1.0" encoding="utf-8" standalone="no"?>
    <AttachedDocument xmlns="urn:oasis:names:specification:ubl:schema:xsd:AttachedDocument-2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
    xmlns:ccts="urn:un:unece:uncefact:data:specification:CoreComponentTypeSchemaModule:2"
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
    xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"
    xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#">
    <cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>Documentos adjuntos</cbc:CustomizationID>
    <cbc:ProfileID>Factura Electrónica de Venta</cbc:ProfileID>
    <cbc:ProfileExecutionID>1</cbc:ProfileExecutionID>
    <cbc:ID>' . $factura["Codigo"] . '</cbc:ID>
    <cbc:IssueDate>' . $fecha . '</cbc:IssueDate>
    <cbc:IssueTime>' . $hora . '</cbc:IssueTime>
    <cbc:DocumentType>Contenedor de Factura Electrónica</cbc:DocumentType>
    <cbc:ParentDocumentID>' . $factura["Codigo"] . '</cbc:ParentDocumentID>
    <cac:SenderParty>
    <cac:PartyTaxScheme>
    <cbc:RegistrationName>' . $configuracion["Nombre_Empresa"] . '</cbc:RegistrationName>
    <cbc:CompanyID schemeName="31" schemeID="' . $dv . '" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">' . $nit . '</cbc:CompanyID>
    <cbc:TaxLevelCode listName="48">R-99-PN</cbc:TaxLevelCode>
    <cac:TaxScheme>
    <cbc:ID>01</cbc:ID>
    <cbc:Name>IVA</cbc:Name>
    </cac:TaxScheme>
    </cac:PartyTaxScheme>
    </cac:SenderParty>
    <cac:ReceiverParty>
    <cac:PartyTaxScheme>
    <cbc:RegistrationName>' . $cliente["Nombre"] . '</cbc:RegistrationName>
    <cbc:CompanyID schemeName="31" schemeID="' . $cliente["Digito_Verificacion"] . '" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">' . $cliente["Id_Cliente"] . '</cbc:CompanyID>
    <cbc:TaxLevelCode listName="48">R‐99‐PN</cbc:TaxLevelCode>
    <cac:TaxScheme/> 
    </cac:PartyTaxScheme> 
    </cac:ReceiverParty>
    <cac:Attachment>
    <cac:ExternalReference>
    <cbc:MimeCode>text/xml</cbc:MimeCode>
    <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
    <cbc:Description><![CDATA[' . trim(str_replace("&#xF3;", "ó", $xml_factura)) . ']]></cbc:Description>
    </cac:ExternalReference>
    </cac:Attachment>
    <cac:ParentDocumentLineReference>
    <cbc:LineID>1</cbc:LineID>
    <cac:DocumentReference>
    <cbc:ID>' . $factura["Codigo"] . '</cbc:ID>
    <cbc:UUID schemeName="CUFE-SHA384">' . $cufe . '</cbc:UUID>
    <cbc:IssueDate>' . $fecha . '</cbc:IssueDate>
    <cbc:DocumentType>ApplicationResponse</cbc:DocumentType>
    <cac:Attachment>
    <cac:ExternalReference>
    <cbc:MimeCode>text/xml</cbc:MimeCode>
    <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
    <cbc:Description><![CDATA[' . str_replace("  ", " ", $aplication_response) . ']]></cbc:Description>
    </cac:ExternalReference>
    </cac:Attachment>
    <cac:ResultOfVerification>
    <cbc:ValidatorID>Unidad Especial Dirección de Impuestos y Aduanas Nacionales</cbc:ValidatorID>
    <cbc:ValidationResultCode>' . $respuesta . '</cbc:ValidationResultCode>
    <cbc:ValidationDate>' . $fecha . '</cbc:ValidationDate>
    <cbc:ValidationTime>' . $hora . '</cbc:ValidationTime>
    </cac:ResultOfVerification>
    </cac:DocumentReference>
    </cac:ParentDocumentLineReference>
    </AttachedDocument>';

    file_put_contents('/home/sigesproph/public_html/AD/ad' . $name_file . '.xml', $xml);

    $xml_resp = '/home/sigesproph/public_html/AD/ad' . $name_file . '.xml';
    return $xml_resp;
}

function getNombre()
{
    global $resolucion, $factura;
    $nit = getNit();
    $codigo = (int)str_replace($resolucion['Codigo'], "", $factura['Codigo']);
    $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date('y') . str_pad($codigo, 8, "0", STR_PAD_LEFT);
    return $nombre;
}

function getNombreConFecha($fecha)
{
    global $resolucion, $factura;
    if (!$fecha) {
        return null;
    }
    $nit = getNit();
    $codigo = (int)str_replace($resolucion['Codigo'], "", $factura['Codigo']);
    $anio = date('y', strtotime($fecha));
    return str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . $anio . str_pad($codigo, 8, "0", STR_PAD_LEFT);
}

function getNit()
{
    global $configuracion;
    if (empty($configuracion) || !isset($configuracion["NIT"])) {
        // Si no hay configuración cargada, intentar cargarla
        $oItem = new complex("Configuracion", "Id_Configuracion", 1);
        $configuracion = $oItem->getData();
        unset($oItem);
    }
    if (empty($configuracion) || !isset($configuracion["NIT"])) {
        return "0000000000"; // Valor por defecto si no se puede obtener
    }
    $num = explode("-", $configuracion["NIT"]);
    $nit = str_replace(".", "", $num[0]);
    return $nit;
}

function getTercero($factura)
{
    $cliente = [];
    $query = 'SELECT * FROM Factura_Administrativa WHERE Id_Factura_Administrativa = ' . $factura['Id_Factura_Administrativa'];
    $oCon = new consulta();
    $oCon->setQuery($query);

    $facturaAdmin = $oCon->getData();
    unset($oCon);


    $query = '';
    switch ($facturaAdmin['Tipo_Cliente']) {
        case 'Funcionario':
            $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,
                                        CONCAT_WS(" ",Nombres,Apellidos)AS Nombre,
                                        Correo AS Correo_Persona_Contacto , Celular, "Natural" AS Tipo, "CC" AS Tipo_Identificacion,
                        "" AS Digito_Verificacion, "Simplificado" AS Regimen, Direccion_Residencia AS Direccion, Telefono,
                        IFNULL(Id_Municipio,99) AS Id_Municipio , 1 AS Condicion_Pago
                            FROM Funcionario WHERE Identificacion_Funcionario = ' . $facturaAdmin['Id_Cliente'];
            break;

        case 'Proveedor':
            $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,
                                    
                                    (CASE 
                                        WHEN Tipo = "Juridico" THEN Razon_Social
                                        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                                        
                                    END) AS Nombre,
                                    Correo AS Correo_Persona_Contacto,
                                        Celular, Tipo, "NIT" AS Tipo_Identificacion,
                                    Digito_Verificacion, Regimen, Direccion ,Telefono,
                    Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                        FROM Proveedor WHERE Id_Proveedor = ' . $facturaAdmin['Id_Cliente'];
            break;

        case 'Cliente':
            return getCliente($factura);
            break;

        default:

            break;
    }

    $oCon = new consulta();
    $oCon->setQuery($query);

    $cliente = $oCon->getData();
    unset($oCon);

    return $cliente;
}

function getCliente($factura)
{

    $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente, Contribuyente, Autorretenedor,
                            (CASE 
                                WHEN Tipo = "Juridico" THEN Razon_Social
                                ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                                
                            END) AS Nombre, 
                            Correo_Persona_Contacto,
                            Celular, Tipo, Tipo_Identificacion,
                            Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono, 
                Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                 FROM Cliente WHERE Id_Cliente =' . $factura['Id_Cliente'];
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cliente = $oCon->getData();

    unset($oCon);
    return $cliente;
}

function obtenerXmlNotaCredito($resolution_ids, $nombres_archivo)
{
    $resultado = [
        'xml' => null,
        'origen' => null,
    ];
    if (empty($resolution_ids) || empty($nombres_archivo)) {
        return $resultado;
    }

    $base = 'https://api-dian.sigesproph.com.co/api-dian';
    $nombres = is_array($nombres_archivo) ? $nombres_archivo : [$nombres_archivo];
    $resoluciones = is_array($resolution_ids) ? $resolution_ids : [$resolution_ids];
    $paths = [
        '/storage/app/xml/1/{res}/nc{name}.xml',
        '/storage/app/xml/1/{res}/NC{name}.xml',
        '/storage/app/xml/{res}/nc{name}.xml',
        '/storage/app/ubl/1/{res}/nc{name}.xml',
    ];

    foreach ($resoluciones as $resolution_id) {
        foreach ($nombres as $nombre_archivo) {
            foreach ($paths as $path) {
                $ruta = str_replace(['{res}', '{name}'], [$resolution_id, $nombre_archivo], $path);
                $url = rtrim($base, '/') . $ruta;
                $xml = leerXmlNotaCredito($url);
                if ($xml !== false && $xml !== '') {
                    $resultado['xml'] = $xml;
                    $resultado['origen'] = $url;
                    return $resultado;
                }
                $resultado['origen'] = $url;
            }
        }
    }

    return $resultado;
}

function leerXmlNotaCredito($url)
{
    $context = stream_context_create([
        'http' => ['timeout' => 8],
        'https' => [
            'timeout' => 8,
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    return @file_get_contents($url, false, $context);
}

function obtenerXmlDesdeRuta($ruta)
{
    $resultado = [
        'xml' => null,
        'origen' => $ruta,
    ];
    if (!$ruta) {
        return $resultado;
    }
    if (file_exists($ruta)) {
        $xml = file_get_contents($ruta);
        if ($xml !== false && $xml !== '') {
            $resultado['xml'] = $xml;
            return $resultado;
        }
    }
    $url = convertirRutaApiDian($ruta);
    if ($url) {
        $xml = leerXmlNotaCredito($url);
        if ($xml !== false && $xml !== '') {
            $resultado['xml'] = $xml;
            $resultado['origen'] = $url;
        }
    }
    return $resultado;
}

function convertirRutaApiDian($ruta)
{
    $prefijo = '/home/sigesproph/api-dian.sigesproph.com.co';
    if (strpos($ruta, $prefijo) !== 0) {
        return null;
    }
    $rel = substr($ruta, strlen($prefijo));
    if ($rel === false) {
        return null;
    }
    return 'https://api-dian.sigesproph.com.co' . $rel;
}

function obtenerResolutionIds($actual_resolution_id)
{
    $ids = [];
    if ($actual_resolution_id) {
        $ids[] = (int)$actual_resolution_id;
    }
    $oCon = new consulta();
    $oCon->setQuery("SELECT resolution_id FROM Resolucion WHERE resolution_id IS NOT NULL");
    $oCon->setTipo("Multiple");
    $lista = $oCon->getData();
    unset($oCon);
    if (is_array($lista)) {
        foreach ($lista as $row) {
            if (!empty($row['resolution_id'])) {
                $ids[] = (int)$row['resolution_id'];
            }
        }
    }
    $ids = array_values(array_unique(array_filter($ids)));
    return $ids;
}

function obtenerFacturaOrigenNota($tipo, $nota_credito)
{
    $tipoFactura = $tipo == 'Nota_Credito_Global' ? $nota_credito['Tipo_Factura'] : 'Factura_Venta';
    $oItem = new complex($tipoFactura, 'Id_' . $tipoFactura, $nota_credito["Id_Factura"]);
    $factura = $oItem->getData();
    unset($oItem);
    return $factura;
}

function obtenerClienteNotaCredito($tipo, $nota_credito, $factura_origen)
{
    if ($tipo == 'Nota_Credito_Global' && $nota_credito['Tipo_Factura'] == 'Factura_Administrativa') {
        return getTercero($factura_origen);
    }
    return getCliente($factura_origen);
}

function obtenerApplicationResponseDesdeDIAN($cufe)
{
    if (empty($cufe)) {
        return null;
    }

    $login = 'facturacion@prohsa.com';
    $password = '804016084';
    $host = 'https://api-dian.sigesproph.com.co';
    $api = '/api';
    $version = '/ubl2.1';
    $modulo = '/status/document/';
    $url = $host . $api . $version . $modulo . $cufe;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    $headers = array(
        "Content-type: application/json",
        "Accept: application/json",
        "Cache-Control: no-cache",
        "Authorization: Basic " . base64_encode($login . ':' . $password),
        "Pragma: no-cache",
        "SOAPAction:\"" . $url . "\""
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Error al consultar GetStatus DIAN con CUFE: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    if ($result) {
        $json_output = json_decode($result, true);

        // GetStatus devuelve el ApplicationResponse en XmlBase64Bytes
        if (isset($json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusResponse"]["GetStatusResult"]["XmlBase64Bytes"])) {
            $app_response_base64 = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusResponse"]["GetStatusResult"]["XmlBase64Bytes"];
            $app_response = base64_decode($app_response_base64);

            if (!empty($app_response) && (strpos($app_response, '<?xml') === 0 || strpos($app_response, '<ApplicationResponse') !== false)) {
                return $app_response;
            }
        }
    }

    return null;
}

function obtenerApplicationResponseDesdeArchivo($resolution_id, $codigo_factura)
{
    if (!$resolution_id || !$codigo_factura) {
        return null;
    }

    // Obtener el nombre del archivo
    global $resolucion, $factura;
    $name_file = getNombre();

    // Rutas posibles donde puede estar guardado el archivo AD
    $rutas_posibles = [
        '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolution_id . '/ad' . $name_file . '.xml',
        '/home/sigesproph/public_html/AD/ad' . $name_file . '.xml',
        'https://api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolution_id . '/ad' . $name_file . '.xml',
    ];

    foreach ($rutas_posibles as $ruta) {
        $xml_content = null;

        // Si es una URL, usar file_get_contents
        if (strpos($ruta, 'http') === 0) {
            $context = stream_context_create([
                'http' => ['timeout' => 5],
                'https' => [
                    'timeout' => 5,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $xml_content = @file_get_contents($ruta, false, $context);
        } else {
            // Si es una ruta local, leer directamente
            if (file_exists($ruta)) {
                $xml_content = @file_get_contents($ruta);
            }
        }

        if ($xml_content && !empty($xml_content)) {
            // Extraer el ApplicationResponse del XML del contenedor
            // Buscar el CDATA que contiene el ApplicationResponse
            if (preg_match('/<cbc:Description><!\[CDATA\[(.*?)\]\]><\/cbc:Description>/is', $xml_content, $matches)) {
                // Puede haber múltiples Description, necesitamos el que está dentro de DocumentReference con DocumentType="ApplicationResponse"
                if (preg_match('/<cbc:DocumentType>ApplicationResponse<\/cbc:DocumentType>.*?<cbc:Description><!\[CDATA\[(.*?)\]\]><\/cbc:Description>/is', $xml_content, $app_matches)) {
                    $app_response = trim($app_matches[1]);
                    if (!empty($app_response) && strpos($app_response, '<?xml') === 0) {
                        return $app_response;
                    }
                }
            }
        }
    }

    return null;
}
