<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $cum_no_encontrados=[];
    $query = 'SELECT * FROM Temporal_Asociaciones ';
    
    $queryObj = new QueryBaseDatos($query);
    $tecn = $queryObj->Consultar('Multiple');

    foreach ($tecn['query_result'] as $key => $value) {
        $cum_asociados=explode(',',$value['Cum']);
        for ($i=0; $i <count($cum_asociados) ; $i++) { 
           $id_producto=GetCumProducto(trim($cum_asociados[$i]));
           if($id_producto){
               $oItem=new complex ('Producto_Tipo_Tecnologia_Mipres', 'Id_Producto_Tipo_Tecnologia_Mipres');
                $oItem->Codigo_Anterior=$value['Codigo_Anterior'];
                $oItem->Codigo_Actual=$value['Codigo_Actual'];
                $oItem->Descripcion=strtoupper($value['Descripcion']);
                $oItem->Id_Tipo_Tecnologia_Mipres=$value['Id_Tipo_Tecnologia_Mipres'];
                $oItem->Id_Producto=$id_producto['Id_Producto'];
                $oItem->save();
                unset($oItem);
           }else{
            array_push($cum_no_encontrados,$cum_asociados[$i] );
           }
        }
    }

   
echo "Finalizo";

    var_dump($cum_no_encontrados);

   

    function GetCumProducto($cum){
        global $queryObj;

        $query_productos = "SELECT Id_Producto FROM Producto WHERE Codigo_Cum='$cum'";

        $queryObj->SetQuery($query_productos);
        $producto = $queryObj->ExecuteQuery('simple');

        return $producto;
    }

   
          
?>