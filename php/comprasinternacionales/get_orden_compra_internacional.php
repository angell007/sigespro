<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_orden = ( isset( $_REQUEST['id_orden'] ) ? $_REQUEST['id_orden'] : '' );

	$query = '
		SELECT 
			OCI.*,
			(P.Nombre) AS Proveedor,
			IFNULL(B.Nombre,BN.Nombre) As Bodega
		FROM Orden_Compra_Internacional OCI
		INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
		LEFT JOIN Bodega B ON OCI.Id_Bodega = B.Id_Bodega
		LEFT JOIN Bodega_Nuevo BN ON OCI.Id_Bodega_Nuevo = BN.Id_Bodega_Nuevo
		WHERE
			OCI.Id_Orden_Compra_Internacional ='.$id_orden;

    $queryObj = new QueryBaseDatos($query);
    $response = $queryObj->Consultar('simple');
    $productos = GetProductosOrdenCompraInternacional($id_orden);
    $response['productos_orden'] = $productos['productos'];
    $response['totales'] = $productos['totales'];

	unset($queryObj);

	echo json_encode($response);

	function GetProductosOrdenCompraInternacional($id_orden){
		global $queryObj;

		$totales = array('total_cajas' => 0, 'total_volumen' => 0, 'subtotal' => 0);
		$result = array('productos' => array(), 'totales' => array());

		$query = '
			SELECT 
				POCI.*,
				P.Nombre_Comercial AS Nombre_Producto,
				P.Embalaje
			FROM Producto_Orden_Compra_Internacional POCI
			INNER JOIN Producto P ON POCI.Id_Producto = P.Id_Producto
			WHERE
				POCI.Id_Orden_Compra_Internacional ='.$id_orden;

		$queryObj->SetQuery($query);
		$productos_orden = $queryObj->ExecuteQuery('multiple');
		
		if (count($productos_orden) > 0) {
			$cantidad_total=0;
			$volumen_total=0;
			$subtotal=0;

			foreach ($productos_orden as $po) {
				$cantidad_total += $po['Cantidad_Caja'];
				$volumen_total += floatval($po['Caja_Volumen']);
				$subtotal += $po['Subtotal'];
			}

			$totales['total_cajas'] = $cantidad_total;
			$totales['total_volumen'] = number_format($volumen_total, 2, ".","");
			$totales['subtotal'] = $subtotal;
		}


		$result['totales'] = $totales;
		$result['productos'] = $productos_orden;

		return $result;
	}
?>