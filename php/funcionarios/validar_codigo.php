<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

date_default_timezone_set('America/Bogota');
require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';

$user = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$code = (isset($_REQUEST['code']) ? $_REQUEST['code'] : '');

$oItem = new complex("Funcionario", "Identificacion_Funcionario", $user);
$funcionario = $oItem->getData();
$hactual = date("H:i:s");

if ($code == ($funcionario['Codigo_Recuperacion'])) {
    $tiempo = calcularTiempo($funcionario['Fecha_Codigo_Recuperacion'], date('Y-m-d H:i:s'));

    if ($tiempo >= 5) {
        $respuesta['tipo'] = 'error';
        $respuesta['Mensaje'] = 'El Código ha expirado, es necesario que solicite uno nuevo';
        $respuesta['Titulo'] = 'error';
    }
    else{
      $oItem->Username=md5($funcionario['Identificacion_Funcionario']);
      $oItem->Password=md5($funcionario['Identificacion_Funcionario']);
      $respuesta['tipo'] = 'success';
      $respuesta['Mensaje'] = 'Cambiado Correctamente';
      $respuesta['Titulo'] = 'error';
      $oItem->save();
    }
} else {
      $respuesta['tipo'] = 'error';
      $respuesta['Mensaje'] = 'El Código no coincide';
      $respuesta['Titulo'] = 'error';
}

echo json_encode($respuesta);


function calcularTiempo($inicial, $final)
{
    $date1 = new DateTime($inicial);
    $date2 = new DateTime($final);
    $diff = $date1->diff($date2);

    return (($diff->days * 24) * 60) + ($diff->i);
}
