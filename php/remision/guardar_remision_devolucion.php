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
 
var_dump($mod);
exit;
$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

 $id_rem_1='';
 $id_rem_2='';
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
$datos['Estado']="Recibida";
$datos['Estado_Alistamiento']='2';


$cod = $configuracion->getConsecutivo('Remision','Remision');

if (!validarCodigo($cod)) { // Si no existe ninguna remision con este consecutivo generado podrá ser guardada.
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
   $id_rem_1=$id_remision;

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
                    $oItem->Precio = $producto["Precio_Venta"]!='' ? number_format($producto["Precio_Venta"],2,".","") : 0;
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
                   $seleccionada = number_format($inv["Cantidad_Seleccionada"],0,"","");
                   $actual = number_format($lote["Cantidad"],0,"","");
                   if($actual<0){
                    $actual=0;
                }
                   $fin = $seleccionada - $actual;
                   if($fin<0){
                       $fin=0;
                   } 
                   $oItem = new complex('Inventario',"Id_Inventario",$lote['Id_Inventario']);
                   $oItem->Cantidad_Seleccionada=number_format($fin,0,"","");                
                    $oItem->save();
                   unset($oItem);

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
       $cod = '';
       $cod = $configuracion->getConsecutivo('Remision','Remision');
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

       $id_rem_2= $id_remision;
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
} else {
    $resultado['mensaje'] = "Ha ocurrido un error al generar el consecutivo de la remisión. Por favor vuelva a intentar guardar. Si el problema persiste comunicarse con soporte técnico.";
    $resultado['tipo'] = "error";
}
CrearActa($id_rem_1);
if($id_rem_2!=''){
    CrearActa($id_rem_2);
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

 function validarCodigo($cod){
    $query = "SELECT Id_Remision FROM Remision WHERE Codigo = '$cod'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado || false;
 }

 function CrearActa($id_remision){
     global $datos;
    $configuracion = new Configuracion();
    $cod = $configuracion->getConsecutivo('Acta_Recepcion_Remision','Acta_Recepcion_Remision'); 
    $acta['Codigo']=$cod;
    $acta['Id_Bodega']=$datos['Id_Destino'];
    $acta['Identificacion_Funcionario']=$datos['Identificacion_Funcionario'];
    $acta['	Observaciones']="Traslado de la bodega de Devoluciones a la Bodega ".$datos['Nombre_Destino'];
    $acta['Id_Remision']=$id_remision;
    $acta['Tipo']='Bodega';
    $acta['Fecha']=date("Y-m-d H:i:s");

    $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision");
    foreach($acta as $index=>$value) {
        $oItem->$index=$value;
        
    }
    $oItem->save();
    $id_acta = $oItem->getId();
    //var_dump($id_Acta_Recepcion_remision);
    unset($oItem);

    CrearQR($id_acta);
    $modelo['Id_Remision']=$id_remision;
    $modelo['Id_Acta_Recepcion_Remision']=$id_acta;
    $modelo['Id_Bodega']=$acta['Id_Bodega'];
    $modelo['Identificacion_Funcionario']=$acta['Identificacion_Funcionario'];

    RegistrarProducto($modelo);

 }

 function CrearQR($id_acta){
    $qr = generarqr('actarecepcionremision',$id_acta,'/IMAGENES/QR/');
    $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision",$id_acta);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);
 }

 function RegistrarProducto($modelo){
    $query = "SELECT PR.Id_Producto, PR.Cantidad,PR.Lote, PR.Precio,PR.Fecha_Vencimiento, PR.Id_Producto_Remision, PR.Id_Remision,$modelo[Id_Acta_Recepcion_Remision] as Id_Acta_Recepcion_Remision, P.Codigo_Cum   FROM Producto_Remision PR INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto WHERE Id_Remision=$modelo[Id_Remision] " ;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    foreach ($productos as $prod) {     

                //se registarn los datos en el Producto acta recepcion remision 
                $oItem = new complex('Producto_Acta_Recepcion_Remision','Id_Producto_Acta_Recepcion_Remision');
                foreach ($prod as $key => $value) {
                    $oItem->$key=$value;
                }
                $oItem->save();
                unset($oItem);

                // validar si el lote ya esta en imventario 
                $query = 'SELECT I.*
                FROM Inventario I
                WHERE I.Id_Bodega='.$modelo['Id_Bodega'].' AND I.Id_Producto='.$prod['Id_Producto'].' AND I.Lote="'.$prod['Lote'].'"' ;

                $oCon= new consulta();
                $oCon->setQuery($query);
                $inventario = $oCon->getData();
                unset($oCon);

                if($inventario){
                    $actual=number_format($prod["Cantidad"],0,"","");
                    $suma=number_format($inventario["Cantidad"],0,"","");
                    $total=$suma+$actual;
                    $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
                        $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
                        $oItem->Id_Producto= $prod['Id_Producto'];
                        $oItem->Costo = $prod['Precio'];
                        $oItem->Codigo_CUM = $prod['Codigo_Cum'];
                        $oItem->Cantidad = number_format($total,0,"","");
                        $oItem->Lote = $prod['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $prod['Fecha_Vencimiento'];            
                        $oItem->save();
                        unset($oItem);
                }else{
                    $oItem = new complex('Inventario','Id_Inventario');
                    $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
                    $oItem->Id_Bodega= $modelo['Id_Bodega'];
                    $oItem->Id_Producto= $prod['Id_Producto'];
                    $oItem->Costo = $prod['Precio'];
                    $oItem->Cantidad = number_format($prod['Cantidad'],0,"","");
                    $oItem->Codigo_CUM = $prod['Codigo_Cum'];
                    $oItem->Lote = strtoupper($prod['Lote']);
                    $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                    $oItem->Fecha_Vencimiento = $prod['Fecha_Vencimiento'];            
                    $oItem->save();
                    unset($oItem);

                }
    }

    $query = 'SELECT Codigo  FROM Remision WHERE Id_Remision ='.$modelo['Id_Remision'] ;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $rem = $oCon->getData();
    unset($oCon);

    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision = $modelo["Id_Remision"];
    $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
    $oItem->Detalles = "Se hace el acta de recepcion de la  ".$rem["Codigo"];
    $oItem->Estado = "Recibida";
    $oItem->save();  
    unset($oItem);
 }

?>
