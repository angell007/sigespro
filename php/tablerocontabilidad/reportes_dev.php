<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_'.$_REQUEST['tipo'].'.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

if(isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != ""){
    $tipo=$_REQUEST['tipo'];
    $query=CrearQuery($tipo);
    ArmarReporte($query);


}


function ArmarReporte($query){

    $encabezado=GetEncabezado($query);
    $datos=GetDatos($query);
    $contenido = '';
    
    if ($encabezado) {
        $contenido .= '<table border="1"><tr>';
        foreach ($encabezado as $key => $value) {
          $contenido.='<td>'.$key.'</td>';
        }
        $contenido .= '</tr>';
    }

    if ($datos) {
        foreach ($datos as $i => $dato) {
            $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {
               
                if(ValidarKey($key) ){
                    $contenido.= '<td>' . number_format($dato[$key],2,",","") . '</td>';
                }else{
                    $contenido.= '<td>' . $dato[$key] . '</td>';
                }
               
            }    

            $contenido .= '</tr>';
        }
    
     $contenido .= '</table>';
    }

    if ($contenido == '') {
        $contenido .= '
            <table>
                <tr>
                    <td>NO EXISTE INFORMACION PARA MOSTRAR</td>
                </tr>
            </table>
        ';
    }

 echo $contenido;

}
function GetEncabezado($query){
    $oCon= new consulta();
    $oCon->setQuery($query);
    $encabezado= $oCon->getData();
    unset($oCon);

    return $encabezado;
}

function GetDatos($query){
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos= $oCon->getData();
    unset($oCon);
    return $datos;
}
function CrearQuery($tipo){

    $condicion_nit = '';
    if ($_REQUEST['nit'] && $_REQUEST['nit'] != '') {
        if ($tipo == 'DevolucionC') {
            $condicion_nit .= " AND NC.Id_Proveedor=$_REQUEST[nit]";
        } elseif ($tipo == 'Acta_Compra') {
            $condicion_nit .= " AND P.Id_Proveedor=$_REQUEST[nit]";
        } elseif($tipo == 'Reporte_Nacionalizacion'){
            $condicion_nit .= " AND OCI.Id_Proveedor=$_REQUEST[nit]";
        } else {
            $condicion_nit .= " AND F.Id_Cliente=$_REQUEST[nit]";
        }
    }
    
    
    switch ($tipo) {
        case 'Inventario_Valorizado':
          $query="SELECT
          #P.Id_Producto,
                (
                CASE
                    WHEN SIK.Id_Bodega != 0 THEN (SELECT Nombre FROM Bodega WHERE Id_Bodega = SIK.Id_Bodega)
                    WHEN SIK.Id_Punto_Dispensacion != 0 THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = SIK.Id_Punto_Dispensacion)
                END
                ) AS 'Punto/Bodega',
                P.Codigo_Cum, 
                P.Nombre_Comercial AS Producto, 
                CAST(SUM(SIK.Cantidad) AS SIGNED) AS Cantidad, 
                CAST(I.Costo AS UNSIGNED) AS Costo, 
                CAST((SUM(SIK.Cantidad) * I.Costo) AS UNSIGNED) AS Valorizado
                FROM Saldo_Inicial_Kardex SIK 
                INNER JOIN Producto P ON SIK.Id_Producto = P.Id_Producto 
                INNER JOIN (SELECT AVG(Costo) AS Costo, Id_Producto FROM Inventario GROUP BY Id_Producto) I ON SIK.Id_Producto = I.Id_Producto WHERE SIK.Fecha = '$_REQUEST[fecha]' AND I.Costo != 0 AND I.Id_Producto != 1 GROUP BY SIK.Id_Producto";
            break;
        case 'Ventas':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
          $query='SELECT 
                    F.Codigo as Factura, 
                    DATE(F.Fecha_Documento) as Fecha_Factura, 
                    F.Id_Cliente as NIT_Cliente, 
                    C.Nombre as Nombre_Cliente, 
                    IFNULL(Z.Nombre,"Sin Zona Comercial") as Zona_Comercial, 
                    
                    IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto!=0),0) as Gravada, 
                    
                    IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto=0),0) as Excenta,
                    
                    IFNULL((SELECT ROUND(SUM((PFV.Cantidad*PFV.Precio_Venta)*(19/100)),2)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto!=0),0) as Iva,
                    
                    0 AS Descuentos,
                    
                    (IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto!=0),0) + IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto=0),0)) AS Total_Venta,
                    
                    
                    ((IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto!=0),0) + IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PFV.Cantidad*PFV.Precio_Venta)*(19/100)),2)
                    FROM Producto_Factura_Venta PFV
                    WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND PFV.Impuesto!=0),0)) AS Neto_Factura,
                    
                    (SELECT IFNULL((SUM(I.Costo*PFV.Cantidad)),0) FROM Producto_Factura_Venta PFV INNER JOIN Producto P ON P.Id_Producto = PFV.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PFV.Id_Producto = I.Id_Producto WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND P.Gravado = "No") AS Costo_Venta_Exenta,
                    
                    (SELECT IFNULL((SUM(I.Costo*PFV.Cantidad)),0) FROM Producto_Factura_Venta PFV INNER JOIN Producto P ON P.Id_Producto = PFV.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PFV.Id_Producto = I.Id_Producto WHERE PFV.Id_Factura_Venta = F.Id_Factura_Venta AND P.Gravado = "Si") AS Costo_Venta_Gravada,
                    F.Estado
                    
                    FROM Factura_Venta F
                    INNER JOIN Cliente C
                    ON C.Id_Cliente = F.Id_Cliente
                    LEFT JOIN Zona Z
                    ON Z.Id_Zona = C.Id_Zona
                    WHERE (DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'") '.$condicion_nit.'
                    
                    UNION (
                        SELECT
                        F.Codigo AS Factura,
                        DATE(F.Fecha_Documento) AS Fecha_Factura,
                        F.Id_Cliente AS NIT_Cliente,
                        C.Nombre AS Nombre_Cliente,
                        Z.Nombre as Zona_Comercial,
                    
                        IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) as Gravada,
                    
                        IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) as Excenta,
                    
                        IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) as Iva,
                    
                        (IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) + F.Cuota) as Descuentos,
                    
                        (IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)) AS Total_Venta,
                    
                        
                    
                        ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) - (IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
                        FROM Producto_Factura PF
                        WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) + F.Cuota)) AS Neto_Factura,
                    
                        (SELECT IFNULL((SUM(I.Costo*PF.Cantidad)),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = "No") AS Costo_Venta_Exenta,
                        
                        (SELECT IFNULL((SUM(I.Costo*PF.Cantidad)),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = "Si") AS Costo_Venta_Gravada,
                        F.Estado_Factura AS Estado
                    
                        FROM Factura F
                        INNER JOIN Cliente C
                        ON C.Id_Cliente = F.Id_Cliente
                        INNER JOIN Zona Z
                        ON Z.Id_Zona = C.Id_Zona
                        WHERE (DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'")
                        AND F.Tipo = "Factura" '.$condicion_nit.'
                    
                    )
                    UNION
                    (SELECT
                    F.Codigo AS Factura,
                    DATE(F.Fecha_Documento) as Fecha_Factura, 
                    F.Id_Cliente, 
                    C.Nombre as Razon_Social,
                    Z.Nombre as Zona_Comercial,
                    0 AS Gravado, (DFC.Cantidad * DFC.Precio) AS Excento, 0 AS Iva, F.Cuota_Moderadora AS Descuentos, ((DFC.Cantidad * DFC.Precio)) AS Total_Factura, ((DFC.Cantidad * DFC.Precio)-F.Cuota_Moderadora) AS Neto_Factura,
                    0 AS Costo_Venta_Exenta,
                    0 AS Costo_Venta_Gravada,
                    F.Estado_Factura AS Estado
                    FROM
                    Descripcion_Factura_Capita DFC
                    INNER JOIN Factura_Capita F ON DFC.Id_Factura_Capita = F.Id_Factura_Capita
                    INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
                    INNER JOIN Zona Z
                    ON Z.Id_Zona = C.Id_Zona
                    WHERE (DATE(F.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'") '.$condicion_nit.'
                    )
                    
                    ORDER BY `Fecha_Factura`  ASC';
            break;
        case 'DevolucionV':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }

            $condicion_nit = $condicion_nit != '' ? " AND NC.Id_Cliente = $_REQUEST[nit]" : '';

          $query="SELECT 
                    NC.Codigo AS Devolucion_Venta,
                    NC.Fecha AS Fecha_Devolucion_Venta,
                    NC.Id_Cliente AS NIT,
                    C.Nombre AS Cliente,
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) as Gravado,
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0) as Excento,
                    
                    IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Precio_Venta*(0.19)))
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) AS Iva,
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0) AS Total,
                    
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0) + IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Precio_Venta*(0.19)))
                    FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) AS Neto
                    FROM Nota_Credito NC
                    INNER JOIN Cliente C 
                    ON C.Id_Cliente = NC.Id_Cliente
                    WHERE (DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin') $condicion_nit
                    HAVING Neto != 0
                    ORDER BY NC.Fecha DESC";
            break;
        case 'DevolucionC':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $condicion_nit = $condicion_nit != '' ? " AND NC.Id_Proveedor = $_REQUEST[nit]" : '';
          $query="SELECT 
                    NC.Codigo AS Devolucion_Venta,
                    NC.Fecha AS Fecha_Devolucion_Venta,
                    NC.Id_Proveedor AS NIT,
                    C.Nombre AS Cliente,
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) as Gravado,
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0) as Excento,
                    
                    IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Costo*(0.19)))
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) AS Iva,
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0) AS Total,
                    
                    
                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0) + IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Costo*(0.19)))
                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) AS Neto
                    FROM Devolucion_Compra NC
                    INNER JOIN Proveedor C 
                    ON C.Id_Proveedor = NC.Id_Proveedor
                    WHERE (DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin') $condicion_nit
                    ORDER BY NC.Fecha DESC";
            break;

        case 'Acta_Compra':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
          $query = "
            SELECT 
                OCN.Id_Proveedor AS Nit,
                P.Nombre, 
                AR.Codigo as Acta_Recepcion,
                IF(AR.Id_Bodega = 0, 'Punto Dispensacion', 'Bodega') AS Tipo,
                FAR.Factura, 
                FAR.Fecha_Factura,
                DATE_FORMAT(AR.Fecha_Creacion,'%Y-%m-%d') as Fecha_Acta, 
                (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                 FROM Producto_Acta_Recepcion PAR
                 INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                 WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si') as Gravada,
                (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                 FROM Producto_Acta_Recepcion PAR
                 INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                 WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No') as Excenta,
                ((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                  FROM Producto_Acta_Recepcion PAR
                  INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                  WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')
                  +
                  (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                  FROM Producto_Acta_Recepcion PAR
                  INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                  WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No')) as Total_Compra,
                IFNULL(((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                  FROM Producto_Acta_Recepcion PAR
                  INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                  WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')
                  +
                  (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                  FROM Producto_Acta_Recepcion PAR INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                  WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No')
                  +
                  (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                  FROM Producto_Acta_Recepcion PAR
                  INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                  WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')
                  -
                  IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                  -
                  IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                  ),0) AS Neto_Factura,
                (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                 FROM Producto_Acta_Recepcion PAR
                 INNER JOIN Producto P ON PAR.Id_Producto = P.Id_Producto
                 WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si') as Iva, 
                 AR.Codigo as Codigo_Acta,
                IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0) AS Rte_Fuente,
                IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0) AS Rte_Ica
                 FROM Orden_Compra_Nacional OCN
                 INNER JOIN Acta_Recepcion AR  ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional 
                 INNER JOIN Factura_Acta_Recepcion FAR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
                 INNER JOIN Proveedor P ON P.Id_Proveedor = OCN.Id_Proveedor
                 INNER JOIN Bodega B ON AR.Id_Bodega = B.Id_Bodega
                 WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$fecha_inicio."' AND '".$fecha_fin."'".$condicion_nit."
                ORDER BY P.Nombre ASC";
            break;

        case 'Reporte_Nacionalizacion':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
          $query = '
            SELECT 
            PRD.Nombre_Comercial,
            IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
                  CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
            IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
            PRD.Embalaje,
            PRD.Codigo_Cum,
            IF((PRO.Primer_Nombre IS NULL OR PRO.Primer_Nombre = ""), PRO.Nombre, CONCAT_WS(" ", PRO.Primer_Nombre, PRO.Segundo_Nombre, PRO.Primer_Apellido, PRO.Segundo_Apellido)) AS Nombre_Proveedor,
            OCI.Codigo AS Codigo_Compra,
            ARI.Codigo AS Codigo_Acta,
            NP.Codigo AS Codigo_Nacionalizacion,
            NP.Fecha_Registro,
            NP.Tasa_Cambio AS Tasa,
            NP.Tramite_Sia,
            NP.Formulario,
            NP.Cargue,
            NP.Gasto_Bancario,
            NP.Descuento_Parcial AS Descuento_Arancelario,
            PNP.Total_Flete AS Flete_Internacional_USD,
            PNP.Total_Seguro AS Seguro_Internacional_USD,
            PNP.Total_Flete_Nacional,
            PNP.Total_Licencia,
            PNP.Total_Arancel,
            PNP.Total_Iva,
            PNP.Subtotal AS Subtotal_Importacion,
            (PNP.Subtotal+PNP.Total_Flete_Nacional+PNP.Total_Licencia+PNP.Total_Iva) AS Subtotal_Nacionalizacion
          FROM Nacionalizacion_Parcial NP
          INNER JOIN Producto_Nacionalizacion_Parcial PNP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial          
          INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
          INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
          INNER JOIN Producto PRD ON PNP.Id_Producto = PRD.Id_Producto
          INNER JOIN Funcionario F ON NP.Identificacion_Funcionario = F.Identificacion_Funcionario
          INNER JOIN Proveedor PRO ON OCI.Id_Proveedor = PRO.Id_Proveedor
          WHERE DATE_FORMAT(NP.Fecha_Registro, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'"'.$condicion_nit;

        break;
    }

    return $query;
}

function ValidarKey($key){
    $datos=["Nada","Excenta", "Iva","Descuentos", "Total_Venta", "Neto_Factura", "Costo_Venta_Exenta", "Costo_Venta_Gravada", "Gravado", "Total", "Excento", "Total_Factura", "Gravada", "Valorizado", "Tramite_Sia", "Formulario", "Cargue", "Gasto_Bancario", "Descuento Arancelario", "Flete_Internacional_USD", "Seguro_Internacional_USD", "Total_Flete_Nacional", "Total_Licencia", "Total_Arancel", "Total_Iva", "Subtotal_Importacion", "Subtotal_Nacionalizacion", "Tasa"];
    $pos = array_search($key,$datos);	
    return strval($pos);
}


