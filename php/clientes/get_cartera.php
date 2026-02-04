<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$id = ( isset( $_REQUEST['id_cliente'] ) ? $_REQUEST['id_cliente'] : false );

	$resultado = [];

	if ($id) {
		$query = "SELECT FV.Id_Factura_Venta, FV.Codigo AS Factura, DATE(FV.Fecha) AS Fecha_Factura, DATE_ADD(DATE(FV.Fecha), INTERVAL IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) DAY) AS Fecha_Vencimiento, FV.Id_Cliente AS Nit, C.Nombre_Cliente, /*IFNULL(IF(C.Condicion_Pago > 0,
		IF(DATEDIFF(CURDATE(), DATE(FV.Fecha)) > C.Condicion_Pago,
			DATEDIFF(CURDATE(), DATE(FV.Fecha)) - C.Condicion_Pago,
			0),
		0),
	0)*/ 0 AS Mora, C.Condicion_Pago AS Plazo, FV.Subtotal_Venta AS Subtotal, FV.Impuestos_Venta AS Iva, FV.Total_Venta AS Total, IFNULL(A.Pagado,0) AS Abono, (FV.Total_Venta - IFNULL(A.Pagado,0)) AS Saldo FROM Factura_Venta FV INNER JOIN (SELECT Id_Cliente, Condicion_Pago, Cupo, IF(Nombre != '' AND Nombre IS NOT NULL, Nombre, Razon_Social) AS Nombre_Cliente FROM Cliente WHERE Estado = 'Activo') C ON C.Id_Cliente = FV.Id_Cliente LEFT JOIN (SELECT Id_Factura_Venta, SUM(Pago) AS Pagado FROM Factura_Recaudo GROUP BY Id_Factura_Venta) A ON A.Id_Factura_Venta = FV.Id_Factura_Venta WHERE FV.Estado = 'Pendiente' AND FV.Tipo_Venta = 'Credito' AND FV.Id_Cliente = $id ORDER BY FV.Fecha ASC";

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$resultado = $queryObj->ExecuteQuery('Multiple');
	
	}

	

	echo json_encode($resultado);
?>