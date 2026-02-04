<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');


    $pagina = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $condicion = SetCondiciones($_REQUEST);

    $query_paginacion ='SELECT COUNT(*) AS Total
                        FROM Plan_Cuentas'
                        .$condicion;

    $query = 'SELECT *
                FROM Plan_Cuentas'
                .$condicion . ' ORDER BY Codigo';

    //Se crea la instancia que contiene los datos de paginacion.
    //Toma como parametros la cantidad de items por pagina, la consulta de paginacion y la pagina actual
    $paginationObj = new PaginacionData(20, $query_paginacion, $pagina);

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $result = $queryObj->Consultar('Multiple', true, $paginationObj);

    echo json_encode($result);

    function SetCondiciones($req){

        $condicion = '';

        if (isset($req['nombre']) && $req['nombre'] != "") {
            $condicion .= " WHERE Nombre LIKE '%".$req['nombre']."%'";
        }

        if (isset($req['nombre_niif']) && $req['nombre_niif']) {
            if ($condicion != "") {
                $condicion .= " AND Nombre_Niif LIKE '%".$req['nombre_niif']."%'";
            } else {
                $condicion .=  " WHERE Nombre_Niif LIKE '%".$req['nombre_niif']."%'";
            }
        }

        if (isset($req['cod']) && $req['cod']) {
            if ($condicion != "") {
                $condicion .= " AND Codigo LIKE '".$req['cod'] ."%'";
            } else {
                $condicion .= " WHERE Codigo LIKE '".$req['cod'] ."%'";
            }
        }

        if (isset($req['cod_niif']) && $req['cod_niif']) {
            if ($condicion != "") {
                $condicion .= " AND Codigo_Niif LIKE '".$req['cod_niif'] ."%'";
            } else {
                $condicion .= " WHERE Codigo_Niif LIKE '".$req['cod_niif'] ."%'";
            }
        }

        if (isset($req['estado']) && $req['estado']) {
            if ($condicion != "") {
                $condicion .= " AND Estado = '".$req['estado']."'";
            } else {
                $condicion .= " WHERE Estado = '".$req['estado']."'";
            }
        }

        return $condicion;
    }
?>