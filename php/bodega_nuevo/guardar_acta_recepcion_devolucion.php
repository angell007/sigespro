<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$contabilizar = new Contabilizar();
$configuracion = new Configuracion();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productoCompra = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$codigoCompra = ( isset( $_REQUEST['codigoCompra'] ) ? $_REQUEST['codigoCompra'] : '' );
$tipoCompra = ( isset( $_REQUEST['tipoCompra'] ) ? $_REQUEST['tipoCompra'] : '' );
$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );
$productos_eliminados = ( isset( $_REQUEST['eliminados'] ) ? $_REQUEST['eliminados'] : '' );
$archivos = ( isset( $_REQUEST['archivos'] ) ? $_REQUEST['archivos'] : '' );
$no_conforme_devolucion = ( isset( $_REQUEST['id_no_conforme'] ) ? $_REQUEST['id_no_conforme'] : false );

$datosProductos = (array) json_decode($productoCompra , true);
$datos = (array) json_decode($datos);
$facturas = (array) json_decode($facturas, true);
$productos_eliminados = (array) json_decode($productos_eliminados, true);

$datos_movimiento_contable = array();

// var_dump($datos);
// var_dump($datosProductos);
// var_dump($facturas);
// var_dump($_FILES);
// exit;



$cod = $configuracion->getConsecutivo('Acta_Recepcion','Acta_Recepcion');
$datos['Codigo']=$cod;
$oItem = new complex("Acta_Recepcion","Id_Acta_Recepcion");
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
if ($datos['Tipo'] == 'Nacional') {
    $oItem->Id_Orden_Compra_Nacional = $datos['Id_Orden_Compra'];
} else {
    $oItem->Id_Orden_Compra_Internacional = $datos['Id_Orden_Compra'];
}

$oItem->save();
$id_Acta_Recepcion = $oItem->getId();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('actarecepcion',$id_Acta_Recepcion,'/IMAGENES/QR/');
$oItem = new complex("Acta_Recepcion","Id_Acta_Recepcion",$id_Acta_Recepcion);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */



// productos
$oCon= new consulta();


foreach ($datosProductos as $prod) {
    unset($prod["producto"][count($prod["producto"])-1]);
        foreach($prod["producto"] as $item){$i++;

            if ($item['Lote'] != '' && $prod['Eliminado']=='No') {
                
                $oItem = new complex('Producto_Acta_Recepcion','Id_Producto_Acta_Recepcion');
                //mandar productos a Producto_Acta_Recepcion
                foreach($item as $index=>$value) {
                    $oItem->$index=$value;
                }
                $subtotal = ((INT) $item['Cantidad']) * ((INT) $item['Precio']);
                $oItem->Id_Producto_Orden_Compra = $prod["Id_Producto_Orden_Compra"];
                $oItem->Codigo_Compra = $codigoCompra;
                $oItem->Tipo_Compra = $tipoCompra;
                $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
            // $precio = number_format($prod['Precio'],2,".","");            
                $subtotal = number_format((INT)$subtotal,2,".","");
                $oItem->Subtotal = $subtotal;
                $oItem->save();
                unset($oItem);
            }

        
        
            // ACA SE AGREGA EL PRODUCTO A LA LISTA DE GANANCIA
            $query = 'SELECT LG.Porcentaje, LG.Id_Lista_Ganancia FROM Lista_Ganancia LG' ;
            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $porcentaje = $oCon->getData();
            unset($oCon);
        //datos
            $cum_producto=GetCodigoCum($item['Id_Producto']);
            foreach ($porcentaje as  $value) {
                $query='SELECT * FROM Producto_Lista_Ganancia WHERE Cum="'.$cum_producto.'" AND Id_lista_Ganancia='.$value['Id_Lista_Ganancia'];
                $oCon= new consulta();
                $oCon->setQuery($query);
                $cum = $oCon->getData();
                unset($oCon);
                if($cum){
                    $precio=number_format($item['Precio']/((100-$value['Porcentaje'])/100),2,'.','');
                    if($cum['Precio']<$precio){
                        $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia', $cum['Id_Producto_Lista_Ganancia']);
                       
                        $oItem->Precio = $precio;
                        $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                       $oItem->save();
                        unset($oItem);
                    }
                  
                }else{
                    $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia');
                    $oItem->Cum = $cum_producto;
                    $precio=number_format($item['Precio']/((100-$value['Porcentaje'])/100),2,'.','');
                    $oItem->Precio =$precio ;
                    $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                    $oItem->save();
                    unset($oItem);
                }
            }
        }
    
}

// Agregando facturas
$i = -1;
if ($facturas[count($facturas)-1]["Factura"] == "") {
    unset($facturas[count($facturas)-1]);
}
foreach ($facturas as $fact) {$i++;
    $oItem = new complex('Factura_Acta_Recepcion','Id_Factura_Acta_Recepcion');
    $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
    $oItem->Factura = $fact["Factura"];
    $oItem->Fecha_Factura = $fact["Fecha_Factura"];

    if (!empty($_FILES["archivos$i"]['name'])){
        $posicion1 = strrpos($_FILES["archivos$i"]['name'],'.')+1;
        $extension1 =  substr($_FILES["archivos$i"]['name'],$posicion1);
        $extension1 =  strtolower($extension1);
        $_filename1 = uniqid() . "." . $extension1;
        $_file1 = $MY_FILE . "ARCHIVOS/FACTURAS_COMPRA/" . $_filename1;
        
        $subido1 = move_uploaded_file($_FILES["archivos$i"]['tmp_name'], $_file1);
            if ($subido1){
                @chmod ( $_file1, 0777 );
                $oItem->Archivo_Factura = $_filename1;
            } 
    }
    $oItem->Id_Orden_Compra = $datos['Id_Orden_Compra'];
    $oItem->Tipo_Compra = $datos['Tipo'];
    $oItem->save();
    $id_factura=$oItem->getId();
    unset($oItem);

    if (count($fact['Retenciones']) > 0) {
                
        foreach ($fact['Retenciones'] as $rt) {

            // $oItem = new complex("Factura_Acta_Recepcion_Retencion","Id_Factura_Acta_Recepcion_Retencion");
            // $oItem->Id_Factura = $id_factura;
            // $oItem->Id_Retencion =$rt['Id_Retencion'];
            // $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
            // $oItem->Valor_Retencion = number_format(floatval($rt['Valor']),2,".","");
            // $oItem->save();
            // unset($oItem);

            if ($rt['Valor'] > 0) {
                $oItem = new complex("Factura_Acta_Recepcion_Retencion","Id_Factura_Acta_Recepcion_Retencion");
                $oItem->Id_Factura = $id_factura;
                $oItem->Id_Retencion = $rt['Id_Retencion'];
                $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
                $oItem->Valor_Retencion = $rt['Valor'] != 0 ? round($rt['Valor'], 0) : '0';
                $oItem->save();
                unset($oItem);
            } 
        }
    }
}

// Actualizando datos del producto
$h=-1;
foreach ($datosProductos as $value) { $h++;
    $oItem = new complex('Producto','Id_Producto', $value["Id_Producto"]);

    if ($oItem->Id_Subcategoria != $value["Id_Subcategoria"]) {
        $oItem->Id_Subcategoria = $value["Id_Subcategoria"];
    }
    if ($oItem->Peso_Presentacion_Regular != $value["Peso"]) {
        $oItem->Peso_Presentacion_Regular = $value["Peso"];
    }
    if (isset($_FILES["fotos$h"]['name'])){
        $posicion2 = strrpos($_FILES["fotos$h"]['name'],'.')+1;
        $extension2 =  substr($_FILES["fotos$h"]['name'],$posicion2);
        $extension2 =  strtolower($extension2);
        $_filename2 = uniqid() . "." . $extension2;
        $_file2 = $MY_FILE . "IMAGENES/PRODUCTOS/" . $_filename2;
        
        $subido2 = move_uploaded_file($_FILES["fotos$h"]['tmp_name'], $_file2);
            if ($subido2){
                @chmod ( $_file2, 0777 );
                $oItem->Imagen = $_filename2;
            } 
    }
    $oItem->save();
    unset($oItem);
}


if($contador==0){
    $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion con los productos No Conformes";
    $resultado['tipo'] = "success";
}

//Consultar el codigo del acta y el id de la orden de compra
$query_codido_acta = 'SELECT 
                        Codigo,
                        Id_Orden_Compra_Nacional
                    FROM
                        Acta_Recepcion
                    WHERE
                        Id_Acta_Recepcion = '.$id_Acta_Recepcion;

$oCon= new consulta();
$oCon->setQuery($query_codido_acta);
$acta_data = $oCon->getData();
unset($oCon);

//Guardando paso en el seguimiento del acta en cuestion
$oItem = new complex('Actividad_Orden_Compra',"Id_Acta_Recepcion_Compra");
$oItem->Id_Orden_Compra_Nacional=$acta_data['Id_Orden_Compra_Nacional'];
$oItem->Id_Acta_Recepcion_Compra=$id_Acta_Recepcion;
$oItem->Identificacion_Funcionario=$datos['Identificacion_Funcionario'];
$oItem->Detalles="Se recibio el acta con codigo ".$acta_data['Codigo']." de los productos faltantes de la orden de compra";
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Estado ='Recepcion';
$oItem->save();
unset($oItem);

  


//GUARDAR MOVMIMIENTO CONTABLE ACTA*/
$datos_movimiento_contable['Id_Registro'] = $id_Acta_Recepcion;
$datos_movimiento_contable['Numero_Comprobante'] = $cod;
$datos_movimiento_contable['Nit'] = $datos['Id_Proveedor'];
$datos_movimiento_contable['Productos'] = $datosProductos;
$datos_movimiento_contable['Facturas'] = $facturas;

$contabilizar->CrearMovimientoContable('Acta Recepcion', $datos_movimiento_contable);

if(count($productos_eliminados)>0){
    $productos_eliminados=implode(',',$productos_eliminados);
    EliminarNoConformes($no_conforme_devolucion,$productos_eliminados);
}


echo json_encode($resultado);

//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");

function GetCodigoCum($id_producto){
   

    $query = '
        SELECT
            Codigo_Cum
        FROM Producto
        WHERE
            Id_Producto = '.$id_producto;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();    
    unset($oCon);


    return $cum['Codigo_Cum'];
}

function EliminarNoConformes($id,$productos){
    $query="DELETE FROM Producto_No_Conforme WHERE Id_No_Conforme=$id AND Id_Producto NOT IN ($productos)";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->createData();     
    unset($oCon);

}
?>