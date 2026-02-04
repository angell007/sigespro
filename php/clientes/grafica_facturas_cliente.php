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

	$query = 'SELECT ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
    FROM Producto_Factura_Venta PAR
    WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
    FROM Producto_Factura_Venta PAR
    WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
    FROM Producto_Factura_Venta PAR
    WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)) as Total
    FROM Factura_Venta F 
    INNER JOIN Producto_Factura_Venta PFV ON F.Id_Factura_Venta=PFV.Id_Factura_Venta
    WHERE F.Fecha_Documento LIKE "%'.date("Y-m",$h).'%" AND F.Id_Cliente='.$id.' AND F.Estado="Pendiente" 
    UNION(
    SELECT  
    ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota) AS Total


    FROM Factura FT
    WHERE FT.Fecha_Documento LIKE "%'.date("Y-m",$h).'%" AND FT.Estado_Factura = "Sin Cancelar" AND FT.Id_Cliente = '.$id.'
)
UNION(
    SELECT
    (IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) - FC.Cuota_Moderadora) as Total

    FROM
    Factura_Capita FC
    WHERE FC.Fecha_Documento LIKE "%'.date("Y-m",$h).'%" AND Estado_Factura = "Sin Cancelar" AND FC.Id_Cliente = '.$id.'
)
    ' ;
 

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
	$oCon->setQuery($query);
	$Ventas = $oCon->getData();
    unset($oCon);	
    $valor=0;
    foreach ($Ventas as  $value) {
        $total_cartera+=$value['Total'];
        $valor+=(float)$value['Total'];
    }
    $final[$i]["Ventas"] = number_format((float)$valor,0,"","");
    $final[$i]["Mes"] = $meses[date("n",$h)-1];
  
}


$query = '
SELECT SUM(R.Facturas) as Facturas_Totales FROM 
(SELECT  COUNT(*) as Facturas
FROM Factura_Venta F 
WHERE  F.Id_Cliente='.$id.' AND F.Estado="Pendiente" 
UNION(
SELECT  COUNT(*) as Facturas
FROM Factura FT
WHERE  FT.Estado_Factura = "Sin Cancelar" AND FT.Id_Cliente = '.$id.'
)
UNION(
SELECT COUNT(*) as Facturas
FROM
Factura_Capita FC
WHERE  Estado_Factura = "Sin Cancelar" AND FC.Id_Cliente = '.$id.'
)) R
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