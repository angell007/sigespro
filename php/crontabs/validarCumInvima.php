<?php
// header('Content-Type: application/json');

include_once("/home/sigesproph/public_html/class/class.lista.php");
include_once("/home/sigesproph/public_html/class/class.complex.php");
include_once("/home/sigesproph/public_html/class/class.consulta.php");
date_default_timezone_set('America/Bogota');


ini_set('memory_limit', '2048M');
set_time_limit(0);
$productos = GetProductos();
$handle = fopen("/home/sigesproph/public_html/php/crontabs/actualizados.txt", 'a+');

// echo "op"; exit;
foreach ($productos as $p) {
	$datos = GetDatosInvima($p['Codigo_Cum']);
	if ($datos['registrosanitario']) {
		CrearProducto($datos, $p['Codigo_Cum'], $p['ID']);
	}
}
echo "Ok";
fclose($handle);
function CrearProducto($datos, $cum, $ids)
{

	$id_productos= explode(',', $ids);

	
	// return;
	foreach ($id_productos as $id ) {
	    $datos = str_replace("'", '.', $datos);
	
		if ($datos['registrosanitario'] != '') {

			$oItem = new complex('Producto', 'Id_Producto', $id);
			$pr = $oItem->getData();
			$campos = '';
			$fecha_exp_inv=date('Y-m-d', strtotime($datos['fechaexpedicion']));
			$fecha_venc_inv=date('Y-m-d', strtotime($datos['fechavencimiento']));
			if ($oItem->Invima != $datos['registrosanitario']) {
				$oItem->Invima = $datos['registrosanitario'];
				$campos .= " Registo Invima a $datos[registrosanitario],";
			}

			if (strtolower($oItem->ATC) != strtolower($datos['atc'])) {
				$campos .= " ATC a $datos[atc],";
				$oItem->ATC = $datos['atc'];
			}
			if (strtolower($oItem->Descripcion_ATC) != strtolower($datos['descripcionatc'])) {
				$campos .= " Descripcion ATC a $datos[descripcionatc],";
				$oItem->Descripcion_ATC = $datos['descripcionatc'];
			}
			if (strtolower($oItem->Principio_Activo) != strtolower($datos['principioactivo'])) {
				$campos .= " Principio Activo a $datos[principioactivo],";
				$oItem->Principio_Activo = $datos['principioactivo'];
			}
			if (utf8_encode(strtolower($oItem->Nombre_Comercial)) != utf8_encode(strtolower($datos['producto'])) && ($datos['producto'])) {

				$campos .= " Nombre Comercial a $datos[producto],";
				$oItem->Nombre_Comercial = $datos['producto'];
			}
			if (strtolower($oItem->Estado) != strtolower($datos['estadocum']) && $oItem->Estado == 'Activo') {
				$campos .= " Estado a $datos[estadocum],";
				$oItem->Estado = $datos['estadocum'];
			}
			if ((float)($oItem->Cantidad_Presentacion) != (float)($datos['cantidadcum']) && !(float)$oItem->Cantidad_Presentacion) {
				$campos .= " Cantidad Presentacion a $datos[cantidadcum],";
				$oItem->Cantidad_Presentacion = number_format((FLOAT)$datos['cantidadcum'],0,"","");
			}
			if (strtolower($oItem->Via_Administracion) != strtolower($datos['viaadministracion']) ) {
				$originales = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ';
				$modificadas = 'AAAAAAACEEEEIIIIDOOOOOOUUUUYbsaaaaaaaceeeeiiiidnoooooouuuyyby';
				$cadena = utf8_decode($datos['viaadministracion']);
				$cadena = strtr($cadena, utf8_decode($originales), $modificadas);
				$oItem->Via_Administracion = $cadena;
			}
			if (strtolower($oItem->Estado_Registro_Invima) != strtolower($datos['estadoregistro'])) {
				$campos .= " Estado Registro Invima a $datos[estadoregistro],";
				$oItem->Estado_Registro_Invima = $datos['estadoregistro'];
			}
			if (strtolower($oItem->Embalaje) != strtolower($datos['descripcioncomercial'])) {
				$campos .= " Embalaje ,";
				$oItem->Embalaje = $datos['descripcioncomercial'];
			}
			if ($oItem->Fecha_Expedicion_Invima != $fecha_exp_inv) {
				$campos .= " Fecha Expedicion Invima a $fecha_exp_inv,";
				$oItem->Fecha_Expedicion_Invima = $fecha_exp_inv;
			}
			if ($oItem->Fecha_Vencimiento_Invima !=$fecha_venc_inv) {
				$campos .= "Fecha Vencimiento Invima a $fecha_venc_inv,";
				$oItem->Fecha_Vencimiento_Invima = $fecha_venc_inv;
			}

			$oItem->Laboratorio_Generico = $datos['titular'];
			$oItem->Laboratorio_Comercial = $datos['nombrerol'];
			if ($campos != '') {

				$oItem->save();
				guardarActividad($pr, $campos);
			}
			unset($oItem);
		}
	}
}

function ValidarProducto($cum)
{
	$tem = explode('-', $cum);
	$cum2 = $tem[0] . '-' . (int)$tem[1];
	$query = "SELECT Codigo_Cum FROM Producto WHERE Codigo_Cum='$cum' OR Codigo_Cum='$cum2' ";


	$oCon = new consulta();
	$oCon->setQuery($query);
	$producto = $oCon->getData();
	unset($oCon);

	return $producto['Codigo_Cum'] ? true : false;
}


function GetProductos()
{

	$query = "SELECT Codigo_Cum, group_concat(Id_Producto)as ID FROM Producto Where Tipo = 'Medicamento' And Id_Producto <=41387  group BY Codigo_Cum 
	ORDER BY Id_Producto DESC";

	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$productos = $oCon->getData();
	unset($oCon);

	return $productos;
}

function GetDatosInvima($cum)
{
	$rutas = array('wqeu-3uhz.json', '994u-gm46.json', '8tya-2uai.json', '6nr4-fx8r.json', '7c5e-muu4.json');
	if ($cum) {
		$cum = explode('-', $cum);
	} else {
		$cum = [];
	}
	$result = [];


	if (count($cum) > 1) {
		for ($i = 0; $i < count($rutas); $i++) {

			if ($i < 3) {
				$curl = curl_init();
				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => 'https://www.datos.gov.co/resource/' . $rutas[$i] . '?expediente=' . $cum[0] . '&consecutivocum=' . $cum[1],
					CURLOPT_USERAGENT => 'Codular Sample cURL Request'
				));
				// Send the request & save response to $resp
				$resp   = curl_exec($curl);
				$result = (array) json_decode($resp, true);

				if (count($result) > 0) {

					return $result[0];
				}

				// Close request to clear up some resources
				curl_close($curl);
			}
		}
	} else {
		for ($i = 0; $i < count($rutas); $i++) {

			if ($i > 3) {
				$curl = curl_init();
				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => 'https://www.datos.gov.co/resource/' . $rutas[$i] . '?expediente=' . $cum[0],
					CURLOPT_USERAGENT => 'Codular Sample cURL Request'
				));
				// Send the request & save response to $resp
				$resp   = curl_exec($curl);
				$result = (array) json_decode($resp, true);

				if (count($result) > 0) {
					return $result[0];
				}
				// Close request to clear up some resources
				curl_close($curl);
			}
		}
	}

	return $result;
}

function guardarActividad($productos, $campos)
{
	global $handle;
	$oItem = new complex('Actividad_Producto', "Id_Actividad_Producto");
	$oItem->Id_Producto = $productos['Id_Producto'];
	$oItem->Identificacion_Funcionario = '12345';
	$oItem->Detalles = "Se modificaron los siguientes parametros: $campos";
	$oItem->Fecha = date("Y-m-d H:i:s");
	$oItem->save();
	unset($oItem);
	$texto =  "(" . date('Y-m-d H:i:s') . ") $productos[Codigo_Cum] - $campos\n";
	echo $texto;
	fwrite($handle, $texto);
}
