<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
header('Cache-Control: max-age=0');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$condicion = SetCondiciones($_REQUEST);
$query = CrearQuery();

// echo $query; exit;

ArmarReporte($query);

function ArmarReporte($query)
{

    $encabezado = GetEncabezado($query);
    $datos = GetDatos($query);
    $contenido = '';

    if ($encabezado) {
        $contenido .= '<table border="1"><tr>';
        $csv = "";
        foreach ($encabezado as $key => $value) {
            $contenido .= '<td>' . $key . '</td>';
            $csv .= "\"$key\";";
        }
        $csv .="\n";
        $contenido .= '</tr>';
    }

    if ($datos) {
        foreach ($datos as $i => $dato) {
            $contenido .= '<tr>';

            foreach ($dato as $key => $value) {

                if (ValidarKey($key)) {
                    $valor = $dato[$key] != '' ? $dato[$key] : 0;
                    $contenido .= '<td>' . number_format($valor, 2, ",", "") . '</td>';
                    $csv .= "\"$valor\";";
                } else {
                    $contenido .= '<td>' . $dato[$key] . '</td>';
                    $csv .= "\"$dato[$key]\";";
                }

            }
            
            $csv .="\n";

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
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Reporte Ventas.csv"');
    // echo $contenido;
    echo $csv;

}
function GetEncabezado($query)
{
    $oCon = new consulta();
    $oCon->setQuery($query);
    $encabezado = $oCon->getData();
    unset($oCon);

    return $encabezado;
}

function GetDatos($query)
{
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos = $oCon->getData();
    unset($oCon);
    return $datos;
}
function CrearQuery()
{
    global $condicion;

    $query = "SELECT P.Nombre_Comercial, SU.Nombre as Nombre_Subcategoria,CN.Nombre as Nombre_Categoria_Nueva,
    CONCAT_WS(' ', P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad, P.Unidad_Medida) as Producto,
     P.Codigo_Cum as Cum,
     PFV.Cantidad as Cantidad,
     PFV.Precio_Venta as PrecioVenta,
     (PFV.Cantidad*PFV.Precio_Venta) as Subtotal,
     ROUND(IFNULL(NG.PrecNota,0)  + IFNULL(NC.PrecNota, 0) , 2) AS Nota_Credito,
     ROUND(  ((PFV.Cantidad*PFV.Precio_Venta)*(1+PFV.Impuesto/100)) -(IFNULL(NG.PrecNota,0)  + IFNULL(NC.PrecNota, 0)) , 2)AS FacturaNeto,
     CONCAT(PFV.Impuesto, '%')as Impuesto, 
     F.Codigo as Factura, 'Factura' as Tipo_Factura,  'Venta' Tipo_Cliente ,IFNULL(CONCAT(FUN.Identificacion_Funcionario,' - ',FUN.Primer_Nombre,' ',FUN.Primer_Apellido),CONCAT(FUN.Identificacion_Funcionario,' - ',FUN.Nombres)) AS Funcionario , DATE(F.Fecha_Documento) as Fecha
     FROM Producto_Factura_Venta PFV
     INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
     INNER JOIN Factura_Venta F  ON PFV.Id_Factura_Venta=F.Id_Factura_Venta
     INNER JOIN Subcategoria SU ON P.Id_Subcategoria = SU.Id_Subcategoria
     INNER JOIN Categoria_Nueva CN ON SU.Id_Categoria_Nueva = CN.Id_Categoria_Nueva
     INNER JOIN Funcionario FUN ON F.Id_Funcionario = FUN.Identificacion_Funcionario
     LEFT JOIN (
                 SELECT
                 FV.Id_Factura_Venta,
                 PFV.Id_Producto,
                 SUM(PNC.Cantidad) AS CantNota,
                 SUM(PNC.Valor_Nota_Credito) AS SubTotalNota, 
                 Round(SUM(PNC.Cantidad * PNC.Precio_Nota_Credito*(1+(PNC.Impuesto)/100)),2) AS PrecNota
                 FROM Producto_Nota_Credito_Global PNC
                 LEFT JOIN Nota_Credito_Global NC ON PNC.Id_Nota_Credito_Global = NC.Id_Nota_Credito_Global AND NC.Tipo_Factura='Factura_Venta'
                 INNER JOIN Factura_Venta FV ON FV.Id_Factura_Venta = NC.Id_Factura
                 LEFT JOIN Producto_Factura_Venta PFV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta AND PFV.Id_Producto_Factura_Venta = PNC.Id_Producto
                 WHERE PNC.Tipo_Producto='Producto_Factura_Venta'
                 GROUP BY NC.Id_Nota_Credito_Global, PFV.Id_Producto
                 ORDER BY NC.Id_Nota_Credito_Global DESC
        )NG ON NG.Id_Factura_Venta = F.Id_Factura_Venta AND NG.Id_Producto = PFV.Id_Producto
        LEFT JOIN (
                SELECT 
                FV.Id_Factura_Venta, 
                PFV.Id_Producto, 
                sum(PNC.Cantidad) AS CantNota,
                sum(PNC.Subtotal) AS SubTotalNota,
                Round(SUM(PNC.Cantidad * PNC.Precio_Venta*(1+(PNC.Impuesto)/100)),2) AS PrecNota
                FROM Producto_Nota_Credito PNC
                Left JOIN Nota_Credito NC ON PNC.Id_Nota_Credito = NC.Id_Nota_Credito  
                INNER JOIN Factura_Venta FV ON  FV.Id_Factura_Venta = NC.Id_Factura
                Left JOIN Producto_Factura_Venta PFV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta AND PFV.Id_Producto = PNC.Id_Producto
                GROUP BY PNC.Id_Producto, NC.Id_Nota_Credito
        )NC ON NC.Id_Factura_Venta = F.Id_Factura_Venta AND NC.Id_Producto = PFV.Id_Producto
        WHERE F.Estado!='Anulada'  $condicion
        HAVING FacturaNeto>0
    UNION ALL (
        SELECT P.Nombre_Comercial, SU.Nombre as Nombre_Subcategoria,CN.Nombre as Nombre_Categoria_Nueva,
        CONCAT_WS(' ' ,P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad, P.Unidad_Medida) as Producto, P.Codigo_Cum as Cum,
        PF.Cantidad,
        PF.Precio as PrecioVenta,
        (PF.Cantidad*PF.Precio) as Subtotal,
        ROUND(ifnull(NG.PrecNota, 0), 2) AS Nota_Credito,
        ROUND( ((PF.Cantidad*PF.Precio)*(1+PF.Impuesto/100)) -ifnull(NG.PrecNota, 0), 2)as FacturaNeto,
        CONCAT(PF.Impuesto, '%')as Impuesto, 
        F.Codigo as Factura, F.Tipo as Tipo_Factura,  'Dispensacion' Tipo_Cliente,IFNULL(CONCAT(FUN.Identificacion_Funcionario,' - ',FUN.Primer_Nombre,' ',FUN.Primer_Apellido),CONCAT(FUN.Identificacion_Funcionario,' - ',FUN.Nombres))   AS Funcionario , DATE(F.Fecha_Documento) as Fecha
        FROM Producto_Factura PF
        INNER JOIN Factura F ON PF.Id_Factura=F.Id_Factura

        INNER JOIN Producto P ON PF.Id_Producto=P.Id_Producto
        INNER JOIN Subcategoria SU ON P.Id_Subcategoria = SU.Id_Subcategoria
        INNER JOIN Categoria_Nueva CN ON SU.Id_Categoria_Nueva = CN.Id_Categoria_Nueva
        INNER JOIN Funcionario FUN  ON F.Id_Funcionario = FUN.Identificacion_Funcionario  
        LEFT JOIN (
				SELECT
				FV.Id_Factura,
				PFV.Id_Producto,
				SUM(PNC.Cantidad) AS CantNota,
				SUM(PNC.Valor_Nota_Credito) AS SubTotalNota, 
                Round(SUM(PNC.Cantidad * PNC.Precio_Nota_Credito*(1+(PNC.Impuesto)/100)),2) AS PrecNota
				FROM Producto_Nota_Credito_Global PNC
				LEFT JOIN Nota_Credito_Global NC ON PNC.Id_Nota_Credito_Global = NC.Id_Nota_Credito_Global AND NC.Tipo_Factura='Factura'
				INNER JOIN Factura FV ON FV.Id_Factura = NC.Id_Factura
				LEFT JOIN Producto_Factura PFV ON PFV.Id_Factura = FV.Id_Factura AND PFV.Id_Producto_Factura = PNC.Id_Producto
				WHERE PNC.Tipo_Producto='Producto_Factura'
				GROUP BY NC.Id_Nota_Credito_Global, PFV.Id_Producto
				ORDER BY NC.Id_Nota_Credito_Global DESC
		)NG ON NG.Id_Factura =F.Id_Factura AND NG.Id_Producto = PF.Id_Producto
        WHERE F.Estado_Factura!='Anulada' $condicion
        HAVING FacturaNeto>0
    )  ORDER BY Nombre_Comercial ASC";

    return $query;
}

function ValidarKey($key)
{
    $datos = ["Nada", "PrecioVenta", "Subtotal", "Nota_Credito", "FacturaNeto"];
    $pos = array_search($key, $datos);
    return strval($pos);
}

function SetCondiciones($req)
{

    $condicion = '';
    $fecha_inicio = '';
    $fecha_fin = '';
    if (isset($req['fini']) && $req['fini'] != "" && $req['fini'] != "undefined") {
        $fecha_inicio = $req['fini'];

    }
    if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
        $fecha_fin = $_REQUEST['ffin'];
    }

    if ($fecha_fin != '' && $fecha_inicio != '') {
        $condicion = " AND (DATE(F.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin') ";
    }

    return $condicion;

}
