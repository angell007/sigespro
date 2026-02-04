<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
ini_set("memory_limit", "32000M");
ini_set('max_execution_time', 0);

include_once '/home/sigesproph/public_html/class/class.lista.php';
include_once '/home/sigesproph/public_html/class/class.complex.php';
include_once '/home/sigesproph/public_html/class/class.consulta.php';
include_once '/home/sigesproph/public_html/class/class.validacion_cufe.php';
include_once '/home/sigesproph/public_html/config/config.inc.php';


$query = getQuery();
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");

$facturas = $oCon->getData();

$query = getQuery1();
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");

$facturas1 = $oCon->getData();

// echo json_encode($facturas1); exit;
echo '[';

foreach ($facturas1 as $i => $fact) {
	if ($fact['Estado_Aceptacion'] == '') {
		$oItem = new complex('Factura_Recibida', "Id_Factura_Recibida", $fact["Id_Factura_Recibida"]);
		
		if(validarTacita($fact)){
		    	$oItem->Factura_Aceptada ='Aceptacion Tacita';
		}
		$oItem->save();
		unset($oItem);
		echo json_encode($fact).',';
	}
}
// echo '{}]'; exit;
foreach ($facturas as $i => $fact) {
	if ($fact['Estado_Aceptacion'] == '') {
		$eventos = new ValidarCufe($fact['Cufe']);
		$fact['eventos'] = $eventos->getEstructura();
		$oItem = new complex($fact['Tipo'], "Id_$fact[Tipo]", $fact["Id_$fact[Tipo]"]);
		foreach ($fact['eventos']['Eventos'] as $evento) {
		   
			switch ($evento['Codigo']) {
				case '030':
					$oItem->Fecha_Acuse_Factura = $evento['Fecha'];
					$fact['Fecha_Acuse_Factura'] = $evento['Fecha'];
					break;
				case '032':
					$oItem->Fecha_Acuse_Mercancia = $evento['Fecha'];
					$fact['Fecha_Acuse_Mercancia'] = $evento['Fecha'];
					break;

				default:
					$oItem->Estado_Aceptacion = $evento['Codigo'];
					$oItem->Fecha_Estado = $evento['Fecha'];
					$fact['Estado_Aceptacion'] = $evento['Codigo'];
					$fact['Fecha_Estado'] = $evento['Fecha'];
					break;
			}
		}
		if($fact['Estado_Aceptacion'] ==''){
		    	$oItem->Fecha_Estado ='';
		}
		$oItem->save();
		unset($oItem);
		echo json_encode($fact).',';
	}
}
echo '{}]';

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
	        'Factura_Venta' as Tipo,
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
			F.Fecha_Estado
		FROM Factura_Venta F
		Inner Join Cliente C on C.Id_Cliente = F.Id_Cliente
		Where F.Fecha_Documento > '2022-07-13 00:00:00' 
		AND F.Condicion_Pago > 1
		AND F.Procesada='true'
		AND F.Estado_Aceptacion is null
		-- AND F.Fecha_Reporte_Mercancia is  null
		ORDER BY F.Id_Factura_Venta DESC";
	return $query;
}
function getQuery1()
{
	$query = "SELECT 
    SQL_CALC_FOUND_ROWS
    IF(P.Tipo='Juridico', P.Razon_Social, COALESCE(P.Nombre, CONCAT_WS(' ',P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido))) AS Proveedor,
    ARBS.*, 
    F.* , 
    IFNULL(F.Factura_Aceptada, AEF.Aceptacion_Factura_Funcionario) as Aceptacion_Factura_Funcionario,
    IFNULL(F.Factura_Aceptada, AEF.Aceptacion_Factura_Codigo) as Estado_Aceptacion 
    FROM Factura_Recibida F
    INNER JOIN Proveedor P ON P.Id_Proveedor = F.Id_Proveedor
    Inner JOIN
    ( 	SELECT ARF.Codigo AS Acuse_Factura_Codigo,
        ARF.Cude AS Acuse_Factura_Cude,
        ARF.Procesada AS Acuse_Factura_Procesada,
        CONCAT_WS(' ', FARF.Nombres, FARF.Apellidos) AS Acuse_Factura_Funcionario,
        ARF.Fecha AS Acuse_Factura_Fecha ,
        ARF.Id_Factura_Recibida as Id_Seguimiento
        FROM  Acuse_Recibo_Factura ARF 
        INNER JOIN Funcionario FARF ON FARF.Identificacion_Funcionario = ARF.Identificacion_Funcionario
    )ARF ON ARF.Id_Seguimiento = F.Id_Factura_Recibida
    Inner JOIN
    ( 	SELECT       
        ARBS.Fecha AS Fecha_Acuse_Mercancia,
        ARBS.Codigo AS Acuse_Servicio_Codigo,
        ARBS.Cude AS Acuse_Servicio_Cude,
        CONCAT_WS(' ', FARBS.Nombres, FARBS.Apellidos) AS Acuse_Servicio_Funcionario,
        ARBS.Procesada AS Acuse_Servicio_Procesada,
        ARBS.Id_Factura_Recibida as Seguimiento_Id
        FROM  Acuse_Recibo_Bien_Servicio ARBS 
        INNER JOIN Funcionario FARBS ON FARBS.Identificacion_Funcionario = ARBS.Identificacion_Funcionario
    )ARBS ON ARBS.Seguimiento_Id = F.Id_Factura_Recibida
      
    LEFT JOIN
    ( 	SELECT       
        AEF.Fecha AS Aceptacion_Factura_Fecha,
        AEF.Codigo AS Aceptacion_Factura_Codigo,
        AEF.Cude AS Aceptacion_Factura_Cude,
        CONCAT_WS(' ', FAEF.Nombres, FAEF.Apellidos) AS Aceptacion_Factura_Funcionario,
        AEF.Procesada AS Aceptacion_Factura_Procesada,
        AEF.Id_Factura_Recibida as Id_Seg
        FROM  Aceptacion_Expresa_Factura AEF
        INNER JOIN Funcionario FAEF ON FAEF.Identificacion_Funcionario = AEF.Identificacion_Funcionario
    )AEF ON AEF.Id_Seg = F.Id_Factura_Recibida
    
    LEFT JOIN
    ( 	SELECT       
        RF.Fecha AS Rechazo_Fecha,
        RF.Codigo AS Rechazo_Codigo,
        RF.Cude AS Rechazo_Cude,
        CONCAT_WS(' ', FRF.Nombres, FRF.Apellidos) AS Rechazo_Funcionario,
        RF.Procesada AS Rechazo_Procesada,
        RF.Id_Factura_Recibida as Seguimiento
        FROM Rechazo_Factura RF 
        INNER JOIN Funcionario FRF ON FRF.Identificacion_Funcionario = RF.Identificacion_Funcionario
    )RF ON RF.Seguimiento = F.Id_Factura_Recibida
	Where RF.Rechazo_Codigo is null and AEF.Aceptacion_Factura_Codigo is null
	And F.Factura_Aceptada is null
	";
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