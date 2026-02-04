<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

	require('../../class/class.impresion_factura.php');
	$queryObj = new QueryBaseDatos();
	$impresion = new ImpresionFactura();

	$codigo_ini = ( isset( $_REQUEST['codigo_inicial'] ) ? $_REQUEST['codigo_inicial'] : '' );
	$codigo_fin = ( isset( $_REQUEST['codigo_final'] ) ? $_REQUEST['codigo_final'] : '' );	

	$id_inicial = GetIdFacturaCodigo($codigo_ini);
	$id_final = GetIdFacturaCodigo($codigo_fin);

	$query = '
		SELECT
			Id_Factura
		FROM Factura
		WHERE
			Id_Factura BETWEEN '.$id_inicial.' AND '.$id_final;

	$queryObj->SetQuery($query);
	$facturas = $queryObj->ExecuteQuery('Multiple');
	$facturas_imprimir =SetearIdProductos($facturas);
	$impresion->SetFacturas($facturas_imprimir);
	$impresion->ImprimirFacturas();
	$impresion->ImprimirPdf();

	// $facturas = ChunkearFacturas($facturas);

	// foreach ($facturas as $facs) {

	// 	$facturas_imprimir =SetearIdProductos($facs);
	// 	// echo "<pre>";
	// 	// var_dump($facturas_imprimir);
	// 	// echo "</pre>";

	// 	$impresion->SetFacturas($facturas_imprimir);
	// 	$impresion->ImprimirFacturas();
	// 	$impresion->ImprimirPdf();
	// }
	
	unset($impresion);
	echo "Facturas impresas";
	

	function SetearIdProductos($facturas){
		$ids = array();
		foreach ($facturas as $factura) {
			
			foreach ($factura as $f) {
				array_push($ids, $f);
			}
		}

		return $ids;
	}

	function ChunkearFacturas($facturas){
		$facturas_chunkeadas = array_chunk($facturas, 10);
		return $facturas_chunkeadas;
	}

	function GetIdFacturaCodigo($codigo){
		global $queryObj;

		$query = '
			SELECT
				Id_Factura
			FROM Factura
			WHERE
				Codigo = "'.$codigo.'"';

		$queryObj->SetQuery($query);
		$cod = $queryObj->ExecuteQuery('simple');
		return intval($cod['Id_Factura']);
	}

?>