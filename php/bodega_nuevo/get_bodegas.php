<?php 
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    include_once('../../class/class.consulta.php');
    require_once('../../helper/response.php');
    
    $query='SELECT * FROM Bodega_Nuevo AS BN 
    WHERE BN.Id_Bodega_Nuevo NOT IN 

     (SELECT Id_Bodega FROM Doc_Inventario_Auditable WHERE Estado != "Terminado" )
     
     AND  BN.Id_Bodega_Nuevo  NOT IN 

     (SELECT B.id_Bodega_Nuevo FROM Doc_Inventario_Fisico As DIF 
     INNER JOIN Estiba E ON E.Id_Estiba=DIF.Id_Estiba INNER JOIN Bodega_Nuevo B ON B.id_Bodega_Nuevo=E.Id_Bodega_Nuevo
     WHERE DIF.Estado != "Terminado")
    ';

    $oCon=new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $Bodegas=$oCon->getData();

//var_dump($query);
    unset($oCon);
    
    $query = 'SELECT IM.Valor 
                FROM Impuesto IM';
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $impuesto = $oCon->getData();
    unset($oCon);

    if($Bodegas){
      
        $producto["Mensaje"]='Bodegas Encontradas con éxito';
        $resultado["Tipo"]="success";
        $resultado["impuestoli"]=$impuesto;
        $resultado["Bodegas"]=$Bodegas;
    
    }else{
        $resultado["Tipo"]="error";
        $resultado["Titulo"]="Error al intentar buscar las bodegas";
        $resultado["Texto"]="Ha ocurrido un error inesperado.";
    }

    echo json_encode($resultado);
?>