<?php

    require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    require_once('../../class/class.qr.php'); 
    require_once('../../class/class.php_mailer.php'); 
    
    
    include_once($MY_CLASS . "class.facturacion_electronica.php");
    
    date_default_timezone_set("America/Bogota");

    header('Content-Type: application/json');
    $tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : "Factura_Administrativa");
    $reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : null);
    $id_factura = ( isset( $_REQUEST['id_factura'] ) ? $_REQUEST['id_factura'] : '' );
    

    
    $fe = new FacturaElectronica($tipo, $id_factura, $reso); 
    $datos = $fe->GenerarFactura();
   
  
    $resultado = array();
    $debug = isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1';

    if (isset($datos['Respuesta_Correo']) && $datos['Respuesta_Correo']['Estado'] === "Exito") {
   
        $resultado['titulo'] = "Correo Enviado";
        $resultado['mensaje'] = $datos['Respuesta_Correo']['Respuesta'];  
        $resultado['tipo'] = "success";
    
    } else {
        
        $resultado['titulo'] = "Error Enviando Correo";
        $resultado['mensaje'] = isset($datos['Respuesta_Correo']['Respuesta']) ? $datos['Respuesta_Correo']['Respuesta'] : "No se pudo enviar el correo.";
        $resultado['tipo'] = "error";
    }

    if ($debug) {
        $resultado['debug'] = [
            'Respuesta_Correo' => isset($datos['Respuesta_Correo']) ? $datos['Respuesta_Correo'] : null,
            'Estado' => isset($datos['Estado']) ? $datos['Estado'] : null,
            'Detalles' => isset($datos['Detalles']) ? $datos['Detalles'] : null,
        ];
    }

    echo json_encode($resultado);

?>
    
