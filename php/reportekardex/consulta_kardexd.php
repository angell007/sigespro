<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../helper/replaceInventario.php';
include_once '../../helper/response.php';

$condicion = '';
$condicion2 = '';
$condicion3 = '';
$condicion4 = '';
$condicion5 = '';
$condicion6 = '';
$tipo = $_REQUEST['tipo'];
$idTipo = isset($_REQUEST['idtipo']) && $_REQUEST['idtipo'] !== '' ? (int) $_REQUEST['idtipo'] : 0;
$producto = $_REQUEST['producto'];
$ruta = '';
$tabla = '';
$tablaDest = '';
$attrFecha = '';
$query_dispensaciones = '';
$query_notas_creditos = '';
$query_devoluciones_compras = '';
$query_actas_internacionales = '';

$documento = '';
$group = '';

if (isset($_REQUEST['fecha_inicio']) && $_REQUEST['fecha_inicio'] != "") {
    $fecha_inicio = $_REQUEST['fecha_inicio'] . "-01";
}
if (isset($_REQUEST['fecha_fin']) && $_REQUEST['fecha_fin'] != "") {
    $fecha_fin = $_REQUEST['fecha_fin'];
}

$sql_acta_recepcion_bodegas = '';

if ($tipo == 'Bodega') {

    $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Bodega'";
    $condicion2 .= " AND AR.Id_Bodega_Nuevo=$idTipo";
    $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Bodega'";
    $condicion4 .= " AND INF.Id_Bodega_Nuevo=$idTipo";
    $condicion5 .= " AND Id_Bodega=$idTipo";
    $condicion6 .= " AND Id_Origen=$idTipo";
    $ruta = 'actarecepcionver';
    $tabla = 'Acta_Recepcion';
    $attrFecha = 'Fecha_Creacion';
    $tablaDest = 'Bodega';

    //10-08-2021 roberth morales

    $condicion2Acta .= " AND AR.Id_Bodega_Nuevo=$idTipo";
    $condicion5Acta .= " AND Id_Bodega_Nuevo=$idTipo";
    $tablaDestACT = 'Bodega_Nuevo';

    $documento .= ' (SELECT INF.Id_Inventario_Fisico_Nuevo AS ID,
                    "" AS Nombre_Origen,
                    (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo=INF.Id_Bodega_Nuevo) AS Destino,
                    "inventariofisico/inventario_final_pdf.php" AS Ruta,
                    "Inventario" AS Tipo,
                    CONCAT("INVF",INF.Id_Inventario_Fisico_Nuevo) AS Codigo,
                    INF.Fecha AS Fecha,
                    IFNULL(SUM(PIF.Segundo_Conteo), PIF.Cantidad_Auditada) AS Cantidad,
                    GROUP_CONCAT(PIF.Lote SEPARATOR " | ") AS Lote,
                    GROUP_CONCAT(PIF.Fecha_Vencimiento SEPARATOR " | ") AS Fecha_Vencimiento,
                    "" AS Id_Factura,
                    "" AS Codigo_Fact
                    FROM Producto_Doc_Inventario_Fisico PIF
                                    INNER JOIN Doc_Inventario_Fisico DIF ON PIF.Id_Doc_Inventario_Fisico = DIF.Id_Doc_Inventario_Fisico
                                    INNER JOIN Inventario_Fisico_Nuevo INF ON DIF.Id_Inventario_Fisico_Nuevo=INF.Id_Inventario_Fisico_Nuevo';

    $group .= 'GROUP BY PIF.Id_Doc_Inventario_Fisico';

    //10-08-2021 roberth morales
    $origen_acta_recepcion_rem = getOrigenActa('Acta_Recepcion_Remision');

    $sql_acta_recepcion_bodegas .=
        "SELECT AR.Id_Acta_Recepcion_Remision as ID,
    $origen_acta_recepcion_rem as Nombre_Origen,
    (SELECT Nombre FROM $tablaDestACT WHERE Id_$tablaDestACT = $idTipo) as Destino,
    'actarecepcionbodegaver' as Ruta, 'Entrada' as Tipo, AR.Codigo, AR.Fecha as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, '' as Id_Factura, '' as Codigo_Fact
    FROM Producto_Acta_Recepcion_Remision PAR
    INNER JOIN Acta_Recepcion_Remision AR
    ON PAR.Id_Acta_Recepcion_Remision = AR.Id_Acta_Recepcion_Remision
    WHERE PAR.Id_Producto = $producto $condicion2Acta AND (AR.Fecha BETWEEN  '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59')";

    $query_notas_creditos .= ' UNION ALL (SELECT NC.Id_Nota_Credito AS ID, R.Nombre_Destino AS Nombre_Origen, R.Nombre_Origen as Destino, "notascreditover" AS Ruta, "Entrada" AS Tipo, NC.Codigo, NC.Fecha, SUM(PNC.Cantidad), GROUP_CONCAT(PNC.Lote SEPARATOR " | ") as Lote, GROUP_CONCAT(PNC.Fecha_Vencimiento SEPARATOR " | ") as Fecha_Vencimiento, NC.Id_Factura, (SELECT Codigo FROM Factura_Venta WHERE Id_Factura_Venta = NC.Id_Factura) AS Codigo_Fact
    FROM Producto_Nota_Credito PNC
    INNER JOIN Nota_Credito NC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito
    INNER JOIN (SELECT RE.Nombre_Destino, RE.Nombre_Origen, RE.Id_Factura FROM Producto_Remision PR INNER JOIN Remision RE ON RE.Id_Remision = PR.Id_Remision WHERE PR.Id_Producto=' . $producto . ' AND RE.Id_Origen=' . $idTipo . ' GROUP BY RE.Id_Factura) R ON R.Id_Factura = NC.Id_Factura
    WHERE NC.Estado IN ("Acomodada","Anulada") AND PNC.Id_Producto = ' . $producto . ' AND (NC.Fecha BETWEEN  "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59") GROUP BY ID)';

    $query_devoluciones_compras .= ' UNION ALL (
                                SELECT D.Id_Devolucion_Compra AS ID, 
                                (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = D.Id_Bodega_Nuevo) AS Nombre_Origen, 
                                (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) as Destino, 
                                "verdetalledevolucion" AS Ruta, "Salida" AS Tipo, D.Codigo, AD.Fecha, PDC.Cantidad, PDC.Lote, 
                                PDC.Fecha_Vencimiento, "", "" AS Codigo_Fact 
                                FROM Producto_Devolucion_Compra PDC 
                                INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra
                                INNER JOIN Actividad_Devolucion_Compra AD ON PDC.Id_Devolucion_Compra = AD.Id_Devolucion_Compra AND AD.Estado ="Fase 2"
                                
                                WHERE PDC.Id_Producto = ' . $producto . $condicion5Acta . ' AND D.Estado = "Anulada" AND (D.Fecha BETWEEN  "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59"))

                            UNION ALL (
                                SELECT D.Id_Devolucion_Compra AS ID,
                                (CASE D.Estado WHEN "Anulada" THEN (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) 
                                    ELSE (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = D.Id_Bodega_Nuevo) END) AS Nombre_Origen, 
                                (CASE D.Estado WHEN "Anulada" THEN (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = D.Id_Bodega_Nuevo) 
                                    ELSE (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = D.Id_Proveedor) END) as Destino, 
                                "verdetalledevolucion" AS Ruta, (CASE D.Estado WHEN "Anulada" THEN "Entrada" ELSE "Salida" END) AS Tipo, CONCAT(D.Codigo,IF(D.Estado="Anulada"," (Anulada)","")) AS Codigo,
                                AD.Fecha, PDC.Cantidad, PDC.Lote, PDC.Fecha_Vencimiento, "", "" AS Codigo_Fact 
                                FROM Producto_Devolucion_Compra PDC 
                                INNER JOIN Devolucion_Compra D ON PDC.Id_Devolucion_Compra = D.Id_Devolucion_Compra
                                INNER JOIN Actividad_Devolucion_Compra AD ON PDC.Id_Devolucion_Compra = AD.Id_Devolucion_Compra AND AD.Estado ="Fase 2"
                                
                                WHERE PDC.Id_Producto = ' . $producto . $condicion5Acta . ' AND (D.Fecha BETWEEN  "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59"))
    ';
    $query_actas_internacionales .= ' UNION ALL (SELECT NP.Id_Nacionalizacion_Parcial AS ID, (SELECT Razon_Social FROM Proveedor WHERE Id_Proveedor = ARI.Id_Proveedor) AS Nombre_Origen, (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = ARI.Id_Bodega) as Destino, "parcialactainternacionalver" AS Ruta, "Entrada" AS Tipo, NP.Codigo, NP.Fecha_Registro, PNP.Cantidad, (SELECT Lote FROM Producto_Acta_Recepcion_Internacional WHERE Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional) AS Lote, (SELECT Fecha_Vencimiento FROM Producto_Acta_Recepcion_Internacional WHERE Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional) AS Fecha_Vencimiento, "", "" AS Codigo_Fact FROM Producto_Nacionalizacion_Parcial PNP INNER JOIN Nacionalizacion_Parcial NP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional WHERE PNP.Id_Producto = ' . $producto . $condicion5Acta . ' AND (NP.Fecha_Registro BETWEEN  "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59"))';
} else {

    $query_comprobar = 'SELECT
                            IFPN.Id_Inventario_Fisico_Punto_Nuevo AS Id,
                            IFPN.Fecha
                            FROM Producto_Doc_Inventario_Fisico_Punto PIF
                            INNER JOIN Doc_Inventario_Fisico_Punto INF ON PIF.Id_Doc_Inventario_Fisico_Punto=INF.Id_Doc_Inventario_Fisico_Punto
                            INNER JOIN Inventario_Fisico_Punto_Nuevo IFPN ON INF.Id_Inventario_Fisico_Punto_Nuevo = IFPN.Id_Inventario_Fisico_Punto_Nuevo
                            WHERE PIF.Id_Producto = ' . $producto . "
                                  AND ( PIF.Lote='" . $prod["Lote"] . "'" . ')
                                  AND IFPN.Id_Punto_Dispensacion = ' . $idTipo
        . ' AND IFPN.Fecha BETWEEN "' . $fecha1 . ' 00:00:00" AND "' . $fecha2 . ' 23:59:59"
                            GROUP BY PIF.Id_Producto, IFPN.Fecha';
    $oCon = new consulta();
    $oCon->setQuery($query_comprobar);
    $oCon->setTipo('multiple');
    $comprobacion = $oCon->getData();
    unset($oCon);

    if (count($comprobacion) > 0) {
        foreach ($comprobacion as $value) {

            $query_invs = 'SELECT GROUP_CONCAT(INF.Id_Inventario_Fisico_Punto_Nuevo) AS Ids
                         FROM Inventario_Fisico_Punto_Nuevo INF
                         WHERE INF.Id_Punto_Dispensacion = ' . $idTipo
                . ' AND INF.Fecha = "' . $value["Fecha_Fin"] . '"';

            $oCon = new consulta();
            $oCon->setQuery($query_invs);
            $result = $oCon->getData();
            $ids_inv .= $result['Ids'] . ",";
            unset($oCon);
        }

        $ids_inv = trim($ids_inv, ",");
    } else {
        $ids_inv = '0';
    }

    $condicion .= " AND R.Id_Origen=$idTipo AND R.Tipo_Origen='Punto_Dispensacion'";
    $condicion3 .= " AND AI.Id_Origen_Destino=$idTipo AND AI.Origen_Destino='Punto'";
    $condicion2 .= " AND AR.Id_Punto_Dispensacion=$idTipo";
    // $condicion4 .= " AND INF.Bodega=''";
    $condicion4 .= " AND INF.Id_Punto_Dispensacion= ''";
    $condicion5 .= " AND Id_Punto_Dispensacion=$idTipo";
    $ruta = 'actarecepcionremisionver';
    $tabla = 'Acta_Recepcion_Remision';
    $tablaDest = 'Punto_Dispensacion';
    $attrFecha = 'Fecha';

    $documento = '';
    $group = '';

    $tablaDestACT = 'Punto_Dispensacion';
    $condicion2Acta .= " AND AR.Id_Punto_Dispensacion=$idTipo";

    //10-08-2021 roberth morales
    $documento .= ' (SELECT INF.Id_Inventario_Fisico_Punto_Nuevo AS ID,
                    "" AS Nombre_Origen,
                    (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino,
                    "inventariofisico/inventario_final_pdf.php" AS Ruta,
                    "Inventario" AS Tipo,
                    CONCAT("INVF",INF.Id_Inventario_Fisico_Punto_Nuevo) AS Codigo,
                    INF.Fecha AS Fecha,
                    IFNULL(SUM(PIF.Segundo_Conteo), PIF.Cantidad_Auditada) AS Cantidad,
                    PIF.Lote AS Lote,
                    PIF.Fecha_Vencimiento AS Fecha_Vencimiento,
                    "" AS Id_Factura,
                    "" AS Codigo_Fact FROM Producto_Doc_Inventario_Fisico_Punto PIF
                    INNER JOIN Doc_Inventario_Fisico_Punto DIF ON PIF.Id_Doc_Inventario_Fisico_Punto = DIF.Id_Doc_Inventario_Fisico_Punto
                    INNER JOIN Inventario_Fisico_Punto_Nuevo INF ON DIF.Id_Inventario_Fisico_Punto_Nuevo = INF.Id_Inventario_Fisico_Punto_Nuevo';

    $group .= 'GROUP BY PIF.Id_Doc_Inventario_Fisico_Punto, PIF.Lote, PIF.Fecha_Vencimiento';
    //10-08-2021 roberth morales

    //(SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo=INF.Id_Bodega_Nuevo) AS Destino,
    // $condicion2Acta .= " AND AR.Id_Bodega_Nuevo=$idTipo";
    // $query_dispensaciones - $sql_acta_recepcion_bodegas

    $origen_acta_recepcion = getOrigenActa('Acta_Recepcion');

    $sql_acta_recepcion_bodegas =
        "SELECT AR.Id_Acta_Recepcion as ID, $origen_acta_recepcion as Nombre_Origen, (SELECT Nombre FROM $tablaDest WHERE Id_$tablaDest = $idTipo) as Destino, 'actarecepcionvernuevo' as Ruta, 'Entrada' as Tipo, AR.Codigo, AC.Fecha as Fecha, PAR.Cantidad, PAR.Lote, PAR.Fecha_Vencimiento, '' as Id_Factura, '' as Codigo_Fact
    FROM Producto_Acta_Recepcion PAR
    INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
    INNER JOIN Actividad_Orden_Compra AC ON PAR.Id_Acta_Recepcion = AC.Id_Acta_Recepcion_Compra 
    WHERE PAR.Id_Producto = $producto$condicion2 AND AC.Estado = 'Acomodada' AND (AC.Fecha BETWEEN  '$fecha_inicio' AND '$fecha_fin 23:59:59')";


$query_invents = 
"SELECT INF.Id_Inventario_Fisico_Punto_Nuevo AS ID, ' ' AS Nombre_Origen,
(SELECT Nombre FROM Punto_Dispensacion
            WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino,
            'inventario_fisico_puntos/descarga_pdf.php' AS Ruta,
            'Inventario' AS Tipo,
CONCAT('INVF',INF.Id_Inventario_Fisico_Punto_Nuevo) AS Codigo,
INF.Fecha AS Fecha,
IFNULL(SUM(PIF.Segundo_Conteo), PIF.Cantidad_Auditada) AS Cantidad,
PIF.Lote AS Lote,
PIF.Fecha_Vencimiento AS Fecha_Vencimiento,
'' AS Id_Factura,
'' AS Codigo_Fact

FROM Producto_Doc_Inventario_Fisico_Punto PIF
                INNER JOIN Doc_Inventario_Fisico_Punto DIF ON PIF.Id_Doc_Inventario_Fisico_Punto = DIF.Id_Doc_Inventario_Fisico_Punto
                INNER JOIN Inventario_Fisico_Punto_Nuevo INF ON DIF.Id_Inventario_Fisico_Punto_Nuevo = INF.Id_Inventario_Fisico_Punto_Nuevo

WHERE PIF.Id_Producto =  $producto
    AND INF.Id_Punto_Dispensacion = $idTipo
    AND INF.Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'
    GROUP BY PIF.Id_Producto, PIF.Lote, PIF.Fecha_Vencimiento, INF.Fecha";

$query_invents2 = 
    "SELECT
            INF.Id_Inventario_Fisico_Punto_Nuevo AS ID,
            '' AS Nombre_Origen,
              (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Destino,
              'inventario_fisico_puntos/descarga_pdf.php' AS Ruta,
               'Inventario' AS Tipo,
               CONCAT('INVF',INF.Id_Inventario_Fisico_Punto_Nuevo) AS Codigo,
              INF.Fecha AS Fecha,
              0 AS Cantidad,
              '' AS Lote,
              '' AS Fecha_Vencimiento,
              '' AS Id_Factura,
              '' AS Codigo_Fact
              FROM Inventario_Fisico_Punto_Nuevo INF
              WHERE INF.Id_Inventario_Fisico_Punto_Nuevo NOT IN ($ids_inv)
                              AND INF.Id_Punto_Dispensacion =  $idTipo
                 AND INF.Fecha BETWEEN '$fecha1 00:00:00' AND '$fecha2 23:59:59'
                 GROUP BY INF.Fecha";


$query_dis = 
    "SELECT   D.Id_Dispensacion AS ID,
                (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = E.Id_Punto_Dispensacion) AS Nombre_Origen,
                (SELECT CONCAT(Primer_Nombre,' ', Primer_Apellido,' (',Id_Paciente,') ') FROM Paciente WHERE Id_Paciente = D.Numero_Documento) AS Destino,
                'dispensacion' AS Ruta,
                'Salida' AS Tipo,
                IF(D.Estado_Dispensacion='Anulada', CONCAT(D.Codigo, ' (Anulada)'), D.Codigo )AS Codigo,
                IFNULL(
                    (SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion = PD.Id_Producto_Dispensacion LIMIT 1),
                        IFNULL((SELECT PD2.Fecha_Carga FROM Producto_Dispensacion PD2 WHERE PD2.Id_Producto_Dispensacion = PD.Id_Producto_Dispensacion LIMIT 1),
                    D.Fecha_Actual)) AS Fecha,
                PD.Cantidad_Entregada AS Cantidad,
                PD.Lote,
                '' AS Fecha_Vencimiento,
                '' AS Id_Factura,
                '' AS Codigo_Fact
                FROM
                Producto_Dispensacion PD
                INNER JOIN
                Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion
                INNER JOIN
                Inventario_Nuevo I ON PD.Id_Inventario_Nuevo = I.Id_Inventario_Nuevo
                LEFT JOIN Estiba E on E.Id_Estiba=I.Id_Estiba
                WHERE
                    PD.Id_Producto =  $producto
                    AND  PD.Cantidad_Entregada!=0
                    AND E.Id_Punto_Dispensacion = $idTipo
                    HAVING Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";

 $query_dis_anuladas = " SELECT D.Id_Dispensacion AS ID,
            ('') AS Nombre_Origen,
            (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = E.Id_Punto_Dispensacion ) AS Destino,
            'dispensacion' AS Ruta,
            'Entrada' AS Tipo,
            CONCAT(D.Codigo, ' (Anulada)') as Codigo,
            AD.Fecha,
            PD.Cantidad_Entregada AS Cantidad,
            PD.Lote,
            '' AS Fecha_Vencimiento,
            '' AS Id_Factura,
            '' AS Codigo_Fact
            FROM
            Producto_Dispensacion PD
            INNER JOIN Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion
            INNER JOIN Inventario_Nuevo I ON PD.Id_Inventario_Nuevo = I.Id_Inventario_Nuevo
            INNER JOIN Actividades_Dispensacion AD ON AD.Id_Dispensacion = D.Id_Dispensacion and AD.Estado = 'Anulada'
            INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
            WHERE PD.Id_Producto = $producto
                AND PD.Cantidad_Entregada!=0
                AND E.Id_Punto_Dispensacion = $idTipo
                AND   D.Estado_Dispensacion='Anulada'
                HAVING Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
    //

    $query_dispensaciones .=
        "UNION ALL($query_invents)
        UNION($query_invents2)
        UNION ALL ($query_dis)
        UNION ALL ($query_dis_anuladas)";
}

$condicion .= " AND R.Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
$condicion2 .= " AND AR.$attrFecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";

// 12-08-2021 roberth
$tipoOrigen = " AND DI.Id_Origen = $idTipo";
$ultimo_dia_mes = date("Y-m", (mktime(0, 0, 0, date("m", strtotime($fecha_inicio)), 1, date("Y", strtotime($fecha_inicio))) - 1));
$fechaConsulta = explode("-", $ultimo_dia_mes);
$ano = $fechaConsulta[0];
$mes = $fechaConsulta[1];
$fechaConsulta = " AND YEAR(IV.Fecha_Documento) = $ano AND MONTH(IV.Fecha_Documento) = $mes";

$query_inicial = "SELECT SUM(Cantidad) as Total
                    FROM Inventario_Valorizado IV
                    INNER JOIN Descripcion_Inventario_Valorizado DI ON IV.Id_Inventario_Valorizado = DI.Id_Inventario_Valorizado
                    WHERE DI.Id_Producto =  $producto $fechaConsulta $tipoOrigen  GROUP BY DI.Id_Producto";

// $query_inicial = 'SELECT SUM(Cantidad) as Total
// FROM Saldo_Inicial_Kardex
// WHERE Id_Producto = '.$producto.' AND Fecha="'.$ultimo_dia_mes.'" '.$condicion5.' GROUP BY Id_Producto';

// 12-08-2021 roberth

$oCon = new consulta();
$oCon->setQuery($query_inicial);
$res = $oCon->getData();

unset($oCon);

$acum = $total = (INT) $res["Total"];
$query_remisiones = "SELECT R.Id_Remision as ID,
        R.Nombre_Origen,
        (CASE
            WHEN R.Tipo='Cliente' THEN CONCAT(R.Id_Destino,' - ',R.Nombre_Destino)
            WHEN R.Tipo='Interna' THEN R.Nombre_Destino
        END) as Destino,
        'remision' as Ruta,
        'Salida' as Tipo,
        CONCAT(R.Codigo,' - (', R.Estado,')') AS Codigo,
        R.Fecha as Fecha,
        PR.Cantidad,
        PR.Lote,
        PR.Fecha_Vencimiento,
        F.Id_Factura_Venta as Id_Factura,
        F.Codigo as Codigo_Fact

        FROM Producto_Remision PR
        INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision
        LEFT JOIN Factura_Venta F ON F.Id_Factura_Venta = R.Id_Factura
        WHERE R.Estado = 'Anulada' AND PR.Id_Producto = $producto $condicion";

$query = '(' . $query_remisiones . ')
UNION ALL (
        SELECT R.Id_Remision as ID,
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
        "remision" as Ruta,
        (
            CASE R.Estado
                WHEN "Anulada" THEN "Entrada"
                ELSE
                    "Salida"
            END
        ) as Tipo,
        CONCAT(R.Codigo," - (", R.Estado,")") AS Codigo,
        (
            CASE R.Estado
                WHEN "Anulada" THEN (SELECT MAX(Fecha) FROM Actividad_Remision WHERE Id_Remision = R.Id_Remision)
                ELSE
                    R.Fecha
            END
        ) as Fecha,
        PR.Cantidad,
        PR.Lote,
        PR.Fecha_Vencimiento,
        F.Id_Factura_Venta as Id_Factura,
        F.Codigo as Codigo_Fact
        FROM Producto_Remision PR
        INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision
        LEFT JOIN Factura_Venta F ON F.Id_Factura_Venta = R.Id_Factura
        WHERE PR.Id_Producto = ' . $producto . $condicion . ')



UNION ALL (
    SELECT AI.Id_Ajuste_Individual as ID,
    IF(AI.Tipo="Entrada","",(SELECT Nombre FROM ' . $tablaDest . ' WHERE Id_' . $tablaDest . '=' . $idTipo . ')) AS Nombre_Origen,
    IF(AI.Tipo="Entrada",(SELECT Nombre FROM ' . $tablaDest . ' WHERE Id_' . $tablaDest . '=' . $idTipo . '),"") as Destino,
    "ajustesinventariover" as Ruta,
    AI.Tipo,
    CONCAT(AI.Codigo," (Anulada)") AS Codigo,
    IFNULL(AC.Fecha_Creacion, AI.Fecha) as Fecha,
    PAI.Cantidad,
    PAI.Lote,
    PAI.Fecha_Vencimiento,
    "" as Id_Factura,
    "" as Codigo_Fact
    FROM Producto_Ajuste_Individual PAI
    INNER JOIN Ajuste_Individual AI ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
    INNER JOIN Actividad_Ajuste_Individual AC on AC.Id_Ajuste_Individual = AI.Id_Ajuste_Individual and (AC.Estado ="Acomodada" or AC.Estado ="Aprobacion" )
    WHERE AI.Estado = "Anulada" AND PAI.Id_Producto = ' . $producto . $condicion3 . ' AND (AI.Fecha BETWEEN  "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59")
    Group By PAI.Id_Producto_Ajuste_Individual, AC.Id_Actividad_Ajuste_Individual
    )



UNION ALL (
        SELECT AI.Id_Ajuste_Individual as ID,
        (
            CASE AI.Estado
                WHEN "Anulada" THEN IF(AI.Tipo="Entrada",(SELECT Nombre FROM ' . $tablaDest . ' WHERE Id_' . $tablaDest . '=' . $idTipo . '),"")
                ELSE
                    IF(AI.Tipo="Entrada","",(SELECT Nombre FROM ' . $tablaDest . ' WHERE Id_' . $tablaDest . '=' . $idTipo . '))
            END
        ) AS Nombre_Origen,
        (
            CASE AI.Estado
                WHEN "Anulada" THEN IF(AI.Tipo="Entrada","",(SELECT Nombre FROM ' . $tablaDest . ' WHERE Id_' . $tablaDest . '=' . $idTipo . '))
                ELSE
                    IF(AI.Tipo="Entrada",(SELECT Nombre FROM ' . $tablaDest . ' WHERE Id_' . $tablaDest . '=' . $idTipo . '),"")
            END
        ) as Destino,
        "ajustesinventariover" as Ruta,
        (
            CASE AI.Estado
                WHEN "Anulada" THEN IF(AI.Tipo="Entrada","Salida","Entrada")
                ELSE
                    AI.Tipo
            END
        ) AS Tipo, AI.Codigo,
        AC.Fecha_Creacion as Fecha, PAI.Cantidad, PAI.Lote, PAI.Fecha_Vencimiento, "" as Id_Factura, "" as Codigo_Fact
        FROM Producto_Ajuste_Individual PAI
        INNER JOIN Ajuste_Individual AI
        ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
        
        INNER JOIN Actividad_Ajuste_Individual AC on AC.Id_Ajuste_Individual = AI.Id_Ajuste_Individual -- and (AC.Estado ="Acomodada" or AC.Estado ="Aprobacion" )
        WHERE PAI.Id_Producto = ' . $producto . $condicion3 . ' AND (AI.Fecha BETWEEN  "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59")
        AND( (AI.Tipo="Entrada" and AC.Estado ="Acomodada" )
        OR (AI.Origen_Destino = "Bodega" AND AI.Tipo="Salida" and AC.Estado ="Aprobacion" )
        OR (AI.Origen_Destino = "Punto" AND AI.Tipo="Salida" and AC.Estado ="Creacion" )
        )
        Group By PAI.Id_Producto_Ajuste_Individual, AC.Id_Actividad_Ajuste_Individual)


UNION ALL (
        SELECT AR.Id_' . $tabla . ' as ID, ' . getOrigenActa($tabla) . ' as Nombre_Origen,
        (SELECT Nombre FROM ' . $tablaDestACT . ' WHERE Id_' . $tablaDestACT . '=' . $idTipo . ') as Destino,
        "' . $ruta . '" as Ruta,
        "Entrada" as Tipo,
        AR.Codigo, AR.' . $attrFecha . ' as Fecha,
        PAR.Cantidad,
        PAR.Lote,
        PAR.Fecha_Vencimiento,
        "" as Id_Factura,
        "" as Codigo_Fact
        FROM Producto_' . $tabla . ' PAR
        INNER JOIN ' . $tabla . ' AR ON PAR.Id_' . $tabla . ' = AR.Id_' . $tabla . '
        WHERE PAR.Id_Producto = ' . $producto . $condicion2 . ' AND AR.Estado = "Acomodada")


UNION ALL(
    ' . $sql_acta_recepcion_bodegas . ' )

UNION ALL 
    ' . $documento . '
    WHERE
    PIF.Id_Producto = ' . $producto . $condicion4 . '
    AND (INF.Fecha BETWEEN  "' . $fecha_inicio . ' 00:00:00"
    AND "' . $fecha_fin . ' 23:59:59")
    ' . $group . ')

    ' . $query_dispensaciones . $query_notas_creditos . $query_devoluciones_compras . $query_actas_internacionales . '
    ORDER BY Fecha ASC';

// echo ($query);exit;
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

$saldo_actual = getSaldoActualProducto($tipo);

$i = -1;
foreach ($resultados as $res) {$i++;
    if ($res["Tipo"] == 'Entrada') {
        $acum += $res["Cantidad"];
    } elseif ($res["Tipo"] == 'Salida') {
        $acum -= $res["Cantidad"];
    } elseif ($res["Tipo"] == 'Inventario') {
        $fecha_ant = date('Y-m-d', strtotime($resultados[$i - 1]['Fecha']));
        $fecha_act = date('Y-m-d', strtotime($res['Fecha']));
        // if ($resultados[$i-1]["Tipo"] != "Inventario" || ($resultados[$i-1]["Tipo"] == "Inventario" && $fecha_ant != $fecha_act)) {
        if ($resultados[$i - 1]["Tipo"] != "Inventario") {
            $acum = $res["Cantidad"];
        } else {
            $acum = $acum + $res["Cantidad"];
        }

    }
    $resultados[$i]["Saldo"] = $acum;
}

$final["Productos"] = $resultados;
$final["Inicial"] = $total;
$final["Saldo_Actual"] = $saldo_actual;

echo json_encode($final);

function getOrigenActa($tabla)
{

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

function getSaldoActualProducto($tipo_bodega)
{
    $cond_saldo_actual = '';
    global $idTipo;
    global $producto;

    if ($tipo_bodega == 'Bodega') {
        $cond_saldo_actual .= "WHERE E.Id_Bodega_Nuevo = $idTipo AND INV.Id_Producto = $producto";
    } else {
        $cond_saldo_actual .= "WHERE E.Id_Punto_Dispensacion = $idTipo AND INV.Id_Producto = $producto";
    }

    $q = "SELECT SUM(INV.Cantidad) AS Cantidad, GROUP_CONCAT(INV.Id_Inventario_Nuevo)as inventarios
    FROM Inventario_Nuevo INV
    INNER JOIN Estiba E ON INV.Id_Estiba = E.Id_Estiba
     $cond_saldo_actual";
    $oCon = new consulta();
    $oCon->setQuery($q);
    $saldo_actual = $oCon->getData();
    unset($oCon);

    return $saldo_actual;
}

function compararSaldoKardexConSaldoActual($saldo_kardex, $saldo_actual)
{
    return $saldo_kardex >= 0 && $saldo_kardex < $saldo_actual ? false : true; // Si el ultimo saldo kardex es menor (es decir, no son iguales) al saldo actual en el inventario, se retorna false.
}

function actualizarSaldoInventario($tipo_bodega, $saldo_kardex)
{
    $cond_saldo_actual = '';
    global $idTipo;
    global $producto;

    if ($tipo_bodega == 'Bodega') {
        $cond_saldo_actual .= "WHERE Id_Bodega = $idTipo AND Id_Producto = $producto";
    } else {
        $cond_saldo_actual .= "WHERE Id_Punto_Dispensacion = $idTipo AND Id_Producto = $producto";
    }

    $q = "UPDATE Inventario_Viejo SET Cantidad = $saldo_kardex $cond_saldo_actual";

    $oCon = new consulta();
    $oCon->setQuery($q);
    $oCon->createData();
    unset($oCon);

    return;
}
