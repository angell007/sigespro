<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productoCompra = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$codigoCompra = ( isset( $_REQUEST['codigoCompra'] ) ? $_REQUEST['codigoCompra'] : '' );
$tipoCompra = ( isset( $_REQUEST['tipoCompra'] ) ? $_REQUEST['tipoCompra'] : '' );
$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );
$archivos = ( isset( $_REQUEST['archivos'] ) ? $_REQUEST['archivos'] : '' );
$id_Acta_Recepcion = ( isset( $_REQUEST['id_acta_recepcion'] ) ? $_REQUEST['id_acta_recepcion'] : '' );

$datosProductos = (array) json_decode($productoCompra , true);
$datos = (array) json_decode($datos);
$facturas = (array) json_decode($facturas, true);

// var_dump($datos);
// var_dump($datosProductos);
// var_dump($facturas);
// var_dump($_FILES);
// exit;

/* var_dump($detalleCompra);
exit; */

// realizar guardado para las caracteristicas de los productos
//1. revisar cuales fueron marcados y no marcados en el array que traigo.
$i=-1;
$contador=0;

/* $oCon = new complex('Producto_Acta_Recepcion', 'Id_Acta_Recepcion', $id_Acta_Recepcion);
$oCon->delete();
unset($oCon);
$oCon = new complex('No_Conforme', 'Id_Acta_Recepcion_Compra', $id_Acta_Recepcion);
$oCon->delete();
unset($oCon);
$oCon = new complex('Producto_No_Conforme', 'Id_Acta_Recepcion', $id_Acta_Recepcion);
$oCon->delete();
unset($oCon); */

foreach ($datosProductos as $prod) {
    unset($prod["producto"][count($prod["producto"])-1]);

    foreach($prod["producto"] as $item){$i++;

            if ($item['Id_Producto_Acta_Recepcion'] == 0) {
                $oItem = new complex('Producto_Acta_Recepcion','Id_Producto_Acta_Recepcion');
            //mandar productos a Producto_Acta_Recepcion
                foreach($item as $index=>$value) {
                    $oItem->$index=$value;
                }
                $subtotal = number_format(str_replace(",",".",$prod['Subtotal']),2,".","");
                $oItem->Id_Producto=$prod['Id_Producto'];
                $oItem->Subtotal = $subtotal;
                $oItem->Id_Producto_Orden_Compra = $prod["Id_Producto_Orden_Compra"];
                $oItem->Tipo_Compra = $datos['Tipo'];
                $oItem2 = new complex('Orden_Compra_'.$datos['Tipo'], 'Id_Orden_Compra_'.$datos['Tipo'], $datos['Id_Orden_Compra']);
                $oItem->Codigo_Compra = $oItem2->Codigo;
                unset($oItem2);
                $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
                $oItem->save();
                unset($oItem);
            
                if ($item["No_Conforme"] != "") {

                    $configuracion = new Configuracion();
                    $cod = $configuracion->Consecutivo('No_Conforme'); 
                    $oItem2 = new complex('No_Conforme','Id_No_Conforme');
                    $oItem2->Codigo = $cod;
                    $oItem2->Persona_Reporta = $datos['Identificacion_Funcionario'];
                    $oItem2->Tipo = "Compra";
                    $oItem2->Id_Acta_Recepcion_Compra = $id_Acta_Recepcion;
                    $oItem2->save();
                    $id_no_conforme = $oItem2->getId();
                    unset($oItem2);

                    /*AQUI GENERA QR */
                    $qr = generarqr('noconforme',$id_no_conforme,'/IMAGENES/QR/');
                    $oItem = new complex("No_Conforme","Id_No_Conforme",$id_no_conforme);
                    $oItem->Codigo_Qr=$qr;
                    $oItem->save();
                    unset($oItem);
                    /*HASTA AQUI GENERA QR */

                    $oItem2 = new complex('Producto_No_Conforme','Id_Producto_No_Conforme');
                    $oItem2->Id_Producto = $item["Id_Producto"];
                    $oItem2->Id_No_Conforme = $id_no_conforme;
                    $oItem2->Id_Compra = $datos["Id_Orden_Compra"];
                    $oItem2->Tipo_Compra = $datos["Tipo"];
                    $oItem2->Id_Acta_Recepcion = $id_Acta_Recepcion;
                    $oItem2->Cantidad = $item["Cantidad_No_Conforme"];
                    $oItem = new complex("Causal_No_Conforme","Id_Causal_No_Conforme",$item['No_Conforme']);
                    $oItem2->Observaciones = $oItem->Nombre;
                    unset($oItem);
                    $oItem2->save();
                    unset($oItem2);
                }
            
                // ACA SE AGREGA EL PRODUCTO A LA LISTA DE GANANCIA
                $query = 'SELECT LG.Porcentaje, LG.Id_Lista_Ganancia FROM Lista_Ganancia LG' ;
                $oCon= new consulta();
                $oCon->setQuery($query);
                $oCon->setTipo('Multiple');
                $porcentaje = $oCon->getData();
                unset($oCon);
            //datos
            
                foreach ($porcentaje as  $value) {
                    $query='SELECT * FROM Producto_Lista_Ganancia WHERE Cum="'.$item['Codigo_CUM'].'" AND Id_lista_Ganancia='.$value['Id_Lista_Ganancia'];
                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $cum = $oCon->getData();
                    unset($oCon);
                    if($cum['Precio']){
                        if($cum['Precio']<$item['Precio']){
                            $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia', $cum['Id_Producto_Lista_Ganancia']);
                            $precio=number_format($item['Precio']*(($value['Porcentaje']/100)+1),2,'.','');
                            $oItem->Precio = $precio;
                            $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                            $oItem->save();
                            unset($oItem);
                        }
                    
                    }else{
                        $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia');
                        $oItem->Cum = $item['Codigo_CUM'];
                        $precio=number_format($item['Precio']*(($value['Porcentaje']/100)+1),2,'.','');
                        $oItem->Precio =$precio ;
                        $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                        $oItem->save();
                        unset($oItem);
                    }
            
                }
                
            } else {
                $oItem = new complex('Producto_Acta_Recepcion','Id_Producto_Acta_Recepcion', $item['Id_Producto_Acta_Recepcion']);
            //mandar productos a Producto_Acta_Recepcion
                foreach($item as $index=>$value) {
                    $oItem->$index=$value;
                }
                $oItem->Id_Producto_Orden_Compra = $prod["Id_Producto_Orden_Compra"];
                $subtotal = number_format(str_replace(",",".",$prod['Subtotal']),2,".","");
                $oItem->Subtotal = $subtotal;
                $oItem->save();
                unset($oItem);
            
                if ($item["No_Conforme"] != "") {

                    $query = "SELECT Id_Producto_No_Conforme FROM Producto_No_Conforme WHERE Id_Producto=$item[Id_Producto] AND Id_Acta_Recepcion=$id_Acta_Recepcion";

                    $con = new consulta();
                    $con->setQuery($query);
                    $result = $con->getData();
                    unset($con);

                    $oItem2 = new complex('Producto_No_Conforme','Id_Producto_No_Conforme', $result['Id_Producto_No_Conforme']);
                    $oItem2->Cantidad = $item["Cantidad_No_Conforme"];
                    $oItem2->save();
                    unset($oItem2);
                }
            
                // ACA SE AGREGA EL PRODUCTO A LA LISTA DE GANANCIA
                $query = 'SELECT LG.Porcentaje, LG.Id_Lista_Ganancia FROM Lista_Ganancia LG' ;
                $oCon= new consulta();
                $oCon->setQuery($query);
                $oCon->setTipo('Multiple');
                $porcentaje = $oCon->getData();
                unset($oCon);
            //datos
            
                foreach ($porcentaje as  $value) {
                    $query='SELECT * FROM Producto_Lista_Ganancia WHERE Cum="'.$item['Codigo_CUM'].'" AND Id_lista_Ganancia='.$value['Id_Lista_Ganancia'];
                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $cum = $oCon->getData();
                    unset($oCon);
                    if($cum['Precio']){
                        if($cum['Precio']<$item['Precio']){
                            $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia', $cum['Id_Producto_Lista_Ganancia']);
                            $precio=number_format($item['Precio']*(($value['Porcentaje']/100)+1),2,'.','');
                            $oItem->Precio = $precio;
                            $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                            $oItem->save();
                            unset($oItem);
                        }
                    
                    }else{
                        $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia');
                        $oItem->Cum = $item['Codigo_CUM'];
                        $precio=number_format($item['Precio']*(($value['Porcentaje']/100)+1),2,'.','');
                        $oItem->Precio =$precio ;
                        $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                        $oItem->save();
                        unset($oItem);
                    }
            
                }
            }
        }
    
}



// Agregando facturas
$i = -1;
unset($facturas[count($facturas)-1]);
foreach ($facturas as $fact) {$i++;

    if ($fact['Id_Factura_Acta_Recepcion'] != 0) {
        $oItem = new complex('Factura_Acta_Recepcion','Id_Factura_Acta_Recepcion', $fact['Id_Factura_Acta_Recepcion']);
        $oItem->Factura = $fact["Factura"];
        $oItem->Fecha_Factura = $fact["Fecha_Factura"];

        if ($fact['Archivo_Factura'] != "") {
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
            unset($oItem);
        }
        
    } else {
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
        unset($oItem);
    }
    
    
}



// Actualizando datos del producto

foreach ($datosProductos as $value) {
    $oItem = new complex('Producto','Id_Producto', $value["Id_Producto"]);

    if ($oItem->Id_Categoria != $value["Id_Categoria"]) {
        $oItem->Id_Categoria = $value["Id_Categoria"];
    }
    if ($oItem->Peso_Presentacion_Regular != $value["Peso"]) {
        $oItem->Peso_Presentacion_Regular = $value["Peso"];
    }
    if (isset($_FILES["fotos$i"]['name'])){
        $posicion1 = strrpos($_FILES["fotos$i"]['name'],'.')+1;
        $extension1 =  substr($_FILES["fotos$i"]['name'],$posicion1);
        $extension1 =  strtolower($extension1);
        $_filename1 = uniqid() . "." . $extension1;
        $_file1 = $MY_FILE . "IMAGENES/PRODUCTOS/" . $_filename1;
        
        $subido1 = move_uploaded_file($_FILES["fotos$i"]['tmp_name'], $_file1);
            if ($subido1){
                list($width, $height, $type, $attr) = getimagesize($_file1);
                @chmod ( $_file1, 0777 );
                $oItem->Archivo_Factura = $_filename1;
            } 
    }
    $oItem->save();
    unset($oItem);

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
    $oItem->Detalles="Se edito el acta de recepcion con codigo ".$acta_data['Codigo'];
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->Estado ='Edicion';
    $oItem->save();
    unset($oItem);

if($contador==0){
    $resultado['mensaje'] = "Se ha editado correctamente el acta de recepcion";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion con los productos No Conformes";
    $resultado['tipo'] = "success";
}

echo json_encode($resultado);

//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");
?>