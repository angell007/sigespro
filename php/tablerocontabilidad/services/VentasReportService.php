<?php

class VentasReportService
{
    public function getCachePrefix(array $request)
    {
        $fecha_inicio = isset($request['fini']) ? $request['fini'] : '';
        $fecha_fin = isset($request['ffin']) ? $request['ffin'] : '';
        if ($fecha_inicio === '' || $fecha_inicio === 'undefined' || $fecha_fin === '' || $fecha_fin === 'undefined') {
            return $this->buildBasePrefix($request) . '_unknown';
        }
        $startTs = strtotime($fecha_inicio);
        $endTs = strtotime($fecha_fin);
        if ($startTs === false || $endTs === false) {
            return $this->buildBasePrefix($request) . '_unknown';
        }
        $startMonth = date('Ym', $startTs);
        $endMonth = date('Ym', $endTs);
        if ($startMonth === $endMonth) {
            return $this->buildBasePrefix($request) . '_' . $startMonth;
        }
        return $this->buildBasePrefix($request) . '_' . date('Ymd', $startTs) . '_' . date('Ymd', $endTs);
    }

    public function getCacheTtlSeconds(array $request)
    {
        $fecha_inicio = isset($request['fini']) ? $request['fini'] : '';
        $fecha_fin = isset($request['ffin']) ? $request['ffin'] : '';
        if ($fecha_inicio === '' || $fecha_inicio === 'undefined' || $fecha_fin === '' || $fecha_fin === 'undefined') {
            return 300;
        }
        $startTs = strtotime($fecha_inicio);
        $endTs = strtotime($fecha_fin);
        if ($startTs === false || $endTs === false) {
            return 300;
        }
        $todayTs = strtotime(date('Y-m-d'));
        if ($startTs <= $todayTs && $endTs >= $todayTs) {
            return 86400;
        }
        return 0;
    }

    private function buildBasePrefix(array $request)
    {
        $prefix = 'ventas_v1';
        if (isset($request['nit']) && $request['nit'] !== '') {
            $prefix .= '_nit' . $request['nit'];
        }
        return $prefix;
    }

    public function buildQueries(array $request, $condicion_nit)
    {
        $fecha_inicio = '';
        $fecha_fin = '';
        if (isset($request['fini']) && $request['fini'] != '' && $request['fini'] != 'undefined') {
            $fecha_inicio = $request['fini'];
        }
        if (isset($request['ffin']) && $request['ffin'] != '' && $request['ffin'] != 'undefined') {
            $fecha_fin = $request['ffin'];
        }

        $query1 = "SELECT
                        F.Codigo AS Factura, F.Codigo, F.Id_Resolucion,
                        F.Fecha_Documento AS Fecha_Factura,
                        IFNULL(F.Id_Cliente, F.Id_Cliente2) AS NIT_Cliente,
                        IFNULL(C.Nombre, C2.Nombre) AS Nombre_Cliente,
                        IFNULL(Z.Nombre, Z2.Nombre) AS Zona_Comercial,

                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Gravada, 0) END AS Gravada,
                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Excenta, 0) END AS Excenta,
                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Iva, 0) END AS Iva,

                        0 AS Descuentos_Gravados,
                        0 AS Descuentos_Excentos,
                        0 AS Cuota_Moderadora,

                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Total_Venta, 0) END AS Total_Venta,
                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Neto_Factura, 0) END AS Neto_Factura,

                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Costo_Venta_Exenta, 0) END AS Costo_Venta_Exenta,
                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Costo_Venta_Gravada, 0) END AS Costo_Venta_Gravada,
                        CASE WHEN F.Estado = 'Anulada' THEN 0 ELSE IFNULL(PF.Total_Costo_Venta, 0) END AS Total_Costo_Venta,

                        CASE
                            WHEN F.Estado = 'Anulada' THEN 0
                            WHEN IFNULL(PF.Total_Venta, 0) = 0 THEN 0
                            ELSE ROUND(((PF.Total_Venta - PF.Total_Costo_Venta) / PF.Total_Venta) * 100, 2)
                        END AS Rentabilidad,

                        F.Estado,
                        'Comercial' AS Tipo_Servicio,
                        '' AS Punto,
                        '' AS Ciudad,
                        R.Codigo AS Prefijo,
                        COALESCE(MC.Numero_Comprobante, 'SIN MOVIMIENTO') AS Movimiento_Contable

                        FROM (
                            SELECT
                                Id_Factura_Venta,
                                Codigo,
                                Id_Resolucion,
                                Fecha_Documento,
                                Id_Cliente,
                                Id_Cliente2,
                                Estado
                            FROM Factura_Venta
                            WHERE Fecha_Documento >= '$fecha_inicio 00:00:00'
                              AND Fecha_Documento < DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
                              $condicion_nit
                        ) F
                        LEFT JOIN (
                            SELECT
                                PFV.Id_Factura_Venta,
                                SUM(CASE WHEN PFV.Impuesto != 0 THEN PFV.Cantidad * PFV.Precio_Venta ELSE 0 END) AS Gravada,
                                SUM(CASE WHEN PFV.Impuesto = 0 THEN PFV.Cantidad * PFV.Precio_Venta ELSE 0 END) AS Excenta,
                                SUM(CASE WHEN PFV.Impuesto != 0
                                         THEN ROUND((PFV.Cantidad * PFV.Precio_Venta) * (PFV.Impuesto / 100), 2)
                                         ELSE 0 END) AS Iva,
                                SUM(PFV.Cantidad * PFV.Precio_Venta) AS Total_Venta,
                                SUM((PFV.Cantidad * PFV.Precio_Venta) +
                                    ROUND((PFV.Cantidad * PFV.Precio_Venta) * (PFV.Impuesto / 100), 2)) AS Neto_Factura,
                                SUM(CASE WHEN PFV.Impuesto = 0
                                         THEN COALESCE(NULLIF(PR.Costo, 0), CP.Costo_Promedio, 0) * PFV.Cantidad
                                         ELSE 0 END) AS Costo_Venta_Exenta,
                                SUM(CASE WHEN PFV.Impuesto != 0
                                         THEN COALESCE(NULLIF(PR.Costo, 0), CP.Costo_Promedio, 0) * PFV.Cantidad
                                         ELSE 0 END) AS Costo_Venta_Gravada,
                                SUM(COALESCE(NULLIF(PR.Costo, 0), CP.Costo_Promedio, 0) * PFV.Cantidad) AS Total_Costo_Venta
                            FROM Producto_Factura_Venta PFV
                            INNER JOIN (
                                SELECT Id_Factura_Venta
                                FROM Factura_Venta
                                WHERE Fecha_Documento >= '$fecha_inicio 00:00:00'
                                  AND Fecha_Documento < DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
                                  $condicion_nit
                            ) Ff ON Ff.Id_Factura_Venta = PFV.Id_Factura_Venta
                            LEFT JOIN (
                                SELECT PR1.Id_Remision, PR1.Id_Producto, PR1.Costo
                                FROM Producto_Remision PR1
                                INNER JOIN (
                                    SELECT Id_Remision, Id_Producto, MAX(Id_Producto_Remision) AS MaxId
                                    FROM Producto_Remision
                                    GROUP BY Id_Remision, Id_Producto
                                ) PR2 ON PR2.Id_Remision = PR1.Id_Remision
                                     AND PR2.Id_Producto = PR1.Id_Producto
                                     AND PR2.MaxId = PR1.Id_Producto_Remision
                            ) PR ON PR.Id_Remision = PFV.Id_Remision AND PR.Id_Producto = PFV.Id_Producto
                            LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = PFV.Id_Producto
                            GROUP BY PFV.Id_Factura_Venta
                        ) PF ON PF.Id_Factura_Venta = F.Id_Factura_Venta

                        LEFT JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
                        LEFT JOIN Cliente C2 ON C2.Id_Cliente = F.Id_Cliente2
                        LEFT JOIN Zona Z ON Z.Id_Zona = C.Id_Zona
                        LEFT JOIN Zona Z2 ON Z2.Id_Zona = C2.Id_Zona

                        LEFT JOIN (
                            SELECT Documento, MAX(Numero_Comprobante) AS Numero_Comprobante
                            FROM Movimiento_Contable
                            GROUP BY Documento
                        ) MC ON MC.Documento = F.Codigo
                        INNER JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion";

        $query2 = "SELECT
                            F.Codigo AS Factura, F.Codigo, F.Id_Resolucion,
                            F.Fecha_Documento AS Fecha_Factura,
                            F.Id_Cliente AS NIT_Cliente,
                            C.Nombre AS Nombre_Cliente,
                            Z.Nombre AS Zona_Comercial,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                    PF.Gravada
                                END
                            ) AS Gravada,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                        PF.Excenta
                                END
                            )  AS Excenta,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                    PF.Iva
                                END
                            )  AS Iva,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                    PF.DescGrav
                                END
                            )  AS Descuentos_Gravados,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                        PF.DescExcenta
                                END
                            )  AS Descuentos_Excentos,


                            IF(F.Estado_Factura = 'Anulada',0,F.Cuota) AS Cuota_Moderadora,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                        PF.Venta
                                END
                            )  AS Total_Venta,

                            (
                                CASE F.Estado_Factura
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    PF.Neto - F.Cuota
                                END
                            ) as Neto_Factura,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                        PF.CExcenta
                                END
                            )   AS Costo_Venta_Exenta,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                    PF.CGravada
                                END
                            )  AS Costo_Venta_Gravada,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    else
                                    PF.Total_Costo
                                    END
                            )   AS Total_Costo_Venta,

                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                        PF.Rentabilidad
                                    END
                            )  AS Rentabilidad,

                
                            F.Estado_Factura AS Estado,

                            IFNULL(D.Tipo_Servicio,D2.Tipo_Servicio) AS Tipo_Servicio,
                            IFNULL(D.Punto_Dis,D2.Punto_Dis) AS Punto,
                            IFNULL(D.Municipio,D2.Municipio) AS Ciudad ,
                            R.Codigo AS Prefijo,
                            COALESCE(MC.Numero_Comprobante, 'SIN MOVIMIENTO') AS Movimiento_Contable

                            FROM (
                                SELECT
                                    Id_Factura,
                                    Codigo,
                                    Id_Resolucion,
                                    Fecha_Documento,
                                    Id_Cliente,
                                    Estado_Factura,
                                    Cuota,
                                    Id_Dispensacion,
                                    Id_Dispensacion2
                                FROM Factura
                                WHERE Fecha_Documento >= '$fecha_inicio 00:00:00'
                                  AND Fecha_Documento < DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
                                  $condicion_nit
                            ) F
                            
                            INNER JOIN (
                                    SELECT PF.Id_Factura,
                                    PF.Impuesto,
                                    PF.Id_Producto,
                                    SUM(IF(PF.Impuesto=0, (PF.Cantidad*PF.Precio) ,0)) AS Excenta,
                                    SUM(IF(PF.Impuesto!=0 ,(PF.Cantidad*PF.Precio),0 ))AS Gravada,
                                    SUM(PF.Impuesto),
                                    ROUND((SUM(IFNULL(((
                                                            ( (PF.Cantidad*PF.Precio) - (PF.Cantidad*PF.Descuento)  ) - 
                                                            (if(PD.Costo >0, PD.Costo, PAR.Precio)*PF.Cantidad))
                                                                    / 
                                                        ( (PF.Cantidad*PF.Precio) - (PF.Cantidad*PF.Descuento)  ))
                                                            , 0)
                                                        )  * 100
                                                    ),2  ) AS Rentabilidad, 

                                                (ROUND(
                                                            SUM(
                                                                    IFNULL(if(PD.Costo >0, PD.Costo, PAR.Precio)*PF.Cantidad,0)),2) )
                                                    AS Total_Costo, 
                                                    
                                            SUM(IF( PF.Impuesto!=0 ,
                                                        IFNULL(if(PD.Costo >0, PD.Costo, PAR.Precio) * PF.Cantidad,0)
                                                        , 0 ) ) AS CGravada, 
                                                SUM(IF( PF.Impuesto=0 ,
                                                    IFNULL(if(PD.Costo >0, PD.Costo, PAR.Precio) *PF.Cantidad,0), 0 ) ) AS CExcenta, 
                                            (SUM(  IFNULL(
                                                    ( PF.Cantidad * PF.Precio - ( PF.Cantidad * PF.Descuento) ) +
                                                        ROUND( (PF.Cantidad * PF.Precio - ( PF.Cantidad * PF.Descuento ) )  *  (PF.Impuesto/100), 2)

                                                    ,0)
                                                    )) AS Neto, 
                                                    ROUND(
                                                    SUM(
                                                        IFNULL( (PF.Cantidad*PF.Precio)-(PF.Cantidad*PF.Descuento)  , 0)
                                                    )
                                                ,2) AS Venta,
                                                            ROUND(
                                                    SUM(IF(
                                                    PF.Impuesto=0,
                                                    IFNULL( PF.Cantidad*PF.Descuento , 0)
                                                    , 0 )
                                                )
                                                ,2) AS DescExcenta, 
                                                ROUND(
                                                    SUM(IF(
                                                        PF.Impuesto!=0,
                                                        IFNULL( PF.Cantidad*PF.Descuento , 0)
                                                        , 0 )
                                                    )
                                                ,2)AS DescGrav, 
                                                SUM(IF(
                                                        PF.Impuesto!=0,
                                                        ROUND(
                                                            IFNULL(
                                                                    (
                                                                        (PF.Cantidad*PF.Precio)
                                                                        -(PF.Cantidad*PF.Descuento)
                                                                    )
                                                                    *(PF.Impuesto/100)
                                                            , 0)
                                                        ,2)
                                                        ,0
                                                        )
                                                    ) AS Iva
                                    From Producto_Factura PF

                                    INNER JOIN (
                                        SELECT Id_Factura
                                        FROM Factura
                                        WHERE Fecha_Documento >= '$fecha_inicio 00:00:00'
                                          AND Fecha_Documento < DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
                                          $condicion_nit
                                    ) Ff ON Ff.Id_Factura = PF.Id_Factura

                                    INNER JOIN Producto_Dispensacion PD ON PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
                                    LEFT JOIN (
                                        SELECT PAR1.Precio, PAR1.Lote, PAR1.Id_Producto
                                        FROM Producto_Acta_Recepcion PAR1
                                        INNER JOIN (
                                            SELECT Id_Producto, Lote, MAX(Id_Producto_Acta_Recepcion) AS MaxId
                                            FROM Producto_Acta_Recepcion
                                            GROUP BY Id_Producto, Lote
                                        ) PAR2 ON PAR2.Id_Producto = PAR1.Id_Producto
                                             AND PAR2.Lote = PAR1.Lote
                                             AND PAR2.MaxId = PAR1.Id_Producto_Acta_Recepcion
                                    ) PAR ON PAR.Id_Producto = PF.Id_Producto AND PAR.Lote = PD.Lote
                                    GROUP BY PF.Id_Factura
                            ) PF ON PF.Id_Factura = F.Id_Factura
                            Inner JOIN (SELECT T.Id_Dispensacion, T.Tipo, PD.Nombre AS Punto_Dis, M.Nombre as Municipio, TS.Nombre AS Tipo_Servicio FROM Dispensacion T INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = T.Id_Punto_Dispensacion INNER JOIN Municipio M ON M.Id_Municipio=PD.Municipio LEFT JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = T.Id_Tipo_Servicio WHERE T.Id_Tipo_Servicio != 7  ) D ON D.Id_Dispensacion = F.Id_Dispensacion
                            LEFT JOIN (SELECT T.Id_Dispensacion, T.Tipo, PD.Nombre AS Punto_Dis, M.Nombre as Municipio, TS.Nombre AS Tipo_Servicio FROM Dispensacion T INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = T.Id_Punto_Dispensacion INNER JOIN Municipio M ON M.Id_Municipio=PD.Municipio LEFT JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = T.Id_Tipo_Servicio WHERE T.Id_Tipo_Servicio != 7 ) D2 ON D2.Id_Dispensacion = F.Id_Dispensacion2
                            INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
                            INNER JOIN Zona Z ON Z.Id_Zona = C.Id_Zona
                            INNER JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion
                            LEFT JOIN (
                                SELECT Documento, MAX(Numero_Comprobante) AS Numero_Comprobante
                                FROM Movimiento_Contable
                                GROUP BY Documento
                            ) MC ON MC.Documento = F.Codigo
                            #AND F.Procesada ='True' se comenta debia a que no se esta facturando electronicamente en el sistema por errores de facturacion electronica 
                            GROUP BY F.Id_Factura
                        ";

        $query3 = "SELECT
                        F.Codigo AS Factura, F.Codigo, F.Id_Resolucion,
                        F.Fecha_Documento as Fecha_Factura,
                        F.Id_Cliente AS NIT_Cliente,   
                        C.Nombre as Nombre_Cliente,
                        Z.Nombre as Zona_Comercial,
                        0 AS Gravada,

                        IF(F.Estado_Factura = 'Anulada',0, (DFC.Cantidad * DFC.Precio) )  AS Excenta,

                        0 AS Iva, 0 AS Descuentos_Gravados, 0 AS Descuentos_Excentos,
                        IF(F.Estado_Factura = 'Anulada',0,F.Cuota_Moderadora) AS Cuota_Moderadora,

                        IF(F.Estado_Factura = 'Anulada',0, ((DFC.Cantidad * DFC.Precio)) ) AS Total_Venta,

                        IF(F.Estado_Factura = 'Anulada',0, ((DFC.Cantidad * DFC.Precio)-F.Cuota_Moderadora) ) AS Neto_Factura,

                        0 AS Costo_Venta_Exenta,
                        0 AS Costo_Venta_Gravada,
                        0 AS Total_Costo_Venta,
                        0 AS Rentabilidad,
                        F.Estado_Factura AS Estado,
                        'Capita' AS Tipo_Servicio,
                        '' AS Punto,
                        '' AS Ciudad,
                        R.Codigo AS Prefijo,
                        COALESCE(MC.Numero_Comprobante, 'SIN MOVIMIENTO') AS Movimiento_Contable
                        FROM (
                            SELECT
                                Id_Factura_Capita,
                                Codigo,
                                Id_Resolucion,
                                Fecha_Documento,
                                Id_Cliente,
                                Estado_Factura,
                                Cuota_Moderadora
                            FROM Factura_Capita
                            WHERE Fecha_Documento >= '$fecha_inicio 00:00:00'
                              AND Fecha_Documento < DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
                              $condicion_nit
                        ) F
                        INNER JOIN Descripcion_Factura_Capita DFC ON DFC.Id_Factura_Capita = F.Id_Factura_Capita

                        INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
                        INNER JOIN Zona Z
                        ON Z.Id_Zona = C.Id_Zona
                        INNER JOIN Resolucion R ON
                        R.Id_Resolucion = F.Id_Resolucion
                        LEFT JOIN (
                            SELECT Documento, MAX(Numero_Comprobante) AS Numero_Comprobante
                            FROM Movimiento_Contable
                            GROUP BY Documento
                        ) MC ON MC.Documento = F.Codigo
                        ";

        $query4 = " SELECT

                            F.Codigo AS Factura, F.Codigo, F.Id_Resolucion,
                            F.Fecha as Fecha_Factura,
                            F.Id_Cliente AS NIT_Cliente,
                            (
                                CASE F.Tipo_Cliente
                                    WHEN  'Cliente'
                                        THEN CL.Razon_Social
                                    WHEN  'Proveedor'
                                        THEN COALESCE(PRV.Nombre, CONCAT(PRV.Primer_Nombre, ' ',PRV.Primer_Apellido))
                                    ELSE IFNULL(CONCAT(FU.Nombres, ' ',FU.Apellidos), 'SIN INFORMACIÓN')
                                END
                            ) AS Nombre_Cliente,

                        /*   IF(F.Tipo_Cliente='Cliente' ,(SELECT Z.Nombre FROM Zona Z
                                                        INNER JOIN Cliente CL ON CL.Id_Cliente = F.Id_Cliente
                                                        WHERE Z.Id_Zona = CL.Id_Zona ), ' ' ) as Zona_Comercial, 	*/
                            '' AS Zona_Comercial,
                            (
                                CASE F.Estado_Factura
                                    WHEN 'Anulada' THEN 0
                                    ELSE
                                    SUM( IF(DFA.Impuesto>0 , (  DFA.Cantidad * DFA.Precio ), 0 ) )
                                END
                            ) as Gravada,

                            (
                                CASE F.Estado_Factura
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    SUM( IF(DFA.Impuesto=0 , (  DFA.Cantidad * DFA.Precio ), 0 ) )
                                END
                            ) as Excenta,

                            (
                                CASE F.Estado_Factura
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    ROUND(SUM( (DFA.Cantidad*DFA.Precio)*(DFA.Impuesto/100) ),2)
                                END
                            ) as Iva,

                            (
                                CASE F.Estado_Factura
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    ROUND(
                                        SUM(IF(
                                        DFA.Impuesto!=0,
                                        IFNULL( DFA.Cantidad*DFA.Descuento , 0)
                                        , 0 )
                                        )
                                    ,2)
                                END
                            ) as Descuentos_Gravados,

                            (
                                CASE F.Estado_Factura
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    ROUND(SUM( IF(DFA.Impuesto=0 , (  DFA.Cantidad * DFA.Descuento ), 0 ) ) ,2)
                                END
                            ) as Descuentos_Excentos,

                            0 AS Cuota_Moderadora,

                            (
                                CASE F.Estado_Factura
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    ROUND(
                                        SUM(
                                            IFNULL( (DFA.Cantidad*DFA.Precio)-(DFA.Cantidad*DFA.Descuento)  , 0)
                                            )
                                    ,2)
                                END
                            ) as Total_Venta,

                            (
                                CASE F.Estado_Factura
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    SUM( ( DFA.Cantidad * DFA.Precio - ( DFA.Cantidad * DFA.Descuento) ) +
                                            ROUND( (DFA.Cantidad * DFA.Precio - ( DFA.Cantidad * DFA.Descuento) )  *  (DFA.Impuesto/100), 2) )
                                END
                            ) as Neto_Factura,


                            0 AS Costo_Venta_Exenta,
                            0 AS Costo_Venta_Gravada,
                            0 AS Total_Costo_Venta,
                            100 AS Rentabilidad,
                            F.Estado_Factura AS Estado,
                            'Administrativa' AS Tipo_Servicio,
                            '' AS Punto,
                            '' AS Ciudad,
                            R.Codigo AS Prefijo,
                            COALESCE(MC.Numero_Comprobante, 'SIN MOVIMIENTO') AS Movimiento_Contable

                            FROM (
                                SELECT
                                    Id_Factura_Administrativa,
                                    Codigo,
                                    Id_Resolucion,
                                    Fecha,
                                    Id_Cliente,
                                    Tipo_Cliente,
                                    Estado_Factura
                                FROM Factura_Administrativa
                                WHERE Fecha >= '$fecha_inicio 00:00:00'
                                  AND Fecha < DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
                                  $condicion_nit
                            ) F
                            INNER JOIN Descripcion_Factura_Administrativa DFA ON F.Id_Factura_Administrativa = DFA.Id_Factura_Administrativa
                            INNER JOIN Resolucion R ON
                            R.Id_Resolucion = F.Id_Resolucion
                            LEFT JOIN Cliente CL ON CL.Id_Cliente = F.Id_Cliente AND F.Tipo_Cliente = 'Cliente'
                            LEFT JOIN Proveedor PRV ON PRV.Id_Proveedor = F.Id_Cliente AND F.Tipo_Cliente = 'Proveedor'
                            LEFT JOIN Funcionario FU ON FU.Identificacion_Funcionario = F.Id_Cliente AND F.Tipo_Cliente NOT IN ('Cliente', 'Proveedor')
                            LEFT JOIN (
                                SELECT Documento, MAX(Numero_Comprobante) AS Numero_Comprobante
                                FROM Movimiento_Contable
                                GROUP BY Documento
                            ) MC ON MC.Documento = F.Codigo
                            GROUP BY F.Id_Factura_Administrativa
                        ";

        $orden = "ORDER BY F.Id_Resolucion,  CONVERT(SUBSTRING(F.Codigo, IF(R.Codigo != '0' , LENGTH(R.Codigo) + 1 , 0)  ),UNSIGNED INTEGER)";

        return [
            "$query1  $orden",
            "$query2  $orden",
            "$query3  $orden",
            "$query4  $orden"
        ];
    }
}
