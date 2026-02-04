<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	$condicion = SetCondiciones($_REQUEST);

	$fecha = date('Y-m-d');

    $query = '
        SELECT 
            GF.Id_Glosa_Factura,
            GF.Valor_Glosado,
            GF.Observacion_Glosa,
            GF.Codigo_Glosa,
            
            R.Codigo AS Radicado,
            R.Id_Radicado,
            F.Codigo AS Factura,
            GF.Estado,            
            (SELECT CONCAT_WS(" ", COUNT(Id_Respuesta_Glosa), " Respuestas") FROM Respuesta_Glosa WHERE Id_Glosa_Factura = GF.Id_Glosa_Factura) AS Cantidad_Respuestas,
            IF(R.Estado = "Cerrada", "No", "Si") AS Habilitar_Responder
        FROM Glosa_Factura GF
        INNER JOIN Radicado_Factura RF ON GF.Id_Radicado_Factura = RF.Id_Radicado_Factura
        INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
        INNER JOIN Radicado R ON RF.Id_Radicado = R.Id_Radicado
        '.$condicion;

    $query_count = '
        SELECT             
            COUNT(GF.Id_Glosa_Factura) AS Total  
        FROM Glosa_Factura GF
        INNER JOIN Radicado_Factura RF ON GF.Id_Radicado_Factura = RF.Id_Radicado_Factura
        INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
        INNER JOIN Radicado R ON RF.Id_Radicado = R.Id_Radicado
        '.$condicion; 

	// $query = '
	// 	SELECT 
 //            RF.Id_Radicado_Factura,
 //            R.Codigo AS Radicado,
 //            F.Codigo AS Factura,
 //            GROUP_CONCAT(GF.Id_Glosa_Factura) AS Glosas_Factura          
	// 	FROM Radicado_Factura RF
 //        INNER JOIN Glosa_Factura GF ON RF.Id_Radicado_Factura = GF.Id_Radicado_Factura
 //        INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
 //        INNER JOIN Radicado R ON RF.Id_Radicado = R.Id_Radicado
	// 	'.$condicion;

	// $query_count = '
	// 	SELECT 
 //            COUNT(RF.Id_Radicado_Factura) AS Total 
 //        FROM Radicado_Factura RF
 //        INNER JOIN Glosa_Factura GF ON RF.Id_Radicado_Factura = GF.Id_Radicado_Factura
 //        INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
 //        INNER JOIN Radicado R ON RF.Id_Radicado = R.Id_Radicado
	// 	'.$condicion.' GROUP BY RF.Id_Radicado_Factura'; 

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $glosas = $queryObj->Consultar('Multiple', true, $paginationData);

    if (count($glosas['query_result']) > 0) {
        
        $i = 0;
        foreach ($glosas['query_result'] as $value) {
            
            $glosas['query_result'][$i]['Respuestas'] = GetRespuestasGlosa($value['Id_Glosa_Factura']);
            $i++;
        }        
    }

    unset($http_response);
    unset($queryObj);

	echo json_encode($glosas);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['factura']) && $req['factura']) {
            if ($condicion != "") {
                $condicion .= " AND F.Codigo LIKE '%".$req['factura']."%'";
            } else {
                $condicion .= " WHERE F.Codigo LIKE '%".$req['factura']."%'";
            }
        }

        if (isset($req['radicado']) && $req['radicado']) {
            if ($condicion != "") {
                $condicion .= " AND R.Codigo LIKE '%".$req['radicado']."%'";
            } else {
                $condicion .= " WHERE R.Codigo LIKE '%".$req['radicado']."%'";
            }
        }

        if (isset($req['tipo_glosa']) && $req['tipo_glosa']) {
            if ($condicion != "") {
                $condicion .= " AND GF.Id_Tipo_Glosa = ".$req['tipo_glosa'];
            } else {
                $condicion .= " WHERE GF.Id_Tipo_Glosa = ".$req['tipo_glosa'];
            }
        }

        if (isset($req['valor_glosado']) && $req['valor_glosado']) {
            if ($condicion != "") {
                $condicion .= " AND GF.Valor_Glosado = ".$req['valor_glosado'];
            } else {
                $condicion .= " WHERE GF.Valor_Glosado = ".$req['valor_glosado'];
            }
        }

        return $condicion;
	}

    function GetRespuestasGlosa($idGlosa){
        global $queryObj;

        $query = '
        SELECT 
            *
        FROM Respuesta_Glosa
        WHERE
            Id_Glosa_Factura = '.$idGlosa;

        $queryObj->SetQuery($query);
        $respuestas = $queryObj->ExecuteQuery('multiple');

        return $respuestas;
    }
?>