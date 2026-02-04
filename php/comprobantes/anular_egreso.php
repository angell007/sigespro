<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require('./funciones.php');
require('../contabilidad/funciones.php');



if ($id_documento_contable) {
    $resultado['mensaje'] = "Se ha registrado un comprobante de egreso satisfactoriamente";
    $resultado['tipo'] = "success";
    $resultado['titulo'] = "Operación Exitosa!";
    $resultado['id'] = $id_documento_contable;
} else {
    $resultado['mensaje'] = "Ha ocurrido un error de conexión, comunicarse con el soporte técnico.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>