<?php
ini_set('memory_limit', '2048M');
set_time_limit(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Edades Cartera Cliente.xls"');
header('Cache-Control: max-age=0'); 

$objPHPExcel = new PHPExcel;

$condiciones = SetCondiciones();


$query = "
SELECT
r.Nit,
r.Nombre_Cliente,
r.Zona,
SUM(r.Valor_Saldo) AS Valor_Saldo,
SUM(r.Sin_Vencer) AS Sin_Vencer,
SUM(r.first_thirty_days) AS first_thirty_days,
SUM(r.thirtyone_sixty) AS thirtyone_sixty,
SUM(r.sixtyone_ninety) AS sixtyone_ninety,
SUM(r.ninetyone_onehundeight) AS ninetyone_onehundeight,
SUM(r.onehundeight_threehundsix) AS onehundeight_threehundsix,
SUM(r.mayor_threehundsix) AS mayor_threehundsix
FROM
(
	SELECT 
DATE_FORMAT(MC.Fecha_Movimiento, '%d/%m/%Y') AS Fecha,
MC.Documento AS Factura,
(CASE PC.Naturaleza
	WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
	ELSE (SUM(MC.Debe) - SUM(MC.Haber))
END) AS Valor_Saldo,
PC.Naturaleza AS Nat,
MC.Nit,
IF(CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido) != '',
	CONCAT_WS(' ',
			C.Primer_Nombre,
			C.Segundo_Nombre,
			C.Primer_Apellido,
			C.Segundo_Apellido),
	C.Razon_Social) AS Nombre_Cliente,
	Z.Nombre AS Zona,
IF(IFNULL(IF(C.Condicion_Pago > 0,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) = 0,
	(CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END),
	0) AS Sin_Vencer,
	
	IF(IFNULL(IF(C.Condicion_Pago > 0,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 1 AND 30, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS first_thirty_days,
	
	IF(IFNULL(IF(C.Condicion_Pago > 0,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 31 AND 60, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS thirtyone_sixty,
	
	IF(IFNULL(IF(C.Condicion_Pago > 0,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 61 AND 90, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS sixtyone_ninety,
	
	IF(IFNULL(IF(C.Condicion_Pago > 0,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 91 AND 180, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS ninetyone_onehundeight,
	
	IF(IFNULL(IF(C.Condicion_Pago > 0,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) BETWEEN 181 AND 360, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS onehundeight_threehundsix,
	
	IF(IFNULL(IF(C.Condicion_Pago > 0,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) > 360, (CASE PC.Naturaleza
		WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
		ELSE (SUM(MC.Debe) - SUM(MC.Haber))
	END), 0) AS mayor_threehundsix
	
FROM
Movimiento_Contable MC
	INNER JOIN
Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
	INNER JOIN
Cliente C ON C.Id_Cliente = MC.Nit
INNER JOIN Zona Z ON C.Id_Zona = Z.Id_Zona
WHERE
MC.Estado != 'Anulado'
	AND Id_Plan_Cuenta = 57
	$condiciones
GROUP BY MC.Id_Plan_Cuenta , MC.Documento, MC.Nit
HAVING Valor_Saldo != 0
ORDER BY MC.Fecha_Movimiento
) r GROUP BY r.Nit
";
	
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$edades= $oCon->getData();
unset($oCon);

$contenido = '

<table>
	<tr style="background:#000;color:#FFF">
		<th>NIT CLIENTE</th>
		<th>RAZON SOCIAL</th>
		<th>ZONA</th>
		<th>SALDO</th>
		<th>SIN VENCER</th>
		<th>1 - 30</th>
		<th>31 - 60</th>
		<th>61 - 90</th>
		<th>91 - 180</th>
		<th>181 - 360</th>
		<th>MAYOR DE 360</th>
	</tr>
';

foreach($edades as $value){ $j++;
	$contenido .= '<tr><td>' .$value["Nit"] . '</td>';
	$contenido .= '<td>' .$value["Nombre_Cliente"] . '</td>';
	$contenido .= '<td>' .$value["Zona"] . '</td>';
	$contenido .= '<td>' .number_format($value["Valor_Saldo"],2,",","") . '</td>';
	$contenido .= '<td>' .number_format($value["Sin_Vencer"],2,",","") . '</td>';
	$contenido .= '<td>' .number_format($value["first_thirty_days"],2,",","") . '</td>';
	$contenido .= '<td>' .number_format($value["thirtyone_sixty"],2,",","") . '</td>';
	$contenido .= '<td>' .number_format($value["sixtyone_ninety"],2,",","") . '</td>';
	$contenido .= '<td>' .number_format($value["ninetyone_onehundeight"],2,",","") . '</td>';
	$contenido .= '<td>' .number_format($value["onehundeight_threehundsix"],2,",","") . '</td>';
	$contenido .= '<td>' .number_format($value["mayor_threehundsix"],2,",","") . '</td></tr>';
}

$contenido .= '</table>';

echo $contenido;

function SetCondiciones(){

    $condicion = '';

    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente']) {
        $condicion .= " AND MC.Nit = $_REQUEST[cliente]";
	}
	if (isset($_REQUEST['zona']) && $_REQUEST['zona']) {
        $condicion .= " AND Z.Id_Zona = $_REQUEST[zona]";
    }

    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas']) {        
        $fecha = $_REQUEST['fechas'];
		$condicion .= " AND (DATE(MC.Fecha_Movimiento) <= '$fecha')";
    }

    return $condicion;
}


?>