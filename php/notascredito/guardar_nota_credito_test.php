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

$contabilizar = new Contabilizar();
$configuracion = new Configuracion();

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

$datos_movimiento_contable = array();

$datos_movimiento_contable['Id_Registro'] = $productos[0]['Id_Factura_Venta'];
$datos_movimiento_contable['Nit'] = $datos['Id_Cliente'];
$datos_movimiento_contable['Productos'] = $productos;

$contabilizar->CrearMovimientoContable('Nota Credito', $datos_movimiento_contable);

echo "llego";
exit;


// $cod= $configuracion->Consecutivo('Nota_Credito');

// $datos['Codigo']=$cod;
    
// $oItem = new complex($mod,"Id_".$mod);
// foreach($datos as $index=>$value) {
//     $oItem->$index=$value;
// }
// $oItem->save();
// $id_venta = $oItem->getId();
// $resultado = array();
// unset($oItem);

// /* AQUI GENERA QR */
// $qr = generarqr('notascredito',$id_venta,'/IMAGENES/QR/');
// $oItem = new complex("Nota_Credito","Id_Nota_Credito",$id_venta);
// $oItem->Codigo_Qr=$qr;
// $oItem->save();
// unset($oItem);
// /* HASTA AQUI GENERA QR */

// foreach($productos as $producto){
//     if($producto['Nota']){
//         $oItem = new complex('Producto_Nota_Credito',"Id_Producto_Nota_Credito");
//         $producto["Id_Nota_Credito"]=$id_venta;
//         foreach($producto as $index=>$value) {
//             $oItem->$index=$value;
//         }
//         $oItem->Cantidad=$producto['Cantidad_Ingresada'];
//         $oItem->Id_Motivo=$producto['Id_Motivo'];
//         $oItem->save();
//         unset($oItem);
//     }
  
// }
// if($id_venta != ""){
//     $resultado['mensaje'] = "Se ha guardado correctamente la nota credito con codigo: ". $datos['Codigo'];
//     $resultado['tipo'] = "success";
// }else{
//     $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
//     $resultado['tipo'] = "error";
// }

// echo json_encode($resultado);

?>		