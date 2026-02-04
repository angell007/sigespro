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
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones($req){
        global $util;

        $condicion = 'WHERE D.Estado="Pendiente"'; 

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
     
        return $condicion;
    }
          
?>