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

$res;
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productoRemision = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datosProductos = (array) json_decode($productoRemision , true);
$datos = (array) json_decode($datos);

//datos
ValidarRemision($datos["Id_Remision"]);

if(!$res['Codigo']){
    $configuracion = new Configuracion();
    $cod = $configuracion->getConsecutivo('Acta_Recepcion_Remision','Acta_Recepcion_Remision'); 
    //var_dump($cod); 
    $datos['Codigo']=$cod;
    $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision");
    foreach($datos as $index=>$value) {
        $oItem->$index=$value;
        
    }
    $oItem->Id_Bodega = $datos['Bodega'];
    $oItem->save();
    $id_Acta_Recepcion_remision = $oItem->getId();
    //var_dump($id_Acta_Recepcion_remision);
    unset($oItem);

    /* AQUI GENERA QR */
    $qr = generarqr('actarecepcionremision',$id_Acta_Recepcion_remision,'/IMAGENES/QR/');
    $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision",$id_Acta_Recepcion_remision);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);
    /* HASTA AQUI GENERA QR */





    // realizar guardado para las caracteristicas de los productos
    //1. revisar cuales fueron marcados y no marcados en el array que traigo.
    $i=-1;
    $contador=0;
    foreach($datosProductos as $item){$i++;

                $query = 'SELECT I.*
                FROM Inventario I
                WHERE I.Id_Bodega='.$datos['Bodega'].' AND I.Id_Producto='.$item['Id_Producto'].' AND I.Fecha_Vencimiento="'.$item['Fecha_Vencimiento'].'" AND I.Lote="'.$item['Lote'].'"' ;

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
                        $oItem->save();
                        unset($oItem);
                        
                        $actual=number_format($item["Cantidad"],0,"","");
                        $suma=number_format($inventario["Cantidad"],0,"","");
                        $total=$suma+$actual;
                        // Agregar al inventario el nuevo producto
                        $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        $oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Cantidad = number_format($total,0,"","");
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        $oItem->save();
                        unset($oItem);
                    }elseif ($item["Cantidad"]!=$item["Cantidad_Ingresada"]){
                        
                        if($datos['NoConforme']=="Si" && $contador==0){
                            $configuracion = new Configuracion();
                            $cod = $configuracion->getConsecutivo('No_Conforme','No_Conforme'); 
                            // generar no conforme , guardar el id del no conforme
                            $oItem = new complex('No_Conforme','Id_No_Conforme');
                            $oItem->Persona_Reporta = $datos['Identificacion_Funcionario'];
                            $oItem->Id_Remision =$datos["Id_Remision"] ;
                            $oItem->Codigo = $cod;
                            $oItem->Tipo = "Remision";
                            $oItem->Estado = "Pendiente";
                            $oItem->save();
                            $idNoConforme = $oItem->getId();
                            unset($oItem);
                            
                            /*AQUI GENERA QR */
                            $qr = generarqr('noconforme',$idNoConforme,'/IMAGENES/QR/');
                            $oItem = new complex("No_Conforme","Id_No_Conforme",$idNoConforme);
                            $oItem->Codigo_Qr=$qr;
                            $oItem->save();
                            unset($oItem);
                            /*HASTA AQUI GENERA QR */
                        }

                        $cantidadconforme = number_format($item["Cantidad_Ingresada"],0,"","");
                        $cantidad=number_format($item["Cantidad"],0,"","");
                        $suma=number_format($inventario["Cantidad"],0,"","");
                        $cantidanoconforme =$cantidadconforme<$cantidad ? ($cantidad - $cantidadconforme) : ($cantidadconforme - $cantidad);
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
                        $oItem->Id_Causal_No_Conforme = $item["Id_Causal_No_Conforme"]!='' ? $item["Id_Causal_No_Conforme"] :'1';
                        $oItem->Id_Inventario = $item["Id_Inventario"];
                        $oItem->save();
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
                        $oItem->save();
                        unset($oItem);
        
        
                        $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                    // $oItem->Id_Punto_Dispensacion= $punto;
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        $oItem->Cantidad = number_format($total,0,"","");
                        $oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        $oItem->save();
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
                        $oItem->save();
                        unset($oItem);
                        
                        // Agregar al inventario el nuevo producto
                        $oItem = new complex('Inventario','Id_Inventario');
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                        $oItem->Id_Bodega= $datos['Bodega'];
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        $oItem->Cantidad = number_format($item['Cantidad'],0,"","");
                        $oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        $oItem->save();
                        unset($oItem);
                    }elseif ($item["Cantidad"]!=$item["Cantidad_Ingresada"]){

                        if($datos['NoConforme']=="Si" && $contador==0){
                            $configuracion = new Configuracion();
                            $cod = $configuracion->getConsecutivo('No_Conforme','No_Conforme'); 
                            // generar no conforme , guardar el id del no conforme
                            $oItem = new complex('No_Conforme','Id_No_Conforme');
                            $oItem->Persona_Reporta = $datos['Identificacion_Funcionario'];
                            $oItem->Id_Remision =$datos["Id_Remision"] ;
                            $oItem->Codigo = $cod;
                            $oItem->Tipo = "Remision";
                            $oItem->Estado = "Pendiente";
                            $oItem->save();
                            $idNoConforme = $oItem->getId();
                            unset($oItem);
                            
                            /*AQUI GENERA QR */
                            $qr = generarqr('noconforme',$idNoConforme,'/IMAGENES/QR/');
                            $oItem = new complex("No_Conforme","Id_No_Conforme",$idNoConforme);
                            $oItem->Codigo_Qr=$qr;
                            $oItem->save();
                            unset($oItem);
                            /*HASTA AQUI GENERA QR */
                        }

                        $cantidadconforme = number_format($item["Cantidad_Ingresada"],0,"","");
                        $cantidad=number_format($item["Cantidad"],0,"","");
                        $cantidanoconforme = $cantidadconforme<$cantidad ? ($cantidad - $cantidadconforme) : ($cantidadconforme - $cantidad);
        
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
                        $oItem->Id_Causal_No_Conforme = $item["Id_Causal_No_Conforme"]!='' ? $item["Id_Causal_No_Conforme"] :'1';
                        $oItem->Id_Inventario = $item["Id_Inventario"];
                        $oItem->save();
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
                        $oItem->save();
                        unset($oItem);
        
        
                        $oItem = new complex('Inventario','Id_Inventario');
                        $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
                        $oItem->Id_Bodega= $datos['Bodega'];
                        $oItem->Id_Producto= $item['Id_Producto'];
                        $oItem->Costo = $item['Precio'];
                        $oItem->Cantidad = number_format($cantidadconforme,0,"","");
                        $oItem->Codigo_CUM = $item['Codigo_Cum'];
                        $oItem->Lote = $item['Lote'];
                        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];            
                        $oItem->save();
                        unset($oItem);
                        $contador++;
                    }
                }

            
    }

    //cambiar el estado de la Remision a RECIBIDA

    $oItem = new complex('Remision','Id_Remision',$datos["Id_Remision"]);
    $oItem->Estado="Recibida";
    $oItem->save();
    $remision= $oItem->getData();
    unset($oItem);

    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision = $datos["Id_Remision"];
    $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
    $oItem->Detalles = "Se hace el acta de recepcion de la  ".$remision["Codigo"];
    $oItem->Estado = "Recibida";
    $oItem->save();  
    unset($oItem);    
        

    if($contador==0){
        $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion";
        $resultado['tipo'] = "success";
    }else{
        $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion con los productos No Conformes";
        $resultado['tipo'] = "success";
    }
    $resultado['titulo'] = "Acta de Recepcion Guardada";

}else{

    $resultado['mensaje'] = "Esta Remision ya tiene registrada un acta de Recepción la cual ".$res['Codigo'];
    $resultado['tipo'] = "warning";
    $resultado['titulo'] = "Error al Guardar";
}

echo json_encode($resultado);

function ValidarRemision($id){
    global $res;
    $query='SELECT ARR.Codigo FROM Acta_Recepcion_Remision ARR WHERE ARR.Id_Remision='.$id;
    $oCon= new consulta();
  //  $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $res = $oCon->getData();
    unset($oCon);
}
//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");
?>