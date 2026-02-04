<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    include_once '../../../class/class.consulta.php';
    include_once '../../../class/class.complex.php';
    
    $agente  = isset($_REQUEST['agente']) ? $_REQUEST['agente'] : false;

    try{
  
      $agente = json_decode($agente,true);
      $oItem = new complex('Agentes_Cliente','Agentes_Cliente');

      foreach($agente as $key => $data){
	$oItem->$key = $data;
      }
      $oItem->Estado = "Activo";
      $oItem->Password = md5($agente['Identificacion']);
      $oItem->save();
    
 
      $res['type']="success";
      $res['title']="Operación exitosa";
      $res['text']="Se ha guardado satisfactoriamente";
   
    }catch(Exception $e){
      $res['type']="error";
      $res['title']="Algo saló mal";
      $res['text']="Ha ocurrido un error";
    }

    echo json_encode($res);
