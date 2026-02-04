<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');


    $pagina = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    //$condicion = SetCondiciones($_REQUEST);

    $query_paginacion ='
        SELECT 
            COUNT(*) AS Total
        FROM Novedad_Sistema '
        .$condicion;

    $query = '
        SELECT 
            NS.*,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,F.Imagen
        FROM Novedad_Sistema NS
        INNER JOIN Funcionario F ON NS.Id_Funcionario = F.Identificacion_Funcionario'
        .$condicion.' ORDER BY NS.Estado_Novedad, NS.Fecha_Creacion DESC';

    //Se crea la instancia que contiene los datos de paginacion.
    $paginationObj = new PaginacionData(20, $query_paginacion, $pagina);

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $result = $queryObj->Consultar('Multiple', true, $paginationObj);

    echo json_encode($result);

    function SetCondiciones($req){

        $condicion = '';

        if (isset($req['nombre_punto_dispensacion']) && $req['nombre_punto_dispensacion'] != "") {
            $condicion .= " WHERE PD.Nombre LIKE '%".$req['nombre_punto_dispensacion']."%'";
        }

        if (isset($req['id_departamento']) && $req['id_departamento']) {
            if ($condicion != "") {
                $condicion .= " AND D.Id_Departamento = ".$req['id_departamento'];
            } else {
                $condicion .=  " WHERE D.Id_Departamento = ".$req['id_departamento'];
            }
        }

        if (isset($req['tipo_dispensacion']) && $req['tipo_dispensacion']) {
            if ($condicion != "") {
                $condicion .= " AND PD.Tipo LIKE '%".$req['tipo_dispensacion']."%'";
            } else {
                $condicion .= " WHERE PD.Tipo LIKE '%".$req['tipo_dispensacion']."%'";
            }
        }

        if (isset($req['direccion']) && $req['direccion']) {
            if ($condicion != "") {
                $condicion .= " AND PD.Direccion LIKE '%".$req['direccion']."%'";
            } else {
                $condicion .= " WHERE PD.Direccion LIKE '%".$req['direccion']."%'";
            }
        }

        if (isset($req['telefono']) && $req['telefono']) {
            if ($condicion != "") {
                $condicion .= " AND PD.Telefono LIKE '%".$req['telefono']."%'";
            } else {
                $condicion .= " WHERE PD.Telefono LIKE '%".$req['telefono']."%'";
            }
        }

        if (isset($req['no_pos']) && $req['no_pos']) {
            if ($condicion != "") {
                $condicion .= " AND PD.No_Pos = '".$req['no_pos']."'";
            } else {
                $condicion .= " WHERE PD.No_Pos = '".$req['no_pos']."'";
            }
        }

        if (isset($req['turnero']) && $req['turnero']) {
            if ($condicion != "") {
                $condicion .= " AND PD.Turnero = '".$req['turnero']."'";
            } else {
                $condicion .= " WHERE PD.Turnero = '".$req['turnero']."'";
            }
        }

        if (isset($req['wacom']) && $req['wacom']) {
            if ($condicion != "") {
                $condicion .= " AND PD.Wacom = '".$req['wacom']."'";
            } else {
                $condicion .= " WHERE PD.Wacom = '".$req['wacom']."'";
            }
        }

        return $condicion;
    }
?>