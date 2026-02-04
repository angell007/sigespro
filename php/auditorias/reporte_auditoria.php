<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/json');


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
        $condicion = '';

        if(isset($_REQUEST['punto']) && $_REQUEST['punto'] != ""){
            if($_REQUEST['punto']!='todos'){
                $condicion.=" WHERE A.Punto_Pre_Auditoria=$_REQUEST[punto] ";
            }
             
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas = SepararFechas($req['fechas']);

            if ($condicion != "") {
                $condicion .= " AND A.Fecha_Preauditoria BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
                //$condicion .= " AND DATE(A.Fecha_Preauditoria) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            } else {
                $condicion .= " WHERE DATE(A.Fecha_Preauditoria) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
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

        $query_auditorias = 
            'SELECT DATE(A.Fecha_Preauditoria) as Fecha, CONCAT("AUD00",A.Id_Auditoria) as Auditoria,
            Id_Paciente,(SELECT CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) FROM Paciente WHERE Id_Paciente=A.Id_Paciente) as Paciente, IFNULL(D.Codigo,"Sin Dispensacion Asociada") as Disp, IFNULL(D.Pendientes,0) as Pendientes, A.Estado,(SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=A.Punto_Pre_Auditoria) as Punto,CONCAT(F.Nombres," ",F.Apellidos) AS Funcionario, (SELECT Fecha FROM Actividad_Auditoria WHERE Id_Auditoria = A.Id_Auditoria ORDER BY Id_Actividad_Auditoria ASC LIMIT 1) AS Fecha_Actividad,
            (SELECT CONCAT_WS(" ", FU.Nombres, FU.Apellidos) FROM Actividad_Auditoria AA INNER JOIN Funcionario FU ON AA.Identificacion_Funcionario = FU.Identificacion_Funcionario WHERE AA.Id_Auditoria = A.Id_Auditoria LIMIT 1) AS Funcionario_Actividad,(SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio=A.Id_Tipo_Servicio) as Tipo_Servicio,(SELECT Nombre FROM Servicio WHERE Id_Servicio=A.Id_Servicio) as Servicio,IFNULL(D.Eps,"") as Eps, PT.*
            FROM Auditoria A 
            LEFT JOIN Dispensacion D ON  A.Id_Dispensacion=D.Id_Dispensacion
            INNER JOIN Funcionario F ON A.Funcionario_Preauditoria=F.Identificacion_Funcionario 
            INNER JOIN (SELECT P.Id_Punto_Dispensacion, P.Nombre AS Punto_Dispensacion, D.Nombre AS Departamento FROM Punto_Dispensacion P INNER JOIN Departamento D ON D.Id_Departamento = P.Departamento) PT ON A.Punto_Pre_Auditoria = PT.Id_Punto_Dispensacion
                '.$condiciones.' 
            ORDER BY A.Id_Auditoria ASC';

        
            echo $query_auditorias, exit;

        $queryObj->SetQuery($query_auditorias);
        $auditorias = $queryObj->ExecuteQuery('multiple');
        return $auditorias;
    }

    function ArmarTablaResultados($resultados){

        $contenido_excel = '';

        $contenido_excel = '
        <table border=1>
        <tr>
            <td align="center"><strong>Auditoria</strong></td>
            <td align="center"><strong>Fecha Auditoria</strong></td>
            <td align="center"><strong>Identificacion Paciente </strong></td>
            <td align="center"><strong>Paciente</strong></td>
            <td align="center"><strong>Servicio</strong></td>
            <td align="center"><strong>Tipo Servicio</strong></td>
            <td align="center"><strong>Eps</strong></td>
            <td align="center"><strong>Estado Auditoria</strong></td>
            <td align="center"><strong>Dispensacion</strong></td>
            <td align="center"><strong>Pendientes</strong></td>
            <td align="center"><strong>Punto Dispensacion</strong></td>
            <td align="center"><strong>Departamento</strong></td>
            <td align="center"><strong>Funcionario Auditoria</strong></td>
            <td align="center"><strong>Funcionario Validacion</strong></td>
            <td align="center"><strong>Fecha Validacion</strong></td>
            
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Auditoria'].'</td>
                    <td>'.$r['Fecha'].'</td>
                    <td>'.$r['Id_Paciente'].'</td>
                    <td>'.$r["Paciente"].'</td>
                    <td>'.$r["Servicio"].'</td>
                    <td>'.$r["Tipo_Servicio"].'</td>
                    <td>'.$r["Eps"].'</td>
                    <td>'.$r["Estado"].'</td>
                    <td>'.$r["Disp"].'</td>
                    <td>'.$r["Pendientes"].'</td>
                    <td>'.$r["Punto_Dispensacion"].'</td>
                    <td>'.$r["Departamento"].'</td>
                    <td>'.$r["Funcionario"].'</td>
                    <td>'.$r["Funcionario_Actividad"].'</td>
                    <td>'.$r["Fecha_Actividad"].'</td>
                   
                </tr>';
            } 
        }else{
    
            $contenido_excel .= '
            <tr>
                <td colspan="8" align="center">SIN RESULTADOS PARA MOSTRAR</td>
            </tr>';
        }        
           
    
        $contenido_excel .= '
        </table>';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_superauditoria.php.xls"');
        header('Cache-Control: max-age=0'); 

        echo $contenido_excel;
    }

?>