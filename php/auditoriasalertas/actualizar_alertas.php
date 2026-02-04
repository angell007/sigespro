<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

    $alertas = ( isset( $_REQUEST['alertas'] ) ? $_REQUEST['alertas'] : '' );
    $funcionario = ( isset( $_REQUEST['funcionario'])  && $_REQUEST['funcionario'] != '') ? $_REQUEST['funcionario'] : null ;

    $datos = (array) json_decode($alertas , true);

    if($funcionario == 'null' ){
        $resultado['title']   = "Alertas sin Asignar";
        $resultado['mensaje'] = "Debe asignar un funcionario a estas alertas";
        $resultado['tipo']    = "error";
    echo json_encode($resultado); 
    }else{
        foreach ($datos as $res){
            $oItem = new complex('Alerta','Id_Alerta',$res['Id_Alerta']);
            $oItem->Identificacion_Funcionario =  $funcionario;
            $oItem->save();
            $oItem->getId();
            unset($oItem);        
        }
        $resultado['title']   = "Alertas Asignadas";
        $resultado['mensaje'] = "Se asignaron las tareas exitosamente.";
        $resultado['tipo']    = "success";
 
    
    echo json_encode($resultado);
    }

   



    