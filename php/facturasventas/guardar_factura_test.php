<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$contabilizar = new Contabilizar();
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos_dis = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$encabezadoFactura = ( isset( $_REQUEST['encabezadoFactura'] ) ? $_REQUEST['encabezadoFactura'] : '' );
$encabezadoHomologo = ( isset( $_REQUEST['encabezadoHomologo'] ) ? $_REQUEST['encabezadoHomologo'] : false );
$productosFactura = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$productosHomologo = ( isset( $_REQUEST['productos1'] ) ? $_REQUEST['productos1'] : false );
$identificacion_Funcionario = ( isset( $_REQUEST['Identificacion_Funcionario'] ) ? $_REQUEST['Identificacion_Funcionario'] : false );
$hom = ( isset( $_REQUEST['switch_hom'] ) ? $_REQUEST['switch_hom'] : '' );
$fact = ( isset( $_REQUEST['switch_fact'] ) ? $_REQUEST['switch_fact'] : '' );

$id_factura_asociada = ''; // Solo aplica para facturas homologos
$encabezadoFactura = (array) json_decode($encabezadoFactura, true);
$encabezadoHomologo = (array) json_decode($encabezadoHomologo, true);
$productosFactura = (array) json_decode($productosFactura , true);
$datos_dis = (array) json_decode($datos_dis);



// if ($encabezadoHomologo && $productosHomologo) {
//     $encabezadoHomologo = (array) json_decode($encabezadoHomologo, true);
//     $productosHomologo = (array) json_decode($productosHomologo , true);
// } else {
//     $encabezadoHomologo = [];
//     $productosHomologo = [];
// }

//  var_dump($datos_dis);
// var_dump($encabezadoFactura);
// var_dump($encabezadoHomologo);
// var_dump($productosFactura);
// var_dump($productosHomologo);
// exit; 
$idDispensacion = (array) json_decode($idDispensacion, true);

$datos_movimiento_contable = array();

$datos_movimiento_contable['Id_Registro'] = "386";
$datos_movimiento_contable['Nit'] = $encabezadoFactura['Id_Cliente'];
$datos_movimiento_contable['Nit2'] = $encabezadoHomologo['Id_Cliente'];
//$datos_movimiento_contable['Productos'] = $productos;

$contabilizar->CrearMovimientoContable('Factura', $datos_movimiento_contable);

exit;

// if(count($encabezadoFactura) >0){
//     if($fact=='true'){
//       $factura = guardarFactura($encabezadoFactura, $productosFactura, "Factura",$mod); 
      
//      // echo "Guarda la factura";
//     }
 
// }
// if(count($encabezadoHomologo) >0){
//     if($hom=='true'){
//        $homologo = guardarFactura($encabezadoHomologo, $productosHomologo, "Homologo",$mod);
//       // echo "Guarda el homologo";
//     }
   
// }


// if($factura[0] != false && $homologo[0] != false){
    
//     $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoFactura['Id_Dispensacion']);
//     $dispensacion = $oItem->getData();
//     $oItem->Id_Factura = $factura[1];
//     $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
//     $oItem->Estado_Facturacion = "Facturada";
//     $oItem->save();
//     unset($oItem);
    
//     $resultado['titulo'] = "Creacion exitosa";
//     $resultado['mensaje'] = "Se ha guardado correctamente la Factura con codigo: ". $factura[0] . " Y la Homologacion con codigo: ".$homologo[0];
//     $resultado['tipo'] = "success";
//     $resultado['Id'] = $factura[1];
//     $resultado['Fact'] = 'Homologo';
// }elseif($factura[0] != false){
    
//     $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoFactura['Id_Dispensacion']);
//     $dispensacion = $oItem->getData();
//     $oItem->Id_Factura = $factura[1];
//     $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
//     $oItem->Estado_Facturacion = "Facturada";
//     $oItem->save();
//     unset($oItem);
    
//     $resultado['titulo'] = "Creacion exitosa";
//     $resultado['mensaje'] = "Se ha guardado correctamente la Factura con codigo: ". $factura[0];
//     $resultado['tipo'] = "success";
//     $resultado['Id'] = $factura[1];
// }elseif($homologo[0] != false){
    
//     if($homologo[0] != false){
//         $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoHomologo['Id_Dispensacion']);
//         $dispensacion = $oItem->getData();
//         $oItem->Id_Factura = $homologo[1];
//         $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
//         $oItem->Estado_Facturacion = "Facturada";
//        $oItem->save();
//         unset($oItem);
    
//         $resultado['titulo'] = "Creacion exitosa";
//         $resultado['mensaje'] = "Se ha guardado correctamente la Homologación con codigo: ".$homologo[0];
//         $resultado['tipo'] = "success";
//         $resultado['Id'] = $homologo[1];
//     }else{
//         $resultado['titulo'] = "Creacion no exitosa";
//         $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
//         $resultado['tipo'] = "error";
//     }
// }

// function guardarFactura($datos, $productos, $tipo,$mod){

//     global $datos_dis;
//     global $id_factura_asociada;

//     switch($tipo){
//         case "Factura":{

//             $oItem = new complex('Resolucion','Id_Resolucion',3); // Resolucion 3 para Facturas Ventas NoPos
//             $nc = $oItem->getData();

//             $oItem->Consecutivo=$oItem->Consecutivo+1;
//             $oItem->save();
//             $num_cotizacion=$nc["Consecutivo"];
//             unset($oItem);
                
//             $cod = $nc["Codigo"].$nc["Consecutivo"];
            
//             $datos['Codigo']=$cod;

//             break;
//         }
//         case "Homologo":{
//                 $configuracion = new Configuracion();
//             /*$oItem->Homologo =$oItem->Homologo+1;
//             // $oItem->save();
//             $num_cotizacion=$nc["Homologo"];
//             unset($oItem);
                
//             $cod = $nc['Prefijo_Homologos'].sprintf("%05d", $num_cotizacion); */
//             /* $cod = $configuracion->Consecutivo('Homologo');
//             $datos['Codigo']=$cod; */

//             $oItem = new complex('Resolucion','Id_Resolucion',3); // Resolucion 3 para Facturas Ventas NoPos
//             $nc = $oItem->getData();

//             $oItem->Consecutivo=$oItem->Consecutivo+1;
//             $oItem->save();
//             $num_cotizacion=$nc["Consecutivo"];
//             unset($oItem);
                
//             $cod = $nc["Codigo"].$nc["Consecutivo"];
            
//             $datos['Codigo']=$cod;
//             break;
//         }
//     }
    
  
//    $oItem = new complex($mod,"Id_".$mod);

//    /* $funcionario = intval($identificacion_Funcionario);
//    $datos['Id_Funcionario'] = $funcionario; */
    
//     foreach($datos as $index=>$value) {
//         $oItem->$index=$value;
//     }
//     if ($tipo == 'Homologo') {
//         $id = (INT) $id_factura_asociada;
//         $oItem->Id_Factura_Asociada = number_format($id,0,"","");
//     }
//     $oItem->save();
//     $id_factura = $oItem->getId();
//     if ($tipo == 'Factura') { // Para poder utilizar la variable cuando se registre un homologo
//         $id_factura_asociada = $id_factura;
//     }

        $datos_movimiento_contable['Id_Registro'] = $id_factura;
        $datos_movimiento_contable['Nit'] = $tipo == 'Factura' ? $encabezadoFactura['Id_Cliente'] : $encabezadoHomologo['Id_Cliente'];
        $contabilizar->CrearMovimientoContable('Factura', $datos_movimiento_contable);
    
//     $resultado = array();
//     unset($oItem);

//     /* AQUI GENERA QR */
//     $qr = generarqr('factura',$id_factura,$MY_FILE.'/IMAGENES/QR/');
//     $oItem = new complex("Factura","Id_Factura",$id_factura);
//     $oItem->Codigo_Qr=$qr;
//     $oItem->save();
//     unset($oItem);
//     /* HASTA AQUI GENERA QR */

//     unset($productos[count($productos)-1]); // Eliminar la última posicion de la lista de productos (nos pos u homologos).

//     foreach($productos as $producto){
//         $oItem = new complex('Producto_'.$mod,"Id_Producto_".$mod);
//         $producto["Id_".$mod]=$id_factura;
//         $subtotal = number_format((INT) $producto['Subtotal'],2,".","");
//         $producto['Subtotal'] = $subtotal;
//         foreach($producto as $index=>$value) {
//             $oItem->$index=$value;
//         }
//         $impuesto = $producto['Impuesto'] != 0 ? (FLOAT) $producto['Impuesto'] * 100 : 0;
//         $oItem->Impuesto = number_format((INT) $impuesto, 0, "","");
//         $oItem->Precio = number_format($producto['Precio'],2,".","");
//         $oItem->Descuento = number_format($producto['Descuento'],2,".","");
//         $oItem->save();
//         unset($oItem);

//         if ($producto['Registrar'] == 1 && $datos_dis['Tipo_Dispensacion'] == 'NoPos') { // Actualizar tabla de producto No pos
//             if ($tipo == 'Factura') {
//                 $q = "SELECT DLN.Id_Lista_Producto_Nopos, PNP.Id_Producto_NoPos FROM Departamento_Lista_Nopos DLN LEFT JOIN Producto_NoPos PNP ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = $datos_dis[Id_Departamento] AND PNP.Cum= '$producto[Cum]'";

//                 $oCon = new consulta();
//                 $oCon->setQuery($q);
//                 $res = $oCon->getData();
//                 unset($oCon);

//                 if (!$res) {

//                     $q = "SELECT DLN.Id_Lista_Producto_Nopos FROM Departamento_Lista_Nopos DLN WHERE DLN.Id_Departamento = $datos_dis[Id_Departamento]"; // Obtener el ID de Lista NoPos

//                     $oCon = new consulta();
//                     $oCon->setQuery($q);
//                     $res = $oCon->getData();
//                     unset($oCon);
                        
//                     $oItem = new complex('Producto_NoPos','Id_Producto_NoPos');
//                     $oItem->Cum = $producto['Cum'];
//                     $oItem->Precio = $producto['Precio'];
//                     $oItem->Id_Lista_Producto_Nopos = $res['Id_Lista_Producto_Nopos'];
//                     $oItem->save();
//                     unset($oItem);
//                 }
//             } elseif ($tipo == 'Homologo') {
//                 $q = "SELECT DLN.Id_Lista_Producto_Nopos, PNP.Id_Producto_NoPos FROM Departamento_Lista_Nopos DLN INNER JOIN Producto_NoPos PNP ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = $datos_dis[Id_Departamento] AND PNP.Cum= '$producto[Cum]'";

//                 $oCon = new consulta();
//                 $oCon->setQuery($q);
//                 $res = $oCon->getData();
//                 unset($oCon);

//                 if ($res) {
//                     $oItem = new complex('Producto_NoPos','Id_Producto_NoPos', $res['Id_Producto_NoPos']);
//                     $oItem->Cum_Homologo = $producto['Cum_Homologo'];
//                     $oItem->Precio_Homologo = $producto['Precio'];
//                     $oItem->Detalle_Homologo = $producto['Detalle_Homologo'];
//                     $oItem->save();
//                     unset($oItem);
//                 }
//             }
//         } elseif ($producto['Registrar'] == 1 && $datos_dis['Tipo_Dispensacion'] == 'Evento') {
//             $q = "SELECT PE.Id_Producto_Evento FROM Producto_Evento PE WHERE PE.Nit_EPS = $datos_dis[Nit] AND PE.Codigo_Cum= '$producto[Cum]'";

//             $oCon = new consulta(); /** Consulto si existe un registro en la tabla de producto evento, si no, registro uno nuevo */
//             $oCon->setQuery($q);
//             $res = $oCon->getData();
//             unset($oCon);

//             if (!$res) {
//                 $oItem = new complex('Producto_Evento','Id_Producto_Evento');
//                 $oItem->Codigo_Cum = $producto['Cum'];
//                 $oItem->Precio = $producto['Precio'];
//                 $oItem->Nit_EPS = $datos_dis['Nit'];
//                 $oItem->save();
//                 unset($oItem);
//             }
//         }
//     }
    
//     if($id_factura != "" || $id_factura != NULL ){
//         return [$cod, $id_factura];
//     }else{
//         return false;
//     }
    
// }

// echo json_encode($resultado);

?>		