<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Reporte_Radicacion.xls"');
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
        $condicion = '
            A.Estado!="Pre Auditado" 
            AND A.Id_Dispensacion IS NOT NULL 
            AND  DATE(A.Fecha_Preauditoria)>"2019-04-14"
            AND D.Estado_Dispensacion!="Anulada"';

        if(isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != ""){
            $tipo=$_REQUEST['tipo'];
           if($tipo=='Evento'){
               $condicion.=" AND D.Tipo='Evento'";
           }else if($tipo=='Cohortes'){
            $condicion.=" AND D.Tipo_Servicio=6 OR D.Tipo='Cohortes' ";
           }else{
            $condicion.=" AND D.Tipo_Servicio=$tipo  ";
           }
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas = SepararFechas($req['fechas']);

            if ($condicion != "") {
                $condicion .= " AND DATE(R.Fecha_Preauditoria) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            } else {
                $condicion .= " WHERE DATE(R.Fecha_Preauditoria) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
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

        $query_auditorias = '
            SELECT    
                CONCAT(D.Codigo," - ",IFNULL((SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio=D.Tipo_Servicio),D.Tipo)) as Dis,
                CONCAT_WS(" ", P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido) AS Nombre_Paciente,
                D.EPS,
                A.Estado,
                D.Estado AS Estado_Dispensacion,
                (SELECT COUNT(*) FROM Actividad_Auditoria WHERE Detalle LIKE "%errores%" AND Id_Auditoria = A.Id_Auditoria) AS Conteo_Errores,
                (SELECT COUNT(*) FROM Actividad_Auditoria WHERE Detalle LIKE "%correcta%" AND Id_Auditoria = A.Id_Auditoria) AS Conteo_Correctos,
                (SELECT MAX(DATE(Fecha)) FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria) AS Fecha_Validacion,
                DATE(A.Fecha_Preauditoria) AS Fecha_Auditoria
            FROM Auditoria A
            INNER JOIN Funcionario FP ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
            LEFT JOIN Funcionario FA ON A.Funcionario_Auditoria=FA.Identificacion_Funcionario
            INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion
            INNER JOIN Paciente P ON A.Id_Paciente=P.Id_Paciente
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
            <td align="center"><strong>Paciente</strong></td>
            <td align="center"><strong>Eps</strong></td>
            <td align="center"><strong>Estado</strong></td>
            <td align="center"><strong>Estado Dispensacion</strong></td>
            <td align="center"><strong>Validaciones errores</strong></td>
            <td align="center"><strong>Validaciones correctas</strong></td>
            <td align="center"><strong>Fecha Auditoria</strong></td>
            <td align="center"><strong>Fecha Validacion</strong></td>
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Dis'].'</td>
                    <td>'.$r['Nombre_Paciente'].'</td>
                    <td>'.$r["EPS"].'</td>
                    <td>'.$r["Estado"].'</td>
                    <td>'.$r["Estado_Dispensacion"].'</td>
                    <td>'.$r["Conteo_Errores"].'</td>
                    <td>'.$r["Conteo_Correctos"].'</td>
                    <td>'.$r["Fecha_Auditoria"].'</td>
                    <td>'.$r["Fecha_Validacion"].'</td>
                </tr>';
            } 
        }else{
    
            $contenido_excel .= '
            <tr>
                <td colspan="11" align="center">SIN RESULTADOS PARA MOSTRAR</td>
            </tr>';
        }        
           
    
        $contenido_excel .= '
        </table>';

        echo $contenido_excel;
    }

?>