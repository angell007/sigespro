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
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$productos = (array) json_decode($productos , true); 
$datos = (array) json_decode($datos ); 

// var_dump($productos);
// var_dump($datos);
// exit;  

$datos_movimiento_contable = array();

$datos_movimiento_contable['Id_Registro'] = "1";
$datos_movimiento_contable['Nit'] = $funcionario;
$datos_movimiento_contable['Tipo'] = 'Entrada';
$datos_movimiento_contable['Productos'] = $productos;

$contabilizar->CrearMovimientoContable('Ajuste Individual', $datos_movimiento_contable);

echo "Finalizo";
exit;

// $configuracion = new Configuracion();
// $cod = $configuracion->Consecutivo('Ajuste_Individual');

// $oItem = new complex('Ajuste_Individual', 'Id_Ajuste_Individual');
// $oItem->Identificacion_Funcionario = $funcionario;
// $oItem->Codigo = $cod;
// $oItem->Tipo = "Entrada";
// $oItem->Origen_Destino = $datos['Tipo'];
// $oItem->Id_Origen_Destino = $datos['Id_Bodega'] != "" ? $datos['Id_Bodega'] : $datos['Id_Punto_Dispensacion'];
// $oItem->save();
// $id_ajuste = $oItem->getId();
// unset($oItem);

// /* AQUI GENERA QR */
// //$qr = generarqr('ordencompranacional',$id_venta,$MY_FILE.'/IMAGENES/QR/');
// $qr = generarqr('ajusteindividual',$id_ajuste,'IMAGENES/QR/');
// $oItem = new complex("Ajuste_Individual","Id_Ajuste_Individual",$id_ajuste);
// $oItem->Codigo_Qr=$qr;
// $oItem->save();
// unset($oItem);
// /* HASTA AQUI GENERA QR */

// foreach($productos as $producto){

//   if ($datos['Id_Bodega'] != "" && $datos['Id_Punto_Dispensacion'] == "") {
//     $query = "SELECT Id_Inventario, Cantidad FROM Inventario WHERE Id_Producto=$producto[Id_Producto] AND Lote='$producto[Lote]' AND Fecha_Vencimiento='$producto[Fecha_Vencimiento]' AND Id_Bodega=$datos[Id_Bodega]";
//   } else {
//     $query = "SELECT Id_Inventario, Cantidad FROM Inventario WHERE Id_Producto=$producto[Id_Producto] AND Lote='$producto[Lote]' AND Fecha_Vencimiento='$producto[Fecha_Vencimiento]' AND Id_Punto_Dispensacion=$datos[Id_Punto_Dispensacion]";
//   }

// $oCon= new consulta();
// $oCon->setQuery($query);
// $inventario = $oCon->getData();
// unset($oCon);

// if ($inventario) { // Si existe el producto en el inventario
//   $oItem = new complex('Inventario','Id_Inventario', $inventario['Id_Inventario']);
//   $cantidad = number_format($producto["Cantidad"],0,"","");
//   $cantidad_inventario = number_format($inventario["Cantidad"],0,"","");
//   $cantidad_final = $cantidad_inventario + $cantidad;
//   $oItem->Cantidad = $cantidad_final;
//   $costo = number_format($producto["Costo"],2,".","");
//   $oItem->Costo=$costo;
//   $id_inventario = $oItem->Id_Inventario;
// } else {
//   $oItem = new complex('Inventario','Id_Inventario');
//   $cantidad = number_format($producto["Cantidad"],0,"","");
//   $oItem->Cantidad=$cantidad;
//   $oItem->Id_Producto=$producto["Id_Producto"];
//   $oItem->Codigo_CUM=$producto["Codigo_Cum"];
//   $oItem->Lote=$producto["Lote"];
//   $oItem->Fecha_Vencimiento=$producto["Fecha_Vencimiento"];
//   if($datos["Tipo"]==="Bodega"){
//     $oItem->Id_Bodega=$datos["Id_Bodega"];  
//     $oItem->Id_Punto_Dispensacion=0;  
//   }else if($datos["Tipo"]==="Punto"){
//       $oItem->Id_Bodega=0;  
//       $oItem->Id_Punto_Dispensacion=$datos["Id_Punto_Dispensacion"]; 
//   }
//   $costo = number_format($producto["Costo"],2,".","");
//   $oItem->Costo=$costo;
//   $oItem->Cantidad_Apartada=0;
// }

// $oItem->Identificacion_Funcionario=$funcionario;

// $oItem->save();


// if (!$inventario) { // Si no existe el producto en el inventario obtengo el último id registrado
//   $id_inventario = $oItem->getId();
// }
// unset($oItem);

// $oItem = new complex('Producto_Ajuste_Individual','Id_Producto_Ajuste_Individual');
// $oItem->Id_Ajuste_Individual = $id_ajuste;
// $oItem->Id_Producto = $producto["Id_Producto"];
// $oItem->Id_Inventario = $id_inventario;
// $oItem->Lote = $producto['Lote'];
// $oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
// $oItem->Observaciones = $producto['Observaciones'];
// $cantidad1= number_format($producto["Cantidad"],0,"","");
// $oItem->Cantidad =$cantidad1;
// $costo = number_format($producto["Costo"],2,".","");
// $oItem->Costo=$costo;
// $oItem->save();
// unset($oItem);

// }

// if ($id_inventario) {
//   $resultado['mensaje'] = "Se ha guarda correctamente la Entrada";
//   $resultado['tipo'] = "success";
//   $resultado['titulo'] = "Operación Exitosa";
// } else {
//   $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor intentelo de nuevo";
//   $resultado['tipo'] = "error";
//   $resultado['titulo'] = "Error";
// }


// echo json_encode($resultado);

?>