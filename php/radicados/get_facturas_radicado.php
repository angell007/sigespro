<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_radicado = ( isset( $_REQUEST['id_radicado'] ) ? $_REQUEST['id_radicado'] : '' );

    $queryObj = new QueryBaseDatos();

	$tipo_servicio = GetTipoServicioRadicacion($id_radicado);

	$query = '';

	if ($tipo_servicio == 'CAPITA') {
		$query = 
		'SELECT 
            	RF.Id_Radicado_Factura,
				RF.Id_Radicado,
                F.Id_Factura_Capita AS Id_Factura,
                F.Codigo AS Codigo_Factura,
                IFNULL(F.Codigo, "") AS Codigo_Dis,
                IFNULL(DFC.Descripcion, "") AS Nombre_Paciente,
                (SUM(DFC.Total) - F.Cuota_Moderadora) AS Valor_Factura,
	            RF.Estado_Factura_Radicacion,
	            IFNULL(RF.Observacion, "") AS Observacion,
	            IFNULL(RF.Total_Glosado, "") AS Total_Glosado,
	            IF(RF.Estado_Factura_Radicacion = "Pagada", true, false) AS Bloquear
			FROM Radicado_Factura RF
            INNER JOIN Factura_Capita F ON RF.Id_Factura = F.Id_Factura_Capita
            INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
            INNER JOIN Descripcion_Factura_Capita DFC ON F.Id_Factura_Capita = DFC.Id_Factura_Capita
			WHERE
				Id_Radicado ='.$id_radicado
            	.' GROUP BY DFC.Id_Factura_Capita';
	}else{
		$query = 
		'SELECT 
				RF.Id_Radicado_Factura,
				RF.Id_Radicado,
				F.Id_Factura,
	            F.Codigo AS Codigo_Factura,
	            D.Codigo AS Codigo_Dis,
	            UPPER(CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido)) AS Nombre_Paciente,
				(
	                CASE
	                    WHEN C.Tipo_Valor = "Exacta" THEN (SELECT SUM( ((Precio * Cantidad)+((Precio * Cantidad - IF(F.Id_Cliente = 890500890,FLOOR(Descuento*Cantidad), (Descuento*Cantidad)) ) * (Impuesto/100) )) - (IF(F.Id_Cliente = 890500890, FLOOR(Descuento* Cantidad), Descuento* Cantidad))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
	                    ELSE (SELECT ROUND(SUM( ((ROUND(Precio) * Cantidad)+((ROUND(Precio) * Cantidad- ROUND((Descuento*Cantidad))) * (Impuesto/100) )) - ROUND((Descuento*Cantidad)))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
	                END
	            ) AS Valor_Factura,
	            RF.Estado_Factura_Radicacion,
	            IFNULL(RF.Observacion, "") AS Observacion,
	            IFNULL(RF.Total_Glosado, "") AS Total_Glosado,
	            IF(RF.Estado_Factura_Radicacion = "Pagada", true, false) AS Bloquear
			FROM Radicado_Factura RF
			INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
	        INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
			INNER JOIN Dispensacion D ON F.Id_Dispensacion = D.Id_Dispensacion
	        INNER JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente
			WHERE
				Id_Radicado ='.$id_radicado;
	}

	

    $queryObj->SetQuery($query);
    $response = $queryObj->Consultar('Multiple');

    if (count($response['query_result']) > 0)  {
    	$i = 0;
    	foreach ($response['query_result'] as $rad_fac) {
    		
    		//$response['query_result'][$i]['Valor_Factura'] = $response['query_result'][$i]['Valor_Factura'] - $response['query_result'][$i]['Valor_Homologo'];

    		$query = 'SELECT 
					GF.Id_Glosa_Factura,
					GF.Id_Codigo_General_Glosa,
					GF.Id_Codigo_Especifico_Glosa,
					GF.Codigo_Glosa,
					GF.Observacion_Glosa,
					GF.Valor_Glosado,
					GF.Id_Radicado_Factura,
					GF.Archivo_Glosa,
					GF.Radicado_Glosa,
					IF((GF.Archivo_Glosa IS NULL OR GF.Archivo_Glosa = ""), false, true) AS Archivo
				FROM Glosa_Factura GF
				INNER JOIN Radicado_Factura RF ON GF.Id_Radicado_Factura = RF.Id_Radicado_Factura
				WHERE
					GF.Id_Radicado_Factura ='.$rad_fac['Id_Radicado_Factura']
					.' AND RF.Id_Factura = '.$rad_fac['Id_Factura'];
		    
		    $queryObj->SetQuery($query);
		    $glosas_factura = $queryObj->ExecuteQuery('Multiple');

		    if (count($glosas_factura) > 0) {
		    	
		    	$glosas_factura = GetCodigosEspecificosGlosa($glosas_factura);
		    	$response['query_result'][$i]['Glosas_Factura'] = $glosas_factura;
		    }else{

		    	$response['query_result'][$i]['Glosas_Factura'] = array();
		    }

		    $i++;

    	}
    }

	unset($queryObj);

	echo json_encode($response);

	function GetTipoServicioRadicacion($idRadicado){
		global $queryObj;

		$query = '
			SELECT 
				Tipo_Servicio
			FROM Radicado
			WHERE
				Id_Radicado ='.$idRadicado;

	    $queryObj->SetQuery($query);
	    $tipo_servicio = $queryObj->ExecuteQuery('simple');
	    return $tipo_servicio['Tipo_Servicio'];
	}

	function GetCodigosEspecificosGlosa($glosas){
		global $queryObj;

		foreach ($glosas as $key => $g) {
			$query = '
				SELECT 
					CONCAT(Codigo," - ",Concepto) as label,Codigo,
					Id_Codigo_Especifico_Glosa, 
					Id_Codigo_Especifico_Glosa as value 
				FROM  Codigo_Especifico_Glosa 
				WHERE 
					Id_Codigo_General_Glosa='.$g["Id_Codigo_General_Glosa"].'
	 			ORDER BY Codigo ASC';

		    $queryObj->SetQuery($query);
		    $codigos_especificos = $queryObj->ExecuteQuery('multiple');
		    $glosas[$key]['Codigos_Especificos'] = $codigos_especificos;
		}

	    return $glosas;

	}
?>