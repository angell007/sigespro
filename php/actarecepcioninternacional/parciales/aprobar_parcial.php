<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../../class/class.querybasedatos.php');
include_once('../../../class/class.http_response.php');
include_once('../../../class/class.contabilizar.php');
include_once('../../../class/class.costo_promedio.php');

$queryObj = new QueryBaseDatos();
$http_response = new HttpResponse();
$contabilizar = new Contabilizar();
$response = array();

$id_parcial = (isset($_REQUEST['id_parcial']) ? $_REQUEST['id_parcial'] : '');

//
// var_dump($nueva_tasa);
// exit;

$parcial = GetParcial($id_parcial);
//var_dump("UNO",$id_parcial);
$productos = GetProductosParcial($id_parcial);
//var_dump("DOS",$id_parcial);
$total_parcial = floatval(GetTotalProductosParcial($id_parcial));
//var_dump("TRES",$id_parcial);
$otros_gastos = GetGastosVarios($id_parcial);
//var_dump("CUATRO",$id_parcial);
$conteo = GetConteoProductosParcial($id_parcial);
//var_dump("CINCO",$id_parcial);
$productos = AdicionarGastosVariosACostosProductos($productos, $parcial, $total_parcial, $conteo);
//var_dump("SEIS",$id_parcial);
$productos = AdicionarOtrosGastosACostosProductos($productos, $otros_gastos, $total_parcial, $conteo);
//var_dump("SIETE",$id_parcial);
$productos = CalcularCostoProducto($parcial['Tasa_Negociacion'], $productos, $otros_gastos, $parcial);
//var_dump("OCHO",$id_parcial);

// echo json_encode($productos); exit;
$datos_movimiento_contable['Modelo'] = $parcial;
$datos_movimiento_contable['Productos'] = $productos;
$datos_movimiento_contable['Otros_Gastos'] = $otros_gastos;
$datos_movimiento_contable['Porcentaje_Flete_Internacional'] = $productos[0]['Porcentaje_Flete'];
$datos_movimiento_contable['Porcentaje_Seguro_Internacional'] = $productos[0]['Porcentaje_Seguro'];
$datos_movimiento_contable['Adicional_Flete_Nacional'] = $productos[0]['Adicional_Flete_Nacional'];
$datos_movimiento_contable['Adicional_Licencia_Importacion'] = $productos[0]['Adicional_Licencia_Importacion'];
$datos_movimiento_contable['Tasa_Dolar_Parcial'] = $parcial['Tasa_Cambio'];
$datos_movimiento_contable['Tasa_Dolar_Negociacion'] = $parcial['Tasa_Negociacion'];
$datos_movimiento_contable['Id_Registro'] = $id_parcial;

//var_dump("NUEVE",$id_parcial);



GuardarMovimientosContables($datos_movimiento_contable,$id_parcial);
//echo json_encode($datos_movimiento_contable);
//exit;
ActualizarInventarioImportacion($productos);
#	ActualizarInventarioNormal($productos);
ActualizarEstadoParcial($id_parcial);
ActualizarCostos($productos);
$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha actualizado el parcial exitosamente!');
$response = $http_response->GetRespuesta();

echo json_encode($response);

function GetParcial($id_parcial)
{
	global $queryObj;

	$query_productos =
		'SELECT 
                NP.*, 
		    (Select OCI.Tasa_Dolar From Orden_Compra_Internacional OCI  
		    		INNER JOIN Acta_Recepcion_Internacional ARCI On ARCI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
				WHERE ARCI.Id_Acta_Recepcion_Internacional = NP.Id_Acta_Recepcion_Internacional) as Tasa_Negociacion
            FROM Nacionalizacion_Parcial NP
            WHERE
                NP.Id_Nacionalizacion_Parcial = ' . $id_parcial;

	$queryObj->SetQuery($query_productos);
	$parcial = $queryObj->ExecuteQuery('simple');

	return $parcial;
}

function GetProductosParcial($id_parcial)
{
	global $queryObj;

	$productos = array();

	$query_productos = 'SELECT 
                PNP.*,
                PARI.Lote,
                IF(P.Gravado = "No", 0, 19) AS Gravado
            FROM Producto_Nacionalizacion_Parcial PNP
            INNER JOIN Producto_Acta_Recepcion_Internacional PARI ON PNP.Id_Producto_Acta_Recepcion_Internacional = PARI.Id_Producto_Acta_Recepcion_Internacional
            INNER JOIN Producto P ON PNP.Id_Producto = P.Id_Producto
            WHERE
                PNP.Id_Nacionalizacion_Parcial = ' . $id_parcial;

	$queryObj->SetQuery($query_productos);
	$productos = $queryObj->ExecuteQuery('multiple');

	return $productos;
}

function GetTotalProductosParcial($id_parcial)
{
	global $queryObj;

	$query_productos = '
            SELECT 
                SUM(Subtotal) As Total_Parcial
            FROM Producto_Nacionalizacion_Parcial
            WHERE
                Id_Nacionalizacion_Parcial = ' . $id_parcial;

	$queryObj->SetQuery($query_productos);
	$total = $queryObj->ExecuteQuery('simple');

	return $total['Total_Parcial'];
}

function GetGastosVarios($id_parcial)
{
	// echo $id_parcial; exit;
	global $queryObj;

	$productos = array();

	$query_productos = '
            SELECT 
                *
            FROM Nacionalizacion_Parcial_Otro_Gasto
            WHERE
                Id_Nacionalizacion_Parcial = ' . $id_parcial;

	$queryObj->SetQuery($query_productos);
	$gastos = $queryObj->ExecuteQuery('multiple');

	return $gastos;
}

function ActualizarEstadoParcial($id_parcial)
{
	global $queryObj;

	$query = 'UPDATE Nacionalizacion_Parcial SET Estado = "Nacionalizado" WHERE Id_Nacionalizacion_Parcial = ' . $id_parcial;
	$queryObj->SetQuery($query);
	$queryObj->QueryUpdate();
}

function GuardarMovimientosContables($datos,$id_parcial)
{
	global $contabilizar;

	// echo json_encode($datos); exit;
	$contabilizar->CrearMovimientoContable('Parcial Acta Internacional', $datos,$id_parcial);
}

function ActualizarInventarioImportacion($productos)
{
	global $queryObj;

	foreach ($productos as $p) {

		//$query = 'UPDATE Importacion SET Cantidad = Cantidad - ' . intval($p['Cantidad']) . ' WHERE Id_Producto = ' . $p['Id_Producto'] . ' AND Lote = "' . $p['Lote'] . '"';
		//$queryObj->SetQuery($query);
		//$queryObj->QueryUpdate();
	}
}

function ActualizarInventarioNormal($productos)
{
	global $queryObj, $parcial;

	foreach ($productos as $p) {

		$product_exist = ConsultarExistenciaProductoInventario($p['Id_Producto'], $p['Lote']);

		if ($product_exist) {

			$query = 'UPDATE Inventario SET Cantidad = Cantidad + ' . intval($p['Cantidad']) . ' WHERE Id_Producto = ' . $p['Id_Producto'] . ' AND Lote = "' . $p['Lote'] . '" AND Id_Bodega = 3';
			$queryObj->SetQuery($query);
			$queryObj->QueryUpdate();
		} else {

			$oItem = new complex("Inventario", "Id_Inventario");

			$oItem->Id_Producto = $p['Id_Producto'];
			$oItem->Codigo_CUM = GetCodigoCum($p['Id_Producto']);
			$oItem->Lote = $p['Lote'];
			$oItem->Fecha_Vencimiento = GetFechaVencimiento($p['Id_Producto_Acta_Recepcion_Internacional']);
			$oItem->Fecha_Carga = date('Y-m-d H:i:s');
			$oItem->Identificacion_Funcionario = $parcial['Identificacion_Funcionario'];
			$oItem->Id_Bodega = 3;
			$oItem->Cantidad = $p['Cantidad'];
			$costo = $p['Precio_Unitario_Final'];
			$oItem->Costo = number_format($costo, 2, ".", "");

			$oItem->save();
			unset($oItem);
		}
	}
}

function ConsultarExistenciaProductoInventario($id_producto, $lote)
{
	global $queryObj;

	$query = '
			SELECT
				Id_Inventario
			FROM Inventario
			WHERE
				Id_Producto = ' . $id_producto
		. ' AND Lote = "' . $lote . '" AND Id_Bodega = 3';

	$queryObj->SetQuery($query);
	$result = $queryObj->ExecuteQuery('simple');

	return isset($result['Id_Inventario']);
}

function GetCodigoCum($id_producto)
{
	global $queryObj;

	$query = '
			SELECT
				Codigo_Cum
			FROM Producto
			WHERE
				Id_Producto = ' . $id_producto;

	$queryObj->SetQuery($query);
	$cum = $queryObj->ExecuteQuery('simple');
	return $cum['Codigo_Cum'];
}

function GetFechaVencimiento($id_producto_acta)
{
	global $queryObj;

	$query = '
			SELECT
				Fecha_Vencimiento
			FROM Producto_Acta_Recepcion_Internacional
			WHERE
				Id_Producto_Acta_Recepcion_Internacional = ' . $id_producto_acta;

	$queryObj->SetQuery($query);
	$fecha = $queryObj->ExecuteQuery('simple');
	return $fecha['Fecha_Vencimiento'];
}

function GetConteoProductosParcial($id_parcial)
{
	global $queryObj;

	$query = '
            SELECT 
                SUM(Cantidad) AS Total
            FROM Producto_Nacionalizacion_Parcial
            WHERE
                Id_Nacionalizacion_Parcial = ' . $id_parcial;

	$queryObj->SetQuery($query);
	$conteo = $queryObj->ExecuteQuery('simple');

	if ($conteo['Total']) {
		return floatval($conteo['Total']);
	} else {
		return 0;
	}
}

function AdicionarGastosVariosACostosProductos($productos, $parcial, $total_parcial, $conteo)
{
	$cantidad_productos = count($productos);
	$tramite_sia = floatval($parcial['Tramite_Sia']);
	$formulario = floatval($parcial['Formulario']);
	$gasto_bancario = floatval($parcial['Gasto_Bancario']);
	$cargue = floatval($parcial['Cargue']);

	$adicional_tramite = $tramite_sia / $conteo;
	$adicional_formulario = $formulario / $conteo;
	$adicional_gasto = $gasto_bancario / $conteo;
	$adicional_cargue = $cargue / $conteo;

	$total_adicional = $adicional_tramite + $adicional_gasto + $adicional_cargue + $adicional_formulario;

	$i = 0;
	foreach ($productos as $p) {

		$subtotal_final = $p['Subtotal'] + $p['Total_Iva'] + $p['Total_Flete_Nacional'] + $p['Total_Licencia'] + ($total_adicional * $p['Cantidad']);
		$productos[$i]['Subtotal_Final'] = $subtotal_final;

		$i++;
	}

	return $productos;
}

function AdicionarOtrosGastosACostosProductos($productos, $gastos, $total_parcial, $conteo)
{
	$cantidad_productos = count($productos);

	$adicional_final = 0;
	foreach ($gastos as $gasto) {

		$monto = floatval($gasto['Monto_Gasto']);
		$adicional_gasto = $monto / $conteo;
		$adicional_final += $adicional_gasto;
	}

	$i = 0;
	foreach ($productos as $p) {

		$sumar_subtotal = ($adicional_final * $p['Cantidad']);
		$productos[$i]['Subtotal_Final'] = $p['Subtotal_Final'] + $sumar_subtotal;

		$i++;
	}

	return $productos;
}
function ActualizarCostos($productos)
{
	foreach ($productos as $key => $producto) {
		$costo = $producto['Recalculo']['Precio_Unitario_Final'];
		$costopromedio =  new Costo_Promedio($producto["Id_Producto"], $producto["Cantidad"], $costo);
		$costopromedio->actualizarCostoPromedio();
		unset($costopromedio);
	}
}
function CalcularCostoProducto($tasa_orden, $productos, $otros_gastos, $parcial)
{
	$adicional_final = 0;
	$total_cantidad_productos = GetConteoProductosParcial($parcial['Id_Nacionalizacion_Parcial']);
	foreach ($otros_gastos as $gasto) {

		$monto = floatval($gasto['Monto_Gasto']);
		$adicional_gasto = $monto / $total_cantidad_productos;
		$adicional_final += $adicional_gasto;
	}
	$recalculo = [];
	foreach ($productos as $i=> $p) {
		$p_nuevo["Id_Producto"] = $p['Id_Producto'];
		$p_nuevo["Cantidad"] = $p['Cantidad'];
		$p_nuevo["Usd"] = $p['Precio'];
		$p_nuevo["Tasa_Neg"] = $tasa_orden;
		$p_nuevo["FI"] = $p['Porcentaje_Flete'];
		$p_nuevo["SI"] = $p['Porcentaje_Seguro'];
		$p_nuevo["FOB_Neg"] = $p['Precio'] * $tasa_orden;
		$p_nuevo["FOB_Nac"] = $p['Precio'] * $parcial['Tasa_Cambio'];
		
		$p_nuevo["Flete_I"] = $p['Precio'] * ( $p['Porcentaje_Flete']);
		$p_nuevo["Seguro_I"] = $p['Precio'] * ( $p['Porcentaje_Seguro']);
		$p_nuevo["Base_CIF"] = (  $p['Precio'] + $p_nuevo["Flete_I"] + $p_nuevo["Seguro_I"] )* $tasa_orden;
		
		$p_nuevo["Base"] = $p['Precio'] * (1 + $p['Porcentaje_Flete'] + $p['Porcentaje_Seguro']);
		$p_nuevo["Base_Inicial"] = $p_nuevo['Base'] * $tasa_orden;
		$p_nuevo["Base_Arancel"] = $p_nuevo['Base'] * $parcial['Tasa_Cambio'];
		$p_nuevo['Porcentaje_Arancel']=($p['Porcentaje_Arancel']);
		$p_nuevo["Arancel"] = $p_nuevo['Base_Arancel'] * ($p['Porcentaje_Arancel'] / 100);
		$p_nuevo["Impuesto"] = ($p_nuevo['Base_Arancel'] + $p_nuevo['Arancel']) * ($p['Gravado'] / 100);
		$p_nuevo['Adicional_Flete_Nacional'] = $p['Adicional_Flete_Nacional'];
		$p_nuevo['Adicional_Licencia_Importacion'] = $p['Adicional_Licencia_Importacion'];
		$p_nuevo['Adicional_Cargue'] = $p['Adicional_Cargue'];
		$p_nuevo['Adicional_Gasto_Bancario'] = $p['Adicional_Gasto_Bancario'];
		$p_nuevo['Otros_Gastos'] = $adicional_final;
		$p_nuevo['Precio_Unitario_Final'] = $p_nuevo['Base_Inicial'] + $p_nuevo["Arancel"]  + $p['Adicional_Flete_Nacional'] + $p['Adicional_Licencia_Importacion'] + $p['Adicional_Cargue'] + $p['Adicional_Gasto_Bancario'] + $adicional_final;


		$productos[$i]['Recalculo']=$p_nuevo;

		$oItem = new complex('Producto_Nacionalizacion_Parcial', 'Id_Producto_Nacionalizacion_Parcial', $p['Id_Producto_Nacionalizacion_Parcial']);
		$oItem->Costo_Real = number_format($p_nuevo['Precio_Unitario_Final'], 4, '.', '');
		$oItem->save();
	}
	return $productos;
}
