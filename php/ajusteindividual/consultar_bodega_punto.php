<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
    $query = '';

    if ($tipo == '') {
        throw new Exception("El tipo para realizar la consulta esta vacio");        
    }

    if ($tipo == 'Bodega') {
        
        $query = '
            SELECT
                Id_Bodega AS Id_Bodega_Punto,
                Nombre AS Nombre_Bodega_Punto
            FROM Bodega
            ORDER BY Nombre ASC';
    }else{
        
        $query = '
            SELECT
                Id_Punto_Dispensacion AS Id_Bodega_Punto,
                Nombre AS Nombre_Bodega_Punto
            FROM Punto_Dispensacion
            ORDER BY Nombre ASC';
    }
    
    $queryObj = new QueryBaseDatos($query);
    $bodega_punto = $queryObj->Consultar('multiple');

    echo json_encode($bodega_punto);  
?>