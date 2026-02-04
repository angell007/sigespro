<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    
    include_once('../../class/class.querybasedatos.php');

    $queryObj = new QueryBaseDatos();
    
    $match = ( isset( $_REQUEST['match'] ) ? $_REQUEST['match'] : '' );
    
    $query = '
        SELECT
            Id_Proveedor,
            IF(Nombre IS NULL OR Nombre = "", CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) AS Nombre_Proveedor
        FROM Proveedor
        WHERE
            (IF(Nombre IS NULL OR Nombre = "", CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) LIKE "%'.$match.'%" OR Id_Proveedor LIKE "%'.$match.'%")';
    
    $queryObj->setQuery($query);
    $proveedores = $queryObj->Consultar('multiple');
    unset($queryObj);
    
    echo json_encode($proveedores);
?>