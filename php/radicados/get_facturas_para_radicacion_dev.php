<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    date_default_timezone_set('America/Bogota');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');

    $http_response = new HttpResponse();
    $condicion_capita = '';

    /*$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );*/
    $tipo = ( isset( $_REQUEST['tipo_servicio'] ) ? $_REQUEST['tipo_servicio'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $fecha = date('Y-m-d');

    $query = '';

    if (strtolower($tipo) != 'capita') {
        
        $query = '
            SELECT 
                F.Id_Factura,
                F.Codigo AS Codigo_Factura,
                D.Codigo AS Codigo_Dis,
                UPPER(CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido)) AS Nombre_Paciente,
                false AS Seleccionada,
                (
                    CASE
                        WHEN C.Tipo_Valor = "Exacta" THEN (SELECT SUM( ((Precio * Cantidad)+((Precio * Cantidad - IF(F.Id_Cliente = 890500890,FLOOR(Descuento*Cantidad), (Descuento*Cantidad)) ) * (Impuesto/100) )) - (IF(F.Id_Cliente = 890500890, FLOOR(Descuento* Cantidad), Descuento* Cantidad))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
                        ELSE (SELECT ROUND(SUM( ((ROUND(Precio) * Cantidad)+((ROUND(Precio) * Cantidad- ROUND((Descuento*Cantidad))) * (Impuesto/100) )) - ROUND((Descuento*Cantidad)))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
                    END
                ) AS Valor_Factura,
                C.Tipo_Valor
            FROM Factura F
            INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
            INNER JOIN Dispensacion D ON F.Id_Dispensacion = D.Id_Dispensacion
            INNER JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente'
            .$condicion;
    }else{
        $query = '
            SELECT 
                F.Id_Factura_Capita AS Id_Factura,
                F.Codigo AS Codigo_Factura,
                IFNULL(F.Codigo, "") AS Codigo_Dis,
                IFNULL(DFC.Descripcion, "") AS Nombre_Paciente,
                false AS Seleccionada,
                (SUM(DFC.Total) - F.Cuota_Moderadora) AS Valor_Factura,
                C.Tipo_Valor
            FROM Factura_Capita F
            INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
            INNER JOIN Descripcion_Factura_Capita DFC ON F.Id_Factura_Capita = DFC.Id_Factura_Capita'
            .$condicion_capita
            .' GROUP BY DFC.Id_Factura_Capita';
    }

    

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $result = $queryObj->Consultar('Multiple');

    // if (count($result['query_result']) > 0) {
    //     $i = 0;
    //     foreach ($result['query_result'] as $value) {
            
    //         $result['query_result'][$i]['Valor_Factura'] = $value['Valor_Factura'] - $value['Valor_Homologo'];
    //     }
    // }

    echo json_encode($result);

    function SetCondiciones($req){
        global $condicion_capita;

        $condicion = '';
        //$condicion = ' WHERE F.Estado_Radicacion = "Pendiente" AND F.Estado_Factura != "Anulada" AND Id_Factura_Asociada IS NOT NULL ';


        $condicion = ' WHERE F.Estado_Radicacion = "Pendiente" AND F.Estado_Factura != "Anulada"';
        $condicion_capita = ' WHERE F.Estado_Radicacion = "Pendiente" AND F.Estado_Factura != "Anulada"';

        if (isset($req['id_regimen']) && $req['id_regimen']) {
            $condicion_capita .= ' AND F.Id_Regimen = '.$req['id_regimen'];
            if ($condicion != "") {
                $condicion .= " AND P.Id_Regimen = ".$req['id_regimen'];
            } else {
                $condicion .= " WHERE P.Id_Regimen = ".$req['id_regimen'];
            }
        }

        if (isset($req['tipo_servicio']) && $req['tipo_servicio']) {

            $tipo = strtolower($req['tipo_servicio']);
            
            if ($condicion != "") {

                if ($tipo == 'evento' || $tipo == 'capita') {
                    $condicion .= " AND D.Tipo = '".$tipo."'";

                }else if ($tipo == '6') {
                    $condicion .= " AND (D.Tipo = 'COHORTES' OR Tipo_Servicio = 6)";
                }else if (intval($tipo)) {
                    $condicion .= " AND D.Tipo = 'NoPos' AND D.Tipo_Servicio = ".$tipo;
                }
            } else {
                if ($tipo == 'evento') {
                    $condicion .= " WHERE D.Tipo = '".$tipo."'";

                }else if ($tipo == '6') {
                    $condicion .= " AND (D.Tipo = 'COHORTES' OR Tipo_Servicio = 6)";
                }else if (intval($tipo)) {
                    $condicion .= " WHERE D.Tipo = 'NoPos' AND D.Tipo_Servicio = ".$tipo;
                }
            }
        }

        if (isset($req['id_departamento']) && $req['id_departamento']) {
            $condicion_capita .= ' AND F.Id_Departamento = '.$req['id_departamento'];
            if ($condicion != "") {
                $condicion .= " AND P.Id_Departamento = ".$req['id_departamento'];
            } else {
                $condicion .= " WHERE P.Id_Departamento = ".$req['id_departamento'];
            }
        }

        if (isset($req['id_cliente']) && $req['id_cliente']) {
            $condicion_capita .= ' AND F.Id_Cliente = '.$req['id_cliente'];
            if ($condicion != "") {
                $condicion .= " AND F.Id_Cliente = ".$req['id_cliente'];
            } else {
                $condicion .= " WHERE F.Id_Cliente = ".$req['id_cliente'];
            }
        }

        if (isset($req['id_eps']) && $req['id_eps']) {
            if ($condicion != "") {
                $condicion .= " AND P.Nit = ".$req['id_eps'];
            } else {
                $condicion .= " WHERE P.Nit = ".$req['id_eps'];
            }
        }

        return $condicion;
    }
?>