<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php'); 

include_once('../class/class.documento_soporte_electronico.php');

$fecha_inicio = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : "2024-09-27");
$reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : null);
$facts = GetDocumentos($reso, $fecha_inicio);


if(count($facts)==0){
        http_response_code(406);
}

foreach ($facts as $fac) { 
        $fe = new DocumentoElectronico($fac["Id_Documento_No_Obligados"], $fac["Id_Resolucion"]);
        $datos = $fe->GenerarDocumento();
        
        $resp['respuesta'] = $datos;
        
}

echo json_encode($resp);


function GetDocumentos($res, $fecha_inicio)
{
       $cond_res = (isset($res) ? "AND Id_Resolucion IN (" . $res . ")" : '');
       
    $query = "SELECT Id_Documento_No_Obligados, Codigo, Id_Resolucion FROM Documento_No_Obligados WHERE 
     (Procesada = 'false' OR Procesada IS NULL)  $cond_res
     $cond_factura
    AND Fecha_Documento >= '" . $fecha_inicio . "'
   order by Fecha_Documento ASC  /*LIMIT 0,1  AND Cufe IS NULL*/  ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $lista = $oCon->getData();
    unset($oCon);

    return $lista;
}
