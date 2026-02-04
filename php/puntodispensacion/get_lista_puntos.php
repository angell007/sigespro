<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');

    $query = 'SELECT PD.* , D.Nombre as NombreDepartamento
                FROM Punto_Dispensacion PD
                INNER JOIN Departamento D ON D.Id_Departamento = PD.Departamento'
                .$condicion;

    //Se crea la instancia que contiene los datos de paginacion.
    $paginationObj = new PaginacionData(20, $query_paginacion, $pagina);

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $result = $queryObj->Consultar('Multiple', true, $paginationObj);

    echo json_encode($result);
?>