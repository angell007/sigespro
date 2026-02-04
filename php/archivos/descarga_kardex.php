<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte-Kardex.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$condicion = '';
$condicion2=''; 
$condicion3=''; 
$condicion4=''; 
$condicion5=''; 
$tipo = $_REQUEST['tipo'];
$idTipo = $_REQUEST['idtipo'];
$producto = $_REQUEST['producto'];
$ruta = '';
$tabla = '';
$tablaDest = '';
$attrFecha = '';
$query_dispensaciones = '';

if ($tipo == 'Bodega') {
    $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Bodega'";
    $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Bodega'";
    // $condicion2 .= " AND AR.Id_Bodega=$idTipo";
    $condicion2 .= "";
    $condicion4 .= " AND INF.Bodega=$idTipo";
    $condicion5 .= " AND Id_Bodega=$idTipo";
    $ruta = 'actarecepcionver';
    $tabla = 'Acta_Recepcion';
    $tablaDest = 'Bodega';
    $attrFecha = 'Fecha_Creacion';
} else {
    $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Punto_Dispensacion'";
    $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Punto'";
    $condicion2 .= " AND AR.Id_Punto_Dispensacion=$idTipo";
    $condicion4 .= " AND INF.Bodega=''";
    $condicion5 .= " AND Id_Punto_Dispensacion=$idTipo";
    $ruta = 'actarecepcionremisionver';
    $tabla = 'Acta_Recepcion_Remision';
    $tablaDest = 'Punto_Dispensacion'; 
    $attrFecha = 'Fecha';

    $query_dispensaciones .= 'UNION (SELECT INF.Id_Inventario_Fisico_Punto AS ID, "" AS Nombre_Origen, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, "inventario_fisico_puntos/descarga_pdf.php" AS Ruta, "Inventario" AS Tipo, CONCAT("INVF",INF.Id_Inventario_Fisico_Punto) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Cantidad_Final) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico_Punto PIF INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto WHERE INF.Estado="Terminado" AND PIF.Id_Producto = ' . $producto . ' AND INF.Id_Punto_Dispensacion = ' . $idTipo . ' GROUP BY PIF.Id_Producto, INF.Fecha_Fin)
    
    UNION (SELECT D.Id_Dispensacion AS ID, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion) AS Nombre_Origen, (SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") FROM Paciente WHERE Id_Paciente=D.Numero_Documento) AS Destino, "dispensacion" AS Ruta, "Salida" AS Tipo, D.Codigo,IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion),D.Fecha_Actual) AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion WHERE D.Estado_Dispensacion != "Anulada" AND PD.Cantidad_Entregada!=0 AND PD.Lote != "Pendiente" AND PD.Id_Producto = ' . $producto . ' AND D.Id_Punto_Dispensacion = ' . $idTipo . ')';
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND R.Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
    $condicion2.= " AND AR.$attrFecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
}


$ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha_inicio)),1,date("Y",strtotime($fecha_inicio)))-1));

$query_inicial = 'SELECT SUM(Cantidad) as Total
FROM Saldo_Inicial_Kardex 
WHERE Id_Producto = '.$producto.' AND Fecha="'.$ultimo_dia_mes.'" '.$condicion5.' GROUP BY Id_Producto';
$oCon= new consulta();
$oCon->setQuery($query_inicial);
$res = $oCon->getData();
unset($oCon);

$acum=$total=(INT)$res["Total"];

$query = '(SELECT R.Id_Remision as ID,
R.Nombre_Origen, 
(CASE   
      WHEN R.Tipo="Cliente" THEN CONCAT(R.Id_Destino," - ",R.Nombre_Destino)   
      WHEN R.Tipo="Interna" THEN R.Nombre_Destino   
END) as Destino,
"remision" as Ruta, "Salida" as Tipo, CONCAT(R.Codigo," - (", R.Estado,")") AS Codigo, R.Fecha as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
FROM Producto_Remision PR
INNER JOIN Remision R
ON R.Id_Remision = PR.Id_Remision
LEFT JOIN Factura_Venta F
ON F.Id_Factura_Venta = R.Id_Factura
WHERE R.Estado IN ("Pendiente","Alistada","Enviada","Facturada","Recibida") AND PR.Id_Producto = '.$producto.$condicion.') 

UNION (SELECT R.Id_Remision as ID,
R.Nombre_Origen, 
(CASE   
      WHEN R.Tipo="Cliente" THEN CONCAT(R.Id_Destino," - ",R.Nombre_Destino)   
      WHEN R.Tipo="Interna" THEN R.Nombre_Destino   
END) as Destino,
"remisionantigua" as Ruta, "Salida" as Tipo, R.Codigo, R.Fecha as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
FROM Producto_Remision_Antigua PR
INNER JOIN Remision_Antigua R
ON R.Id_Remision = PR.Id_Remision
LEFT JOIN Factura_Venta F
ON F.Id_Factura_Venta = R.Id_Factura
WHERE PR.Id_Producto = '.$producto.$condicion.') 

UNION (SELECT AI.Id_Ajuste_Individual as ID,
IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.')) AS Nombre_Origen,IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'),"") as Destino,
"ajusteinventariover" as Ruta, AI.Tipo, AI.Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
FROM Producto_Ajuste_Individual PAI
INNER JOIN Ajuste_Individual AI
ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
WHERE PAI.Id_Producto = '.$producto.$condicion3.') 

UNION (SELECT AR.Id_'.$tabla.' as ID, "" as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "'.$ruta.'" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.'.$attrFecha.' as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
FROM Producto_'.$tabla.' PAR
INNER JOIN '.$tabla.' AR
ON PAR.Id_'.$tabla.' = AR.Id_'.$tabla.'
WHERE PAR.Id_Producto = '.$producto.$condicion2. ') 

UNION (SELECT INF.Id_Inventario_Fisico AS ID, "" AS Nombre_Origen, (SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Destino, "inventariofisico/inventario_final_pdf.php" AS Ruta, "Inventario" AS Tipo, CONCAT("INVF",INF.Id_Inventario_Fisico) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Segundo_Conteo) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico PIF INNER JOIN Inventario_Fisico INF ON PIF.Id_Inventario_Fisico=INF.Id_Inventario_Fisico WHERE INF.Estado="Terminado" AND PIF.Id_Producto = '.$producto.$condicion4. ' GROUP BY PIF.Id_Inventario_Fisico) '.$query_dispensaciones.' ORDER BY Fecha ASC';


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Kardex');


$acum=$total=(INT)$res["Total"];
$i=-1;
$objSheet->getCell('I1')->setValue('INICIAL');
$objSheet->getCell('J1')->setValue($total);
$j=1;
foreach($resultados as $res){ $i++; $j++;
    if($res["Tipo"]=='Entrada'){
        $acum+=$res["Cantidad"];
        $objSheet->getCell('H'.$j)->setValue($res["Cantidad"]);
    }elseif($res["Tipo"]=='Salida'){
        $acum-=$res["Cantidad"];
        $objSheet->getCell('I'.$j)->setValue($res["Cantidad"]);
    } elseif ($res["Tipo"]=='Inventario') {
        $acum=$res["Cantidad"];
    }
    $resultados[$i]["Saldo"]=$acum;
    
    $objSheet->getCell('A'.$j)->setValue($res["Fecha"]);
	$objSheet->getCell('B'.$j)->setValue($res["Tipo"]);
	$objSheet->getCell('C'.$j)->setValue($res["Codigo"]);
	$objSheet->getCell('D'.$j)->setValue($res["Destino"]);
	$objSheet->getCell('E'.$j)->setValue($res["Lote"]);
	$objSheet->getCell('F'.$j)->setValue($res["Fecha_Vencimiento"]);
	$objSheet->getCell('J'.$j)->setValue($acum);
	
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>