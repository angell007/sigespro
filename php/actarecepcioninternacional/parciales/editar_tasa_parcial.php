<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();

	$id_parcial = ( isset( $_REQUEST['id_parcial'] ) ? $_REQUEST['id_parcial'] : '' );
	$nueva_tasa = ( isset( $_REQUEST['nueva_tasa'] ) ? $_REQUEST['nueva_tasa'] : '' );

	// var_dump($id_parcial);
	// var_dump($nueva_tasa);
	// exit;

	ActualizarTasa($id_parcial, $nueva_tasa);
	$productos = GetProductosParcial($id_parcial);
	RecalculoProductosConNuevaTasa($nueva_tasa, $productos);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha actualizado el parcial exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GetProductosParcial($id_parcial){
        global $queryObj;

        $productos = array();

        $query_productos = '
            SELECT 
                *
            FROM Producto_Nacionalizacion_Parcial
            WHERE
                Id_Nacionalizacion_Parcial = '.$id_parcial;

        $queryObj->SetQuery($query_productos);
        $productos = $queryObj->ExecuteQuery('multiple');        

        return $productos;
    }

	function RecalculoProductosConNuevaTasa($nueva_tasa, $productos){

		$i = 0;
		foreach ($productos as $p) {

			$oItem = new complex("Producto_Nacionalizacion_Parcial","Id_Producto_Nacionalizacion_Parcial", $p['Id_Producto_Nacionalizacion_Parcial']);

			$precio_unitario = ConversionPrecioDolarAPesos(floatval($p['Precio']), $nueva_tasa);
	    	$oItem->Precio_Unitario_Pesos = number_format(CalcularFotPesos($precio_unitario, $p['Porcentaje_Flete'], $p['Porcentaje_Seguro']), 2, ".", "");
	    	$oItem->Precio_Unitario_Final = number_format(CalcularPrecioUnitarioFinal($oItem->Precio_Unitario_Pesos, floatval($p['Porcentaje_Arancel'])), 2, ".", "");
	    	$oItem->Subtotal = number_format(floatval($oItem->Precio_Unitario_Final) * intval($p['Cantidad']), 2, ".", "");
	    	$oItem->Total_Flete = number_format((floatval($oItem->Precio_Unitario_Final) * intval($p['Cantidad'])) * floatval($p['Porcentaje_Flete']), 2, ".", "");
	    	$oItem->Total_Seguro = number_format((floatval($oItem->Precio_Unitario_Final) * intval($p['Cantidad'])) * floatval($p['Porcentaje_Seguro']), 2, ".", "");
	    	$oItem->Total_Flete_Nacional = number_format(((floatval($oItem->Precio_Unitario_Final) + floatval($p['Adicional_Flete_Nacional'])) * intval($p['Cantidad'])), 2, ".", "");
	    	$oItem->Total_Licencia_Importacion = number_format(((floatval($oItem->Precio_Unitario_Final) + floatval($p['Adicional_Licencia_Importacion'])) * intval($p['Cantidad'])), 2, ".", "");
	    	$oItem->Total_Arancel = number_format((floatval($oItem->Precio_Unitario_Final) * intval($p['Cantidad'])) * (floatval($p['Porcentaje_Arancel'])/100), 2, ".", "");
	    	$gravado = GetImpuestoProducto($p['Id_Producto']);
	    	$oItem->Total_Iva = number_format((floatval($oItem->Precio_Unitario_Final) * intval($p['Cantidad'])) * (floatval($gravado)/100), 2, ".", "");
	    	
	    	$oItem->save();
		    unset($oItem);

			$i++;
		}
	}

	function ConversionPrecioDolarAPesos($precio, $tasa){
		$conversion = $precio * $tasa;
		return $conversion;
	}

	function CalcularFotPesos($precio_unitario, $flete, $seguro){
		$valor_flete = $precio_unitario * $flete;
		$valor_seguro = $precio_unitario * $seguro;
		$fot = $precio_unitario + $valor_flete + $valor_seguro;
		return $fot;
	}

	function CalcularPrecioUnitarioFinal($fot_pesos, $arancel){
		$valor_arancel = $fot_pesos * ($arancel/100);
		$puf = $fot_pesos + $valor_arancel;
		return $puf;
	}

	function GetImpuestoProducto($id_producto){
		global $queryObj;

        $productos = array();

        $query_productos = '
            SELECT 
                IF(Gravado = "No", 0, 19) AS Gravado
            FROM Producto
            WHERE
                Id_Producto = '.$id_producto;

        $queryObj->SetQuery($query_productos);
        $gravado = $queryObj->ExecuteQuery('simple');

        return $gravado['Gravado'];
	}

	function ActualizarTasa($id_parcial, $nueva_tasa){
		global $queryObj;

		$query = 'UPDATE Nacionalizacion_Parcial SET Tasa_Cambio = '.$nueva_tasa.' WHERE Id_Nacionalizacion_Parcial = '.$id_parcial;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}
?>