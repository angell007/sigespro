<?php 
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    include_once('../../class/class.consulta.php');
    
    $id_bodega = isset($_REQUEST['Id_Bodega_Nuevo'])?$_REQUEST['Id_Bodega_Nuevo'] : false;


    $query='Select * From Bodega_Nuevo WHERE Id_Bodega_Nuevo ='.$id_bodega;
    $oCon=new consulta();
    $oCon->setQuery($query);
    $Bodega=$oCon->getData();
    unset($oCon);
    
    if($Bodega){
      
        $producto["Mensaje"]='Bodegas Encontradas con éxito';
        $resultado["Tipo"]="success";
        $resultado["Bodega"]=$Bodega;
    
    }else{
        $resultado["Tipo"]="error";
        $resultado["Titulo"]="Error al intentar buscar las bodegas";
        $resultado["Texto"]="Ha ocurrido un error inesperado.";
    }

    echo json_encode($resultado);
