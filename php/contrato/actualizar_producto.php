<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;
$PrecioActual = isset($_REQUEST['PrecioActual']) ? $_REQUEST['PrecioActual'] : false;
$datos = (array) json_decode($datos);

// var_dump($datos);

actualizarPrecioProducto($datos['Id_Producto_Contrato'],$PrecioActual, $datos['Precio'],$datos['Cantidad']);

guardarActividad($datos['Id_Producto'],$funcionario,$datos['Id_contrato'],'Actualizacion de precio');

function actualizarPrecioProducto($id,$PrecioActual,$nuevo_precio,$cantidad){
    $nuevo_precio = number_format($nuevo_precio,2,'.','');

    $query = 'UPDATE Producto_Contrato 
                SET Precio_Anterior = '.$PrecioActual.', Cantidad = '.$cantidad.', Precio = '.$nuevo_precio.',
                Ultima_Actualizacion = NOW()
                WHERE Id_Producto_Contrato = '.$id;

  
    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->createData();
    unset($oCon);

    return $data;
}

function guardarActividad($id_producto, $funcionario, $Id_Contrato, $observacion){

    $query = 'INSERT INTO Actividad_Producto_Contrato 
    (Id_Producto, Identificacion_Funcionario, Id_Contrato, Fecha, Detalle)
    VALUES('.$id_producto.','.$funcionario.','.$Id_Contrato.',NOW(), "'.$observacion.'")';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}