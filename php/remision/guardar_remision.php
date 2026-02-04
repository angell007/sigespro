<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
 
$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

 
/*$oItem = new complex('Configuracion','Id_Configuracion',1);
$nc = $oItem->getData();
$oItem->Remision=$oItem->Remision+1;$oItem->save();
$num_remison=$nc["Remision"];
unset($oItem);*/

//var_dump($datos);

if($datos['Tipo']=="Cliente"){
      
    $cliente=explode("-",$datos['Id_Cliente']);
    $datos['Id_Destino']=$cliente[1];
    $datos['Tipo_Destino']="Cliente";
   
    
    $tipo=explode("-",$datos['Lista_Ganancia']);
   
    if($tipo[0]=="L"){
        $datos['Tipo_Lista']="Lista_Ganancia";
        $datos['Id_Lista']=$tipo[1];
    }elseif($tipo[0]=="C"){
        $datos['Tipo_Lista']="Contrato";
        $datos['Id_Lista']=$tipo[1];
    }

    
}elseif($datos['Tipo']=="Interna"){
    $destino=explode("-",$datos['Id_Destino']);
    
    $datos['Id_Destino']=$destino[1];
	if($destino[0]=="P"){
	    $datos['Tipo_Destino']="Punto_Dispensacion";
	}elseif($destino[0]=="B"){
	    $datos['Tipo_Destino']="Bodega";
	}	
}

$origen=explode("-",$datos['Id_Origen']);
$datos['Id_Origen']=$origen[1];
if($origen[0]=="B"){
    $datos['Tipo_Origen']="Bodega";
}elseif($origen[0]=="P"){
    $datos['Tipo_Origen']="Punto_Dispensacion";
}
if($origen[0]=="B" && $datos['Id_Origen']==2){
    $datos['Tipo_Bodega']="MATERIALES";
}

$cod='';
$datos['Estado']="Pendiente";
$datos['Estado_Alistamiento']=0;
if($datos['Modelo']=="PuntoBodega"){
    $datos['Estado']="Alistada";
    $datos['Estado_Alistamiento']=2;
}else if ($datos['Modelo']=="PuntoPunto"){
    $datos['Estado']="Alistada";
    $datos['Estado_Alistamiento']=2;
}

$cod = $configuracion->Consecutivo('Remision');
$datos["Codigo"]=$cod;

$datos["Costo_Remision"]=number_format($datos["Costo_Remision"],2,".","");
$datos["Subtotal_Remision"]=number_format($datos["Subtotal_Remision"],2,".","");
$datos["Descuento_Remision"]=number_format($datos["Descuento_Remision"],2,".","");
$datos["Impuesto_Remision"]=number_format($datos["Impuesto_Remision"],2,".","");

$oItem = new complex($mod,"Id_".$mod);
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->save();
$id_remision = $oItem->getId();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
$oItem = new complex("Remision","Id_Remision",$id_remision);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */
unset($productos[count($productos)-1]);
$refrigerados=[];
$nodisponibles=[];
$j=-1;
$h=-1;
$items = 0;
foreach($productos as $producto){$i++;

    if ($datos["Tipo"] == "Interna") {
        
        if (isset($producto['Pendientes']) && $producto['Pendientes'] != 0) { // Si se generó un pendiente en la remision

            $query = "SELECT Id_Producto_Pendientes_Remision FROM Producto_Pendientes_Remision WHERE Id_Producto IN ($producto[Id_Producto]) AND Id_Punto_Dispensacion=$datos[Id_Destino]";
            
            $con = new consulta();
            $con->setQuery($query);
            $res = $con->getData();
            unset($con);

            if ($res) {
                $oItem = new complex("Producto_Pendientes_Remision", "Id_Producto_Pendientes_Remision", $res['Id_Producto_Pendientes_Remision']);
                $cantidadPendiente = $oItem->Cantidad;
                $cantidad_diferencial = $producto["Cantidad"] - $cantidadPendiente;
                if ($cantidad_diferencial <= 0) {
                   $oItem->delete();
                } else {
                    $oItem->Cantidad = number_format($cantidad_diferencial,0,"","");
                    $oItem->save();
                }
                unset($oItem);
            } else {
                $oItem = new complex("Producto_Pendientes_Remision", "Id_Producto_Pendientes_Remision");
                $oItem->Id_Remision = $id_remision;
                $oItem->Id_Producto = AsignarIdProductoPendiente($producto['Id_Producto']);
                $oItem->Cantidad = $producto['Pendientes'];
                $oItem->Id_Punto_Dispensacion = $datos['Id_Destino'];
                $oItem->save();
                unset($oItem);
            }

        } else {
            $query = "SELECT Id_Producto_Pendientes_Remision FROM Producto_Pendientes_Remision WHERE Id_Producto IN ($producto[Id_Producto]) AND Id_Punto_Dispensacion=$datos[Id_Destino]";
           
            $con = new consulta();
            $con->setQuery($query);
            $con->setTipo("Multiple");
            $res = $con->getData();
            unset($con);

            if ($res) {
                $oItem = new complex("Producto_Pendientes_Remision", "Id_Producto_Pendientes_Remision", $res['Id_Producto_Pendientes_Remision']);
                $cantidadPendiente = $oItem->Cantidad;
                $cantidad_diferencial = $producto["Cantidad"] - $cantidadPendiente;
                if ($cantidad_diferencial <= 0) {
                    $oItem->delete();
                } else {
                    $oItem->Cantidad = number_format($cantidad_diferencial,0,"","");
                    $oItem->save();
                }
                unset($oItem);
            }
        }
    }

    if($producto['Lotes_Seleccionados']!=null){
        foreach($producto['Lotes_Seleccionados'] as $lote){
        if($lote['Id_Inventario']!=0){
            $oItem = new complex('Inventario',"Id_Inventario",$lote['Id_Inventario']);
            $inv = $oItem->getData();
            
            if(($inv["Cantidad"]-$inv["Cantidad_Apartada"])>=$lote["Cantidad"] && $lote["Cantidad"]>0){
                if($datos['Modelo']=="PuntoBodega" || $datos['Modelo']=="PuntoPunto"){
                    $seleccionada = number_format($inv["Cantidad_Seleccionada"],0,"","");
                    $cantidadinventario=number_format($inv["Cantidad"],0,"","");
                    $actual = number_format($lote["Cantidad"],0,"","");
                    $fin = $seleccionada - $actual;
                   
                    $cantidadfinal = $cantidadinventario - $actual;
                    if($cantidadfinal<0){
                        $cantidadfinal=0;
                    }
                    $oItem->Cantidad=number_format($cantidadfinal,0,"","");
                }else{
                    $seleccionada = number_format($inv["Cantidad_Seleccionada"],0,"","");
                    $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
                    $actual = number_format($lote["Cantidad"],0,"","");
                    $fin = $seleccionada - $actual;
                    $cantidad = $apartada + $actual;
                    $oItem->Cantidad_Apartada=number_format($cantidad,0,"","");
                }  
                if($fin<0){
                    $fin=0;
                }              
                $oItem->Cantidad_Seleccionada=number_format($fin,0,"","");                
	            $oItem->save();
                unset($oItem);
                
                $subtotal = $producto["Precio_Venta"] * $lote["Cantidad"];
                $total_descuento = $subtotal*($producto["Descuento"]/100);
                $total_impuesto = (($subtotal-$total_descuento)*($producto["Impuesto"]/100));
                
            
                if($lote["Id_Categoria"]==3){$j++;
	                $refrigerados[$j]["Id_Remision"]=$id_remision;
	                $refrigerados[$j]["Id_Inventario"]=$lote["Id_Inventario"];
	                $refrigerados[$j]["Lote"]=$lote["Lote"];
	                $refrigerados[$j]["Fecha_Vencimiento"]=$lote["Fecha_Vencimiento"];
	                $refrigerados[$j]["Cantidad"]=$lote["Cantidad"];
	                $refrigerados[$j]["Id_Producto"]=AsignarIdProducto($producto['Id_Producto'], $lote['Id_Producto']);
	                $refrigerados[$j]["Nombre_Producto"]=$producto["Nombre_Producto"];
	                $refrigerados[$j]["Cantidad_Total"]=$producto["Cantidad"];
	                $refrigerados[$j]["Precio"]=number_format($producto["Precio_Venta"],2,".","");
	                $refrigerados[$j]["Descuento"]=$producto["Descuento"];
	                $refrigerados[$j]["Impuesto"]=$producto["Impuesto"];
	                $refrigerados[$j]["Total_Descuento"]=number_format($total_descuento,2,".","");
	                $refrigerados[$j]["Total_Impuesto"]=number_format($total_impuesto,2,".","");
	                $refrigerados[$j]["Subtotal"]=number_format($subtotal,2,".","");        
	            }else{
	                $oItem = new complex('Producto_'.$mod,"Id_Producto_".$mod);
	                $oItem->Id_Remision=$id_remision;
	                $oItem->Id_Inventario = $lote["Id_Inventario"];
	                $oItem->Lote = $lote["Lote"];
	                $oItem->Fecha_Vencimiento = $lote["Fecha_Vencimiento"];
	                $oItem->Cantidad = $lote["Cantidad"];
	                $oItem->Id_Producto = AsignarIdProducto($producto['Id_Producto'], $lote['Id_Producto']);
	                $oItem->Nombre_Producto = $producto["Nombre_Producto"];
	                   
	                $oItem->Cantidad_Total = $producto["Cantidad"];
	                $oItem->Precio =number_format($producto["Precio_Venta"],2,".","");
	                $oItem->Descuento = $producto["Descuento"]=='' ? 0 : $producto["Descuento"] ;
	                $oItem->Impuesto = $producto["Impuesto"];
	                $oItem->Total_Descuento=number_format($total_descuento,2,".","");
	                $oItem->Total_Impuesto=number_format($total_impuesto,2,".","");
	                $oItem->Subtotal = number_format($subtotal,2,".","");
	                $oItem->save();
	                unset($oItem);
	                $items++;
	            }
               
	             
            }else{ $h++;
            	$nodisponibles[$h]["Nombre"]=$producto["Nombre_Producto"];
                $nodisponibles[$h]["Lote"]=$lote["Lote"];
                $nodisponibles[$h]["Fecha_Vencimiento"]=$lote["Fecha_Vencimiento"];
                $nodisponibles[$h]["Cantidad"]=$lote["Cantidad"];
                $nodisponibles[$h]["Cantidad_Disponible"]=($inv["Cantidad"]-$inv["Cantidad_Apartada"]);
            }  
        }    
        }
    }
}

$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$id_remision;
$oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
$oItem->Detalles="Se creo la remision con codigo ".$cod;
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->save();
unset($oItem);

if(count($refrigerados) != (count($productos)-count($nodisponibles)) && count($refrigerados)>0 ){
    $cod = $configuracion->Consecutivo('Remision');
    $datos['Codigo']=$cod;
    $datos['Tipo_Bodega']="REFRIGERADOS";
    $oItem = new complex($mod,"Id_".$mod);
    foreach($datos as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->save();
    $id_remision = $oItem->getId();
    unset($oItem);
     /* AQUI GENERA QR */
     $qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
     $oItem = new complex("Remision","Id_Remision",$id_remision);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);
  
     /* HASTA AQUI GENERA QR */
   
     foreach($refrigerados as $index) {
        $oItem = new complex("Producto_Remision","Id_Producto_Remision");
        $oItem->Id_Remision=$id_remision;
        $oItem->Id_Inventario =$index["Id_Inventario"];
        $oItem->Lote =$index["Lote"];
        $oItem->Fecha_Vencimiento = $index["Fecha_Vencimiento"];
        $oItem->Cantidad = $index["Cantidad"];
        $oItem->Id_Producto = $index["Id_Producto"];
        $oItem->Nombre_Producto =$index ["Nombre_Producto"];
           
        $oItem->Cantidad_Total = $index["Cantidad"];
        $oItem->Precio = number_format($index["Precio"],2,".","");
        $oItem->Descuento = $index["Descuento"];
        $oItem->Impuesto = $index["Impuesto"];
        $oItem->Subtotal = number_format($index["Subtotal"],2,".","");
        $oItem->Total_Descuento=number_format($index["Total_Descuento"],2,".","");
	    $oItem->Total_Impuesto=number_format($index["Total_Impuesto"],2,".","");
       $oItem->save();
        unset($oItem);
        $items++;
     }
    
     $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
     $oItem->Id_Remision=$id_remision;
     $oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
     $oItem->Detalles="Se creo la remision con codigo ".$cod;
     $oItem->Fecha=date("Y-m-d H:i:s");
     $oItem->save();
     unset($oItem);

}else if (count($refrigerados)==(count($productos)-count($nodisponibles))){
    $oItem = new complex('Remision',"Id_Remision",$id_remision);
    $oItem->Tipo_Bodega="REFRIGERADOS";
    $oItem->save();
    unset($oItem);

    foreach($refrigerados as $index) {
        $oItem = new complex("Producto_Remision","Id_Producto_Remision");
        $oItem->Id_Remision=$id_remision;
        $oItem->Id_Inventario =$index["Id_Inventario"];
        $oItem->Lote =$index["Lote"];
        $oItem->Fecha_Vencimiento = $index["Fecha_Vencimiento"];
        $oItem->Cantidad = $index["Cantidad"];
        $oItem->Id_Producto = $index["Id_Producto"];
        $oItem->Nombre_Producto =$index["Nombre_Producto"];
           
        $oItem->Cantidad_Total = $index["Cantidad"];
        $oItem->Precio = number_format($index["Precio"],2,".","");
        $oItem->Descuento = $index["Descuento"];
        $oItem->Impuesto = $index["Impuesto"];
        $oItem->Subtotal = $index["Subtotal"];
        $oItem->save();
        unset($oItem);
        $items++;
    } 
    /*if($items>0){
	$cod = $configuracion->Consecutivo('Remision');
	$oItem = new complex("Remision","Id_Remision",$id_remision);
	$oItem->Codigo=$cod;
	$oItem->save();
	unset($oItem);
    }*/
}
//borrar borrador de la remision
$query = 'DELETE 
FROM Borrador 
WHERE Codigo="'.$codigo.'"' ;
$oCon= new consulta();
$oCon->setQuery($query);
$dato = $oCon->deleteData();
unset($oCon);

if($id_remision != ""){
	if($items>0){
	    $resultado['mensaje'] = "Se ha guardado correctamente la Remision con codigo: <b>".$cod."</b><br>";
	    if(count($nodisponibles)>0){
	    	$resultado['mensaje'] .= "<span style='font-size:10px;'>Los siguientes productos no pudieron cargarse:<br>";
	    	foreach($nodisponibles as $nd){
	    	    $o++;
	    	    $resultado['mensaje'] .=$o.".- ".$nd["Nombre"]."(L:".$nd["Lote"]." V:".$nd["Fecha_Vencimiento"].") - Cant. Solicitada: ".$nd["Cantidad"]." - Cant. Disponible: ".$nd["Cantidad_Disponible"]."<br>";
	    	}
	    	$resultado['mensaje'] .="</span>";
	    }
	    $resultado['tipo'] = "success";
	}else{
	    $resultado['mensaje'] = "<span style='font-size:10px;'>No se ha creado ninguna remisión porque faltaron los siguientes productos<br>";
	    if(count($nodisponibles)>0){
	    $o=0;
	    	foreach($nodisponibles as $nd){
	    	 	$o++;
	    	    $resultado['mensaje'] .=$o.".- ".$nd["Nombre"]."(L:".$nd["Lote"]." V:".$nd["Fecha_Vencimiento"].") - Cant. Solicitada: ".$nd["Cantidad"]." - Cant. Disponible: ".$nd["Cantidad_Disponible"]."<br>";
	    	}
	    	$resultado['mensaje'] .="</span>";
	    }
	    $resultado['tipo'] = "error";
	}
}else{
    $resultado['mensaje'] = "Ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);
 function AsignarIdProducto($idproducto, $idproductolote){
     $siextiste=strpos($idproducto,",");
     if($siextiste){
         return $idproductolote;
     }else{
         return $idproducto;
     }
 }
 function AsignarIdProductoPendiente($idproducto){
    $prod=explode( ",", $idproducto);
    return $prod[0];
 }
?>
