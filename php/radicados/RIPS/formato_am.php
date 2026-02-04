<?php
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$query = "WITH FacturaProductos AS (
    SELECT PF.Id_Producto_Factura, PF.Id_Producto, PF.Cantidad, PF.Precio,
           ((PF.Subtotal)*(1+(PF.Impuesto/100))) AS Total,
           FT.Codigo, FT.Id_Factura, FT.Id_Cliente, FT.Id_Dispensacion
    FROM Producto_Factura PF
    INNER JOIN Factura FT ON FT.Id_Factura = PF.Id_Factura
    WHERE FT.Estado_Factura != 'Anulada' AND FT.Nota_Credito IS NULL
),
DispensacionPaciente AS (
    SELECT PD.Id_Producto, DP.Id_Dispensacion, PD.Numero_Autorizacion,
           DP.Numero_Documento AS Id_Paciente
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion DP ON DP.Id_Dispensacion = PD.Id_Dispensacion
    WHERE DP.Id_Tipo_Servicio != 7 AND DP.Estado_Facturacion = 'Facturada'
),
PacienteEps AS (
    SELECT PC.Id_Paciente, PC.Tipo_Documento,
           CASE E.Nit
               WHEN 901097473 THEN IF(PC.Id_Regimen = 1, E.Codigo_Eps, 'EPS045')
               ELSE E.Codigo_Eps
           END AS Codigo_Eps
    FROM Paciente PC
    INNER JOIN Eps E ON E.Nit = PC.Nit
)

SELECT 
    F.Codigo, 
    PCT.Codigo_Eps, 
    PCT.Tipo_Documento, 
    PCT.Id_Paciente, 
    D.Numero_Autorizacion, 
    P.Codigo_Cum, 
    '' AS Tipo_Medicamento, 
    P.Nombre_Comercial AS Nombre_Generico, 
    P.Forma_Farmaceutica, 
    P.Cantidad AS Concentracion, 
    P.Unidad_Medida, 
    F.Cantidad, 
    F.Precio,
    CASE C.Tipo_Valor
        WHEN 'Cerrada' THEN ROUND(F.Total)
        ELSE F.Total
    END AS Total
FROM Radicado_Factura RF
INNER JOIN Radicado R ON RF.Id_Radicado = R.Id_Radicado
INNER JOIN FacturaProductos F ON F.Id_Factura = RF.Id_Factura
INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
LEFT JOIN DispensacionPaciente D ON F.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN PacienteEps PCT ON PCT.Id_Paciente = D.Id_Paciente
INNER JOIN Producto P ON P.Id_Producto = F.Id_Producto
INNER JOIN Categoria CT ON CT.Id_Categoria = P.Id_Subcategoria
WHERE R.Id_Radicado = $id 
  AND CT.Nombre NOT IN ('MATERIALES','PANALES','COSMETICOS')
GROUP BY F.Id_Producto_Factura, D.Id_Dispensacion

";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$registros = $queryObj->Consultar('Multiple');

$contenido = '';

foreach ($registros['query_result'] as $i => $value) {
    $contenido .= str_replace("\t","",$value['Codigo']) . ",";
    $contenido .= "680010399501,"; // Código de Habilitación
    $contenido .= str_replace("\t","",$value['Tipo_Documento']) . ",";
    $contenido .= str_replace("\t","",$value['Id_Paciente']) . ",";
    $contenido .= str_replace("\t","",$value['Numero_Autorizacion']) . ",";
    $contenido .= str_replace("\t","",$value['Codigo_Cum']) . ",";
    $contenido .= str_replace("\t","",$value['Tipo_Medicamento']) . ",";
    $contenido .= str_replace("\t","",substr($value['Nombre_Generico'],0,30)) . ",";
    $contenido .= str_replace("\t","",substr($value['Forma_Farmaceutica'],0,20)) . ",";
    $contenido .= str_replace("\t","",$value['Concentracion']) . ",";
    $contenido .= str_replace("\t","",$value['Unidad_Medida']) . ",";
    $contenido .= str_replace("\t","",$value['Cantidad']) . ",";
    $contenido .= number_format($value['Precio'],2,".","") . ",";
    $contenido .= number_format($value['Total'],2,".","") . "\r\n";
}

echo $contenido;
?>