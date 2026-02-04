<?php
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$query = "SELECT 
'680010399501' AS Cod_Hab,
'PRODUCTOS HOSPITALARIOS S.A.' AS Empresa,
'NI' AS Tipo_Nit,
'804016084' AS Nit,
F.Codigo,
DATE_FORMAT(F.Fecha_Factura,'%d/%m/%Y') AS Fecha_Factura,
F.Fecha_Dis AS Fecha_Inicio,
F.Fecha_Dis AS Fecha_Fin,
F.Codigo_Eps,
F.EPS,
'' AS Contrato,
UPPER(F.Regimen) AS Regimen,
'' AS Poliza,
F.Cuota_Moderadora,
0 AS Total_Iva,
(
    CASE
    WHEN F.Id_Cliente = 890500890 THEN (ROUND(F.Total_Descuentos))
    ELSE
        IF(C.Tipo_Valor = 'Cerrada', (ROUND(F.Total_Descuentos)), F.Total_Descuentos)
    END
) AS Total_Descuentos,
(
CASE C.Tipo_Valor
WHEN 'Cerrada' THEN (ROUND((F.Total_Factura - F.Cuota_Moderadora)))
ELSE
	(F.Total_Factura - F.Cuota_Moderadora)
END
) AS Total_Factura
FROM
Radicado_Factura RF INNER JOIN (SELECT fact.Id_Factura, fact.Codigo, fact.Id_Cliente, fact.Fecha_Documento AS Fecha_Factura, fact.Cuota AS Cuota_Moderadora, prod_fact.Total_Iva, prod_fact.Total_Descuentos, prod_fact.Total_Factura, D.* FROM Factura fact INNER JOIN (SELECT Id_Factura, SUM(Subtotal*(Impuesto/100)) AS Total_Iva, SUM(Cantidad*Descuento) AS Total_Descuentos, ((SUM(Subtotal)-(SUM(Cantidad*Descuento)))+(SUM(Subtotal*(Impuesto/100)))) AS Total_Factura FROM Producto_Factura GROUP BY Id_Factura) prod_fact ON fact.Id_Factura = prod_fact.Id_Factura INNER JOIN (SELECT DIS.Id_Dispensacion, DATE_FORMAT(DIS.Fecha_Actual, '%d/%m/%Y') AS Fecha_Dis, DIS.EPS, P.* FROM Dispensacion DIS INNER JOIN (SELECT PC.Id_Paciente, IF(PC.Id_Regimen=1,'Contributivo','Subsidiado') AS Regimen, (CASE E.Nit
            WHEN 901097473 THEN IF(PC.Id_Regimen = 1, E.Codigo_Eps, 'EPS045')
            ELSE E.Codigo_Eps
        END) AS Codigo_Eps FROM Paciente PC INNER JOIN Eps E ON PC.Nit = E.Nit) P ON DIS.Numero_Documento = P.Id_Paciente WHERE DIS.Id_Tipo_Servicio != 7 AND DIS.Estado_Facturacion = 'Facturada') D ON fact.Id_Dispensacion = D.Id_Dispensacion WHERE fact.Estado_Factura != 'Anulada' AND fact.Nota_Credito IS NULL  ) F ON RF.Id_Factura = F.Id_Factura INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente WHERE RF.Id_Radicado = $id GROUP BY RF.Id_Factura";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$registros = $queryObj->Consultar('Multiple');

$contenido = '';

foreach ($registros['query_result'] as $i => $value) {
    $count = count($value);
    $i = 0;
    foreach ($value as $columna => $valor) {
        if (isNumeric($columna)) {
            $contenido .= number_format($valor,2,".","");
        } else{
            $contenido .= str_replace("\t","",$valor);
        }
        $contenido .= ($i == ($count-1)) ? "\r\n" : ","; // Separador
        $i++;
    }
}

echo $contenido;
?>