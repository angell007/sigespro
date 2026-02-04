<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.http_response.php');
    //$util = new Utility();
    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();

    $codigo_cum = ( isset( $_REQUEST['Cum'] ) ? $_REQUEST['Cum'] : '' );
    $tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
    $validar = ( isset( $_REQUEST['validar'] ) ? $_REQUEST['validar'] : '' );
    $nit = ( isset( $_REQUEST['nit'] ) ? $_REQUEST['nit'] : '' );

    if($tipo=='Evento'){
        $tabla="Producto_Evento";
    }else{
        $tabla="Producto_Cohorte";
    }

    $condicion = SetCondiciones($_REQUEST);

    $query="SELECT IFNULL(PE.Id_$tabla, 0 ) as Id_$tabla, P.Id_Producto FROM Producto P LEFT JOIN $tabla PE ON P.Codigo_Cum=PE.Codigo_Cum  $condicion";

    $queryObj->SetQuery($query);
    $cum=$queryObj->ExecuteQuery('simple');

    if($cum){

        if($validar=='eps'){

            $prod=GetProductoEps();
            if($prod){
                $http_response->SetRespuesta(1,'Alerta','El Codigo Cum ya esta asignado en la lista a esta EPS!');
                $response=$http_response->GetRespuesta(); 
            }else{
                $http_response->SetRespuesta(0,'Exitoso','Se puede registrar el cum');
                $response=$http_response->GetRespuesta(); 
                $response['Id_Producto']=$cum['Id_Producto'];
            }


        }else{
            $http_response->SetRespuesta(0,'Exitoso','Se puede registrar el cum');
            $response=$http_response->GetRespuesta(); 
            $response['Id_Producto']=$cum['Id_Producto'];
        }

    }else{
        $http_response->SetRespuesta(1,'Alerta','El Codigo Cum Ingresado no esta registrado en la base de datos!');
        $response=$http_response->GetRespuesta(); 
    }



    echo json_encode($response);

    function SetCondiciones($req){

        $condicion = ''; 
            
        $condicion .= " WHERE P.Codigo_Cum LIKE '".$req['Cum']."'";  
        return $condicion;
    }

    function GetProductoEps(){
        global $codigo_cum,$nit,$tabla,$queryObj;
        $query="SELECT Id_$tabla FROM $tabla  WHERE Codigo_Cum LIKE '$codigo_cum' AND Nit_EPS=$nit";

        $queryObj->SetQuery($query);
        $prod=$queryObj->ExecuteQuery('simple');
         

        return $prod;
    }
          
?>