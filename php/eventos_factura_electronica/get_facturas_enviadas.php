<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta_paginada.php';
include_once '../../class/class.validacion_cufe.php';

$codigo = (isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '');
$fechas = (isset($_REQUEST['fechas']) ? $_REQUEST['fechas'] : '');
$cliente = (isset($_REQUEST['cliente']) ? $_REQUEST['cliente'] : '');
$cufe = (isset($_REQUEST['Cufe']) ? $_REQUEST['Cufe'] : '');
// $id = (isset($_REQUEST['id_documento']) ? $_REQUEST['id_documento'] : '');
$pag = (isset($_REQUEST['pag']) ? $_REQUEST['pag'] : 1);
$tam = (isset($_REQUEST['tam']) ? $_REQUEST['tam'] : 20);
$estado_aceptacion = (isset($_REQUEST['Estado_Aceptacion']) ? $_REQUEST['Estado_Aceptacion'] : '');
$acuse_servicio = (isset($_REQUEST['Acuse_Servicio_Codigo']) ? $_REQUEST['Acuse_Servicio_Codigo'] : '');
$acuse_factura = (isset($_REQUEST['Acuse_Factura_Codigo']) ? $_REQUEST['Acuse_Factura_Codigo'] : '');


$limit = ($pag - 1) * $tam;




$query = getQuery();
$oCon = new consulta();
$oCon->setQuery($query . " Limit $limit, $tam");
$oCon->setTipo("Multiple");

$facturas = $oCon->getData();

echo json_encode($facturas);


function getQuery()
{
	global $codigo, $fechas, $cliente, $id, $cufe, $estado_aceptacion, $acuse_servicio, $acuse_factura;

	$condiciones = [];

	if ($codigo != '') {
		array_push($condiciones, "F.Codigo like '%$codigo%'");
	}
	if ($fechas != '') {
		$fechas = str_replace(" - ", " 00:00:00' AND '", $fechas) . ' 23:59:00';
		array_push($condiciones, "F.Fecha_Documento BETWEEN '$fechas'");
	}
	if ($id != '') {
		array_push($condiciones, "F.Id_Factura_Venta ='$id'");
	}
	if ($cufe != '') {
		array_push($condiciones, "F.Cufe like '%$cufe%'");
	}
	if ($cliente != '') {
		$proveedor = str_replace(" ", "%", $cliente);
		$having = "HAVING Cliente like '%$proveedor%'";
	}
	switch ($estado_aceptacion) {

		case 'rechazo':
			array_push($condiciones, "F.Estado_Aceptacion ='031'");
			break;
		case 'tacita':
			array_push($condiciones, "F.Estado_Aceptacion ='034'");
			break;
		case 'expresa':
			array_push($condiciones, "F.Estado_Aceptacion ='033'");
			break;
		case 'pend':
			array_push($condiciones, "F.Estado_Aceptacion IS NULL");
			break;
		default:
			break;
	}
	switch ($acuse_factura) {
		case 'pend':
			array_push($condiciones, "F.Fecha_Acuse_Factura IS NULL");
			break;
		case 'generado':
			array_push($condiciones, "F.Fecha_Acuse_Factura IS NOT NULL");
			break;

		default:
			break;
	}
	switch ($acuse_servicio) {
		case 'pend':
			array_push($condiciones, "F.Fecha_Acuse_Mercancia IS NULL");
			break;
		case 'generado':
			array_push($condiciones, "F.Fecha_Acuse_Mercancia IS NOT NULL");
			break;
		default:
			break;
	}

	$condiciones = count($condiciones) > 0 ? 'AND ' . implode(" AND ", $condiciones) : '';
	$query = "SELECT 
			SQL_CALC_FOUND_ROWS
			F.Codigo, 
			F.Id_Factura_Venta, 
			F.Fecha_Documento, 
			F.Cufe, 
			(CASE
				WHEN C.Tipo = 'Juridico' THEN C.Razon_Social
				ELSE  COALESCE(C.Nombre, CONCAT_WS(' ',C.Primer_Nombre,C.Segundo_Nombre,C.Primer_Apellido,C.Segundo_Apellido) )

			END) AS Cliente,
			F.Fecha_Acuse_Factura, 
			F.Fecha_Acuse_Mercancia,
			F.Estado_Aceptacion,
			F.Fecha_Estado, ATA.*
		FROM Factura_Venta F
		Inner Join Cliente C on C.Id_Cliente = F.Id_Cliente
		LEFT JOIN ( Select ATA.Codigo as Tacita_Codigo, ATA.Id_Factura, ATA.Fecha as Tacita_Fecha, ATA.Procesada as Tacita_Procesada
			From Aceptacion_Tacita ATA
		) ATA on ATA.Id_Factura = F.Id_Factura_Venta
		Where F.Fecha_Documento > '2022-07-13 00:00:00' 
		AND F.Condicion_Pago > 1
		AND F.Procesada='true'
		$condiciones
		$having
		ORDER BY F.Id_Factura_Venta Desc";
	return $query;
}
