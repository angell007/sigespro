<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );
    $tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

    if($tipo=='Evento'){
        $tabla="Producto_Evento";
    }else{
        $tabla="Producto_Cohorte";
    }

    $condicion = SetCondiciones($_REQUEST);

    $query = 'SELECT PE.*, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre, P.Nombre_Comercial, (SELECT Nombre FROM Cliente WHERE Id_Cliente=PE.Nit_EPS) as Eps
    FROM '.$tabla.' PE 
    INNER JOIN Producto P ON PE.Codigo_Cum=P.Codigo_Cum
    '.$condicion;

    $query_count = '
        SELECT 
            COUNT(*) AS Total
            FROM '.$tabla.' PE 
            INNER JOIN Producto P ON PE.Codigo_Cum=P.Codigo_Cum
        '.$condicion;
    $paginationData = new PaginacionData($tam, $query_count, $pag);
  
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones($req){

        $condicion = ''; 

        if (isset($req['cum']) && $req['cum']) {
            
            $condicion .= " WHERE P.Codigo_Cum LIKE '%".$req['cum']."%'";
           
        }

        if (isset($req['nom']) && $req['nom']) {
            if ($condicion != '') {
                $condicion .= " AND ((CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) LIKE '%".$req['nom']."%') OR P.Nombre_Comercial LIKE '%".$req['nom']."%') ";
            } else {
                $condicion .= " WHERE  ((CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) LIKE '%".$req['nom']."%') OR P.Nombre_Comercial LIKE '%".$req['nom']."%') ";
            }
        }

        

        return $condicion;
    }
          
?>