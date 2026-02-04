<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');

    $id_producto = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

    $query = 'SELECT
                T1.*,

                (SELECT Nombre FROM Subcategoria WHERE Id_Subcategoria = T1.Id_Subcategoria) AS Subcategoria
            FROM Producto T1
            WHERE
                T1.Id_Producto = '.$id_producto;
            
    try {
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $result = $oCon->getData();
        unset($oCon);
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode($e->message);
    }
?>