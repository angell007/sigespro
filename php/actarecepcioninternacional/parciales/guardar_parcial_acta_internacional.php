<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	//require_once('../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.contabilizar.php');
	include_once('../../../class/class.http_response.php');
	require_once('../../../class/class.configuracion.php');
	require_once('../../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */


	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();
	$contabilizar = new Contabilizar();
	$configuracion = new Configuracion();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$gastos_varios = ( isset( $_REQUEST['gastos_varios'] ) ? $_REQUEST['gastos_varios'] : '' );
	$porcentaje_flete = ( isset( $_REQUEST['porcentaje_flete'] ) ? $_REQUEST['porcentaje_flete'] : '' );
	$porcentaje_seguro = ( isset( $_REQUEST['porcentaje_seguro'] ) ? $_REQUEST['porcentaje_seguro'] : '' );
	$porcentaje_flete_nac = ( isset( $_REQUEST['porcentaje_flete_nac'] ) ? $_REQUEST['porcentaje_flete_nac'] : '' );
	$porcentaje_licencia = ( isset( $_REQUEST['porcentaje_lic'] ) ? $_REQUEST['porcentaje_lic'] : '' );
	$porcentaje_cargue = ( isset( $_REQUEST['porcentaje_cargue'] ) ? $_REQUEST['porcentaje_cargue'] : '' );
	$porcentaje_gasto_banco = ( isset( $_REQUEST['porcentaje_gasto_banc'] ) ? $_REQUEST['porcentaje_gasto_banc'] : '' );

	$modelo = (array) json_decode($modelo, true);
	$gastos_varios = (array) json_decode($gastos_varios, true);

	$productos = $modelo['ProductoNacionalizar'];

	$datos_movimiento_contable = array();

	//var_dump($modelo);
	//var_dump($productos);
	// var_dump($gastos_varios);
	// var_dump($porcentaje_seguro);
	// var_dump($porcentaje_flete);
	//exit;

	unset($modelo['ProductoNacionalizar']);
	unset($modelo['Id_Nacionalizacion_Parcial']);
	$modelo['Fecha_Registro'] = date('Y-m-d');

	$cod = $configuracion->getConsecutivo('Nacionalizacion_Parcial','Parcial_Acta_Internacional');
    $modelo['Codigo']= $cod;
	//$modelo['Codigo']= 'PAI0001';

	$oItem = new complex("Nacionalizacion_Parcial","Id_Nacionalizacion_Parcial");

	foreach($modelo as $index=>$value) {
		if ($index == 'Descuento_Parcial') {
			
			$value = number_format($value, 2, ".", "");
		}
		if($value!=''){
			$oItem->$index=$value;
		}
    }

   
   $oItem->save();
    $id_nacionalizacion = $oItem->getId();
    unset($oItem);

    
    $datos_movimiento_contable['Modelo'] = $modelo;
    $datos_movimiento_contable['Productos'] = $productos;
    $datos_movimiento_contable['Otros_Gastos'] = $gastos_varios;
    $datos_movimiento_contable['Porcentaje_Flete_Internacional'] = $porcentaje_flete;
    $datos_movimiento_contable['Porcentaje_Seguro_Internacional'] = $porcentaje_seguro;
    $datos_movimiento_contable['Tasa_Dolar_Parcial'] = $modelo['Tasa_Cambio'];
    $datos_movimiento_contable['Id_Registro'] = $id_nacionalizacion;
    //$datos_movimiento_contable['Id_Registro'] = '1'; 

	GuardarProductosParcial($productos, $id_nacionalizacion, $porcentaje_flete, $porcentaje_seguro, $porcentaje_flete_nac, $porcentaje_licencia, $porcentaje_cargue, $porcentaje_gasto_banco);
	
	if (count($gastos_varios) > 0) {
		GuardarGastosAdicionales($gastos_varios, $id_nacionalizacion);
	}
	// ActualizarInventarioImportacion($productos);
	//ActualizarInventarioNormal($productos);
	GuardarMovimientosContables($datos_movimiento_contable,$id_nacionalizacion);
	LiberarActaInternacional($modelo['Id_Acta_Recepcion_Internacional']);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado el parcial exitosamente!');
	$response = $http_response->GetRespuesta();
    
	echo json_encode($response);

	function GuardarProductosParcial($productos, $id_parcial, $p_flete, $p_seguro, $p_flete_nac, $p_lic, $pcargue, $pbanco){

		$i = 0;
		if (is_string($pbanco)) {
			$pbanco = 0;
		}

		foreach ($productos as $p) {

			$oItem = new complex("Producto_Nacionalizacion_Parcial","Id_Producto_Nacionalizacion_Parcial");
	        $datos_movimiento_contable['id_parcial'] = $id_parcial; 
	    	$oItem->Id_Producto_Acta_Recepcion_Internacional = $p['Id_Producto_Acta_Recepcion_Internacional'];
	    	$oItem->Id_Nacionalizacion_Parcial = $id_parcial;
	    	$oItem->Cantidad = $p['Cantidad'];
	    	$oItem->Precio = number_format($p['Precio_Dolares'], 4, ".", "");
	    	$oItem->Id_Producto = $p['Id_Producto'];
	    	$oItem->Subtotal = number_format($p['Subtotal'], 2, ".", "");
	    	$oItem->Porcentaje_Flete = $p_flete;
	    	$oItem->Porcentaje_Seguro = $p_seguro;
	    	$oItem->Adicional_Flete_Nacional = number_format($p_flete_nac, 4, ".", "");
	    	$oItem->Adicional_Cargue = number_format($pcargue, 4, ".", "");
	    	$oItem->Adicional_Gasto_Bancario = number_format($pbanco+0, 4, ".", "");
	    	$oItem->Adicional_Licencia_Importacion = number_format($p_lic+0, 4, ".", "");
	    	$oItem->Porcentaje_Arancel = trim($p['Porcentaje_Arancel'], " %");
	    	$oItem->Precio_Unitario_Final = number_format($p['Precio_Unitario_Final'], 2, ".", "");
	    	$oItem->Precio_Unitario_Pesos = number_format($p['FOT_Pesos'], 2, ".", "");
	    	$oItem->Total_Flete = number_format($p['Subtotal_Flete'], 2, ".", "");
	    	$oItem->Total_Seguro = number_format($p['Subtotal_Seguro'], 2, ".", "");
	    	$oItem->Total_Flete_Nacional = number_format($p['Subtotal_Flete_Nacional'], 2, ".", "");
	    	$oItem->Total_Licencia = number_format($p['Subtotal_Licencia'], 2, ".", "");
	    	$oItem->Total_Arancel = number_format($p['Valor_Arancel'], 2, ".", "");
	    	$oItem->Total_Iva = number_format((floatval($p['Subtotal'])*(floatval($p['Gravado'])/100)), 2, ".", "");
	    	$oItem->save();
		    unset($oItem);
			
			$i++;
		}
	}

	function GuardarGastosAdicionales($gastos, $id_parcial)
	{
		global $modelo;

		foreach ($gastos as $og) {
	    	$oItem = new complex("Nacionalizacion_Parcial_Otro_Gasto","Id_Nacionalizacion_Parcial_Otro_Gasto");

	    	$oItem->Id_Nacionalizacion_Parcial = $id_parcial;
	    	$oItem->Concepto_Gasto = $og['Concepto_Gasto'];
	    	$oItem->Monto_Gasto = number_format($og['Monto_Gasto'], 2, ".", "");
	    	$oItem->Id_Proveedor = number_format($og['Tercero_Gasto'], 2, ".", "");
	    	
	    	$oItem->save();
		    unset($oItem);
	    }

	    //GuardarActividadOrdenInternacionalOtrosGastos($id_acta, $gastos, $modelo['Identificaicon_Funcionario']);
	}

	function GuardarMovimientosContables($datos,$id_nacionalizacion){
		global $contabilizar;

		$contabilizar->CrearMovimientoContable('Parcial Acta Internacional', $datos, $id_nacionalizacion);
	}

	function GetCodigoActa($id_acta){
		global $queryObj;

		$query = '
			SELECT
				Codigo
			FROM Acta_Recepcion_Internacional
			WHERE
				Id_Acta_Recepcion_Internacional = '.$id_acta;

		$queryObj->SetQuery($query);
		$codigo = $queryObj->ExecuteQuery('simple');
		return $codigo['Codigo'];
	}
 
	function GenerarNoConforme($producto, $id_acta){
		global $modelo, $configuracion;

		$cod = $configuracion->getConsecutivo('No_Conforme_Internacional','No_Conforme_Internacional');

		$oItem = new complex("No_Conforme_Internacional","Id_No_Conforme_Internacional");
    	
    	$oItem->Id_Acta_Recepcion_Internacional = $id_acta;
    	$oItem->Fecha_Registro = date('Y-m-d');
    	$oItem->Persona_Reporta = $modelo['Identificacion_Funcionario'];
    	$oItem->Codigo = $cod;
    	$oItem->Estado = 'Pendiente';

		$oItem->save();
	    $id_no_conforme = $oItem->getId();
	    unset($oItem);

    	$qr = generarqr('noconformeinternacional',$id_no_conforme,'IMAGENES/QR/');
		$oItem = new complex("No_Conforme_Internacional","Id_No_Conforme_Internacional",$id_no_conforme);
		$oItem->Codigo_Qr=$qr;
		$oItem->save();
		unset($oItem);

		$cantidad_producto_lotes = GetTotalCantidadProductoLote($producto['Producto_Lotes']);
	}

	function GetTotalCantidadProductoLote($lotesProducto){
		$cantidad_producto = 0;

		foreach ($lotesProducto as $lote) {
			
			$cantidad_producto += intval($lote['Cantidad']);
		}

		return $cantidad_producto;
	}

	function ActualizarEstadoOrdenCompraInternacional($id_orden){
		global $queryObj;

		$query = 'UPDATE Orden_Compra_Internacional SET Estado = "Recibida" WHERE Id_Orden_Compra_Internacional = '.$id_orden;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadActa($id_acta, $id_funcionario, $id_orden){

		$codigo_acta = $codigo_acta = GetCodigoActa($id_acta);
		
		$oItem = new complex("Actividad_Orden_Compra_Internacional","Id_Actividad_Orden_Compra_Internacional");

		$oItem->Identificacion_Funcionario = $id_funcionario;
		$oItem->Id_Orden_Compra_Internacional = $id_orden;
		$oItem->Accion = "Creacion";
		$oItem->Descripcion = "Se ha creado el acta de recepcion con codigo ".$codigo_acta;

	    $oItem->save();
	    unset($oItem);
	}

	function GuardarActividadOrdenInternacionalOtrosGastos($id_orden, $otros_gastos, $id_funcionario){

		$oItem = new complex("Actividad_Orden_Compra_Internacional","Id_Actividad_Orden_Compra_Internacional");

		$oItem->Identificacion_Funcionario = $id_funcionario;
		$oItem->Id_Orden_Compra_Internacional = $id_orden;
		$oItem->Accion = "Creacion";
		$oItem->Descripcion = ArmarMensajeActividadOtrosGastos($otros_gastos);

	    $oItem->save();
	    unset($oItem);
	}

	function ArmarMensajeActividadOtrosGastos($otros_gastos){
		$mensaje = 'Se adicionaron los siguientes gastos ';

		foreach ($otros_gastos as $og) {
	    	$mensaje .= $og['Concepto_Gasto']." por un monto de $ ".$og['Monto_Gasto'].", ";
	    }

	    return trim($mensaje, ", ");
	}

	function ActualizarInventarioImportacion($productos){
		global $queryObj;

		foreach ($productos as $p) {
			
			$query = 'UPDATE Importacion SET Cantidad = Cantidad - '.intval($p['Cantidad']).' WHERE Id_Producto = '.$p['Id_Producto'].' AND Lote = '.$p['Lote'];
			$queryObj->SetQuery($query);
			$queryObj->QueryUpdate();
		}
	}

	function ActualizarInventarioNormal($productos){
		global $queryObj, $modelo;

		foreach ($productos as $p) {

			$product_exist = ConsultarExistenciaProductoInventario($p['Id_Producto'], $p['Lote']);

			if ($product_exist) {
				
				$query = 'UPDATE Inventario SET Cantidad = Cantidad + '.intval($p['Cantidad']).' WHERE Id_Producto = '.$p['Id_Producto'].' AND Lote = "'.$p['Lote'].'" AND Id_Bodega = 3';
				$queryObj->SetQuery($query);
				$queryObj->QueryUpdate();
			}else{

				$oItem = new complex("Inventario","Id_Inventario");

				$oItem->Id_Producto = $p['Id_Producto'];
				$oItem->Codigo_CUM = GetCodigoCum($p['Id_Producto']);
				$oItem->Lote = $p['Lote'];
				$oItem->Fecha_Vencimiento = GetFechaVencimiento($p['Id_Producto_Acta_Recepcion_Internacional']);
				$oItem->Fecha_Carga = date('Y-m-d H:i:s');
				$oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
				$oItem->Id_Bodega = 3;
				$oItem->Cantidad = $p['Cantidad'];
				// $costo = $p['Precio_Unitario_Final'] + ($p['Precio_Unitario_Final'] * (floatval($p['Gravado'])/100));
				$costo = $p['Precio_Unitario_Final'];
				$oItem->Costo = number_format($costo, 2, ".", "");

			    $oItem->save();
			    unset($oItem);
			}
			
			
		}
	}

	function ConsultarExistenciaProductoInventario($id_producto, $lote){
		global $queryObj;

		$query = '
			SELECT
				Id_Inventario
			FROM Inventario
			WHERE
				Id_Producto = '.$id_producto
				.' AND Lote = '.$lote
				.' AND Id_Bodega = 3';

		$queryObj->SetQuery($query);
		$result = $queryObj->ExecuteQuery('simple');

		return isset($result['Id_Inventario']);
	}

	function GetCodigoCum($id_producto){
		global $queryObj;

		$query = '
			SELECT
				Codigo_Cum
			FROM Producto
			WHERE
				Id_Producto = '.$id_producto;

		$queryObj->SetQuery($query);
		$cum = $queryObj->ExecuteQuery('simple');
		return $cum['Codigo_Cum'];
	}

	function GetFechaVencimiento($id_producto_acta){
		global $queryObj;

		$query = '
			SELECT
				Fecha_Vencimiento
			FROM Producto_Acta_Recepcion_Internacional
			WHERE
				Id_Producto_Acta_Recepcion_Internacional = '.$id_producto_acta;

		$queryObj->SetQuery($query);
		$fecha = $queryObj->ExecuteQuery('simple');
		return $fecha['Fecha_Vencimiento'];
	}

	function LiberarActaInternacional($idActa){
		global $queryObj;

		$query = 'UPDATE Acta_Recepcion_Internacional SET Bloquear_Parcial = "No" WHERE Id_Acta_Recepcion_Internacional = '.$idActa;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}
