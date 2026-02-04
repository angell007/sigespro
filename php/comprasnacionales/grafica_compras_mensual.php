<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$final=[];
$fecha = date('Y-m-01');
$nuevafecha = strtotime ( '-3 month' , strtotime ( $fecha ) ) ;

$startTime = $nuevafecha;
$endTime = strtotime ($fecha);

$i=-1;

for($h=$startTime;$h<=$endTime; $h=strtotime("+1 Month",$h)){ $i++;
	$query = 'SELECT SUM(POCN.Total) AS Subtotal
    FROM Orden_Compra_Nacional OCN 
    INNER JOIN Producto_Orden_Compra_Nacional POCN
    ON OCN.Id_Orden_Compra_Nacional=POCN.Id_Orden_Compra_Nacional
    WHERE OCN.Fecha_Creacion_Compra LIKE "%'.date("Y-m",$h).'%"' ;
	
	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$mes = $oCon->getData();
	unset($oCon);
	if(isset($mes[0]["Subtotal"])){
	   $final[$i]["Subtotal"] = number_format((float)$mes[0]["Subtotal"],0,"","");
	}else{
	   $final[$i]["Subtotal"] = 0;
	}
	
	$final[$i]["Mes"] = $meses[date("n",$h)-1];
}




echo json_encode($final);


?>