<?php 
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    include_once('../../class/class.consulta.php');
    

    $query = 'Select Id_Bodega_Nuevo, Nombre From Bodega_Nuevo';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);
    
    echo json_encode($resultado);
   
