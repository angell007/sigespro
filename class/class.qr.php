<?php

require_once('phpqrcode/qrlib.php');


function generarqr($tipo, $id, $ruta)
{
	$errorCorrectionLevel = 'H';
	$matrixPointSize = min(max((int)5, 1), 10);
	$nombre = md5($tipo . '|' . $id . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
	$filename = $_SERVER["DOCUMENT_ROOT"] . "/" . $ruta . $nombre;
	QRcode::png($tipo . "/" . $id, $filename, $errorCorrectionLevel, $matrixPointSize, 5);

	return ($nombre);
}
function generarqrFE($qr)
{
	$ruta = "ARCHIVOS/FACTURACION_ELECTRONICA/";
	$errorCorrectionLevel = 'H';
	$matrixPointSize = min(max((int)5, 1), 10);
	$nombre = md5($qr) . '.png';
	$filename = $_SERVER["DOCUMENT_ROOT"] . "/" . $ruta . $nombre;

	// Asegurar que el directorio existe
	$dir = dirname($filename);
	if (!is_dir($dir)) {
		mkdir($dir, 0777, true);
	}

	QRcode::png($qr, $filename, $errorCorrectionLevel, $matrixPointSize, 10);

	return ($nombre);
}
