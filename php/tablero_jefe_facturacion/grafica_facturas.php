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

	$query = 'SELECT (SElECT SUM((PF1.Cantidad*PF1.Precio)-(PF1.Descuento*PF1.Cantidad)+(((PF1.Cantidad*PF1.Precio)-(PF1.Descuento*PF1.Cantidad))*PF1.Impuesto/100)) FROM Producto_Factura PF1 WHERE PF1.Id_Factura=F.Id_Factura) as Resultado, F.Cuota as Cuota
    FROM Factura F
    WHERE F.Fecha_Documento LIKE "%'.date("Y-m",$h).'%"  AND F.Estado_Factura != "Anulada"' ;

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$facturas = $oCon->getData();
    unset($oCon);

	$query = 'SELECT (SElECT SUM(PF1.Total) FROM Descripcion_Factura_Capita PF1 WHERE PF1.Id_Factura_Capita=F.Id_Factura_Capita) as Resultado, F.Cuota_Moderadora as Cuota
    FROM Factura_Capita F
    WHERE F.Fecha_Documento LIKE "%'.date("Y-m",$h).'%" AND F.Estado_Factura != "Anulada" ' ;

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$capitas = $oCon->getData();
    unset($oCon);

    $subtotal=0;
    $subtotal_capita=0;

    foreach ($facturas as $value) {
       $subtotal+=$value['Resultado']-$value['Cuota'];    
    }
    foreach ($capitas as $value) {
       $subtotal_capita+=$value['Resultado']-$value['Cuota'];    
    }

    $final[$i]["Subtotal"] = number_format((float)$subtotal,0,"","");
    $final[$i]["Capita"] = number_format((float)$subtotal_capita,0,"","");
    $final[$i]["Mes"] = $meses[date("n",$h)-1];
}

echo json_encode($final);
?>