<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

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

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
}

$sql_acta_recepcion_bodegas = '';

if ($tipo == 'Bodega') {
    $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Bodega'";
    $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Bodega'";
    $condicion2 .= " AND AR.Id_Bodega=$idTipo";
    $condicion4 .= " AND INF.Bodega=$idTipo";
    $condicion5 .= " AND Id_Bodega=$idTipo";
    $ruta = 'actarecepcionver';
    $tabla = 'Acta_Recepcion';
    $tablaDest = 'Bodega';
    $attrFecha = 'Fecha_Creacion';

    $sql_acta_recepcion_bodegas .= ' UNION (SELECT AR.Id_Acta_Recepcion_Remision as ID, '.getOrigenActa('Acta_Recepcion_Remision').' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "actarecepcionbodegaver" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.Fecha as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_Acta_Recepcion_Remision PAR
    INNER JOIN Acta_Recepcion_Remision AR
    ON PAR.Id_Acta_Recepcion_Remision = AR.Id_Acta_Recepcion_Remision
    WHERE PAR.Id_Producto = '.$producto.$condicion2. ')';
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
    
    

    $query_dispensaciones .= 'UNION (SELECT INF.Id_Inventario_Fisico_Punto AS ID, "" AS Nombre_Origen,
     (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, "inventario_fisico_puntos/descarga_pdf.php" AS Ruta, "Inventario" AS Tipo, 
     CONCAT("INVF",INF.Id_Inventario_Fisico_Punto) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Cantidad_Final) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico_Punto PIF INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto WHERE INF.Estado="Terminado" AND PIF.Id_Producto = ' . $producto . ' AND INF.Id_Punto_Dispensacion = ' . $idTipo . ' AND INF.Fecha_Inicio BETWEEN "'.$fecha_inicio .' 00:00:00" AND "'.$fecha_fin .' 23:59:59" GROUP BY PIF.Id_Producto, INF.Fecha_Fin)
     
     UNION (SELECT INF.Id_Inventario_Fisico_Punto_Nuevo AS ID, "" AS Nombre_Origen,
     (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, "inventario_fisico_puntos/descarga_pdf.php" AS Ruta, "Inventario" AS Tipo,
     CONCAT("INVF",INF.Id_Inventario_Fisico_Punto_Nuevo) AS Codigo, INF.Fecha AS Fecha, IFNULL(SUM(PIF.Segundo_Conteo), PIF.Cantidad_Auditada) AS Cantidad, PIF.Lote AS Lote, PIF.Fecha_Vencimiento AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact
     FROM Producto_Doc_Inventario_Fisico_Punto PIF
     INNER JOIN Doc_Inventario_Fisico_Punto DIF ON PIF.Id_Doc_Inventario_Fisico_Punto=DIF.Id_Doc_Inventario_Fisico_Punto
     INNER JOIN Inventario_Fisico_Punto_Nuevo INF ON DIF.Id_Inventario_Fisico_Punto_Nuevo=INF.Id_Inventario_Fisico_Punto_Nuevo
     WHERE PIF.Id_Producto = ' . $producto . ' AND INF.Id_Punto_Dispensacion = ' . $idTipo . ' AND INF.Fecha BETWEEN "'.$fecha_inicio .' 00:00:00" AND "'.$fecha_fin .' 23:59:59" GROUP BY PIF.Id_Producto, PIF.Lote, PIF.Fecha_Vencimiento, INF.Fecha)
     
     UNION (SELECT D.Id_Dispensacion AS ID, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion) AS Nombre_Origen, (SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") FROM Paciente WHERE Id_Paciente=D.Numero_Documento) AS Destino, "dispensacion" AS Ruta, "Salida" AS Tipo, D.Codigo, IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion LIMIT 1),D.Fecha_Actual) AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion WHERE D.Estado_Dispensacion != "Anulada" AND PD.Lote != "Pendiente" AND PD.Id_Producto = ' . $producto . ' AND  PD.Cantidad_Entregada!=0 AND D.Id_Punto_Dispensacion = ' . $idTipo . '  HAVING Fecha BETWEEN "'.$fecha_inicio .' 00:00:00" AND "'.$fecha_fin .' 23:59:59")';
}

$condicion .= " AND R.Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
$condicion2.= " AND AR.$attrFecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";

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
"ajustesinventariover" as Ruta, AI.Tipo, AI.Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
FROM Producto_Ajuste_Individual PAI
INNER JOIN Ajuste_Individual AI
ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
WHERE PAI.Id_Producto = '.$producto.$condicion3.') 

UNION (SELECT AR.Id_'.$tabla.' as ID, '.getOrigenActa($tabla).' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "'.$ruta.'" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.'.$attrFecha.' as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
FROM Producto_'.$tabla.' PAR
INNER JOIN '.$tabla.' AR
ON PAR.Id_'.$tabla.' = AR.Id_'.$tabla.'
WHERE PAR.Id_Producto = '.$producto.$condicion2. ') 
'.$sql_acta_recepcion_bodegas.' 
UNION (SELECT INF.Id_Inventario_Fisico AS ID, "" AS Nombre_Origen, (SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Destino, "inventariofisico/inventario_final_pdf.php" AS Ruta, "Inventario" AS Tipo, CONCAT("INVF",INF.Id_Inventario_Fisico) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Segundo_Conteo) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico PIF INNER JOIN Inventario_Fisico INF ON PIF.Id_Inventario_Fisico=INF.Id_Inventario_Fisico WHERE INF.Estado="Terminado" AND PIF.Id_Producto = '.$producto.$condicion4. ' GROUP BY PIF.Id_Inventario_Fisico) '.$query_dispensaciones.' ORDER BY Fecha ASC';




$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

$cond_saldo_actual = '';

if ($tipo == 'Bodega') {
    $cond_saldo_actual .= "WHERE Id_Bodega = $idTipo AND Id_Producto = $producto";
} else {
    $cond_saldo_actual .= "WHERE Id_Punto_Dispensacion = $idTipo AND Id_Producto = $producto";
}

$q = "SELECT SUM(Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario $cond_saldo_actual";

$oCon= new consulta();
$oCon->setQuery($q);
$saldo_actual = $oCon->getData();
unset($oCon);


$i=-1;
foreach($resultados as $res){ $i++;
    if($res["Tipo"]=='Entrada'){
        $acum+=$res["Cantidad"];
    }elseif ($res["Tipo"]=='Salida'){
        $acum-=$res["Cantidad"];
    } elseif ($res["Tipo"]=='Inventario') {
        $acum=$res["Cantidad"];
    }
    $resultados[$i]["Saldo"]=$acum;
}

$final["Productos"]=$resultados;
$final["Inicial"]=$total;
$final["Saldo_Actual"] = $saldo_actual;

echo json_encode($final);

function getOrigenActa($tabla) {

    $string = '""';

    if ($tabla == 'Acta_Recepcion') {
        $string = "(SELECT Nombre FROM Proveedor WHERE Id_Proveedor = AR.Id_Proveedor)";
    } elseif ($tabla == 'Acta_Recepcion_Remision') {
        $string = "(SELECT Nombre_Origen FROM Remision WHERE Id_Remision = AR.Id_Remision)";
    }

    return $string;
    
}

?>
