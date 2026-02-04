<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php'); 

include_once('../../class/class.documento_soporte_electronico.php');

$fecha_inicio = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : "2024-09-27");
$reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : null);
$id_documento = (isset($_REQUEST['id_doc']) ? $_REQUEST['id_doc'] : null); 

$facts = GetDocumentos($reso, $fecha_inicio, $id_documento); 

if(count($facts) == 0){
    http_response_code(406);
    echo json_encode([
        'error' => 'No se encontraron documentos para reprocesar.',
        'mensaje' => 'No se encontraron documentos para reprocesar.',
    ]);
    exit;
}

foreach ($facts as $fac) { 
    $fe = new DocumentoElectronico($fac["Id_Documento_No_Obligados"], $fac["Id_Resolucion"]);
    $datos = $fe->GenerarDocumento();
    
    if (isset($datos['Estado']) && $datos['Estado'] === 'Exito') {
        $resp = [
            'titulo' => 'Reproceso Exitoso',
            'mensaje' => isset($datos['Detalles']) ? $datos['Detalles'] : 'Documento reprocesado correctamente.',
            'tipo' => 'success',
        ];
    } else {
        $resp = [
            'titulo' => 'Error en reproceso',
            'mensaje' => isset($datos['Detalles']) && $datos['Detalles'] ? $datos['Detalles'] : 'No se pudo reprocesar el documento.',
            'tipo' => 'error',
        ];
    }
    $resp['data'] = $datos;
}

echo json_encode($resp);

function GetDocumentos($res, $fecha_inicio, $id_documento)
{
    $cond_res = (isset($res) ? "AND Id_Resolucion IN (" . $res . ")" : '');
    $cond_id_doc = (isset($id_documento) ? "AND Id_Documento_No_Obligados = " . $id_documento : ''); 
    
    $query = "SELECT Id_Documento_No_Obligados, Codigo, Id_Resolucion FROM Documento_No_Obligados WHERE 
    (Procesada = 'false' OR Procesada IS NULL) $cond_res $cond_id_doc
    AND Fecha_Documento >= '" . $fecha_inicio . "'
    ORDER BY Fecha_Documento ASC /*LIMIT 0,1 AND Cufe IS NULL*/";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $lista = $oCon->getData();
    unset($oCon);

    return $lista;
}
