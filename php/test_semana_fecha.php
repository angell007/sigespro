<?
	$dia = ( isset( $_REQUEST['dia'] ) ? $_REQUEST['dia'] : '' );
	$mes = ( isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '' );
	$anio = ( isset( $_REQUEST['anio'] ) ? $_REQUEST['anio'] : '' );

	function numberOfWeek ($dia, $mes, $ano) {


		$hora = date('H');
		$min = date('i');
		$seg = date('s');

	//generamos la fecha para el día 1 del mes y año especificado
	$fecha = mktime ($hora, $min, $seg, $mes, 1, $ano);

	/*
	El número de semana en el que nos encontramos será igual a:
	– el día espeficado +
	– el número de día de la semana (lunes, martes …) al que se corresponde la fecha almacenada en $fecha – 1
	– entre 7 días que tiene la semana.

	Quedando la fórmula de la siguiente manera …
	*/
	$numberOfWeek = ceil (($dia + (date ("w", $fecha)-1)) / 7);

	return $numberOfWeek;
	}

	//mostramos en pantalla el resultado devuelto por la función
/*
	echo numberOfWeek ($dia, $mes, $anio);*/
	echo "adsasd";
	echo date("j, d-M-Y", strtotime("next monday")); 

?>