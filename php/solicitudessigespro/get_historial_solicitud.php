<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $id_solicitud = ( isset( $_REQUEST['id_solicitud'] ) ? $_REQUEST['id_solicitud'] : '' );

    $query = '
        SELECT 
            ASS.Id_Solicitud_Sigespro,
            F.Imagen,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Funcionario,
            ASS.Fecha_Actividad AS Fecha,
            ASS.Detalle AS Detalles,
            ASS.Tipo_Actividad AS Estado
        FROM Actividad_Solicitud_Sigespro ASS
        INNER JOIN Funcionario F ON ASS.Id_Funcionario = F.Identificacion_Funcionario
        WHERE
            ASS.Id_Solicitud_Sigespro = '.$id_solicitud;
    
    $queryObj = new QueryBaseDatos($query);
    $solicitud = $queryObj->Consultar('multiple');

    echo json_encode($solicitud);          
?>