<?php
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$query = "SELECT 
F.Codigo AS Factura,
'680010399501' AS Cod_Hab,
'13' AS Cod_Concepto,
F.Q_Productos AS Cant_Servicios,
F.Total_Precio,
(
CASE C.Tipo_Valor
WHEN 'Cerrada' THEN (ROUND((F.Total_Factura - F.Cuota_Moderadora)))
ELSE
	(F.Total_Factura - F.Cuota_Moderadora)
END
) AS Neto_Factura
FROM
Radicado_Factura RF INNER JOIN (SELECT fact.Id_Factura, fact.Codigo, fact.Id_Cliente, fact.Cuota AS Cuota_Moderadora, prod_fact.Q_Productos, prod_fact.Total_Precio, prod_fact.Total_Factura FROM Factura fact INNER JOIN (SELECT Id_Factura, COUNT(Id_Producto_Factura) AS Q_Productos, SUM(Precio) AS Total_Precio, ((SUM(Subtotal)-(SUM(Cantidad*Descuento)))+(SUM(Subtotal*(Impuesto/100)))) AS Total_Factura FROM Producto_Factura GROUP BY Id_Factura) prod_fact ON fact.Id_Factura = prod_fact.Id_Factura WHERE fact.Estado_Factura != 'Anulada'  AND fact.Nota_Credito IS NULL ) F ON RF.Id_Factura = F.Id_Factura INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente WHERE RF.Id_Radicado = $id GROUP BY RF.Id_Factura";

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