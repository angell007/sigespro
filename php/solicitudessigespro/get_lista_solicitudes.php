<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');
    include_once(__DIR__ . '/permisos_sigespro.php');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );
    $funcionario_consulta = ( isset( $_REQUEST['funcionario_consulta'] ) ? $_REQUEST['funcionario_consulta'] : '' );

    $permiso_gerencia = ObtenerPermisoModulo($funcionario_consulta, 'Solicitudes Sigespro - Gerencia');
    $permiso_desarrollo = ObtenerPermisoModulo($funcionario_consulta, 'Solicitudes Sigespro - Desarrollo');
    $permiso_revision = ObtenerPermisoModulo($funcionario_consulta, 'Solicitudes Sigespro - Revision');
    $condicion = SetCondiciones($_REQUEST, $funcionario_consulta, $permiso_gerencia, $permiso_desarrollo, $permiso_revision);

    $query = '
        SELECT 
            SS.*,
            CONCAT_WS(" ", FU.Nombres, FU.Apellidos) AS Funcionario_Solicita,
            CONCAT_WS(" ", FUN.Nombres, FUN.Apellidos) AS Funcionario_Crea,
            IF(SS.Desarrollador_Asignado IS NOT NULL, (SELECT CONCAT_WS(" ", Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = SS.Desarrollador_Asignado), "No Asignado") AS Desarrollador
        FROM Solicitud_Sigespro SS
        INNER JOIN Funcionario FU ON SS.Identificacion_Funcionario_Solicita = FU.Identificacion_Funcionario
        INNER JOIN Funcionario FUN ON SS.Identificacion_Funcionario_Crea = FUN.Identificacion_Funcionario
        '.$condicion.'
        ORDER BY SS.Fecha_Solicitud DESC';

    $query_count = '
        SELECT 
            COUNT(SS.Id_Solicitud_Sigespro) AS Total
        FROM Solicitud_Sigespro SS
        INNER JOIN Funcionario FU ON SS.Identificacion_Funcionario_Solicita = FU.Identificacion_Funcionario
        INNER JOIN Funcionario FUN ON SS.Identificacion_Funcionario_Crea = FUN.Identificacion_Funcionario
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $solicitudes = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($solicitudes);

    function SetCondiciones($req, $funcionario_consulta, $permiso_gerencia, $permiso_desarrollo, $permiso_revision){
        global $util;

        $condicion = ''; 

        $ver_todas = ValidarPermiso($permiso_gerencia, 'Ver') || ValidarPermiso($permiso_desarrollo, 'Ver') || ValidarPermiso($permiso_revision, 'Ver');
        if (!$ver_todas) {
            $condicion = ' WHERE SS.Identificacion_Funcionario_Solicita = '.$funcionario_consulta;
        }

        if (isset($req['funcionario_solicita']) && $req['funcionario_solicita']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', FU.Nombres, FU.Apellidos) LIKE '%".$req['funcionario_solicita']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', FU.Nombres, FU.Apellidos) LIKE '%".$req['funcionario_solicita']."%'";
            }
        }

        if (isset($req['funcionario_crea']) && $req['funcionario_crea']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', FUN.Nombres, FUN.Apellidos) LIKE '%".$req['funcionario_crea']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', FUN.Nombres, FUN.Apellidos) LIKE '%".$req['funcionario_crea']."%'";
            }
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas_separadas = $util->SepararFechas($req['fechas']);
            
            if ($condicion != "") {
                $condicion .= " AND SS.Fecha_Solicitud BETWEEN '".$fechas_separadas[0]."' AND '".$fechas_separadas[1]."'";
            } else {
                $condicion .= " WHERE SS.Fecha_Solicitud BETWEEN '".$fechas_separadas[0]."' AND '".$fechas_separadas[1]."'";
            }
        }

        if (isset($req['area']) && $req['area']) {
            if ($condicion != "") {
                $condicion .= " AND SS.Area_Sistema = '".$req['area']."'";
            } else {
                $condicion .= " WHERE SS.Area_Sistema = '".$req['area']."'";
            }
        }

        if (isset($req['modulo']) && $req['modulo']) {
            if ($condicion != "") {
                $condicion .= " AND SS.Modulo_Sistema = '".$req['modulo']."'";
            } else {
                $condicion .= " WHERE SS.Modulo_Sistema = '".$req['modulo']."'";
            }
        }

        if (isset($req['tipo_solicitud']) && $req['tipo_solicitud']) {
            if ($condicion != "") {
                $condicion .= " AND SS.Tipo_Solicitud = '".$req['tipo_solicitud']."'";
            } else {
                $condicion .= " WHERE SS.Tipo_Solicitud = '".$req['tipo_solicitud']."'";
            }
        }

        if (isset($req['estado']) && $req['estado']) {
            if ($condicion != "") {
                $condicion .= " AND SS.Estado_Solicitud = '".$req['estado']."'";
            } else {
                $condicion .= " WHERE SS.Estado_Solicitud = '".$req['estado']."'";
            }
        }

        return $condicion;
    }
          
?>
