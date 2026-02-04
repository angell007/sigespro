<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Reporte_Atencion.xls"');
    header('Cache-Control: max-age=0');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.querybasedatos.php');

    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();

    $condiciones = SetConditions();
    $atencion_turneros = GetAuditorias($condiciones);
    ArmarTablaResultados($atencion_turneros);

    function SetConditions(){
        $req = $_REQUEST;
        $condicion = '';

        if(isset($_REQUEST['id_turneros']) && $_REQUEST['id_turneros'] != ""){
             if ($condicion != "") {
                $condicion .= " AND TS.Id_Turneros = ".$_REQUEST['id_turneros'];
            } else {
                $condicion .= " WHERE TS.Id_Turneros = ".$_REQUEST['id_turneros'];
            }
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas = SepararFechas($req['fechas']);

            if ($condicion != "") {
                $condicion .= " AND DATE(T.Fecha) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            } else {
                $condicion .= " WHERE DATE(T.Fecha) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            }
        }

        return $condicion;
    }

    function SepararFechas($fechas){
        $fechas_separadas = explode(" - ", $fechas);
        return $fechas_separadas;
    }

    function GetAuditorias($condiciones){
        global $queryObj;

        $query_atencion = '
            SELECT DISTINCT
                IFNULL(CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido),"No Aplica") AS Paciente,
                T.Persona as Reclamante,
                IF(T.Hora_Turno = "23:59:59", "Auditoria", T.Hora_Turno) AS Hora_Turno,
                IF(T.Hora_Inicio_Atencion = "00:00:00", "No Atendido", T.Hora_Inicio_Atencion) Hora_Inicio_Atencion,
                T.Fecha,
                TS.Nombre,
                T.Tipo
            FROM Turnero T
            INNER JOIN Turneros TS ON TS.Id_Turneros=T.Id_Turneros
            INNER JOIN Punto_Turnero PT ON TS.Id_Turneros=PT.Id_Turneros
            LEFT JOIN Auditoria A ON T.Id_Auditoria=A.Id_Auditoria
            LEFT JOIN Paciente P ON A.Id_Paciente=P.Id_Paciente
            '.$condiciones.' 
            ORDER BY T.Hora_Turno DESC';

        $queryObj->SetQuery($query_atencion);
        $atenciones = $queryObj->ExecuteQuery('multiple');

        return $atenciones;
    }

    function ArmarTablaResultados($resultados){

        $contenido_excel = '';

        $contenido_excel = '
        <table border=1>
        <tr>
            <td align="center"><strong>Paciente</strong></td>
            <td align="center"><strong>Reclamente</strong></td>
            <td align="center"><strong>Fecha</strong></td>
            <td align="center"><strong>Tipo</strong></td>
            <td align="center"><strong>Hora Turno</strong></td>
            <td align="center"><strong>Hora Atencion</strong></td>
            <td align="center"><strong>Turnero</strong></td>
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Paciente'].'</td>
                    <td>'.$r['Reclamante'].'</td>
                    <td>'.$r['Fecha'].'</td>
                     <td>'.$r['Tipo'].'</td>
                    <td>'.$r['Hora_Turno'].'</td>
                    <td>'.$r["Hora_Inicio_Atencion"].'</td>
                    <td>'.$r["Nombre"].'</td>
                </tr>';
            } 
        }else{
    
            $contenido_excel .= '
            <tr>
                <td colspan="4" align="center">SIN RESULTADOS PARA MOSTRAR</td>
            </tr>';
        }        
           
    
        $contenido_excel .= '
        </table>';

        echo $contenido_excel;
    }

?>