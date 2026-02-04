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

    $query = 
        'SELECT 
            ARI.*,
            F.Imagen,
            OCI.Codigo AS Codigo_Orden,
            OCI.Fecha_Registro AS Fecha_Orden,
            IF((P.Primer_Nombre IS NULL OR P.Primer_Nombre = ""), P.Nombre, CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido)) AS Nombre_Proveedor,
            (SELECT GROUP_CONCAT(Factura) FROM Factura_Acta_Recepcion_Internacional WHERE Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional) AS Facturas
        FROM Acta_Recepcion_Internacional ARI
        INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion.'
        ORDER BY Fecha_Creacion DESC';
    $query_count = '
        SELECT 
            COUNT(ARI.Id_Acta_Recepcion_Internacional) AS Total
        FROM Acta_Recepcion_Internacional ARI
        INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($actas_realizadas);

    function SetCondiciones($req){
        global $util;

        $condicion = ' WHERE ARI.Estado = "Recibida" '; 

        if (isset($req['codigo_acta']) && $req['codigo_acta']) {
            if ($condicion != "") {
                $condicion .= " AND ARI.Codigo LIKE '%".$req['codigo_acta']."%'";
            } else {
                $condicion .= " WHERE ARI.Codigo LIKE '%".$req['codigo_acta']."%'";
            }
        }

        if (isset($req['codigo_orden']) && $req['codigo_orden']) {
            if ($condicion != "") {
                $condicion .= " AND OCI.Codigo LIKE '%".$req['codigo_orden']."%'";
            } else {
                $condicion .= " WHERE OCI.Codigo LIKE '%".$req['codigo_orden']."%'";
            }
        }

        if (isset($req['facturas']) && $req['facturas']) {
            if ($condicion != "") {
                $condicion .= " AND ARI.Factura LIKE '%".$req['facturas']."%'";
            } else {
                $condicion .= " WHERE ARI.Factura LIKE '%".$req['facturas']."%'";
            }
        }

        if (isset($req['proveedor']) && $req['proveedor']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
            }
        }

        if (isset($req['fechas_acta']) && $req['fechas_acta']) {
            $fechas_separadas = $util->SepararFechas($req['fechas_acta']);
            
            if ($condicion != "") {
                $condicion .= " AND ARI.Fecha_Creacion >= '".$fechas_separadas[0]."' AND ARI.Fecha_Creacion <= '".$fechas_separadas[1]."'";
            } else {
                $condicion .= " WHERE ARI.Fecha_Creacion >= '".$fechas_separadas[0]."' AND ARI.Fecha_Creacion <= '".$fechas_separadas[1]."'";
            }
        }

        if (isset($req['fechas_orden']) && $req['fechas_orden']) {
            $fechas_separadas = $util->SepararFechas($req['fechas_orden']);

            if ($condicion != "") {
                $condicion .= " AND OCI.Fecha_Registro >= '".$fechas_separadas[0]."' AND OCI.Fecha_Registro <= '".$fechas_separadas[1]."'";
            } else {
                $condicion .= " WHERE OCI.Fecha_Registro >= '".$fechas_separadas[0]."' AND OCI.Fecha_Registro <= '".$fechas_separadas[1]."'";
            }
        }

        return $condicion;
    }
          
?>