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
            SS.*,
            CONCAT_WS(" ", FU.Nombres, FU.Apellidos) AS Funcionario_Solicita,
            CONCAT_WS(" ", FUN.Nombres, FUN.Apellidos) AS Funcionario_Crea,
            IFNULL(SS.Desarrollador_Asignado, "No Asignado") AS Desarrollador_Asignado
        FROM Solicitud_Sigespro SS
        INNER JOIN Funcionario FU ON SS.Identificacion_Funcionario_Solicita = FU.Identificacion_Funcionario
        INNER JOIN Funcionario FUN ON SS.Identificacion_Funcionario_Crea = FUN.Identificacion_Funcionario
        WHERE
            SS.Id_Solicitud_Sigespro = '.$id_solicitud;
    
    $queryObj = new QueryBaseDatos($query);
    $solicitud = $queryObj->Consultar('simple');

    $habilitarIncidencia = HabilitarReporteIncidencia($solicitud['query_result']['Fecha_Solicitud']);
    $solicitud['query_result']['Habilitar_Incidencia'] = $habilitarIncidencia;
    $solicitud['Incidencias'] = GetIncidencias($solicitud['query_result']['Id_Solicitud_Sigespro']);

    echo json_encode($solicitud);

    function HabilitarReporteIncidencia($fecha_solicitud){
        $fecha_tope_incidencia = date('Y-m-d H:i:s', strtotime($fecha_solicitud.' + 24 hours'));

        $f1 = new DateTime(date('Y-m-d H:i:s'));
        $f2 = new DateTime($fecha_tope_incidencia);

        $diferencia = $f1->diff($f2);        
        $dias_diferencia = $diferencia->format('%d');
        $horas_diferencia = $diferencia->format('%H');

        if (intval($dias_diferencia) > 0) {
            return false;
        }else{

            if (intval($horas_diferencia) > 8) {
                return false;
            }else{
                return true;
            }
        }
    }

    function GetIncidencias($idSolicitud){
        global $queryObj;

        $query = '
                SELECT 
                    *
                FROM Incidencia_Solicitud
                WHERE
                    Id_Solicitud_Sigespro = '.$idSolicitud;
            
        $queryObj->SetQuery($query);
        $incidencias = $queryObj->ExecuteQuery('multiple');
        return $incidencias;
    }
?>