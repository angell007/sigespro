<?php

// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');
include_once($_SERVER['DOCUMENT_ROOT']."/class/class.http_response.php");

if (!function_exists('myerror')) {

    function myerror($message = '')
    {
        header("HTTP/1.0 400 ");
        $response = new HttpResponse();
        $response->SetRespuesta(1, 'Operacion Erronea', $message);
        $response->SetDatosRespuesta([]);
        return $response->GetRespuesta();
    }
}


if (!function_exists('mysuccess')) {
    function mysuccess($data)
    {
        http_response_code(200);
        $response = new HttpResponse();
        $response->SetRespuesta(0, 'Operacion Exitosa', '');
        $response->SetDatosRespuesta($data);
        return $response->GetRespuesta();
    }
}


if (!function_exists('show')) {
    function show($data)
    {
        http_response_code(200);
        echo json_encode($data);
        die();
    }
}