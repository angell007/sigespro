<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../../class/class.querybasedatos.php');

    $modalidad = ( isset( $_REQUEST['modalidad'] ) ? $_REQUEST['modalidad'] : '' );

    $query = '
        SELECT
            *
        FROM Retencion
        WHERE
            LOWER(Modalidad_Retencion) = "'.strtolower($modalidad).'"';

    $queryObj= new QueryBaseDatos($query);
    $retenciones = $queryObj->ExecuteQuery('multiple');
    unset($queryObj);

    echo json_encode($retenciones);
?> 