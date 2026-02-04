<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_envio_mensajes.php.xls"');
    header('Cache-Control: max-age=0');

    require_once('../../../config/start.inc.php');
    include_once('../../../class/class.http_response.php');
    include_once('../../../class/class.querybasedatos.php');

    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();

    $condiciones = SetConditions();
   
    $Mensajes = GetMensajes($condiciones);

    ArmarTablaResultados($Mensajes);

    function SetConditions(){
        $req = $_REQUEST;
        $condicion = '';

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas = SepararFechas($req['fechas']);

            if ($condicion != "") {
                $condicion .= " AND DATE(M.Fecha) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            } else {
                $condicion .= " WHERE DATE(M.Fecha) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            }
        }

        return $condicion;
    }



    function SepararFechas($fechas){
        $fechas_separadas = explode(" - ", $fechas);
        return $fechas_separadas;
    }

    function GetMensajes($condiciones){
        global $queryObj;

        $query_mensajes = 'SELECT    
                M.*,
                CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Funcionario,
                IF(M.Id_Paciente IS NULL, "Sin Paciente", (SELECT CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) FROM Paciente WHERE Id_Paciente = M.Id_Paciente) ) AS Paciente, M.Id_Paciente as Identificacion_Paciente
            FROM Mensaje M
            INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
            '.$condiciones;


        $queryObj->SetQuery($query_mensajes);
        $mensajes = $queryObj->ExecuteQuery('multiple');
        return $mensajes;
    }

    function ArmarTablaResultados($resultados){

        $contenido_excel = '';

        $contenido_excel = '
        <table border=1>
        <tr>
            <td align="center"><strong>Funcionario Envia</strong></td>
            <td align="center"><strong>Paciente</strong></td>
            <td align="center"><strong>Fecha Envio</strong></td>
            <td align="center"><strong>Mensaje</strong></td>
            <td align="center"><strong>Identificacion Paciente</strong></td>
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Funcionario'].'</td>
                    <td>'.$r['Paciente'].'</td>
                    <td>'.$r['Fecha'].'</td>
                    <td>'.$r["Mensaje"].'</td>
                    <td>'.$r["Identificacion_Paciente"].'</td>
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