<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

$motivo_anulacion = isset($_REQUEST['Motivo_Anulacion']) ? $_REQUEST['Motivo_Anulacion'] : false;

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once('../../class/class.contabilizar.php');
include_once('./helper_anular.php');
require_once('./helper.ajuste_individual.php');
include_once('../../class/class.contabilizar.php');
$contabilizar = new Contabilizar();


$id_ajuste = isset($_REQUEST['Id_Ajuste']) ? $_REQUEST['Id_Ajuste'] : false;
$funcionario = isset($_REQUEST['Funcionario']) ? $_REQUEST['Funcionario'] : false;

$response = [];

$ajuste = [];
if ($id_ajuste && $funcionario && $motivo_anulacion) {
    $oCon = new complex('Ajuste_Individual','Id_Ajuste_Individual',$id_ajuste);
    $ajuste = $oCon->getData();
    unset($oCon);
   // var_dump($ajuste);
}


if ( $ajuste && $ajuste['Estado']  == 'Activo' ) {
    
    if ( $ajuste['Tipo'] == 'Entrada' ) {

        if ( $ajuste['Origen_Destino'] == 'Bodega' ) {
            # code...

            if ($ajuste['Estado_Entrada_Bodega'] == 'Aprobada') {
            
                if(!validarBodegaInventario($ajuste['Id_Origen_Destino'])){

                    cambiarEstado($id_ajuste,'Entrada');
                    guardarActividad($id_ajuste,$funcionario,$ActividadE,'Anulada');
        
                    if ($ajuste['Cambio_Estiba']) {
                        cambiarEstado($ajuste['Id_Salida'],'Salida');
                        devolverInventario($id_ajuste);
                        guardarActividad($ajuste['Id_Salida'],$funcionario,$ActividadS,'Anulada');
                    }else{
                        AnularMovimientoContable($id_ajuste);
                    }
                    setResponse('success',$Ok,$Anulado_Ok,$response); 

                }else{
                    setResponse('error','¡No se puede realizar la operación!','En este momento la bodega que seleccionó se encuentra realizando un inventario.',$response); 
                }
    
            }elseif ( $ajuste['Estado_Entrada_Bodega'] == 'Acomodada' ) {
                # NO ES POSIBLE ANULAR
                setResponse('error',$error,$Acomodada_F,$response);
            }    

        }elseif ( $ajuste['Origen_Destino'] == 'Punto' ) {

            if ( validarCantidades($id_ajuste) ) {
                
                cambiarEstado( $id_ajuste,'Entrada' );
                guardarActividad( $id_ajuste,$funcionario,$ActividadS,'Anulada' );
                retirarDeInventario( $id_ajuste );
                
                AnularMovimientoContable($id_ajuste);

                setResponse('success',$Ok,$Anulado_Ok,$response); 

            }else{
                setResponse('error',$error,$Cantidad_F,$response); 
            }
        }

    
    }elseif ( $ajuste['Tipo'] == 'Salida') {

        if ( $ajuste['Origen_Destino'] == 'Bodega' ) {

            #si es cambio de estiba y está aprobado la salida quiere decir que creó la entrada
            if ( $ajuste['Cambio_Estiba'] && $ajuste['Estado_Salida_Bodega'] == 'Aprobado' ){
                #BUSCAR LA ENTRADA ASOCIADA          
                $oCon = new complex('Ajuste_Individual','Id_Salida',$id_ajuste);
                $entradaAsociada = $oCon->getData();
                unset($oCon);      
                
                #SI SOLO SI LA ENTRADA NO HA SIDO ACOMODADA!!
                if ( $entradaAsociada['Estado_Entrada_Bodega'] == 'Aprobada' ) {
                    if(!validarBodegaInventario($ajuste['Id_Origen_Destino'])){

                        cambiarEstado($id_ajuste,'Salida');
                        guardarActividad($id_ajuste,$funcionario,$ActividadS,'Anulada');
        
                        cambiarEstado($entradaAsociada['Id_Ajuste_Individual'],'entradaAsociada');
                        guardarActividad($entradaAsociada['Id_Ajuste_Individual'],$funcionario,$ActividadE,'Anulada');
        
                        devolverInventario($id_ajuste);
                        setResponse('success',$Ok,$Anulado_Ok,$response);
            

                    }else{
                        setResponse('error','¡No se puede realizar la operación!','En este momento la bodega que seleccionó se encuentra realizando un inventario.',$response); 
                    }
                }else{
                    setResponse('error',$error,$Acomodada_F,$response);
                }
        
    
            }else{
                if(!validarBodegaInventario($ajuste['Id_Origen_Destino'])){
                    cambiarEstado($id_ajuste,'Salida');
                    guardarActividad($id_ajuste,$funcionario,$ActividadS,'Anulada');
            
                    if( $ajuste['Estado_Salida_Bodega'] == 'Aprobado' ){
                        devolverInventario($id_ajuste);
                        AnularMovimientoContable($id_ajuste);
                    }
                    setResponse('success',$Ok,$Anulado_Ok,$response);
                }else{
                    setResponse('error','¡No se puede realizar la operación!','En este momento la bodega que seleccionó se encuentra realizando un inventario.',$response); 
                }
            }

        }elseif ( $ajuste['Origen_Destino'] == 'Punto' ) {
                
                cambiarEstado($id_ajuste,'Salida');
                guardarActividad($id_ajuste,$funcionario,$ActividadS,'Anulada');
                devolverInventario($id_ajuste);
                AnularMovimientoContable($id_ajuste);

                setResponse('success',$Ok,$Anulado_Ok,$response); 
        }

      
    }
}else if( $ajuste['Estado'] == 'Anulada' ){
    setResponse('error',$error,$Anulado_F,$response);
}else{
    setResponse('error',$error,$Datos_F,$response);
}

echo json_encode($response);


