
<?php
if (! function_exists('replaceInventario')) {
    function replaceInventario()
    {
       return  'Inventario_Nuevo As  InN
        INNER JOIN Estiba As E ON E.Id_Estiba =  InN.Id_Estiba
        INNER JOIN Bodega_Nuevo As B ON B.Id_Bodega_Nuevo =  E.Id_Bodega_Nuevo';

    }
}

if (! function_exists('sqlActaRecepcionBodegas')) {
    function sqlActaRecepcionBodegas($tipo, $tablaDest, $idTipo, $producto, $condicion, $fecha_inicio, $fecha_fin )
    {

        if ($tipo ==  'Bodega') {
            return  ' UNION ALL (SELECT AR.Id_Acta_Recepcion_Remision as ID, '.getOrigenActa('Acta_Recepcion_Remision').' as Nombre_Origen, 
            (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "actarecepcionbodegaver" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.Fecha as Fecha, 
            PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento,
            "" as Id_Factura, "" as Codigo_Fact
            FROM Producto_Acta_Recepcion_Remision PAR
            INNER JOIN Acta_Recepcion_Remision AR
            ON PAR.Id_Acta_Recepcion_Remision = AR.Id_Acta_Recepcion_Remision
            WHERE PAR.Id_Producto = '.$producto.$condicion. ' AND (AR.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))';
        }
            return 'UNION ALL (SELECT AR.Id_Acta_Recepcion as ID, '.getOrigenActa('Acta_Recepcion').' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "actarecepcionver" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.Fecha_Creacion as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
            FROM Producto_Acta_Recepcion PAR
            INNER JOIN Acta_Recepcion AR
            ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
            WHERE PAR.Id_Producto = '.$producto.$condicion. ' AND AR.Estado = "Aprobada" AND (AR.Fecha_Creacion BETWEEN  "'.$fecha_inicio.'" AND "'.$fecha_fin.'"))';
    }
}

if (! function_exists('queryNotasCreditos')) {

    function queryNotasCreditos($idTipo, $producto, $fecha_inicio, $fecha_fin )
    {
       return  'UNION ALL (SELECT NC.Id_Nota_Credito AS ID, R.Nombre_Destino AS Nombre_Origen, R.Nombre_Origen as Destino, "notascreditover" AS Ruta,
       "Entrada" AS Tipo, NC.Codigo, NC.Fecha, SUM(PNC.Cantidad), GROUP_CONCAT(PNC.Lote SEPARATOR " | ") as Lote, GROUP_CONCAT(PNC.Fecha_Vencimiento SEPARATOR " | ") as Fecha_Vencimiento, 
       NC.Id_Factura, (SELECT Codigo FROM Factura_Venta WHERE Id_Factura_Venta = NC.Id_Factura) AS Codigo_Fact 
       FROM Producto_Nota_Credito PNC 
       INNER JOIN Nota_Credito NC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito 
       INNER JOIN (SELECT RE.Nombre_Destino, RE.Nombre_Origen, RE.Id_Factura FROM Producto_Remision PR 
       INNER JOIN Remision RE ON RE.Id_Remision = PR.Id_Remision WHERE PR.Id_Producto='.$producto.' AND RE.Id_Origen='.$idTipo.' GROUP BY RE.Id_Factura) R ON R.Id_Factura = NC.Id_Factura
       WHERE NC.Estado NOT IN ("Pendiente","Anulada") AND PNC.Id_Producto = '.$producto.' AND (NC.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") GROUP BY ID)';
    }
}


if (! function_exists('queryDevolucionesCompras')) {

    function queryDevolucionesCompras($condicion, $producto, $fecha_inicio, $fecha_fin )
    {
       return ' UNION ALL (SELECT D.Id_Devolucion_Compra AS ID, (SELECT Nombre FROM Bodega WHERE Id_Bodega = D.Id_Bodega) AS Nombre_Origen,
                (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) as Destino, "verdetalledevolucion" AS Ruta, "Salida" AS Tipo, D.Codigo, D.Fecha, PDC.Cantidad, PDC.Lote, PDC.Fecha_Vencimiento,
                "", "" AS Codigo_Fact FROM Producto_Devolucion_Compra PDC INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra 
                WHERE PDC.Id_Producto = '.$producto.$condicion.' AND D.Estado = "Anulada" AND (D.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))
                UNION ALL (SELECT D.Id_Devolucion_Compra AS ID, (CASE D.Estado WHEN "Anulada" THEN (SELECT Razon_Social FROM Proveedor 
                WHERE Id_Proveedor = D.Id_Proveedor) ELSE (SELECT Nombre FROM Bodega WHERE Id_Bodega = D.Id_Bodega) END) AS Nombre_Origen, 
                (CASE D.Estado WHEN "Anulada" THEN (SELECT Nombre FROM Bodega WHERE Id_Bodega = D.Id_Bodega) ELSE (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) END) as Destino,
                "verdetalledevolucion" AS Ruta, (CASE D.Estado WHEN "Anulada" THEN "Entrada" ELSE "Salida" END) AS Tipo, CONCAT(D.Codigo,IF(D.Estado="Anulada"," (Anulada)","")) AS Codigo, D.Fecha, PDC.Cantidad, PDC.Lote, 
                PDC.Fecha_Vencimiento, "", "" AS Codigo_Fact FROM Producto_Devolucion_Compra PDC INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra 
                WHERE PDC.Id_Producto = '.$producto.$condicion.' AND (D.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))
      ';
    }
}

if (! function_exists('queryActasInternacionales')) {

    function queryActasInternacionales($condicion, $producto, $fecha_inicio, $fecha_fin )
    {
       return  'UNION ALL (SELECT NP.Id_Nacionalizacion_Parcial AS ID, (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = ARI.Id_Proveedor) AS Nombre_Origen, 
       (SELECT Nombre FROM Bodega WHERE Id_Bodega = ARI.Id_Bodega) as Destino, "comprainternacionalver" AS Ruta, "Entrada" AS Tipo, NP.Codigo, NP.Fecha_Registro, PNP.Cantidad, 
       (SELECT Lote FROM Producto_Acta_Recepcion_Internacional WHERE Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional) AS Lote, 
       (SELECT Fecha_Vencimiento FROM Producto_Acta_Recepcion_Internacional WHERE Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional) AS Fecha_Vencimiento,
        "", "" AS Codigo_Fact FROM Producto_Nacionalizacion_Parcial PNP INNER JOIN Nacionalizacion_Parcial NP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial 
        INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional 
        WHERE PNP.Id_Producto = '.$producto.$condicion.' AND (NP.Fecha_Registro BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))';
    }
}

if (! function_exists('sqlDispensaciones')) {
    
    function sqlDispensaciones($fecha1 = '', $fecha2 = '', $idTipo,  $ids_inv,  $producto, $fecha_inicio, $fecha_fin )
    {
        
        return 'UNION ALL (SELECT INF.Id_Inventario_Fisico_Punto AS ID, "" AS Nombre_Origen,
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
        
        UNION ALL (SELECT D.Id_Dispensacion AS ID, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion) AS Nombre_Origen, 
        (SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") FROM Paciente WHERE Id_Paciente=D.Numero_Documento) AS Destino, "dispensacion" AS Ruta, 
        "Salida" AS Tipo, CONCAT(D.Codigo," (Anulada)") AS Codigo, IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion
        LIMIT 1),IFNULL((SELECT PD2.Fecha_Carga FROM Producto_Dispensacion PD2 WHERE PD2.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion
        LIMIT 1),D.Fecha_Actual)) AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento,
        "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion 
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
                    WHERE PD.Id_Producto = ' . $producto . ' AND  PD.Cantidad_Entregada!=0 AND I.Id_Punto_Dispensacion = ' . $idTipo . '  HAVING Fecha BETWEEN "'.$fecha_inicio .' 00:00:00" AND "'.$fecha_fin .' 23:59:59")';
                    
                }
            }
            
            if (! function_exists('resultData')) {
                
                function resultData($params)
                
                {
                    return  '(SELECT R.Id_Remision as ID,
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
                    WHERE R.Estado = "Anulada" AND PR.Id_Producto = '.$params["producto"].$params["condicion"].')
                    UNION ALL (SELECT R.Id_Remision as ID,
                    (CASE R.Estado WHEN "Anulada" THEN "" ELSE R.Nombre_Origen END) AS Nombre_Origen, 
                    (CASE R.Estado  WHEN "Anulada" THEN R.Nombre_Origen
                    ELSE (CASE WHEN R.Tipo="Cliente" THEN CONCAT(R.Id_Destino," - ",R.Nombre_Destino) WHEN R.Tipo="Interna" THEN R.Nombre_Destino END) END) as Destino, "remision" as Ruta, (CASE R.Estado
                    WHEN "Anulada" THEN "Entrada" ELSE "Salida" END ) as Tipo, CONCAT(R.Codigo," - (", R.Estado,")") AS Codigo, (CASE R.Estado WHEN "Anulada" THEN (SELECT MAX(Fecha) FROM Actividad_Remision WHERE Id_Remision = R.Id_Remision)
                    ELSE R.Fecha END) as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
                    FROM Producto_Remision PR
                    INNER JOIN Remision R
                    ON R.Id_Remision = PR.Id_Remision
                    LEFT JOIN Factura_Venta F
                    ON F.Id_Factura_Venta = R.Id_Factura
                    WHERE PR.Id_Producto = '.$params["producto"].$params["condicion"].')
                    UNION ALL (SELECT R.Id_Remision as ID,
                    R.Nombre_Origen, 
                    (CASE WHEN R.Tipo="Cliente" THEN CONCAT(R.Id_Destino," - ",R.Nombre_Destino)   
                    WHEN R.Tipo="Interna" THEN R.Nombre_Destino   
                    END) as Destino, "remisionantigua" as Ruta, "Salida" as Tipo, R.Codigo, R.Fecha as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
                    FROM Producto_Remision_Antigua PR INNER JOIN Remision_Antigua R
                    ON R.Id_Remision = PR.Id_RemisionLEFT JOIN Factura_Venta F
                    ON F.Id_Factura_Venta = R.Id_Factura
                    WHERE PR.Id_Producto = '.$params["producto"].$params["condicion"].') 
                    
                    UNION ALL (SELECT AI.Id_Ajuste_Individual as ID,
                    IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$params["tablaDest"].' WHERE Id_'.$params["tablaDest"].'='.$params["idTipo"].')) AS Nombre_Origen,IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$params["tablaDest"].' WHERE Id_'.$params["tablaDest"].'='.$params["idTipo"].'),"") as Destino,
                    "ajustesinventariover" as Ruta, AI.Tipo, CONCAT(AI.Codigo," (Anulada)") AS Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
                    FROM Producto_Ajuste_Individual PAI
                    INNER JOIN Ajuste_Individual AI
                    ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
                    WHERE AI.Estado = "Anulada" AND PAI.Id_Producto = '.$params["producto"].$params["condicion3"].' AND (AI.Fecha BETWEEN  "'.$params["fecha"].' 00:00:00" AND "'.$params["fecha_fin"].' 23:59:59") ) 
                    
                    UNION ALL (SELECT AI.Id_Ajuste_Individual as ID,
                    (
                        CASE AI.Estado
                        WHEN "Anulada" THEN IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$params["tablaDest"].' WHERE Id_'.$params["tablaDest"].'='.$params["idTipo"].'),"")
                        ELSE
                        IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$params["tablaDest"].' WHERE Id_'.$params["tablaDest"].'='.$params["idTipo"].'))
                        END
                        ) AS Nombre_Origen,
                        (
                            CASE AI.Estado
                            WHEN "Anulada" THEN IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$params["tablaDest"].' WHERE Id_'.$params["tablaDest"].'='.$params["idTipo"].'))
                            ELSE
                            IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$params["tablaDest"].' WHERE Id_'.$params["tablaDest"].'='.$params["idTipo"].'),"")
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
                                WHERE PAI.Id_Producto = '.$params["producto"].$params["condicion3"].' AND (AI.Fecha BETWEEN  "'.$params["fecha_inicio"].' 00:00:00" AND "'.$params["fecha_fin"].' 23:59:59") ) 
                                
                                UNION ALL (SELECT AR.Id_'.$params["tabla"].' as ID, '.getOrigenActa($params["tabla"]).' as Nombre_Origen, (SELECT Nombre FROM '.$params["tablaDest"].' WHERE Id_'.$params["tablaDest"].'='.$params["idTipo"].') as Destino, "'.$params["ruta"].'" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.'.$params["attrFecha"].' as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
                                FROM Producto_'.$params["tabla"].' PAR
                                INNER JOIN '.$params["tabla"].' AR
                                ON PAR.Id_'.$params["tabla"].' = AR.Id_'.$params["tabla"].'
                                WHERE PAR.Id_Producto = '.$params["producto"].$params["condicion2"]. ' AND AR.Estado = "Aprobada") 
            '.$params["sql_acta_recepcion_bodegas"].' 
            UNION ALL (SELECT INF.Id_Inventario_Fisico AS ID, "" AS Nombre_Origen, (SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Destino, 
            "inventariofisico/inventario_final_pdf.php" AS Ruta, "Inventario" AS Tipo, CONCAT("INVF",INF.Id_Inventario_Fisico) AS Codigo, INF.Fecha_Fin AS Fecha, 
            SUM(PIF.Segundo_Conteo) AS Cantidad, GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, 
            "" AS Id_Factura, "" AS Codigo_Fact FROM Producto_Inventario_Fisico PIF INNER JOIN Inventario_Fisico INF ON PIF.Id_Inventario_Fisico=INF.Id_Inventario_Fisico WHERE INF.Estado="Terminado" AND PIF.Id_Producto = '.$params["producto"].$params["condicion4"]. ' AND (INF.Fecha_Fin BETWEEN  "'.$params["fecha_inicio"].' 00:00:00" AND "'.$params["fecha_fin"].' 23:59:59") 
            GROUP BY PIF.Id_Inventario_Fisico) '.$params["query_dispensaciones"].$params["query_notas_creditos"].$params["query_devoluciones_compras"].$params["query_actas_internacionales"].' ORDER BY Fecha ASC';
            
        }
    }
    
    if (! function_exists('getOrigenActa')) {
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
    }
    
    if (! function_exists('getSaldoActualProducto')) {
        function getSaldoActualProducto($tipo_bodega) {
            $cond_saldo_actual = '';
            global $idTipo;
            global $producto;
            
            if ($tipo_bodega == 'Bodega') {
                $cond_saldo_actual .= "WHERE Id_Bodega = $idTipo AND Id_Producto = $producto";
            } else {
                $cond_saldo_actual .= "WHERE Id_Punto_Dispensacion = $idTipo AND Id_Producto = $producto";
            }
            
            $q = "SELECT SUM(Cantidad-Cantidad_Apartada) AS Cantidad FROM Inventario $cond_saldo_actual";
            
            $oCon= new consulta();
            $oCon->setQuery($q);
            $saldo_actual = $oCon->getData();
            unset($oCon);
            
            return $saldo_actual;
        }
    }
    
    if (! function_exists('compararSaldoKardexConSaldoActual')) {
        function compararSaldoKardexConSaldoActual($saldo_kardex,$saldo_actual) {
            return $saldo_kardex >= 0 && $saldo_kardex < $saldo_actual ? false : true; // Si el ultimo saldo kardex es menor (es decir, no son iguales) al saldo actual en el inventario, se retorna false.
        }
    }

    if (! function_exists('actualizarSaldoInventario')) {

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

    }

    
  
    
  