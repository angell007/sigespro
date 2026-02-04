<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');



$id_correspondencia = ( isset( $_REQUEST['id_correspondencia'] ) ? $_REQUEST['id_correspondencia'] : '' );
$datos = ( isset( $_REQUEST['dispensaciones'] ) ? $_REQUEST['dispensaciones'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$observacion = ( isset( $_REQUEST['observacion'] ) ? $_REQUEST['observacion'] : '' );
$oItem = new complex('Correspondencia','Id_Correspondencia',$id_correspondencia);

$datos = (array) json_decode($datos , true);


$oItem = new complex('Correspondencia','Id_Correspondencia',$id_correspondencia);
$correspondencia = $oItem->getData();
$oItem->Id_Funcionario_Recibe=$funcionario;
$oItem->Fecha_Entrega_Real = date('Y-m-d H:i:s');
$oItem->Estado="Recibida";
$oItem->save();
unset($oItem);

foreach($datos as $item){
        if($item['Cumple']=='Si'){
                $oItem = new complex('Auditoria','Id_Auditoria',$item['Id_Auditoria']);
             //   $oItem->Estado="Auditado";
                $oItem->Funcionario_Auditoria=$funcionario;
                $oItem->Fecha_Auditoria=date("Y-m-d H:i:s");
                $oItem->save();
                unset($oItem);    
                
                $oItem = new complex('Dispensacion','Id_Dispensacion',$item['Id_Dispensacion']);
                //$oItem->Estado_Auditoria="Auditado";
                $oItem->Identificacion_Auditor=$funcionario;
                $oItem->Estado_Correspondencia="Recibida";
                $oItem->Fecha_Auditado=date("Y-m-d H:i:s");
                $oItem->save();
                unset($oItem); 

                $oItem = new complex('Actividades_Dispensacion','Id_Actividades_Dispensacion');
                $oItem->Id_Dispensacion=$item['Id_Dispensacion'];
                $oItem->Identificacion_Funcionario=$funcionario;
                $oItem->Estado_Correspondencia="Pendiente";
                $oItem->Detalle="La correspondecia fue recibida";
                $oItem->Fecha=date("Y-m-d H:i:s");
                $oItem->Estado="Auditada";
                $oItem->save();
                unset($oItem);
        }else{
                $oItem = new complex('Actividades_Dispensacion','Id_Actividades_Dispensacion');
                $oItem->Id_Dispensacion=$item['Id_Dispensacion'];
                $oItem->Identificacion_Funcionario=$funcionario;
                $oItem->Detalle="La correspondencia no aplica: $observacion";
                $oItem->Fecha=date("Y-m-d H:i:s");
                $oItem->Estado="Auditada";
                $oItem->save();
                unset($oItem);
                $oItem = new complex('Alerta','Id_Alerta');
                $oItem->Identificacion_Funcionario=$correspondencia['Id_Funcionario_Envia'];
                $oItem->Tipo="Dispensacion";
                $oItem->Modulo=$item['Codigo'];
                $oItem->Respuesta='No';
                $oItem->Detalles="La correspondencia de la dispensacion $item[Codigo] no cumple con los requisitos";
                $oItem->save();
                unset($oItem);
                
                $oItem = new complex('Dispensacion','Id_Dispensacion',$item['Id_Dispensacion']);
                $oItem->Id_Correspondencia = "0";
                $oItem->save();
                //DIS928714
                
       
        }
 
}

$resultado['mensaje'] = "Se ha guardado correctamente la correspondencia ";
$resultado['tipo'] = "success";


echo json_encode($resultado);
?>