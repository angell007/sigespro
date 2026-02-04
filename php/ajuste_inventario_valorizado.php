<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$oLista = new lista("Inventario");
$oLista->setRestrict("Id_Bodega","=",0);
$oLista->setRestrict("Id_Punto_Dispensacion","!=",0);
// $oLista->setRestrict("Id_Punto_Dispensacion","=",108);
// $oLista->SetRestrict("Id_Punto_Dispensacion","!=",101);
// $oLista->SetRestrict("Id_Punto_Dispensacion","!=",999);
//$oLista->setItems();
$productos= $oLista->getList();
unset($oLista);

//echo count($productos);
$j=0;
$k=0;
foreach($productos as $prod){ $k++;
    $condicion = '';
    $condicion2=''; 
    $condicion3=''; 
    $condicion4=''; 
    $condicion5=''; 
    
    $ruta = '';
    $tabla = '';
    $tablaDest = '';
    $attrFecha = '';
    $query_dispensaciones = '';
    
    $fecha1 ="2018-12-01";
    $fecha2 ="2018-12-31";
    $acum=0;
    $resultados =[];
    $producto = $prod["Id_Producto"];
    $tipo = $prod['Id_Bodega'] != 0 ? 'Bodega' : 'Punto_Dispensacion';
    $idTipo = $prod['Id_Bodega'] != 0 ? $prod['Id_Bodega'] : $prod['Id_Punto_Dispensacion'];

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
        (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, 
        "inventario_fisico_puntos/descarga_pdf.php" AS Ruta, "Inventario" AS Tipo, 
        CONCAT("INVF",INF.Id_Inventario_Fisico_Punto) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Cantidad_Final) AS Cantidad,
        GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, 
        GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact 
        FROM Producto_Inventario_Fisico_Punto PIF 
        INNER JOIN Inventario_Fisico_Punto INF 
        ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto 
        WHERE INF.Estado="Terminado" AND PIF.Id_Producto = ' . $producto." AND PIF.Lote='".$prod["Lote"]."'" . ' AND INF.Id_Punto_Dispensacion = ' . $idTipo . ' AND INF.Fecha_Inicio BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59" GROUP BY PIF.Id_Producto) 
        UNION (SELECT D.Id_Dispensacion AS ID, 
        (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion) AS Nombre_Origen, 
        (SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") FROM Paciente 
        WHERE Id_Paciente=D.Numero_Documento) AS Destino, "dispensacion" AS Ruta, "Salida" AS Tipo, 
        D.Codigo, D.Fecha_Actual AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento, "" AS Id_Factura,
        "" AS Codigo_Fact FROM Producto_Dispensacion PD 
        INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
        WHERE D.Estado_Dispensacion != "Anulada" AND PD.Id_Producto = ' . $producto." AND PD.Lote='".$prod["Lote"]."'" . ' AND D.Id_Punto_Dispensacion = ' . $idTipo . ' AND D.Fecha_Actual BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59")';
    }
    
    
    $condicion .= " AND R.Fecha BETWEEN '$fecha1 00:00:00' AND '$fecha2 23:59:59'";
    $condicion2.= " AND AR.$attrFecha BETWEEN '$fecha1 00:00:00' AND '$fecha2 23:59:59'";
   
    $condicion .= " AND PR.Lote LIKE '%".$prod["Lote"]."%'";
     
    
    $ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha1)),1,date("Y",strtotime($fecha1)))-1));

    $query_inicial = 'SELECT SUM(Cantidad) as Total
    FROM Saldo_Inicial_Kardex 
    WHERE Id_Producto = '.$producto.' AND Fecha="'.$ultimo_dia_mes.'" '.$condicion5.' GROUP BY Id_Producto';
    $oCon= new consulta();
    $oCon->setQuery($query_inicial);
    $ress = $oCon->getData();
    unset($oCon);

    $acum=$total=(INT)$ress["Total"];
    
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
    WHERE R.Estado IN ("Pendiente","Alistada","Enviada","Facturada","Recibida") AND PR.Id_Producto = '.$producto.$condicion.' AND PR.Lote = "'.$prod["Lote"].'") 
    
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
    WHERE PR.Id_Producto = '.$producto.$condicion.' AND PR.Lote = "'.$prod["Lote"].'") 
    
    UNION (SELECT AI.Id_Ajuste_Individual as ID,
    IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.')) AS Nombre_Origen,IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'),"") as Destino,
    "ajusteinventariover" as Ruta, AI.Tipo, AI.Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_Ajuste_Individual PAI
    INNER JOIN Ajuste_Individual AI
    ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
    WHERE PAI.Id_Producto = '.$producto.$condicion3.' AND PAI.Lote = "'.$prod["Lote"].'") 
    
    UNION (SELECT AR.Id_'.$tabla.' as ID, "" as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "'.$ruta.'" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.'.$attrFecha.' as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_'.$tabla.' PAR
    INNER JOIN '.$tabla.' AR
    ON PAR.Id_'.$tabla.' = AR.Id_'.$tabla.'
    WHERE PAR.Id_Producto = '.$producto.$condicion2. ' AND PAR.Lote = "'.$prod["Lote"].'") 
    
    UNION (SELECT INF.Id_Inventario_Fisico AS ID, "" AS Nombre_Origen, (SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Destino, "inventariofisico/inventario_final_pdf.php" AS Ruta, "Inventario" AS Tipo, CONCAT("INVF",INF.Id_Inventario_Fisico) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Segundo_Conteo) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico PIF INNER JOIN Inventario_Fisico INF ON PIF.Id_Inventario_Fisico=INF.Id_Inventario_Fisico WHERE INF.Estado="Terminado" AND PIF.Id_Producto = '.$producto.$condicion4. ' AND PIF.Lote = "'.$prod["Lote"].'" GROUP BY PIF.Id_Inventario_Fisico) '.$query_dispensaciones.' ORDER BY Fecha ASC';
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultados = $oCon->getData();
    unset($oCon);
    
    $i=-1;
    foreach($resultados as $res){ $i++;
    //echo $res["Codigo"]." + ".$res["Tipo"]."  --  ".$res["Cantidad"]."<br>"; 
        if($res["Tipo"]=='Entrada'){
            $acum+=$res["Cantidad"];
        }elseif ($res["Tipo"]=='Salida'){
            $acum-=$res["Cantidad"];
        } elseif ($res["Tipo"]=='Inventario') {
            $acum=$res["Cantidad"];
        }
        $resultados[$i]["Saldo"]=$acum;
    }
    
    $j++;
    if((INT)$resultados[$i]["Saldo"]<0){
        $resultados[$i]["Saldo"] = 0;
    }
    
    $oItem = new complex("Saldo_Inicial_Kardex","Id_Saldo_Inicial_Kardex");
    $oItem->Fecha = $fecha2; // fecha fin
    $oItem->Id_Producto = $prod["Id_Producto"];
    $oItem->Cantidad = number_format($resultados[$i]["Saldo"],0,"","");
    $oItem->Lote = $prod["Lote"];
    $oItem->Fecha_Vencimiento = $prod["Fecha_Vencimiento"];
    $oItem->Id_Bodega = $prod['Id_Bodega'];
    $oItem->Id_Punto_Dispensacion = $prod['Id_Punto_Dispensacion'];
    $oItem->save(); 
    unset($oItem);

    echo $k." - ". $j.") - ".$prod["Lote"]." - ".$idTipo." - ".$prod["Id_Producto"]." - ".$prod["Cantidad"]." - ".(INT)$resultados[$i]["Saldo"]."<br>";
    
}
?>