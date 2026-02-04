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

    $query = '
        SELECT 
            NP.*,
            ARI.Codigo AS Codigo_Acta,
            F.Imagen,
            (SELECT SUM(Subtotal) FROM Producto_Nacionalizacion_Parcial WHERE Id_Nacionalizacion_Parcial = NP.Id_Nacionalizacion_Parcial) AS Total_Parcial
        FROM Nacionalizacion_Parcial NP
        INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
        INNER JOIN Funcionario F ON NP.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion. ' ORDER BY Fecha_Registro DESC';

    $query_count = '
        SELECT 
            COUNT(Id_Nacionalizacion_Parcial) AS Total
        FROM Nacionalizacion_Parcial NP
        INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
        INNER JOIN Funcionario F ON NP.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $parciales = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($parciales);

    function SetCondiciones($req){
        global $util;

        $condicion = ''; 

        if (isset($req['codigo_acta']) && $req['codigo_acta']) {
            if ($condicion != "") {
                $condicion .= " AND ARI.Codigo LIKE '%".$req['codigo_acta']."%'";
            } else {
                $condicion .= " WHERE ARI.Codigo LIKE '%".$req['codigo_acta']."%'";
            }
        }

        if (isset($req['codigo_parcial']) && $req['codigo_parcial']) {
            if ($condicion != "") {
                $condicion .= " AND NP.Codigo LIKE '%".$req['codigo_parcial']."%'";
            } else {
                $condicion .= " WHERE NP.Codigo LIKE '%".$req['codigo_parcial']."%'";
            }
        }

        if (isset($req['estado']) && $req['estado']) {
            if ($condicion != "") {
                $condicion .= " AND NP.Estado = '".$req['estado']."'";
            } else {
                $condicion .= " WHERE NP.Estado = '".$req['estado']."'";
            }
        }

        if (isset($req['tasa']) && $req['tasa']) {
            if ($condicion != "") {
                $condicion .= " AND NP.Tasa_Cambio = ".$req['tasa'];
            } else {
                $condicion .= " WHERE NP.Tasa_Cambio = ".$req['tasa'];
            }
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas_separadas = $util->SepararFechas($req['fechas']);
            
            if ($condicion != "") {
                $condicion .= " AND NP.Fecha_Registro >= '".$fechas_separadas[0]."' AND NP.Fecha_Registro <= '".$fechas_separadas[1]."'";
            } else {
                $condicion .= " WHERE NP.Fecha_Registro >= '".$fechas_separadas[0]."' AND NP.Fecha_Registro <= '".$fechas_separadas[1]."'";
            }
        }

        return $condicion;
    }
          
?>