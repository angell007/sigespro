<?php

/* header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); */
//header('Content-Type: application/json');

require_once("/home/sigespro/public_html/config/start.inc_cron.php");
include_once("/home/sigespro/public_html/class/class.lista.php");
include_once("/home/sigespro/public_html/class/class.complex.php");
include_once("/home/sigespro/public_html/class/class.consulta.php");

$query = "
    SELECT
        I.Id_Inventario_Nuevo,
        I.Codigo,
        I.Id_Estiba,
        I.Id_Producto,
        I.Codigo_CUM,
        I.Lote,
        I.Fecha_Vencimiento,
        I.Fecha_Carga,
        I.Identificacion_Funcionario,
        
        I.Id_Punto_Dispensacion,
        I.Cantidad,
        I.Lista_Ganancia,
        I.Id_Dispositivo,
        I.Costo,
        I.Cantidad_Apartada,
        I.Estiba,
        I.Fila,
        I.Alternativo,
        I.Actualizado,
        I.Cantidad_Seleccionada,
        I.Cantidad_Leo,
        I.Negativo,
        I.Cantidad_Pendientes,
        
         P.Id_Categoria, P.Codigo_Cum, E.Id_Bodega_Nuevo AS Id_Bodega
         
    FROM Inventario_Nuevo I
    INNER JOIN Producto P ON P.Id_Producto = I.Id_Producto
    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
    WHERE I.Id_Estiba != 0
    AND I.Id_Punto_Dispensacion = 0 
    #AND I.Id_Producto IN (2400,2400,3000,3036,5163,5163,6092,6120,6604,7145,8420,8977,8977,9217,10152,10161,10362,12394,12972,12972,16618,18803,21071,21071,21852,21852,22837,25350,26974,27080,27080,27080,27172,27172,27967,28541,28657,28657,28781,29278,30229,30560,31287,33880,33880,34421,36067,36497,36929,37395,37576,37922,38335,38335,40953,42241,42534,42534,43098,43327,43327,43764,43982,45752,45799,47261,50228,50246,50274,50275,50291,50292,50298,50299,50336,50336,50336,50365,50414,50421,50439,54151,54153,54155,54157,54254,54254,54255,54388,54979)
    #AND E.Id_Bodega_Nuevo IN (1,2)
    ORDER BY E.Id_Bodega_Nuevo,I.Id_Producto,I.Lote
        
    ";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('multiple');
$productos = $oCon->getData();

// $oLista = new lista("Inventario");
// $oLista->setRestrict("Id_Bodega","!=",0);
// $oLista->setRestrict("Id_Punto_Dispensacion","=",0);
// $oLista->setRestrict("Id_Punto_Dispensacion","=",108);
// $oLista->SetRestrict("Id_Punto_Dispensacion","!=",101);
// $oLista->SetRestrict("Id_Punto_Dispensacion","!=",999);
//$oLista->setItems();
// $productos= $oLista->getList();
// unset($oLista);

// echo count($productos);
$j=0;
$k=0;
$registros = 0;
$valuesInsert = [];

$fechas = fechasFiltros();

echo "<br><br> SALDO INICIAL KARDEX PARA BODEGAS <br>";

for ($index=0; $index < count($fechas) ; $index++) { 
    foreach($productos as $prod){ $k++;
        $condicion = '';
        $condicion2=''; 
        $condicion3=''; 
        $condicion4=''; 
        $condicion5=''; 
        $condicion6=''; 
        
        $ruta = '';
        $tabla = '';
        $tablaDest = '';
        $attrFecha = '';
        $query_dispensaciones = '';
        $query_notas_creditos = '';
        $sql_acta_recepcion_bodegas = '';
        $query_devoluciones_compras = '';
        $query_actas_internacionales = '';
        
        // $fecha1 =date('Y-m-01');
        $fecha_inicio = $fechas[$index]["fecha_inicio"];
        // $fecha2 =date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha1)),1,date("Y",strtotime($fecha1)))-1));
        $fecha_fin = $fechas[$index]["fecha_fin"];
        $acum=0;
        $resultados =[];
        $producto = $prod["Id_Producto"];
        $categoria = $prod["Id_Categoria"];
        $cantidad = ((INT)$prod["Cantidad"]-(INT)$prod["Cantidad_Apartada"]);
        $lote = $prod["Lote"];
        $tipo = $prod['Id_Bodega'] != 0 ? 'Bodega' : 'Punto_Dispensacion';
        $idTipo = $prod['Id_Bodega'] != 0 ? $prod['Id_Bodega'] : $prod['Id_Punto_Dispensacion'];
    
        if ($tipo == 'Bodega') {
            $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Bodega'";
            $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Bodega'";
            $condicion2 .= " AND AR.Id_Bodega_Nuevo=$idTipo";
            $condicion4 .= " AND E.Id_Bodega_Nuevo=$idTipo";
            $condicion5 .= " AND Id_Bodega_Nuevo=$idTipo";
            $condicion6 .= " AND Id_Origen=$idTipo";
            $ruta = 'actarecepcionver';
            $tabla = 'Acta_Recepcion';
            $tablaDest = 'Bodega_Nuevo';
            $attrFecha = 'Fecha_Creacion';
        
            $sql_acta_recepcion_bodegas .= ' UNION ALL (
            SELECT AR.Id_Acta_Recepcion_Remision as ID,
            '.getOrigenActa('Acta_Recepcion_Remision').' as Nombre_Origen, 
            (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino,
            "actarecepcionbodegaver" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.Fecha as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
            FROM Producto_Acta_Recepcion_Remision PAR
            INNER JOIN Acta_Recepcion_Remision AR
            ON PAR.Id_Acta_Recepcion_Remision = AR.Id_Acta_Recepcion_Remision
            WHERE PAR.Lote = "'.$lote.'" AND PAR.Id_Producto = '.$producto.$condicion2. ' AND (AR.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))';
        
          
          
            $query_notas_creditos .= ' UNION ALL (
                                            SELECT NC.Id_Nota_Credito AS ID, R.Nombre_Destino AS Nombre_Origen, R.Nombre_Origen as Destino, "notascreditover" AS Ruta,
                                            "Entrada" AS Tipo, NC.Codigo, NC.Fecha, PNC.Cantidad, PNC.Lote, PNC.Fecha_Vencimiento, NC.Id_Factura, 
                                            (SELECT Codigo FROM Factura_Venta WHERE Id_Factura_Venta = NC.Id_Factura) AS Codigo_Fact 
                                            FROM Producto_Nota_Credito PNC INNER JOIN Nota_Credito NC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito 
                                            INNER JOIN Remision R ON NC.Id_Factura = R.Id_Factura WHERE NC.Estado NOT IN ("Pendiente","Anulada")
                                            AND PNC.Lote = "'.$lote.'" AND PNC.Id_Producto = '.$producto.$condicion6.' 
                                            AND (NC.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") GROUP BY ID)';
            
          
          
          
            $query_devoluciones_compras .= ' UNION ALL
                                             (
                                                SELECT D.Id_Devolucion_Compra AS ID, 
                                                (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = D.Id_Bodega_Nuevo) AS Nombre_Origen,
                                                (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) as Destino, 
                                                "verdetalledevolucion" AS Ruta, "Salida" AS Tipo, D.Codigo, D.Fecha, PDC.Cantidad, PDC.Lote, PDC.Fecha_Vencimiento,
                                                "", "" AS Codigo_Fact 
                                                FROM Producto_Devolucion_Compra PDC
                                                INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra
                                                WHERE PDC.Lote = "'.$lote.'" AND PDC.Id_Producto = '.$producto.$condicion5.' AND D.Estado = "Anulada" AND (D.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))
                                            
                                                UNION ALL (SELECT D.Id_Devolucion_Compra AS ID, 
                                                (CASE D.Estado WHEN "Anulada"
                                                    THEN (SELECT Razon_Social 
                                                        FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor)
                                                    ELSE (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = D.Id_Bodega_Nuevo) END) AS Nombre_Origen, 
                                                (CASE D.Estado WHEN "Anulada" 
                                                    THEN (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = D.Id_Bodega_Nuevo)
                                                    ELSE (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) END) as Destino,
                                                
                                                "verdetalledevolucion" AS Ruta,
                                                (CASE D.Estado WHEN "Anulada" THEN "Entrada" ELSE "Salida" END) AS Tipo,
                                                CONCAT(D.Codigo,IF(D.Estado="Anulada"," (Anulada)","")) AS Codigo, D.Fecha, PDC.Cantidad, PDC.Lote, PDC.Fecha_Vencimiento, "",
                                                "" AS Codigo_Fact 
                                            FROM Producto_Devolucion_Compra PDC 
                                            INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra
                                            WHERE PDC.Lote = "'.$lote.'" AND PDC.Id_Producto = '.$producto.$condicion5.' AND (D.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))';
            
            
            
            
            
            
            
            $query_actas_internacionales .= ' UNION ALL 
            (SELECT NP.Id_Nacionalizacion_Parcial AS ID, 
                (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = ARI.Id_Proveedor) AS Nombre_Origen, 
                (SELECT Nombre FROM Bodega WHERE Id_Bodega = ARI.Id_Bodega) as Destino, 
                "comprainternacionalver" AS Ruta, 
                "Entrada" AS Tipo, NP.Codigo, NP.Fecha_Registro, PNP.Cantidad, 
                PARI.Lote AS Lote, 
                PARI.Fecha_Vencimiento AS Fecha_Vencimiento, "", 
                "" AS Codigo_Fact 
                FROM Producto_Nacionalizacion_Parcial PNP 
                INNER JOIN Nacionalizacion_Parcial NP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial 
                INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
                INNER JOIN Producto_Acta_Recepcion_Internacional PARI ON PARI.Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional
                WHERE PARI.Lote = "'.$lote.'" AND PNP.Id_Producto = '.$producto.$condicion5.' AND (NP.Fecha_Registro BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59"))';
       
            $query_actas_internacionales ='';
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
            WHERE INF.Estado="Terminado" AND PIF.Id_Producto = ' . $producto." AND PIF.Lote='".$prod["Lote"]."'" . ' AND INF.Id_Punto_Dispensacion = ' . $idTipo . ' AND INF.Fecha_Inicio BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59" GROUP BY PIF.Id_Producto, PIF.Lote) 
            UNION (SELECT D.Id_Dispensacion AS ID, 
            (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=D.Id_Punto_Dispensacion) AS Nombre_Origen, 
            (SELECT CONCAT(Primer_Nombre," ",Primer_Apellido," (",Id_Paciente,") ") FROM Paciente 
            WHERE Id_Paciente=D.Numero_Documento) AS Destino, "dispensacion" AS Ruta, "Salida" AS Tipo, 
            D.Codigo, D.Fecha_Actual AS Fecha, PD.Cantidad_Entregada AS Cantidad, PD.Lote, "" AS Fecha_Vencimiento, "" AS Id_Factura,
            "" AS Codigo_Fact FROM Producto_Dispensacion PD 
            INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
            WHERE D.Estado_Dispensacion != "Anulada" AND PD.Id_Producto = ' . $producto." AND PD.Lote='".$prod["Lote"]."'" . ' AND D.Id_Punto_Dispensacion = ' . $idTipo . ' AND D.Fecha_Actual BETWEEN "'.$fecha1.' 00:00:00" AND "'.$fecha2.' 23:59:59")';
        }
        
        
        
        $condicion .= " AND R.Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
        $condicion2.= " AND AR.$attrFecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
        
        $ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha_inicio)),1,date("Y",strtotime($fecha_inicio)))-1));
        
        $query_inicial = 'SELECT SUM(Cantidad) as Total
        FROM Saldo_Inicial_Kardex 
        WHERE Lote = "'.$lote.'" AND Id_Producto = '.$producto.' AND Fecha="'.$ultimo_dia_mes.'" '.$condicion5.' GROUP BY Id_Producto, Lote';
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
            WHERE R.Estado = "Anulada" AND PR.Lote = "'.$lote.'" AND PR.Id_Producto = '.$producto.$condicion.')
            
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
            ) as Tipo, CONCAT(R.Codigo," - (", R.Estado,")") AS Codigo, (
                CASE R.Estado
                    WHEN "Anulada" THEN (SELECT MAX(Fecha) FROM Actividad_Remision WHERE Id_Remision = R.Id_Remision)
                    ELSE
                        R.Fecha
                END
            ) as Fecha, PR.Cantidad, PR.Lote, PR.Fecha_Vencimiento, F.Id_Factura_Venta as Id_Factura, F.Codigo as Codigo_Fact
            FROM Producto_Remision PR
            INNER JOIN Remision R
            ON R.Id_Remision = PR.Id_Remision
            LEFT JOIN Factura_Venta F
            ON F.Id_Factura_Venta = R.Id_Factura
            WHERE PR.Lote = "'.$lote.'" AND PR.Id_Producto = '.$producto.$condicion.')
            
            
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
            WHERE PR.Lote = "'.$lote.'" AND PR.Id_Producto = '.$producto.$condicion.') 
            
            UNION ALL (SELECT AI.Id_Ajuste_Individual as ID,
            IF(AI.Tipo="Entrada","",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.')) AS Nombre_Origen,IF(AI.Tipo="Entrada",(SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.'),"") as Destino,
            "ajustesinventariover" as Ruta, AI.Tipo, CONCAT(AI.Codigo," (Anulada)") AS Codigo, AI.Fecha as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
            FROM Producto_Ajuste_Individual PAI
            INNER JOIN Ajuste_Individual AI
            ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
            WHERE AI.Estado = "Anulada" AND PAI.Lote = "'.$lote.'" AND PAI.Id_Producto = '.$producto.$condicion3.' AND (AI.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") ) 
            
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
            WHERE PAI.Lote = "'.$lote.'" AND PAI.Id_Producto = '.$producto.$condicion3.' AND (AI.Fecha BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") ) 
            
            UNION ALL (SELECT AR.Id_'.$tabla.' as ID, '.getOrigenActa($tabla).' as Nombre_Origen, (SELECT Nombre FROM '.$tablaDest.' WHERE Id_'.$tablaDest.'='.$idTipo.') as Destino, "'.$ruta.'" as Ruta, "Entrada" as Tipo, AR.Codigo, AR.'.$attrFecha.' as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
            FROM Producto_'.$tabla.' PAR
            INNER JOIN '.$tabla.' AR
            ON PAR.Id_'.$tabla.' = AR.Id_'.$tabla.'
            WHERE PAR.Lote = "'.$lote.'" AND PAR.Id_Producto = '.$producto.$condicion2. ' AND AR.Estado = "Aprobada") 
            '.$sql_acta_recepcion_bodegas.' 
            
            
            UNION ALL (
                
               SELECT *
                FROM
                (
                   
                        SELECT INF.Id_Doc_Inventario_Fisico AS ID, "" AS Nombre_Origen, 
                        (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo=E.Id_Bodega_Nuevo) AS Destino, 
                        "inventariofisico/inventario_final_pdf.php" AS Ruta, 
                        "Inventario" AS Tipo, 
                        CONCAT("INVF",INF.Id_Doc_Inventario_Fisico) AS Codigo, 
                        INF.Fecha_Fin AS Fecha, SUM( IFNULL(PIF.Cantidad_Auditada, PIF.Segundo_Conteo)  ) AS Cantidad, 
                        GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote, 
                        GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento, "" AS Id_Factura, 
                        "" AS Codigo_Fact 
                        FROM Producto_Doc_Inventario_Fisico PIF 
                        INNER JOIN Doc_Inventario_Fisico INF ON PIF.Id_Doc_Inventario_Fisico=INF.Id_Doc_Inventario_Fisico
                        INNER JOIN Estiba E ON E.Id_Estiba = INF.Id_Estiba
                        WHERE INF.Estado="Terminado" AND PIF.Lote = "'.$lote.'" AND PIF.Id_Producto = '.$producto.$condicion4. ' AND 
                        (INF.Fecha_Fin BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") GROUP BY PIF.Id_Doc_Inventario_Fisico 
                    
                    
                    UNION 
                    
                    
                        SELECT INF.Id_Doc_Inventario_Fisico AS ID, "" AS Nombre_Origen, 
                        (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo=E.Id_Bodega_Nuevo) AS Destino, 
                        "inventariofisico/inventario_final_pdf.php" AS Ruta, 
                        "Inventario" AS Tipo, 
                        CONCAT("INVF",INF.Id_Doc_Inventario_Fisico) AS Codigo,
                        INF.Fecha_Fin AS Fecha,
                        0 AS Cantidad, 
                        "" AS Lote, 
                        "" AS Fecha_Vencimiento, "" AS Id_Factura, 
                        "" AS Codigo_Fact 
                        FROM Producto_Doc_Inventario_Fisico PIF 
                        INNER JOIN Doc_Inventario_Fisico INF ON PIF.Id_Doc_Inventario_Fisico=INF.Id_Doc_Inventario_Fisico 
                        INNER JOIN Estiba E ON E.Id_Estiba = INF.Id_Estiba
                        
                        WHERE INF.Estado="Terminado" /*AND INF.Categoria = "'.$categoria.'"*/ AND PIF.Lote != "'.$lote.'" AND PIF.Id_Producto != '.$producto.$condicion4. ' AND 
                        (INF.Fecha_Fin BETWEEN  "'.$fecha_inicio.' 00:00:00" AND "'.$fecha_fin.' 23:59:59") GROUP BY INF.Fecha_Fin
                    
                ) t  
                ORDER BY Fecha ASC, Cantidad ASC
            ) 
            
            '.$query_dispensaciones.$query_notas_creditos.$query_devoluciones_compras.$query_actas_internacionales.' ORDER BY Fecha ASC';
           
            
            
          //  WHERE INF.Estado="Terminado" AND INF.Categoria = "'.$categoria.'" AND PIF.Lote != "'.$lote.'" AND PIF.Id_Producto != '.$producto.$condicion4. ' AND */
            
            $oCon= new consulta();
            $oCon->setQuery($query);
       
                echo '<br> ***-'.$query.'</br>';
          
            $oCon->setTipo('Multiple');
            $resultados = $oCon->getData();
            unset($oCon);
        
        $i=-1;
        foreach($resultados as $res){ $i++;
            if($res["Tipo"]=='Entrada'){
                $acum+=$res["Cantidad"];
                //echo "------".$res["Fecha"]." -".$res["Codigo"]." - ".$res["Tipo"]." -- ".$res["Cantidad"]." - ".$acum."<br>";
            }elseif ($res["Tipo"]=='Salida'){
                $acum-=$res["Cantidad"];
                //echo "------".$res["Fecha"]." -".$res["Codigo"]." - ".$res["Tipo"]." -- ".$res["Cantidad"]." - ".$acum."<br>";
            } elseif ($res["Tipo"]=='Inventario') {
                // $acum= $resultados[$i-1]["Tipo"] != "Inventario" ? $res["Cantidad"] : $acum + $res["Cantidad"];
                $fecha_ant = date('Y-m-d',strtotime($resultados[$i-1]['Fecha']));
                $fecha_act = date('Y-m-d',strtotime($res['Fecha']));
                if ($resultados[$i-1]["Tipo"] != "Inventario" || ($resultados[$i-1]["Tipo"] == "Inventario" && $fecha_ant != $fecha_act)) {
                    $acum = $res["Cantidad"];
                } else {
                    $acum = $acum + $res["Cantidad"];
                }
                //echo "------".$res["Fecha"]." -".$res["Codigo"]." - ".$res["Tipo"]." -- ".$res["Cantidad"]." - ".$acum."<br>";
                
            }
            $resultados[$i]["Saldo"]=$acum;
        }
        
        /* $oItem = new complex("Saldo_Inicial_Kardex","Id_Saldo_Inicial_Kardex");
        $oItem->Fecha = $fecha2; // fecha fin
        $oItem->Id_Producto = $prod["Id_Producto"];
        $oItem->Cantidad = number_format($resultados[$i]["Saldo"],0,"","");
        $oItem->Lote = $prod["Lote"];
        $oItem->Fecha_Vencimiento = $prod["Fecha_Vencimiento"];
        $oItem->Id_Bodega = $prod['Id_Bodega'];
        $oItem->Id_Punto_Dispensacion = $prod['Id_Punto_Dispensacion'];
        $oItem->save(); 
        unset($oItem); */
    
        $valuesInsert[] = "(NULL,'$fecha2',$prod[Id_Producto],".number_format($resultados[$i]["Saldo"],0,"","").",'$prod[Lote]','$prod[Fecha_Vencimiento]',NULL,$prod[Id_Bodega],$prod[Id_Punto_Dispensacion])";
        $registros++;
    
        if ($registros == 1000) {
            $sqlValues = implode(',',$valuesInsert);
            registrarSaldoInicial($sqlValues);
            $registros = 0;
            $valuesInsert = [];
        }
        if((INT)$cantidad!=(INT)$resultados[$i]["Saldo"]){ $j++;
          echo $k." - ". $j.") - ".$prod["Codigo_Cum"]." - ".$prod["Lote"]." - ".$prod["Fecha_Vencimiento"]." - ".$idTipo." - ".$prod["Id_Producto"]." - ".$cantidad." - ".(INT)$resultados[$i]["Saldo"]." - DIFERENCIA<br>";  
        }else{
          //echo $k." - ". $j.") - ".$prod["Codigo_Cum"]." - ".$prod["Lote"]." - ".$prod["Fecha_Vencimiento"]." - ".$idTipo." - ".$prod["Id_Producto"]." - ".$cantidad." - ".(INT)$resultados[$i]["Saldo"]." - CORRECTO<br>";
        }
        
        //
        
    }
    
    if ($registros > 0) {
        $sqlValues = implode(',',$valuesInsert);
        registrarSaldoInicial($sqlValues);
        $registros = 0;
        $valuesInsert = [];
    }
    $k = 0;
    $j = 0;
    $resultados = [];
}

echo "finalizó";

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

function registrarSaldoInicial($valuesInsert) {
    $query = "INSERT INTO Saldo_Inicial_Kardex VALUES " . $valuesInsert;

    $oCon = new consulta();
    $oCon->setQuery($query);
    //$oCon->createData();
    unset($oCon);

    //echo "<br>################### REGISTRADO ################## <br>";
    return;
}

function fechasFiltros() {
    $fechas = [
        ["fecha_inicio" => "2020-07-01", "fecha_fin" => "2020-07-31"]
    ];
    /* $fechas = [
        ["fecha_inicio" => "2018-10-01", "fecha_fin" => "2018-10-31"],
        ["fecha_inicio" => "2018-11-01", "fecha_fin" => "2018-11-30"],
        ["fecha_inicio" => "2018-10-01", "fecha_fin" => "2018-10-31"],
        ["fecha_inicio" => "2018-11-01", "fecha_fin" => "2018-11-30"],
        ["fecha_inicio" => "2018-12-01", "fecha_fin" => "2018-12-31"],
        ["fecha_inicio" => "2019-01-01", "fecha_fin" => "2019-01-31"],
        ["fecha_inicio" => "2019-02-01", "fecha_fin" => "2019-02-28"],
        ["fecha_inicio" => "2019-03-01", "fecha_fin" => "2019-03-31"],
        ["fecha_inicio" => "2019-04-01", "fecha_fin" => "2019-04-30"],
        ["fecha_inicio" => "2019-05-01", "fecha_fin" => "2019-05-31"],
        ["fecha_inicio" => "2019-06-01", "fecha_fin" => "2019-06-30"],
        ["fecha_inicio" => "2019-07-01", "fecha_fin" => "2019-07-31"],
        ["fecha_inicio" => "2019-08-01", "fecha_fin" => "2019-08-31"]
    ]; */

    return $fechas;
}


?>