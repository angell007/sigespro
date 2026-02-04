<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_superauditoria.php.xls"');
    header('Cache-Control: max-age=0');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.querybasedatos.php');

    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();

    $condiciones = SetConditions();
    $auditorias = GetAuditorias($condiciones);
    ArmarTablaResultados($auditorias);

    function SetConditions(){
        $req = $_REQUEST;
        $condicion = 'A.Estado!="Pre Auditado"
            AND A.Id_Dispensacion IS NOT NULL 
            AND  DATE(A.Fecha_Preauditoria)>"2019-04-14"
           ';

        if(isset($_REQUEST['tipo_servicio']) && $_REQUEST['tipo_servicio'] != ""){
            $tipo=$_REQUEST['tipo_servicio'];          
            $condicion.=" AND A.Id_Tipo_Servicio=$tipo  ";
        }

        if(isset($_REQUEST['Punto']) && $_REQUEST['Punto'] != ""){
            $punto=$_REQUEST['Punto'];          
            $condicion.=" AND PT.Id_Punto_Dispensacion=$punto";
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas = SepararFechas($req['fechas']);

            if ($condicion != "") {
                $condicion .= " HAVING DATE(Fecha_Actividad) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
                //$condicion .= " AND DATE(A.Fecha_Preauditoria) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            } else {
                $condicion .= " HAVING DATE(Fecha_Actividad) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
                //$condicion .= " WHERE DATE(A.Fecha_Preauditoria) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
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

        $query_auditorias = 'SELECT    
                D.Codigo as Dis,
                CONCAT_WS(" ", P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido) AS Nombre_Paciente,
                (SELECT CONCAT(S.Nombre," - ",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=A.Id_Tipo_Servicio ) AS Tipo_Servicio,
                D.EPS,
                A.Estado, CONCAT("AUD00",A.Id_Auditoria) as Auditoria,
                D.Estado_Dispensacion AS Estado_Dispensacion,
                (SELECT COUNT(*) FROM Actividad_Auditoria WHERE Detalle LIKE "%errores%" AND Id_Auditoria = A.Id_Auditoria) AS Conteo_Errores,
                (SELECT COUNT(*) FROM Actividad_Auditoria WHERE Detalle LIKE "%correcta%" AND Id_Auditoria = A.Id_Auditoria) AS Conteo_Correctos,
                (SELECT MAX(Fecha) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) AS Fecha_Validacion,
                A.Fecha_Preauditoria AS Fecha_Auditoria,
                (SELECT Fecha FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria ORDER BY Id_Actividad_Auditoria ASC LIMIT 1) AS Fecha_Actividad,
                (SELECT CONCAT_WS(" ", FU.Nombres, FU.Apellidos) FROM Actividad_Auditoria AA INNER JOIN Funcionario FU ON AA.Identificacion_Funcionario = FU.Identificacion_Funcionario WHERE AA.Id_Auditoria = A.Id_Auditoria LIMIT 1) AS Funcionario_Actividad,
                PT.Punto_Dispensacion,
                PT.Departamento, F.Funcionario as Funcionario_Preauditoria,  (SELECT GROUP_CONCAT(Observacion) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) as Observaciones_Auditor, (SELECT GROUP_CONCAT(Errores) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) as Tipo_Error
            FROM Auditoria A
            INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion
            INNER JOIN Paciente P ON D.Numero_Documento=P.Id_Paciente
            INNER JOIN (SELECT P.Id_Punto_Dispensacion, P.Nombre AS Punto_Dispensacion, D.Nombre AS Departamento FROM Punto_Dispensacion P INNER JOIN Departamento D ON D.Id_Departamento = P.Departamento) PT ON D.Id_Punto_Dispensacion = PT.Id_Punto_Dispensacion
            INNER JOIN (SELECT CONCAT_WS(" ",Nombres,Apellidos) as Funcionario,Identificacion_Funcionario FROM Funcionario ) F
            ON A.Funcionario_Preauditoria=F.Identificacion_Funcionario
            WHERE
                '.$condiciones.' 
            ORDER BY A.Id_Auditoria ASC';

        $queryObj->SetQuery($query_auditorias);
        $auditorias = $queryObj->ExecuteQuery('multiple');
        return $auditorias;
    }

    function ArmarTablaResultados($resultados){

        $contenido_excel = '';

        $contenido_excel = '
        <table border=1>
        <tr>
            <td align="center"><strong>Codigo Dispensacion</strong></td>
            <td align="center"><strong>Auditoria</strong></td>
            <td align="center"><strong>Tipo Servicio</strong></td>
            <td align="center"><strong>Paciente</strong></td>
            <td align="center"><strong>Eps</strong></td>
            <td align="center"><strong>Estado</strong></td>
            <td align="center"><strong>Estado Dispensacion</strong></td>
            <td align="center"><strong>Validaciones errores</strong></td>
            <td align="center"><strong>Validaciones correctas</strong></td>
            <td align="center"><strong>Fecha Auditoria</strong></td>
            <td align="center"><strong>Fecha Validacion</strong></td>
            <td align="center"><strong>Funcionario Preauditoria</strong></td>
            <td align="center"><strong>Funcionario</strong></td>
            <td align="center"><strong>Departamento</strong></td>
            <td align="center"><strong>Punto Dispensacion</strong></td>
            <td align="center"><strong>Observaciones Auditor</strong></td>
            <td align="center"><strong>Tipo Error</strong></td>
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Dis'].'</td>
                    <td>'.$r['Auditoria'].'</td>
                    <td>'.$r['Tipo_Servicio'].'</td>
                    <td>'.$r['Nombre_Paciente'].'</td>
                    <td>'.$r["EPS"].'</td>
                    <td>'.$r["Estado"].'</td>
                    <td>'.$r["Estado_Dispensacion"].'</td>
                    <td>'.$r["Conteo_Errores"].'</td>
                    <td>'.$r["Conteo_Correctos"].'</td>
                    <td>'.$r["Fecha_Auditoria"].'</td>
                    <td>'.$r["Fecha_Validacion"].'</td>
                    <td>'.$r["Funcionario_Preauditoria"].'</td>
                    <td>'.$r["Funcionario_Actividad"].'</td>
                    <td>'.$r["Departamento"].'</td>
                    <td>'.$r["Punto_Dispensacion"].'</td>
                    <td>'.$r["Observaciones_Auditor"].'</td>
                    <td>'.$r["Tipo_Error"].'</td>
                </tr>';
            } 
        }else{
    
            $contenido_excel .= '
            <tr>
                <td colspan="13" align="center">SIN RESULTADOS PARA MOSTRAR</td>
            </tr>';
        }        
           
    
        $contenido_excel .= '
        </table>';

        echo $contenido_excel;
    }

?>