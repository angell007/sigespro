<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$condicion = '';

if ($funcionario != '') {
	$condicion = ' AND FV.Id_Funcionario ='.$funcionario;
}

$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$final=[];
for($i=1;$i<=12;$i++){

	$query = 'SELECT sum(PFV.Subtotal) as Subtotal
	FROM `Factura_Venta` FV , Producto_Factura_Venta PFV 
	WHERE PFV.Id_Factura_Venta = FV.Id_Factura_Venta
	AND FV.Fecha_Documento LIKE "%'.date("Y-").str_pad($i, 2, '0', STR_PAD_LEFT).'%"'
	.$condicion;
	
	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$mes = $oCon->getData();
	unset($oCon);
	if(isset($mes[0]["Subtotal"])){
	   $final[$i-1]["Subtotal"] = $mes[0]["Subtotal"];
	}else{
	   $final[$i-1]["Subtotal"] = 0;
	}
	
	$final[$i-1]["Mes"] = $meses[$i-1];
}




echo json_encode($final);


?>