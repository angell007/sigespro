<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','256M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="PRODUCTOS MEDICAMENTOS DESCUADRADOS.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT I.Id_Producto, P.Nombre_Comercial, P.Codigo_Cum, C.Nombre AS Categoria FROM Inventario I INNER JOIN Producto P ON I.Id_Producto = P.Id_Producto INNER JOIN Categoria C ON P.Id_Categoria = C.Id_Categoria WHERE I.Id_Bodega = 1 GROUP BY I.Id_Producto LIMIT 3000,1000";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$registros = $oCon->getData();
unset($oCon);

$lista_productos = [];

$contenido = '
<table border="1" style="border-collapse: collapse">
<tr>
<th>Nombre Producto</th>
<th>Codigo CUM</th>
<th>Cantidad Kardex</th>
<th>Cantidad Inventario</th>
<th>Categoria</th>
</tr>
';

foreach ($registros as $x => $reg) {
    $condicion = '';
    $condicion2=''; 
    $condicion3=''; 
    $condicion4=''; 
    $condicion5=''; 
    $condicion6=''; 
    $tipo = 'Bodega';
    $idTipo = 1;
    $producto = $reg['Id_Producto'];
    $ruta = '';
    $tabla = '';
    $tablaDest = '';
    $attrFecha = '';
    $query_dispensaciones = '';
    $query_notas_creditos = '';
    $query_devoluciones_compras = '';
    $query_actas_internacionales = '';
    
    $fecha_inicio = "2019-06-01";
    $fecha_fin = "2019-12-20";
    
    $sql_acta_recepcion_bodegas = '';
    
    if ($tipo == 'Bodega') {
        $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Bodega'";
        $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Bodega'";
        $condicion2 .= " AND AR.Id_Bodega=$idTipo";
        $condicion4 .= " AND INF.Bodega=$idTipo";
        $condicion5 .= " AND Id_Bodega=$idTipo";
        $condicion6 .= " AND Id_Origen=$idTipo";
        $ruta = 'actarecepcionver';
        $tabla = 'Acta_Recepcion';
        $tablaDest = 'Bodega';
        $attrFecha = 'Fecha_Creacion';
    
        $sql_acta_recepcion_bodegas .= ' UNION ALL (SELECT AR.Id_Acta_Recepcion_Remision as ID, '.getOrigenActa('Acta_Recepcion_Remision').' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "actarecepcionbodegaver" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.Fecha as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
        FROM Producto_Acta_Recepcion_Remision PAR
        INNER JOIN Acta_Recepcion_Remision AR
        ON PAR.Id_Acta_Recepcion_Remision = AR.Id_Acta_Recepcion_Remision
        WHERE PAR.Id_Producto = '.$producto.$condicion2. ')';
    
        $query_notas_creditos .= ' UNION ALL (SELECT NC.Id_Nota_Credito AS ID, R.Nombre_Destino AS Nombre_Origen, R.Nombre_Origen as Destino, "notascreditover" AS Ruta, "Entrada" AS Tipo, NC.Codigo, NC.Fecha, PNC.Cantidad, PNC.Lote, PNC.Fecha_Vencimiento, NC.Id_Factura, (SELECT Codigo FROM Factura_Venta WHERE Id_Factura_Venta = NC.Id_Factura) AS Codigo_Fact FROM Producto_Nota_Credito PNC INNER JOIN Nota_Credito NC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito INNER JOIN Remision R ON NC.Id_Factura = R.Id_Factura WHERE NC.Estado NOT IN ("Pendiente","Anulada") AND PNC.Id_Producto = '.$producto.$condicion6.' AND (NC.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") GROUP BY ID)';
        
        $query_devoluciones_compras .= ' UNION ALL (SELECT D.Id_Devolucion_Compra AS ID, (SELECT Nombre FROM Bodega WHERE Id_Bodega = D.Id_Bodega) AS Nombre_Origen, (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) as Destino, "verdetalledevolucion" AS Ruta, "Salida" AS Tipo, D.Codigo, D.Fecha, PDC.Cantidad, PDC.Lote, PDC.Fecha_Vencimiento, "", "" AS Codigo_Fact FROM Producto_Devolucion_Compra PDC INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra WHERE PDC.Id_Producto = '.$producto.$condicion5.' AND D.Estado = "Anulada" AND (D.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))
    
        UNION ALL (SELECT D.Id_Devolucion_Compra AS ID, (CASE D.Estado WHEN "Anulada" THEN (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) ELSE (SELECT Nombre FROM Bodega WHERE Id_Bodega = D.Id_Bodega) END) AS Nombre_Origen, (CASE D.Estado WHEN "Anulada" THEN (SELECT Nombre FROM Bodega WHERE Id_Bodega = D.Id_Bodega) ELSE (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) END) as Destino, "verdetalledevolucion" AS Ruta, (CASE D.Estado WHEN "Anulada" THEN "Entrada" ELSE "Salida" END) AS Tipo, CONCAT(D.Codigo,IF(D.Estado="Anulada"," (Anulada)","")) AS Codigo, D.Fecha, PDC.Cantidad, PDC.Lote, PDC.Fecha_Vencimiento, "", "" AS Codigo_Fact FROM Producto_Devolucion_Compra PDC INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra WHERE PDC.Id_Producto = '.$producto.$condicion5.' AND (D.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))
        ';
        
        
        $query_actas_internacionales .= ' UNION ALL (SELECT NP.Id_Nacionalizacion_Parcial AS ID, (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = ARI.Id_Proveedor) AS Nombre_Origen, (SELECT Nombre FROM Bodega WHERE Id_Bodega = ARI.Id_Bodega) as Destino, "comprainternacionalver" AS Ruta, "Entrada" AS Tipo, NP.Codigo, NP.Fecha_Registro, PNP.Cantidad, (SELECT Lote FROM Producto_Acta_Recepcion_Internacional WHERE Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional) AS Lote, (SELECT Fecha_Vencimiento FROM Producto_Acta_Recepcion_Internacional WHERE Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional) AS Fecha_Vencimiento, "", "" AS Codigo_Fact FROM Producto_Nacionalizacion_Parcial PNP INNER JOIN Nacionalizacion_Parcial NP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional WHERE PNP.Id_Producto = '.$producto.$condicion5.' AND (NP.Fecha_Registro BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))';
    } else {
    
      $query_comprobar = '
            SELECT 
              INF.Id_Inventario_Fisico_Punto AS Id,
            INF.Fecha_Fin
                FROM Producto_Inventario_Fisico_Punto PIF 
                INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto 
                WHERE 
                  INF.Estado="Terminado" 
                  AND PIF.Id_Producto = ' . $producto
                  ." AND ( PIF.Lote='".$prod["Lote"]."'" . ') 
                  AND INF.Id_Punto_Dispensacion = ' . $idTipo 
                  . ' AND INF.Fecha_Fin BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59" 
                GROUP BY PIF.Id_Producto, INF.Fecha_Fin';
    
          $oCon= new consulta();
        $oCon->setQuery($query_comprobar);
        $oCon->setTipo('multiple');
        $comprobacion = $oCon->getData();
        unset($oCon);
    
        if (count($comprobacion) > 0) {
    
        
    
          foreach ($comprobacion as $value) {
            
            $query_invs = '
              SELECT 
                  GROUP_CONCAT(INF.Id_Inventario_Fisico_Punto) AS Ids
                  FROM Inventario_Fisico_Punto INF
                  WHERE 
                    INF.Estado="Terminado"
                    AND INF.Id_Punto_Dispensacion = ' . $idTipo 
                    . ' AND INF.Fecha_Fin = "'.$value["Fecha_Fin"].'"';
    
                $oCon= new consulta();
            $oCon->setQuery($query_invs);
            $result = $oCon->getData(); 
            $ids_inv .= $result['Ids'].",";
            unset($oCon);
          }       
    
          $ids_inv = trim($ids_inv, ",");
        }else{
    
          $ids_inv = '0';
        }
    
    
        $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Punto_Dispensacion'";
        $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Punto'";
        $condicion2 .= " AND AR.Id_Punto_Dispensacion=$idTipo";
        $condicion4 .= " AND INF.Bodega=''";
        $condicion5 .= " AND Id_Punto_Dispensacion=$idTipo";
        $ruta = 'actarecepcionremisionver';
        $tabla = 'Acta_Recepcion_Remision';
        $tablaDest = 'Punto_Dispensacion'; 
        $attrFecha = 'Fecha';
    
        $sql_acta_recepcion_bodegas='UNION ALL (SELECT AR.Id_Acta_Recepcion as ID, '.getOrigenActa('Acta_Recepcion').' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "actarecepcionver" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.Fecha_Creacion as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
        FROM Producto_Acta_Recepcion PAR
        INNER JOIN Acta_Recepcion AR
        ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
        WHERE PAR.Id_Producto = '.$producto.$condicion2. ' AND AR.Estado = "Aprobada" AND (AR.Fecha_Creacion BETWEEN  "'.$fecha_inicio.'" AND "'.$fecha_fin.'"))';
       
        
        $query_dispensaciones .= 'UNION ALL (SELECT INF.Id_Inventario_Fisico_Punto AS ID, "" AS Nombre_Origen,
         (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, "inventario_fisico_puntos/descarga_pdf.php" AS Ruta, "Inventario" AS Tipo, 
         CONCAT("INVF",INF.Id_Inventario_Fisico_Punto) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Cantidad_Final) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico_Punto PIF INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto WHERE INF.Estado="Terminado" AND PIF.Id_Producto = ' . $producto . ' AND INF.Id_Punto_Dispensacion = ' . $idTipo . ' AND INF.Fecha_Inicio BETWEEN "'.$fecha_inicio .' 00:00:00" AND "'.$fecha_fin .' 23:59:59" GROUP BY PIF.Id_Producto, INF.Fecha_Fin)
            UNION
              (SELECT 
                INF.Id_Inventario_Fisico_Punto AS ID, 
                "" AS Nombre_Origen, 
                  (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino, 
                  "inventario_fisico_puntos/descarga_pdf.php" AS Ruta, 
                  "Inventario" AS Tipo, 
                  CONCAT("INVF",INF.Id_Inventario_Fisico_Punto) AS Codigo, 
                  INF.Fecha_Fin AS Fecha,
                  0 AS Cantidad,
                  "" AS Lote, 
                  "" AS Fecha_Vencimiento, 
                  "" AS Id_Factura, 
                  "" AS Codigo_Fact 
                  FROM Inventario_Fisico_Punto INF
                  WHERE 
                    INF.Estado="Terminado" 
                    AND INF.Id_Inventario_Fisico_Punto NOT IN ('.$ids_inv.')              
                    AND INF.Id_Punto_Dispensacion = ' . $idTipo 
                    . ' AND INF.Fecha_Fin BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59" 
                  GROUP BY INF.Fecha_Fin)
    
         UNION ALL (SELECT D.Id_Dispensacion AS ID, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion) AS Nombre_Origen, (SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") FROM Paciente WHERE Id_Paciente=D.Numero_Documento) AS Destino, "dispensacion" AS Ruta, "Salida" AS Tipo, CONCAT(D.Codigo," (Anulada)") AS Codigo, IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion
            LIMIT 1),IFNULL((SELECT PD2.Fecha_Carga FROM Producto_Dispensacion PD2 WHERE PD2.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion
            LIMIT 1),D.Fecha_Actual)) AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion 
         INNER JOIN Inventario I ON PD.Id_Inventario=I.Id_Inventario
         WHERE D.Estado_Dispensacion = "Anulada" AND  PD.Id_Producto = ' . $producto . ' AND  PD.Cantidad_Entregada!=0 AND I.Id_Punto_Dispensacion = ' . $idTipo . '  HAVING Fecha BETWEEN "'.$fecha_inicio .' 00:00:00" AND "'.$fecha_fin .' 23:59:59")
         
         UNION ALL (SELECT D.Id_Dispensacion AS ID, 
         (
             CASE D.Estado_Dispensacion
                WHEN "Anulada" THEN ""
                ELSE
                    (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion)
             END
         ) AS Nombre_Origen, 
         (
             CASE D.Estado_Dispensacion
                WHEN "Anulada" THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion)
                ELSE
                    (SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") FROM Paciente WHERE Id_Paciente=D.Numero_Documento)
             END
         ) AS Destino, "dispensacion" AS Ruta, 
         (
             CASE D.Estado_Dispensacion
                WHEN "Anulada" THEN "Entrada"
                ELSE
                    "Salida"
             END
         ) AS Tipo, D.Codigo, IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion
            LIMIT 1),IFNULL((SELECT PD2.Fecha_Carga FROM Producto_Dispensacion PD2 WHERE PD2.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion
            LIMIT 1),D.Fecha_Actual)) AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion 
         INNER JOIN Inventario I ON PD.Id_Inventario=I.Id_Inventario
         WHERE PD.Id_Producto = ' . $producto . ' AND  PD.Cantidad_Entregada!=0 AND I.Id_Punto_Dispensacion = ' . $idTipo . '  HAVING Fecha BETWEEN "'.$fecha_inicio .' 00:00:00" AND "'.$fecha_fin .' 23:59:59")
         
         ';
    
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
    
    $query = '
    
    (SELECT R.Id_Remision as ID,
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
    WHERE R.Estado = "Anulada" AND PR.Id_Producto = '.$producto.$condicion.')
    
    UNION ALL (SELECT R.Id_Remision as ID,
    (
        CASE R.Estado
            WHEN "Anulada" THEN ""
            ELSE
                R.Nombre_Origen
        END
    ) AS Nombre_Origen, 
    (
        CASE R.Estado
            WHEN "Anulada" THEN R.Nombre_Origen
            ELSE
                (CASE   
                    WHEN R.Tipo="Cliente" THEN CONCAT(R.Id_Destino," - ",R.Nombre_Destino)   
                    WHEN R.Tipo="Interna" THEN R.Nombre_Destino   
                END)
        END
    ) as Destino,
    "remision" as Ruta, (
        CASE R.Estado
            WHEN "Anulada" THEN "Entrada"
            ELSE
                "Salida"
        END
    ) as Tipo, CONCAT(R.Codigo," - (", R.Estado,")") AS Codigo, R.Fecha as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
    FROM Producto_Remision PR
    INNER JOIN Remision R
    ON R.Id_Remision = PR.Id_Remision
    LEFT JOIN Factura_Venta F
    ON F.Id_Factura_Venta = R.Id_Factura
    WHERE PR.Id_Producto = '.$producto.$condicion.')
    
    
    UNION ALL (SELECT R.Id_Remision as ID,
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
    
    UNION ALL (SELECT AI.Id_Ajuste_Individual as ID,
    IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.')) AS Nombre_Origen,IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'),"") as Destino,
    "ajustesinventariover" as Ruta, AI.Tipo, CONCAT(AI.Codigo," (Anulada)") AS Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_Ajuste_Individual PAI
    INNER JOIN Ajuste_Individual AI
    ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
    WHERE AI.Estado = "Anulada" AND PAI.Id_Producto = '.$producto.$condicion3.' AND (AI.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") ) 
    
    UNION ALL (SELECT AI.Id_Ajuste_Individual as ID,
    (
        CASE AI.Estado
            WHEN "Anulada" THEN IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'),"")
            ELSE
                IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'))
        END
    ) AS Nombre_Origen,
    (
        CASE AI.Estado
            WHEN "Anulada" THEN IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'))
            ELSE
                IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'),"")
        END
    ) as Destino,
    "ajustesinventariover" as Ruta, 
    (
        CASE AI.Estado
            WHEN "Anulada" THEN IF(AI.Tipo="Entrada","Salida","Entrada")
            ELSE
                AI.Tipo
        END
    ) AS Tipo, AI.Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_Ajuste_Individual PAI
    INNER JOIN Ajuste_Individual AI
    ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
    WHERE PAI.Id_Producto = '.$producto.$condicion3.' AND (AI.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") ) 
    
    UNION ALL (SELECT AR.Id_'.$tabla.' as ID, '.getOrigenActa($tabla).' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "'.$ruta.'" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.'.$attrFecha.' as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
    FROM Producto_'.$tabla.' PAR
    INNER JOIN '.$tabla.' AR
    ON PAR.Id_'.$tabla.' = AR.Id_'.$tabla.'
    WHERE PAR.Id_Producto = '.$producto.$condicion2. ' AND AR.Estado = "Aprobada") 
    '.$sql_acta_recepcion_bodegas.' 
    UNION ALL (SELECT INF.Id_Inventario_Fisico AS ID, "" AS Nombre_Origen, (SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Destino, "inventariofisico/inventario_final_pdf.php" AS Ruta, "Inventario" AS Tipo, CONCAT("INVF",INF.Id_Inventario_Fisico) AS Codigo, INF.Fecha_Fin AS Fecha, SUM(PIF.Segundo_Conteo) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico PIF INNER JOIN Inventario_Fisico INF ON PIF.Id_Inventario_Fisico=INF.Id_Inventario_Fisico WHERE INF.Estado="Terminado" AND PIF.Id_Producto = '.$producto.$condicion4. ' AND (INF.Fecha_Fin BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") GROUP BY PIF.Id_Inventario_Fisico) '.$query_dispensaciones.$query_notas_creditos.$query_devoluciones_compras.$query_actas_internacionales.' ORDER BY Fecha ASC';
    
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultados = $oCon->getData();
    unset($oCon);
    
    $saldo_actual = getSaldoActualProducto($tipo);
    
    $i=-1;
    foreach($resultados as $res){ $i++;
        if($res["Tipo"]=='Entrada'){
            $acum+=$res["Cantidad"];
        }elseif ($res["Tipo"]=='Salida'){
            $acum-=$res["Cantidad"];
        } elseif ($res["Tipo"]=='Inventario') {
            // $acum= $resultados[$i-1]["Tipo"] != "Inventario" ? $res["Cantidad"] : $acum + $res["Cantidad"];
            $fecha_ant = date('Y-m-d',strtotime($resultados[$i-1]['Fecha']));
            $fecha_act = date('Y-m-d',strtotime($res['Fecha']));
            if ($resultados[$i-1]["Tipo"] != "Inventario" || ($resultados[$i-1]["Tipo"] == "Inventario" && $fecha_ant != $fecha_act)) {
                $acum = $res["Cantidad"];
            } else {
                $acum = $acum + $res["Cantidad"];
            }
        }
        $resultados[$i]["Saldo"]=$acum;
    }

    if (count($resultados) > 0) {
        $saldo_kardex = $resultados[count($resultados)-1]['Saldo'];
        $lote = $resultados[count($resultados)-1]['Lote'];
    
        if (compararSaldoKardexConSaldoActual($saldo_kardex,$saldo_actual) === false) { // Si es false, actualizamos el saldo real.
            $contenido .= '
            <tr>
                <td>'.$reg['Nombre_Comercial'].'</td>
                <td>'.$reg['Codigo_Cum'].'</td>
                <td>'.$saldo_kardex.'</td>
                <td>'.$saldo_actual.'</td>
                <td>'.$reg['Categoria'].'</td>
            </tr>
            ';
        }
    }
}

$contenido .= "</table>";

echo $contenido;


function getOrigenActa($tabla) {

    $string = '""';

    if ($tabla == 'Acta_Recepcion') {
        $string = "(SELECT Nombre FROM Proveedor WHERE Id_Proveedor = AR.Id_Proveedor)";
    } elseif ($tabla == 'Acta_Recepcion_Remision') {
        $string = "(SELECT Nombre_Origen FROM Remision WHERE Id_Remision = AR.Id_Remision)";
    } elseif ($tabla == 'Nota_Credito') {
        $string = "(SELECT Nombre_Origen FROM Remision WHERE Id_Factura = NC.Id_Factura)";
    }

    return $string;
    
}

function getSaldoActualProducto($tipo_bodega) {
    $cond_saldo_actual = '';
    global $idTipo;
    global $producto;

    if ($tipo_bodega == 'Bodega') {
        $cond_saldo_actual .= "WHERE Id_Bodega = $idTipo AND Id_Producto = $producto";
    } else {
        $cond_saldo_actual .= "WHERE Id_Punto_Dispensacion = $idTipo AND Id_Producto = $producto";
    }
    
    $q = "SELECT SUM(Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario $cond_saldo_actual";
    
    $oCon= new consulta();
    $oCon->setQuery($q);
    $saldo_actual = $oCon->getData();
    unset($oCon);

    return $saldo_actual['Cantidad'];
}

function compararSaldoKardexConSaldoActual($saldo_kardex,$saldo_actual) {
    return $saldo_kardex >= 0 && $saldo_kardex < $saldo_actual ? false : true; // Si el ultimo saldo kardex es menor (es decir, no son iguales) al saldo actual en el inventario, se retorna false.
}

function actualizarSaldoInventario($tipo_bodega,$saldo_kardex) {
    $cond_saldo_actual = '';
    global $idTipo;
    global $producto;

    if ($tipo_bodega == 'Bodega') {
        $cond_saldo_actual .= "WHERE Id_Bodega = $idTipo AND Id_Producto = $producto";
    } else {
        $cond_saldo_actual .= "WHERE Id_Punto_Dispensacion = $idTipo AND Id_Producto = $producto";
    }
    
    $q = "UPDATE Inventario SET Cantidad = $saldo_kardex $cond_saldo_actual";
    
    $oCon= new consulta();
    $oCon->setQuery($q);
    $oCon->createData();
    unset($oCon);

    return;
}

?>