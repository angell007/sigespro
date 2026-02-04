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
$no_conforme_devolucion = ( isset( $_REQUEST['id_no_conforme'] ) ? $_REQUEST['id_no_conforme'] : false );

$datosProductos = (array) json_decode($productoCompra , true);
$datos = (array) json_decode($datos);
$facturas = (array) json_decode($facturas, true);

// var_dump($datos);
 //var_dump($datosProductos);
// var_dump($facturas);
// var_dump($_FILES);
// exit;

$cod = $configuracion->Consecutivo('Acta_Recepcion');
$datos['Codigo']=$cod;
$datos['Tipo_Acta']="Punto_Dispensacion";
$datos['Estado']="Aprobada";
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

switch($tipoCompra){
    
    case "Nacional":{
        $query ="SELECT	Id_Orden_Compra_Nacional as id,Codigo,Identificacion_Funcionario,Id_Bodega,Id_Proveedor FROM Orden_Compra_Nacional WHERE Codigo = '".$codigoCompra."'";
        $oCon->setQuery($query);
        $detalleCompra = $oCon->getData();
        $oCon->setTipo('Multiple');
        unset($oCon);
        break;
    }
    case "Internacional":{

        $query ="SELECT Id_Orden_Compra_Internacional as id,Codigo,Identificacion_Funcionario,Id_Bodega,Id_Proveedor FROM Orden_Compra_Internacional WHERE Codigo = '".$codigoCompra."'";
        $oCon->setQuery($query);
        $detalleCompra = $oCon->getData();
        $oCon->setTipo('Multiple');
        unset($oCon);
        break;
    }
}

/* var_dump($detalleCompra);
exit; */

// realizar guardado para las caracteristicas de los productos
//1. revisar cuales fueron marcados y no marcados en el array que traigo.
$i=-1;
$contador=0;
$genero_no_conforme = false;
$id_no_conforme = '';

foreach ($datosProductos as $prod) {
    unset($prod["producto"][count($prod["producto"])-1]);

    if ($prod['No_Conforme'] == 2) {
        

        if (!$genero_no_conforme) {
            $genero_no_conforme = true;
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
        }
        
        $oItem2 = new complex('Producto_No_Conforme','Id_Producto_No_Conforme');
        $oItem2->Id_Producto = $prod["Id_Producto"];
        $oItem2->Id_No_Conforme = $id_no_conforme;
        $oItem2->Id_Compra = $datos["Id_Orden_Compra"];
        $oItem2->Tipo_Compra = $datos["Tipo"];
        $oItem2->Id_Acta_Recepcion = $id_Acta_Recepcion;
        $oItem2->Cantidad = $prod["CantidadProducto"];
        $oItem2->Id_Causal_No_Conforme = 2;
        $oItem2->Observaciones = "PRODUCTO NO LLEGO EN FISICO";
        $oItem2->save();
        unset($oItem2);
    } else {
        foreach($prod["producto"] as $item){$i++;

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
        
            if ($item["No_Conforme"] != "") {
                if(!$genero_no_conforme){ // Para que solo registre un solo registro por cada no conforme de productos.
                    $genero_no_conforme = true;
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
                }

                $oItem2 = new complex('Producto_No_Conforme','Id_Producto_No_Conforme');
                $oItem2->Id_Producto = $item["Id_Producto"];
                $oItem2->Id_No_Conforme = $id_no_conforme;
                $oItem2->Id_Compra = $datos["Id_Orden_Compra"];
                $oItem2->Tipo_Compra = $datos["Tipo"];
                $oItem2->Id_Acta_Recepcion = $id_Acta_Recepcion;
                $oItem2->Cantidad = $item["Cantidad_No_Conforme"];
                $oItem2->Id_Causal_No_Conforme = $item['No_Conforme'];
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
                if($cum){
                    $precio=number_format($item['Precio']/((100-$value['Porcentaje'])/100),2,'.','');
                    if($cum['Precio']<$precio){
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

// Ingresar a inventario

foreach ($datosProductos as $prod) {
    unset($prod["producto"][count($prod["producto"])-1]);

    if ($prod['No_Nonforme'] != 2) {
        foreach ($prod['producto'] as $item) {
            $query = "SELECT Id_Inventario FROM Inventario WHERE Id_Producto=$prod[Id_Producto] AND Lote='$item[Lote]' AND Fecha_Vencimiento='$item[Fecha_Vencimiento]' AND Id_Punto_Dispensacion=$datos[Id_Punto_Dispensacion]";

            $oCon= new consulta();
            $oCon->setQuery($query);
            $inventario = $oCon->getData();
            unset($oCon);

            if ($inventario) {
                $oItem = new complex('Inventario','Id_Inventario', $inventario['Id_Inventario']);
                $cantidad = number_format($item["Cantidad"],0,"","");
                $cantidad_final = $oItem->Cantidad + $cantidad;
                $oItem->Cantidad = $cantidad_final;
                $id_inventario = $oItem->Id_Inventario;
            } else {
                $oItem = new complex('Inventario','Id_Inventario');
                $oItem->Codigo = substr(hexdec(uniqid()),2,12);
                $oItem->Cantidad=$item["Cantidad"];
                $oItem->Id_Producto=$prod["Id_Producto"];
                $oItem->Codigo_CUM=$prod["Codigo_Cum"];
                $oItem->Lote=$item["Lote"];
                $oItem->Fecha_Vencimiento=$item["Fecha_Vencimiento"];
                $oItem->Id_Punto_Dispensacion = $datos["Id_Punto_Dispensacion"];
                $oItem->Costo = $item['Precio'];
                $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
            }
           // $oItem->save();
            $id_inventario = $oItem->getId();
            unset($oItem);
        }
    }
}

//cambiar el estado de la compra a RECIBIDA
switch($tipoCompra){
    
    case "Nacional":{
        $oItem = new complex('Orden_Compra_Nacional','Id_Orden_Compra_Nacional',$detalleCompra['id']);
        $oItem->getData();
        $oItem->Estado="Recibida";
        $oItem->save();
        unset($oItem);
        break;
    }
    case "Internacional":{
        $oItem = new complex('Orden_Compra_Internacional','Id_Orden_Compra_Internacional',$detalleCompra['id']);
        $oItem->getData();
        $oItem->Estado="Recibida";
        $oItem->save();
        unset($oItem);
        break;
    }
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
    $oItem->Detalles="Se recibio el acta con codigo ".$acta_data['Codigo'];
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->Estado ='Recepcion';
    $oItem->save();
    unset($oItem);

    if ($no_conforme_devolucion) {
        $oItem = new complex('No_Conforme', 'Id_No_Conforme', $no_conforme_devolucion);
        $oItem->Estado = 'Cerrado';
        $oItem->save();
        unset($oItem);
    }

echo json_encode($resultado);

//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");
?>