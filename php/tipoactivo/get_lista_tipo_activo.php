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

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	$condicion = SetCondiciones($_REQUEST);

	$fecha = date('Y-m-d');

	$query = '
		SELECT 
			*,
            (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = Id_Plan_Cuenta_Depreciacion_NIIF) AS Cuenta_Depreciacion_NIIF,
            (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = Id_Plan_Cuenta_Depreciacion_PCGA) AS Cuenta_Depreciacion_PCGA,
            (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = Id_Plan_Cuenta_NIIF) AS Cuenta_Niif,
            (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = Id_Plan_Cuenta_PCGA) AS Cuenta_Pcga,
            (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = Id_Plan_Cuenta_Credito_Depreciacion_PCGA) AS Cuenta_Credito_Pcga, (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = Id_Plan_Cuenta_Credito_Depreciacion_NIIF) as Cuenta_Credito_Niif
		FROM Tipo_Activo_Fijo
		'.$condicion.' 
		ORDER BY Nombre_Tipo_Activo ASC';

	$query_count = '
		SELECT 
			COUNT(Id_Tipo_Activo_Fijo) AS Total
		FROM Tipo_Activo_Fijo
		'.$condicion;    

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $tipos_contrato = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($tipos_contrato);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND Nombre_Tipo_Activo LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE Nombre_Tipo_Activo LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['categoria']) && $req['categoria']) {
            if ($condicion != "") {
                $condicion .= " AND Categoria LIKE '%".$req['categoria']."%'";
            } else {
                $condicion .= " WHERE Categoria LIKE '%".$req['categoria']."%'";
            }
        }

        if (isset($req['vida_util']) && $req['vida_util']) {
            if ($condicion != "") {
                $condicion .= " AND Vida_Util = ".$req['vida_util'];
            } else {
                $condicion .= " WHERE Vida_Util = ".$req['vida_util'];
            }
        }

        if (isset($req['depreciacion']) && $req['depreciacion']) {
            if ($condicion != "") {
                $condicion .= " AND Porcentaje_Depreciacion_Anual = ".$req['depreciacion'];
            } else {
                $condicion .= " WHERE Porcentaje_Depreciacion_Anual = ".$req['depreciacion'];
            }
        }

        return $condicion;
	}
?>