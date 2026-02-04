<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once('../../class/class.contabilizar.php');

$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$tipo_ajuste = ( isset( $_REQUEST['tipo_ajuste'] ) ? $_REQUEST['tipo_ajuste'] : '' );

$contabilizacion = new Contabilizar(true);

if ($tipo_ajuste == "Salida") {
  # code...
  $productos = (array) json_decode($productos , true); 
$datos = (array) json_decode($datos ); 

$configuracion = new Configuracion();
$cod = $configuracion->getConsecutivo('Ajuste_Individual','Ajuste_Individual');

$oItem = new complex('Ajuste_Individual', 'Id_Ajuste_Individual');
$oItem->Identificacion_Funcionario = $funcionario;
$oItem->Codigo = $cod;
$oItem->Tipo = "Salida";
$oItem->Id_Clase_Ajuste_Individual = $datos['Id_Clase_Ajuste_Individual'];
$oItem->Origen_Destino = $datos['Tipo'];
if ($datos['Tipo']=="Bodega") {
  # code...
  $oItem->Id_Origen_Destino = $datos['Id_Bodega_Nuevo'] ;
}else if($datos['Tipo']=="Punto"){

  $oItem->Id_Origen_Destino = $datos['Id_Punto_Dispensacion'];
}
$oItem->save();
$id_ajuste = $oItem->getId();
unset($oItem);

/* AQUI GENERA QR 

$qr = generarqr('ajusteindividual',$id_ajuste,'IMAGENES/QR/');
$oItem = new complex("Ajuste_Individual","Id_Ajuste_Individual",$id_ajuste);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
HASTA AQUI GENERA QR */


foreach($productos as $producto){
//Descontar del inventario
//var_dump($producto["Cantidad_Actual"]);
$oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo', $producto["Id_Inventario_Nuevo"]);
$cantidad = number_format($producto["Cantidad_Actual"],0,"","");
$cantidad_final = $oItem->Cantidad-$cantidad;
if($cantidad_final<0){
  $cantidad_final=0;
}
$oItem->Cantidad=number_format($cantidad_final,0,"","");
$id_inventario_nuevo = $oItem->Id_Inventario_Nuevo;
$oItem->save();
unset($oItem);

$oItem = new complex('Producto_Ajuste_Individual','Id_Producto_Ajuste_Individual');
$oItem->Id_Ajuste_Individual = $id_ajuste;
$oItem->Id_Producto = $producto["Id_Producto"];
$oItem->Id_Inventario_Nuevo = $id_inventario_nuevo;
$oItem->Lote = $producto['Lote'];
$oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
$oItem->Observaciones = $producto['Observaciones'];
$oItem->Cantidad = $producto['Cantidad_Actual'];
$oItem->Costo = $producto['Costo'];
$oItem->save();
unset($oItem);

}

    
if ($id_inventario_nuevo) {

    $datos_movimiento_contable['Id_Registro'] = $id_ajuste;
    $datos_movimiento_contable['Nit'] = $funcionario;
    $datos_movimiento_contable['Tipo'] = "Salida";
    $datos_movimiento_contable['Clase_Ajuste'] = $datos['Id_Clase_Ajuste_Individual'];
    $datos_movimiento_contable['Productos'] = $productos;
    
    $contabilizacion->CrearMovimientoContable('Ajuste Individual',$datos_movimiento_contable);
  
    $resultado['mensaje'] = "Se ha guarda correctamente la salida de los productos";
    $resultado['tipo'] = "success";
    $resultado['titulo'] = "Operación Exitosa";
  } else {
    $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor intentelo de nuevo";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Error";
  }
}else{
  $resultado['mensaje'] = "El tipo de ajuste no es permitido";
  $resultado['tipo'] = "error";
  $resultado['titulo'] = "Ha ocurrido un error inesperado.";
}

echo json_encode($resultado);

?>