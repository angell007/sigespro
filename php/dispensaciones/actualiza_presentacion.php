<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = (isset($_REQUEST['id_controlado']) ? $_REQUEST['id_controlado'] : '');
$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');

$oItem = new complex("Producto_Control_Cantidad", "Id_Producto_Control_Cantidad", $id);
$datos = $oItem->getData();
unset($oItem);

$id_producto = $datos["Id_Producto"];
$multiplo = $datos["Multiplo"];

$query = "SELECT PD.*, MOD(PD.Cantidad_Formulada,$multiplo) AS Modulo
	  FROM Producto_Dispensacion PD
	  INNER JOIN Dispensacion D ON D.Id_Dispensacion=PD.Id_Dispensacion
	  WHERE MOD(PD.Cantidad_Formulada,$multiplo)>0 
	  AND D.Fecha_Actual > '2020-01-01 00:00:00'
	  AND PD.Id_Producto =" . $id_producto . "
	  AND D.Estado_Dispensacion != 'Anulada' ";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

$var = '';
foreach ($productos as $prod) {
	$oItem = new complex("Dispensacion", "Id_Dispensacion", $prod["Id_Dispensacion"]);
	$dis = $oItem->getData();
	unset($oItem);

	if ($prod["Cantidad_Entregada"] < $prod["Cantidad_Formulada"]) {

		if ($multiplo > $prod["Cantidad_Formulada"]) {

			$formulada = redondearCantidadFormulada((int)$prod["Cantidad_Formulada"], (int)$prod["Modulo"]);

		}else{
			$formulada = (int)$prod["Cantidad_Formulada"] - (int)$prod["Modulo"];

		}

		$oItem = new complex("Producto_Dispensacion", "Id_Producto_Dispensacion", $prod["Id_Producto_Dispensacion"]);
		$oItem->Cantidad_Formulada = $formulada;
		$oItem->save();
		unset($oItem);

		$oItem = new complex("Dispensacion", "Id_Dispensacion", $prod["Id_Dispensacion"]);
		if ($dis["Pendientes"] != 0) {
			$pendientes = (int)$dis["Pendientes"] - (int)$prod["Modulo"];
		} else {
			$pendientes = '0';
		}
		$oItem->Pendientes = strval($pendientes);
		$oItem->save();
		unset($oItem);
		$act = 'Actualizado';

		$o = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
		// echo "aaqui";
		$o->Id_Dispensacion = $prod["Id_Dispensacion"];
		$o->Identificacion_Funcionario = $funcionario;
		$o->Detalle = "original:" . $prod["Cantidad_Formulada"] . " -formulada:" . $formulada . " -entregada:" . $prod["Cantidad_Entregada"] . " - pendientes:" . $pendientes . " - " . $act;
		$o->Estado = 'Presentacion Actualizada';
		$o->save();
		unset($o);

	} else {

		$act = 'No Actualizado';
	}
}

function redondearCantidadFormulada($valor, $multiplo)
{

	// Convertimos $valor a entero 
	$valor = intval($valor);

	// Redondeamos al múltiplo de 10 más cercano 
	$n = round($valor, -1);

	// Si el resultado $n es menor, quiere decir que redondeo hacia abajo 
	// por lo tanto sumamos 10. Si no, lo devolvemos así. 
	return $n < $valor ? $n + $multiplo : $n;
}

$respuesta["titulo"] = 'Actualizado Correctamente';
$respuesta["mensaje"] = 'Presentación en Cantidades Formuladas Actualizadas Correctamente' . $var;
$respuesta["codigo"] = 'success';

echo json_encode($respuesta);
