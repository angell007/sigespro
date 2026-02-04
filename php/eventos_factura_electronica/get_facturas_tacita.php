<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.validacion_cufe.php';
include_once '../../config/config.inc.php';

$query = getQuery();
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");

$facturas = $oCon->getData();

foreach ($facturas as $i => $fact) {

	if (!validarTacita($fact)) {
		unset($facturas[$i]);
	} else {
		$facturas[$i] = $fact;
	}
}
$facturas = array_values($facturas);
echo json_encode($facturas);

function validarTacita($factura)
{
	if ($factura['Estado_Aceptacion'] == '' && $factura['Fecha_Acuse_Mercancia'] != '') {
		$dias = validarDiasHabiles($factura['Fecha_Acuse_Mercancia'], date('Y-m-d'));
		if ($dias > 3) {
			return true;
		}
	}
	return false;
}


function getQuery()
{
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
		AND F.Fecha_Acuse_Mercancia is not null
		AND F.Estado_Aceptacion is null
		ORDER BY F.Id_Factura_Venta Desc";
	return $query;
}


function validarDiasHabiles($fecha_inicio, $fecha_fin)
{
	$anio1 = date('Y', strtotime($fecha_inicio));
	$anio2 = date('Y', strtotime($fecha_fin));
	// echo json_encode($anio1);
	$oItem = new complex('Festivos_Anio', 'Anio', $anio1);
	$fests = $oItem->getData();
	$fests = $fests ? $fests['Festivos'] : actualizarFestivos($anio1);
	unset($oItem);
	if ($anio1 != $anio2) {
		$oItem = new complex('Festivos_Anio', 'Anio', $anio2);
		$fests2 = $oItem->getData();
		$fests .= $fests2 ? ";$fests2[Festivos]" : actualizarFestivos($anio2);
	}

	$fests = explode(';', $fests);
	return (diasHabiles($fecha_inicio, $fecha_fin, $fests));
}

function diasHabiles($inicio, $fin, $holidays)
{

	$start = new DateTime($inicio);
	$end = new DateTime($fin);
	//de lo contrario, se excluye la fecha de finalización (¿error?)
	$end->modify('+1 day');

	$interval = $end->diff($start);

	// total dias
	$days = $interval->days;

	// crea un período de fecha iterable (P1D equivale a 1 día)
	$period = new DatePeriod($start, new DateInterval('P1D'), $end);

	// almacenado como matriz, por lo que puede agregar más de una fecha feriada


	foreach ($period as $dt) {
		$curr = $dt->format('D');

		// obtiene si es Sábado o Domingo
		if ($curr == 'Sat' || $curr == 'Sun') {
			$days--;
		} elseif (in_array($dt->format('Y-m-d'), $holidays)) {
			$days--;
		}
	}
	return $days;
}

function actualizarFestivos($anio)
{
	global $API_CALENDAR;

	$oItem = new complex('Festivos_Anio', 'Anio', $anio);
	$festivos_base = $oItem->getData();

	// echo json_encode($festivos); exit;
	$festivos_base = $festivos_base ? explode(';', $festivos_base['Festivos']) : [];

	$xml_body = array(
		'year' => $anio,
		'api_key' => $API_CALENDAR,
		'country' => 'CO',
		'type' => 'national'
	);



	$ch = curl_init('https://calendarific.com/api/v2/holidays?' . http_build_query($xml_body));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$response = curl_exec($ch);
	$response = (array) json_decode($response, true);
	$festivos = array_map(function ($a) {
		return ($a['date']['iso']);
	}, $response['response']['holidays']);

    // echo json_encode($response); exit;
	$oItem->Festivos = implode(';', $festivos);
	$oItem->Anio = $anio;
	$oItem->save();
	return implode(';', $festivos);
}

function ArmarJson($idFactura)
{
	
}