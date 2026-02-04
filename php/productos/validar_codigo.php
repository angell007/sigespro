<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.consulta.php');

    $codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );

    $query = 'SELECT Nombre_Comercial, Imagen, Id_Producto, Laboratorio_Comercial, Embalaje            
            FROM Producto 
            WHERE
                Codigo_Barras LIKE "'.$codigo.'"';

                           
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