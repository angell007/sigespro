<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$fechas = ( isset( $_REQUEST['fechas_indicadores'] ) ? $_REQUEST['fechas_indicadores'] : '' );

	$condicion = '';
	$condicion_cop = '';
	SetCondiciones($fechas);

	/*$query = 'SELECT DISTINCT 
	            (SELECT count(*) FROM `Orden_Compra_Nacional` '.$condicion.') as conteo , 
	            (SELECT count(*) FROM `Orden_Compra_Nacional` '.$condicion.' AND `Estado` = "Anulada") as "Anulados" , 
	            (SELECT count(*) FROM `Orden_Compra_Nacional` '.$condicion.' AND `Estado` = "No Conforme" ) as "No_Conformes" , 
	            (SELECT IFNULL(SUM(total),0) FROM Producto_Orden_Compra_Nacional '.$condicion.') as "Total_Compras" 
	          FROM `Orden_Compra_Nacional` ' ;*/

  $query = 'SELECT DISTINCT 
	            (SELECT count(*) FROM `Orden_Compra_Nacional` '.$condicion.') as conteo , 
	            (SELECT count(*) FROM `Orden_Compra_Nacional` '.$condicion.' AND `Estado` = "Anulada") as "Anulados" , 
	            (SELECT count(*) FROM `Orden_Compra_Nacional` '.$condicion.' AND `Estado` = "No Conforme" ) as "No_Conformes" , 
	            (SELECT IFNULL(SUM(Subtotal),0) FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion '.$condicion_cop.') as "Total_Compras" 
	          FROM `Orden_Compra_Nacional` ' ;

	$oCon= new consulta();
	$oCon->setQuery($query);
	$lista = $oCon->getData();
	unset($oCon);

	echo json_encode($lista);

	function SetCondiciones($fechas){
		global $condicion, $condicion_cop;

		if ($fechas == '') {
			$fecha_actual = Date('Y/m/d');

			//$fechas = $fecha_actual."-".$fecha_actual;
			$fechas = Date("Y-m-")."01 - ".$fecha_actual;
		}

		$fecha_separada = SepararFechas($fechas);
        $condicion = " WHERE DATE(Fecha) BETWEEN '".$fecha_separada[0]."' AND '".$fecha_separada[1]."'";

        $condicion_cop = " WHERE DATE(Fecha_Creacion) BETWEEN '".$fecha_separada[0]."' AND '".$fecha_separada[1]."'";

        return $condicion;
    }

    function SepararFechas($fechaString){
    	$splittedDate = explode(" - ", $fechaString);

    	return $splittedDate;
    }

?>

