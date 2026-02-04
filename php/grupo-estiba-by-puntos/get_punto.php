<?php 

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    include_once('../../class/class.consulta.php');
    require_once('../../helper/response.php');
    
    $id_punto = isset($_REQUEST['IdPunto'])?$_REQUEST['IdPunto'] : false;

    $query='SELECT * FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion ='.$id_punto;
    $oCon=new consulta();
    $oCon->setQuery($query);

    $puntoDis=$oCon->getData();

    unset($oCon);
    
    if($puntoDis){
        $resultado["Mensaje"]='Puntos Encontradas con éxito';
        $resultado["Tipo"]="success";
        $resultado["Punto"]=$puntoDis;
    }else{
        $resultado["Tipo"]="error";
        $resultado["Titulo"]="Error al intentar buscar las Puntos";
        $resultado["Texto"]="Ha ocurrido un error inesperado.";
    }

    show($resultado);
