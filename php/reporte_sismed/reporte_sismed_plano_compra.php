<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$meses= ( isset( $_REQUEST['meses'] ) ? $_REQUEST['meses'] : '' );
$ano= ( isset( $_REQUEST['ano'] ) ? $_REQUEST['ano'] : '' );

$meses=explode("-", $meses);


header('Content-Type: text/plain; ');
header('Content-Disposition: attachment; filename="Reporte_Sismed.txt"');

$resultado = [];

for ($i=0; $i < count($meses); $i++) { 
    $query2 = 'SELECT
    MONTH(FAR.Fecha_Factura) as Mes,
    P.Codigo_Cum,
    PR.Precio as Precio_Regulacion,
    MAX(PAR.Precio) as Maximo,
    MIN(PAR.Precio) as Minimo,
    MAX(CONCAT(PAR.Precio,"-",FAR.Factura)) AS Maximo_Factura,
    MIN(CONCAT(PAR.Precio,"-",FAR.Factura)) AS Mimimo_Factura,
    SUM(PAR.Precio*PAR.Cantidad) AS Precio,
    SUM(PAR.Cantidad) AS Cantidad,
    IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," (",P.Nombre_Comercial, ") ", P.Cantidad," ", P.Unidad_Medida, " "), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as Nombre_Producto
    FROM
    Producto_Acta_Recepcion PAR
    INNER JOIN Factura_Acta_Recepcion FAR ON PAR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura
    INNER JOIN Producto P ON P.Id_Producto = PAR.Id_Producto
    LEFT JOIN Precio_Regulado PR ON P.Codigo_Cum = PR.Codigo_Cum
    WHERE MoNTH(FAR.Fecha_Factura)='.$meses[$i].' AND YEAR(FAR.Fecha_Factura)='.$ano.' AND P.Id_Categoria IN (12,8,9,3,5,10) GROUP by P.Codigo_Cum;';
    


    $oCon= new consulta();
    $oCon->setQuery($query2);
    $oCon->setTipo('Multiple');
    $resultado[] = $oCon->getData();
    unset($oCon);
}

$resultado=array_merge($resultado[0],$resultado[1],$resultado[2]);

$j=1;
$i=0;
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
$fecha=date("Y");
$nit=explode("-",$config['NIT']);
$nitnumero=str_replace(".","",$nit[0]);
$contador=0;
$texto='';
foreach($resultado as $item){ $j++;$i++;
    $factura=explode("-",$item['Minimo_Factura']);
    $factura_maxima=explode("-",$item['Maximo_Factura']);

    $texto.="2,".$i.",".$item["Mes"].",INS,".$item["Codigo_Cum"].",".$item["Minimo"].",".$item["Maximo"].",".number_format($item["Precio"],2,".","").",".$item["Cantidad"].",".$factura[1].",".$factura_maxima[1]."\r\n";
    
    $contador+=$item["Precio"];
}

echo "1,1,NI,".$nitnumero.",".$nit[1].",".$fecha.",".$meses[0].",".count($resultado).",".number_format($contador,2,".","").", ,"."\r\n";
echo $texto;

?>