<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$final=[];
 
$fecha = date('Y-m-01');
$nuevafecha = strtotime ( '-5 month' , strtotime ( $fecha ) ) ;

$startTime = $nuevafecha;
$endTime = strtotime ($fecha);



$i=-1;
$total_cartera=0;
for($h=$startTime;$h<=$endTime; $h=strtotime("+1 Month",$h)){ $i++;

	$query = 'SELECT
        ((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
        FROM Producto_Acta_Recepcion PAR2
        WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
        FROM Producto_Acta_Recepcion PAR2
        WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)*(PAR2.Impuesto/100)),0)
        FROM Producto_Acta_Recepcion PAR2
        WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) as Total 
        FROM  Factura_Acta_Recepcion FAR
        INNER JOIN Acta_Recepcion AR
        ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
        INNER JOIN Proveedor P
        ON AR.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Producto_Acta_Recepcion PAR
        ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
        WHERE AR.Id_Proveedor = '.$id.' AND FAR.Estado = "Pendiente" AND  AR.Fecha_Creacion LIKE "%'.date("Y-m",$h).'%"
        GROUP BY FAR.Id_Acta_Recepcion, FAR.Factura ORDER BY FAR.Fecha_Factura DESC
    ' ;
 

    $oCon= new consulta();
	$oCon->setQuery($query);
	$Ventas = $oCon->getData();
    unset($oCon);	
    $total_cartera+=$Ventas['Total'];
    $valor+=(float)$Ventas['Total'];
    
    $final[$i]["Ventas"] = number_format((float)$valor,0,"","");
    $final[$i]["Mes"] = $meses[date("n",$h)-1];
  
}


$query = 'SELECT  COUNT(*) as Facturas_Totales
        FROM
        Factura_Acta_Recepcion FAR
        INNER JOIN Acta_Recepcion AR
        ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
        
        WHERE AR.Id_Proveedor = '.$id.' AND FAR.Estado = "Pendiente" AND  AR.Fecha_Creacion 
        
' ;


$oCon= new consulta();
$oCon->setQuery($query);
$facturas = $oCon->getData();
unset($oCon);	

$resultado['Grafica']=$final;
$resultado['Cartera']=number_format($total_cartera,2,".",",");
$resultado['Facturas']=number_format($facturas['Facturas_Totales'],0,"","");
echo json_encode($resultado);
?>