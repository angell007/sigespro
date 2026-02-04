<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once '../../class/class.configuracion.php';
require_once '../../class/class.qr.php'; /* AGREGAR ESTA CLASE PARA GENERAR QR */
$configuracion = new Configuracion();

$mod = (isset($_REQUEST['modulo']) ? $_REQUEST['modulo'] : '');
$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
// $id_pre_compra = (isset($_REQUEST['id_no_conforme']) ? $_REQUEST['id_no_conforme'] : '');

$datos = (array) json_decode($datos, true);
$productos = (array) json_decode($productos, true);

$cod = $configuracion->getConsecutivo($mod, 'Orden_Compra');

$datos['Codigo'] = $cod;
$datos['Id_Proveedor'] = $datos['Proveedor']['Id_Proveedor'];
$id_no_conforme = $productos[0]['Id_No_Conforme'];

if ($id_no_conforme > "0") {
	$query = "SELECT OC.Codigo AS OC, NC.Estado, NC.Id_No_Conforme, OC.Id_Orden_Compra_Nacional
		From No_Conforme NC 
		Inner Join Acta_Recepcion AC ON AC.Id_Acta_Recepcion = NC.Id_Acta_Recepcion_Compra
		INNER JOIN Orden_Compra_Nacional OC ON OC.Id_Orden_Compra_Nacional = AC.Id_Orden_Compra_Nacional Where NC.Id_No_Conforme = $id_no_conforme";
	$oCon = new consulta();
	$oCon->setQuery($query);
	$no_conforme = $oCon->getData();
}

if ($no_conforme['Estado'] != 'Cerrado') {



	$oItem = new complex($mod, "Id_" . $mod);

	foreach ($datos as $index => $value) {
		$i++;
		$oItem->$index = $value;
	}

	$oItem->save();
	$id_venta = $oItem->getId();
	$resultado = array();
	unset($oItem);

	/* AQUI GENERA QR */
	//$qr = generarqr('ordencompranacional',$id_venta,$MY_FILE.'/IMAGENES/QR/');
	$qr = generarqr('ordencompranacional', $id_venta, 'IMAGENES/QR/');
	$oItem = new complex("Orden_Compra_Nacional", "Id_Orden_Compra_Nacional", $id_venta);
	$oItem->Codigo_Qr = $qr;
	$oItem->save();
	unset($oItem);
	/*HASTA AQUI GENERA QR */

	foreach ($productos as $producto) {
		
		$pnc = new complex('Producto_No_Conforme', 'Id_Producto_No_Conforme', $producto['Id_Producto_No_Conforme']);
		$pnc->Cantidad_Nueva_Orden = (int)$pnc->Cantidad_Nueva_Orden+$producto['Cantidad'];
		$pnc->save();
		unset($pnc);
		

		$oItem = new complex('Producto_Orden_Compra_Nacional', "Id_Producto_Orden_Compra_Nacional");

		$producto["Id_Orden_Compra_Nacional"] = $id_venta;
		foreach ($producto as $index => $value) {
			$oItem->$index = $value;
		}
		$oItem->Costo = number_format($producto['Costo'], 2, '.', '');
		$oItem->Iva = $producto['Iva'] == '' ? '0' : number_format($producto['Iva'], 0, '', '');
		$oItem->save();
		unset($oItem);


	}


	$query = "SELECT SUM(if(Cantidad_Nueva_Orden> Cantidad, 0, Cantidad-Cantidad_Nueva_Orden)) as TotalPendiente from Producto_No_Conforme Where Id_No_Conforme = $id_no_conforme";
	$nc = new consulta();
	$nc->setQuery($query); 
	$nc = $nc->getData();

	if($nc['TotalPendiente']<=0){
		$oItem = new complex('No_Conforme', 'Id_No_Conforme', $id_no_conforme);
		$oItem->Estado = 'Cerrado';	
		$oItem->save();
		unset($oItem);
	}



	$oItem = new complex('Actividad_Orden_Compra', "Id_Actividad_Orden_Compra");
	$oItem->Id_Orden_Compra_Nacional = $id_venta;
	$oItem->Identificacion_Funcionario = $datos["Identificacion_Funcionario"];
	$oItem->Detalles = "Se creo la orden de compra con codigo $datos[Codigo] por Faltantes de la orden $no_conforme[OC]";
	$oItem->Fecha = date("Y-m-d H:i:s");
	$oItem->Estado = "Creacion";
	$oItem->save();
	unset($oItem);
	
		
	$oItem = new complex('Actividad_Orden_Compra', "Id_Actividad_Orden_Compra");
	$oItem->Id_Orden_Compra_Nacional = $no_conforme['Id_Orden_Compra_Nacional'];
	$oItem->Identificacion_Funcionario = $datos["Identificacion_Funcionario"];
	$oItem->Detalles = "Se creo la orden de compra con codigo $datos[Codigo] por Faltantes de la orden ";
	$oItem->Fecha = date("Y-m-d H:i:s");
	$oItem->Estado = "Creacion";
	$oItem->save();
	unset($oItem);



	if ($id_venta != "") {
		$resultado['mensaje'] = "Se ha guardado correctamente la orden de compra: " . $datos['Codigo'];
		$resultado['tipo'] = "success";
	} else {
		$resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
		$resultado['tipo'] = "error";
	}
} else {
	$resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
	$resultado['tipo'] = "error";
}

echo json_encode($resultado);
