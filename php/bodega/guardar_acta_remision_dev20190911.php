<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productoRemision = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );


$datosProductos = (array) json_decode($productoRemision , true);
$datos = (array) json_decode($datos);

// var_dump($datosProductos);
// var_dump($datos);
// exit;

//datos
$res=ValidarRemision($datos['Id_Remision']);


if($res['Id_Acta_Recepcion_Remision']){
    $resultado['mensaje'] = "No se puede guardar el acta para esta remision ya exite un acta asociada con codigo: ".$res['Codigo'];
    $resultado['tipo'] = "warning";
    $resultado['titulo'] = "Upps Ha sucedido un error!";
}else{
   /*  $configuracion = new Configuracion();
    $cod = $configuracion->Consecutivo('Acta_Recepcion_Remision'); */ 
    //var_dump($cod); 
    $datos['Codigo']='54444';
    $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision");
    foreach($datos as $index=>$value) {
        $oItem->$index=$value;
        
    }
    //$oItem->save();
    $id_Acta_Recepcion_remision = $oItem->getId();
    //var_dump($id_Acta_Recepcion_remision);
    unset($oItem);
    
    /* AQUI GENERA QR */
    $qr = generarqr('actarecepcionremision',$id_Acta_Recepcion_remision,'/IMAGENES/QR/');
    $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision",$id_Acta_Recepcion_remision);
    $oItem->Codigo_Qr=$qr;
    //$oItem->save();
    unset($oItem);
    /* HASTA AQUI GENERA QR */
    
    
    //cambiar el estado de la Remision a RECIBIDA
    
    $oItem = new complex('Remision','Id_Remision',$datos["Id_Remision"]);
    $oItem->Estado="Recibida";
    //$oItem->save();
    $remision= $oItem->getData();
    unset($oItem);
    
    // realizar guardado para las caracteristicas de los productos
    //1. revisar cuales fueron marcados y no marcados en el array que traigo.
    $i=-1;
    $contador=0;
    foreach($datosProductos as $item){$i++;
    
                $query = 'SELECT I.*
                FROM Inventario I
                WHERE I.Id_Punto_Dispensacion='.$remision['Id_Destino'].' AND I.Id_Producto='.$item['Id_Producto'].' AND I.Lote="'.$item['Lote'].'"' ;
    
                $oCon= new consulta();
                $oCon->setQuery($query);
                $inventario = $oCon->getData();
                unset($oCon);
    
                if($inventario){
    
                    if($item["Cantidad"]==$item["Cantidad_Ingresada"]){
                        $oItem = new complex('Producto_Acta_Recepcion_Remision','Id_Producto_Acta_Recepcion_Remision');
                        //mandar productos a Producto_Acta_Recepcion_remision                            
                        $oItem->Id_Producto = $item["Id_Producto"];
                        $oItem->Lote=$item['Lote'];
                        $oItem->Fecha_Vencimiento=$item['Fecha_Vencimiento'];
                        $oItem->Cantidad=number_format($item["Cantidad"],0,"","");
                        $oItem->Cumple = $item['Cumple'];
                        $oItem->Revisado = $item['Revisado'];
                        $oItem->Id_Remision = $datos["Id_Remision"];
                        $oItem->Id_Producto_Remision = $item["Id_Producto_Remision"];
                        $oItem->Id_Acta_Recepcion_Remision = $id_Acta_Recepcion_remision;
                        $oItem->Temperatura =$item['Temperatura'] ;
                        //$oItem->save();
                        unset($oItem);
                        
                        $actual=number_format($item["Cantidad"],0,"","");
                        $suma=number_format($inventario["Cantidad"],0,"","");
                        $total=$suma+$actual;
                        // Agregar al inventario el nuevo producto
                        $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        //$oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Codigo_CUM = GetCodigoCumProducto($item['Id_Producto']);
                        $oItem->Cantidad = number_format($total,0,"","");
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        //$oItem->save();
                        unset($oItem);
                    }elseif ($item["Cantidad"]>$item["Cantidad_Ingresada"]){
                        $contador++;
                        if($datos['NoConforme']=="Si" && $contador==1){
                            $configuracion = new Configuracion();
                            $cod = $configuracion->Consecutivo('No_Conforme'); 
                            // generar no conforme , guardar el id del no conforme
                            $oItem = new complex('No_Conforme','Id_No_Conforme');
                            $oItem->Persona_Reporta = $datos['Identificacion_Funcionario'];
                            $oItem->Id_Remision =$datos["Id_Remision"] ;
                            $oItem->Codigo = $cod;
                            $oItem->Tipo = "Remision";
                            $oItem->Estado = "Pendiente";
                            //$oItem->save();
                            $idNoConforme = $oItem->getId();
                            unset($oItem);
                            
                             /*AQUI GENERA QR */
                            $qr = generarqr('noconforme',$idNoConforme,'/IMAGENES/QR/');
                            $oItem = new complex("No_Conforme","Id_No_Conforme",$idNoConforme);
                            $oItem->Codigo_Qr=$qr;
                            //$oItem->save();
                            unset($oItem);
                             /*HASTA AQUI GENERA QR */
                        }
                        $cantidadconforme = number_format($item["Cantidad_Ingresada"],0,"","");
                        $cantidad=number_format($item["Cantidad"],0,"","");
                        $suma=number_format($inventario["Cantidad"],0,"","");
                        $cantidanoconforme = ($cantidad - $cantidadconforme);
                        $total=$cantidadconforme+$suma;
                        $oItem = new complex('Producto_No_Conforme_Remision','Id_Producto_No_Conforme_Remision');
                        $oItem->Id_Producto=$item['Id_Producto'];
                        $oItem->Lote=$item['Lote'];
                        $oItem->Fecha_Vencimiento=$item['Fecha_Vencimiento'];
                        $oItem->Cantidad=number_format($cantidanoconforme,0,"","");
                        $oItem->Id_No_Conforme = $idNoConforme;
                        $oItem->Id_Remision = $datos["Id_Remision"];
                        $oItem->Observaciones = $item["Observaciones"];
                        $oItem->Id_Producto_Remision = $item["Id_Producto_Remision"];
                        $oItem->Id_Acta_Recepcion_Remision = $id_Acta_Recepcion_remision;
                        $oItem->Id_Causal_No_Conforme = (INT)$item["Id_Causal_No_Conforme"];
                        $oItem->Id_Inventario = $item["Id_Inventario"];
                        //$oItem->save();
                        unset($oItem); 
                      
        
                        $oItem = new complex('Producto_Acta_Recepcion_Remision','Id_Producto_Acta_Recepcion_Remision');
                        //mandar productos a Producto_Acta_Recepcion_remision                            
                        $oItem->Id_Producto = $item["Id_Producto"];
                        $oItem->Lote=$item['Lote'];
                        $oItem->Fecha_Vencimiento=$item['Fecha_Vencimiento'];
                        $oItem->Cantidad = number_format($cantidadconforme,0,"","");
                        $oItem->Cumple = $item['Cumple'];
                        $oItem->Revisado = $item['Revisado'];
                        $oItem->Id_Remision = $datos["Id_Remision"];
                        $oItem->Id_Producto_Remision = $item["Id_Producto_Remision"];
                        $oItem->Id_Acta_Recepcion_Remision = $id_Acta_Recepcion_remision;
                        $oItem->Temperatura =$item['Temperatura'] ;
                        //$oItem->save();
                        unset($oItem);
        
        
                        $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                       // $oItem->Id_Punto_Dispensacion= $punto;
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        $oItem->Cantidad = number_format($total,0,"","");
                        //$oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Codigo_CUM = GetCodigoCumProducto($item['Id_Producto']);
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        //$oItem->save();
                        unset($oItem);
                        $contador++;
                    }
    
    
                }else {
                    if($item["Cantidad"]==$item["Cantidad_Ingresada"]){
                        $oItem = new complex('Producto_Acta_Recepcion_Remision','Id_Producto_Acta_Recepcion_Remision');
                        //mandar productos a Producto_Acta_Recepcion_remision                            
                        $oItem->Id_Producto = $item["Id_Producto"];
                        $oItem->Lote=$item['Lote'];
                        $oItem->Fecha_Vencimiento=$item['Fecha_Vencimiento'];
                        $oItem->Cantidad=number_format($item["Cantidad"],0,"","");
                        $oItem->Cumple = $item['Cumple'];
                        $oItem->Revisado = $item['Revisado'];
                        $oItem->Id_Remision = $datos["Id_Remision"];
                        $oItem->Id_Producto_Remision = $item["Id_Producto_Remision"];
                        $oItem->Id_Acta_Recepcion_Remision = $id_Acta_Recepcion_remision;
                        $oItem->Temperatura =$item['Temperatura'] ;
                        //$oItem->save();
                        unset($oItem);
                        
                        // Agregar al inventario el nuevo producto
                        $oItem = new complex('Inventario','Id_Inventario');
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                        $oItem->Id_Punto_Dispensacion= $punto;
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        $oItem->Cantidad = number_format($item['Cantidad'],0,"","");
                        //$oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Codigo_CUM = GetCodigoCumProducto($item['Id_Producto']);
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        //$oItem->save();
                        unset($oItem);
                    }elseif ($item["Cantidad"]>$item["Cantidad_Ingresada"]){
                        $contador++;
                        if($datos['NoConforme']=="Si" && $contador==1){
                            $configuracion = new Configuracion();
                            $cod = $configuracion->Consecutivo('No_Conforme'); 
                            // generar no conforme , guardar el id del no conforme
                            $oItem = new complex('No_Conforme','Id_No_Conforme');
                            $oItem->Persona_Reporta = $datos['Identificacion_Funcionario'];
                            $oItem->Id_Remision =$datos["Id_Remision"] ;
                            $oItem->Codigo = $cod;
                            $oItem->Tipo = "Remision";
                            $oItem->Estado = "Pendiente";
                            //$oItem->save();
                            $idNoConforme = $oItem->getId();
                            unset($oItem);
                            
                             /*AQUI GENERA QR */
                            $qr = generarqr('noconforme',$idNoConforme,'/IMAGENES/QR/');
                            $oItem = new complex("No_Conforme","Id_No_Conforme",$idNoConforme);
                            $oItem->Codigo_Qr=$qr;
                            //$oItem->save();
                            unset($oItem);
                             /*HASTA AQUI GENERA QR */
                        }
    
    
                        $cantidadconforme = number_format($item["Cantidad_Ingresada"],0,"","");
                        $cantidad=number_format($item["Cantidad"],0,"","");
                        $cantidanoconforme = $cantidad - $cantidadconforme;
        
                        $oItem = new complex('Producto_No_Conforme_Remision','Id_Producto_No_Conforme_Remision');
                        $oItem->Id_Producto=$item['Id_Producto'];
                        $oItem->Lote=$item['Lote'];
                        $oItem->Fecha_Vencimiento=$item['Fecha_Vencimiento'];
                        $oItem->Cantidad=number_format($cantidanoconforme,0,"","");
                        $oItem->Id_No_Conforme = $idNoConforme;
                        $oItem->Id_Remision = $datos["Id_Remision"];
                        $oItem->Observaciones = $item["Observaciones"];
                        $oItem->Id_Acta_Recepcion_Remision = $id_Acta_Recepcion_remision;
                        $oItem->Id_Producto_Remision = $item["Id_Producto_Remision"];
                        $oItem->Id_Causal_No_Conforme = (INT)$item["Id_Causal_No_Conforme"];
                        $oItem->Id_Inventario = $item["Id_Inventario"];
                        //$oItem->save();
                        unset($oItem); 
        
                        $oItem = new complex('Producto_Acta_Recepcion_Remision','Id_Producto_Acta_Recepcion_Remision');
                        //mandar productos a Producto_Acta_Recepcion_remision                            
                        $oItem->Id_Producto = $item["Id_Producto"];
                        $oItem->Lote=$item['Lote'];
                        $oItem->Fecha_Vencimiento=$item['Fecha_Vencimiento'];
                        $oItem->Cantidad = number_format($cantidadconforme,0,"","");
                        $oItem->Cumple = $item['Cumple'];
                        $oItem->Revisado = $item['Revisado'];
                        $oItem->Id_Remision = $datos["Id_Remision"];
                        $oItem->Id_Producto_Remision = $item["Id_Producto_Remision"];
                        $oItem->Id_Acta_Recepcion_Remision = $id_Acta_Recepcion_remision;
                        $oItem->Temperatura =$item['Temperatura'] ;
                        //$oItem->save();
                        unset($oItem);
        
        
                        $oItem = new complex('Inventario','Id_Inventario');
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                        $oItem->Id_Punto_Dispensacion= $punto;
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        $oItem->Cantidad = number_format($cantidadconforme,0,"","");
                        //$oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Codigo_CUM = GetCodigoCumProducto($item['Id_Producto']);
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        //$oItem->save();
                        unset($oItem);
                        $contador++;
                    }
                }
    
               
    }
    
    
    
    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision = $datos["Id_Remision"];
    $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
    $oItem->Detalles = "Se hace el acta de recepcion de la  ".$remision["Codigo"];
    $oItem->Estado = "Recibida";
    //$oItem->save();  
    unset($oItem);    
         
    
    if($contador==0){
        $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion";
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Acta de recepción Guardada";

    }else{
        $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion con los productos No Conformes";
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Acta de recepción Guardada";
    }
}


echo json_encode($resultado);

function GetCodigoCumProducto($id_producto){
    $query = '
        SELECT 
            Codigo_Cum
        FROM Producto
        WHERE 
            Id_Producto='.$id_producto;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum['Codigo_Cum'];
}

function ValidarRemision($id){
    $query = 'SELECT Id_Acta_Recepcion_Remision, Codigo
    FROM Acta_Recepcion_Remision
    WHERE Id_Remision='.$id;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $acta = $oCon->getData();
    unset($oCon);

   

    return $acta;
}

//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");
?>