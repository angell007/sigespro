<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = '
        SELECT 
            D.*,Concat(F.Nombres," ",F.Apellidos) as Funcionario, F.Imagen
        FROM Devolucion_Interna D
        INNER JOIN Funcionario F ON D.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;

    $query_count = '
        SELECT 
            COUNT(D.Id_Devolucion_Interna) AS Total
            FROM Devolucion_Interna D
            INNER JOIN Funcionario F ON D.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $depreciaciones = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($depreciaciones);

    function SetCondiciones($req){
        global $util;

        $condicion = ''; 

        if (isset($req['codigo']) && $req['codigo']) {
            if ($condicion != "") {
                $condicion .= " AND D.Codigo LIKE '%".$req['codigo']."%'";
            } else {
                $condicion .= " WHERE D.Codigo LIKE '%".$req['codigo']."%'";
            }
        }

        if (isset($req['destino']) && $req['destino']) {
            if ($condicion != "") {
                $condicion .= " AND D.Nombre_Destino LIKE '%".$req['destino']."%'";
            } else {
                $condicion .= " WHERE D.Nombre_Destino LIKE '%".$req['destino']."%'";
            }
        }

        if (isset($req['origen']) && $req['origen']) {
            if ($condicion != "") {
                $condicion .= " AND D.Nombre_Origen LIKE '%".$req['origen']."%'";
            } else {
                $condicion .= " WHERE D.Nombre_Origen LIKE '%".$req['origen']."%'";
            }
        }

        if (isset($req['funcionario']) && $req['funcionario']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', F.Nombres, F.Apellidos) LIKE '%".$req['funcionario']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', F.Nombres, F.Apellidos) LIKE '%".$req['funcionario']."%'";
            }
        }

        if (isset($req['fecha']) && $req['fecha']) {
            $fechas_separadas = $util->SepararFechas($req['fecha']);
            
            if ($condicion != "") {
                $condicion .= " AND D.Fecha >= '".$fechas_separadas[0]."' AND D.Fecha <= '".$fechas_separadas[1]."'";
            } else {
                $condicion .= " WHERE D.Fecha >= '".$fechas_separadas[0]."' AND D.Fecha <= '".$fechas_separadas[1]."'";
            }
        }

       

        return $condicion;
    }



          
?>