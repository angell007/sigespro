<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../class/class.consulta.php');


	$filtros = isset($_REQUEST['filtros']) ? $_REQUEST['filtros'] : false;
	$filtros = json_decode($filtros,true);

	$tipoCierre = isset($_REQUEST['tipoCierre']) ? $_REQUEST['tipoCierre'] : false;
	
	$cond = '';
	if ($filtros) {
		if ($filtros['codigo'] != '') {
			# code...
			$cond .= ' AND Codigo LIKE "'.$filtros['codigo'].'%" ';
		}
		if ($filtros['nombre'] != '') {
			# code...
			$cond .= ' AND Nombre LIKE "'.$filtros['nombre'].'%" ';
		}
		if ($filtros['tipoCierre'] != '') {
			# code...
			$cond .= ' AND Tipo_Cierre_'.$tipoCierre.' LIKE "'.$filtros['tipoCierre'].'%" ';
		}
	}

	$query = '
		SELECT 
            Id_Plan_Cuentas,
            Codigo,
            Nombre,
            Tipo_Cierre_'.$tipoCierre.'
		FROM Plan_Cuentas
		WHERE  
			Estado = "ACTIVO" '.$cond.'
		ORDER BY Codigo'; 

	//Se crea la instancia que contiene la consulta a realizar
	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$planes = $oCon->getData();
	unset($oCon);

	$res['type'] = $planes ? 'success' : 'error';
	$res['query_result'] = $planes;
	
    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    
	echo json_encode($res);
?>