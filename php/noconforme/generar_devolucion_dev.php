<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

require '../../class/class.awsS3.php';
$files = (isset($_FILES) ? $_FILES : []);

$configuracion = new Configuracion();
$contabilizacion = new Contabilizar();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

/* var_dump($datos);
var_dump($productos);
exit;
 */
$cod = $configuracion->getConsecutivo('Devolucion_Compra','Devolucion_Compras');
$datos['Codigo'] = $cod;


if(count($files)>0){
  
  $s3 = new AwsS3();
  $ruta = "devolucion_compra/soporte";
  $uri = $s3->putObject( $ruta, $files['soporte']);
}




$oItem = new complex('Devolucion_Compra','Id_Devolucion_Compra');
foreach($datos as $index=>$value){
  $oItem->$index=$value;
}
$oItem->Estado = 'Activa';
$oItem->Estado_Alistamiento = '0';
$oItem->Soporte = $uri;
$oItem->save();
$idDevolucionCompra = $oItem->getId();





unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('devolucioncompra',$idDevolucionCompra,'/IMAGENES/QR/');
$oItem = new complex("Devolucion_Compra","Id_Devolucion_Compra",$idDevolucionCompra);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */


  //Guardar actividad de la  
  $oItem = new complex('Actividad_Devolucion_Compra',"Id_Actividad_Devolucion_Compra".$idDevolucionCompra);
  $oItem->Id_Devolucion_Compra=$idDevolucionCompra;
  $oItem->Identificacion_Funcionario=$datos['Identificacion_Funcionario'];
  $oItem->Detalles="Se crea la devolucion de compra ".$datos["Codigo"];
  $oItem->Estado="Creacion";
  $oItem->Fecha=date("Y-m-d H:i:s");
  $oItem->save();
  unset($oItem);


unset($productos[count($productos)-1]);

foreach($productos as $producto){
    $oItem = new complex('Producto_Devolucion_Compra',"Id_Producto_Devolucion_Compra");
    $oItem->Id_Devolucion_Compra = $idDevolucionCompra;
    $oItem->Id_Producto = $producto['Id_Producto'];
    $oItem->Cantidad = $producto['Cantidad'];
    $oItem->Lote = $producto['Lote'];
    
    $oItem->Factura = $producto['Factura'];
    $oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
    $oItem->Id_Inventario_Nuevo = $producto['Id_Inventario_Nuevo'];
    $oItem->Motivo = $producto['Motivo'];
    $oItem->Costo = $producto['Costo'];
    $oItem->Costo_Reconocido = $producto['Costo_Reconocido'];
    $oItem->Impuesto = number_format($producto['Impuesto'],0,"","");
  
    $oItem->save();
    unset($oItem);


}

    
$resultado['mensaje'] = "Se ha generado la devolucion de compra con codigo: ". $datos['Codigo'];
$resultado['tipo'] = "success";


echo json_encode($resultado);

$datos_movimientos['Id_Registro'] = $idDevolucionCompra;
$datos_movimientos['datos'] = $datos;
$datos_movimientos['productos'] = $productos;

$contabilizacion->CrearMovimientoContable('Devolucion Acta', $datos_movimientos);


?>