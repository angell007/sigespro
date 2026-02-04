<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../../class/class.querybasedatos.php');
    include_once('../../../class/class.paginacion.php');
    include_once('../../../class/class.http_response.php');
    include_once('../../../class/class.utility.php');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query_mensajes = '
        SELECT    
            M.Mensaje,
            DATE(M.Fecha) AS Fecha,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Funcionario,
            IF(M.Id_Paciente IS NULL, "Sin Paciente", IFNULL((SELECT CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) FROM Paciente WHERE Id_Paciente = M.Id_Paciente), "Sin Paciente") ) AS Paciente
        FROM Mensaje M
        INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
        '.$condicion;

    $query_count = '
        SELECT    
            COUNT(Id_Mensaje) AS Total
        FROM Mensaje M
        INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query_mensajes);
    $mensajes = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($mensajes);

    function SetCondiciones($req){
        global $util;

        $condicion = '';

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas_separadas = $util->SepararFechas($req['fechas']);
            
            if ($condicion != "") {
                $condicion .= " AND M.Fecha BETWEEN '".$fechas_separadas[0]."' AND '".$fechas_separadas[1]."'";
            } else {
                $condicion .= " WHERE M.Fecha BETWEEN '".$fechas_separadas[0]."' AND '".$fechas_separadas[1]."'";
            }
        }

        if (isset($req['funcionario']) && $req['funcionario']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', F.Nombres, F.Apellidos) LIKE '%".$req['funcionario']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', F.Nombres, F.Apellidos) LIKE '%".$req['funcionario']."%'";
            }
        }

        return $condicion;
    }
          
?>