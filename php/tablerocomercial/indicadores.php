<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT SUM(Rep.Total_Facturas) AS Total_Facturas,
SUM(Rep.Factura_Medicamentos) AS Factura_Medicamento,
SUM(Rep.Factura_Material) AS Factura_Material,
SUM(Rep.Total_Ventas) AS Total_Ventas,
SUM(Rep.Total_Materiales) AS Total_Materiales,
SUM(Rep.Total_Medicamentos) AS Total_Medicamentos

FROM(
	SELECT
	COUNT(DISTINCT FV.Id_Factura_Venta) AS Total_Facturas,
	PFV.Id_Producto_Factura_Venta, 
	FV.Codigo,
	(IFNULL(SUM(PFV.Subtotal), 0))-(Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0)) AS  Total_Ventas,
	IF(S.Id_Subcategoria, (IFNULL(SUM(PFV.Subtotal), 0))-(Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0)), null  ) AS  Total_Materiales,
	IF(S2.Id_Subcategoria,(IFNULL(SUM(PFV.Subtotal), 0))-(Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0)) , null ) AS  Total_Medicamentos,
	IF(S.Id_Subcategoria, COUNT(DISTINCT FV.Id_Factura_Venta ) , null ) AS Factura_Material,
	IF(S2.Id_Subcategoria, COUNT(DISTINCT FV.Id_Factura_Venta ) , null )AS Factura_Medicamentos,
	DATE_FORMAT(FV.Fecha_Documento, '%Y-%m') AS Tiempo,
	If(S.Id_Subcategoria, 'Material', 'Medicamentos')AS Tipo,
	Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0) AS Notas
	FROM Producto_Factura_Venta PFV
	INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta
	INNER JOIN Producto P ON P.Id_Producto =  PFV.Id_Producto  
	LEFT JOIN Subcategoria S ON S.Id_Subcategoria =  P.Id_Subcategoria AND S.Id_Categoria_Nueva IN (2,3)
	LEFT JOIN Subcategoria S2 ON S2.Id_Subcategoria =  P.Id_Subcategoria AND S2.Id_Categoria_Nueva Not IN (2,3)		
	LEFT JOIN (
		SELECT SUM(PNC.Subtotal) AS Nota, NC.Id_Factura, PNC.Id_Producto,
		PNC.Lote
		FROM Producto_Nota_Credito PNC
		INNER JOIN Nota_Credito NC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito 
		GROUP BY NC.Id_Factura, PNC.Id_Producto, PNC.Lote
	)NC ON NC.Id_Factura = FV.Id_Factura_Venta and PFV.Id_Producto = NC.Id_Producto AND PFV.Lote= NC.Lote
	
	LEFT JOIN (
		SELECT SUM(PNG.Precio_Nota_Credito*PNG.Cantidad) AS Nota, NG.Id_Factura, PNG.Id_Producto as Id_Producto_Factura_Venta
		FROM Producto_Nota_Credito_Global PNG
		INNER JOIN Nota_Credito_Global NG ON NG.Id_Nota_Credito_Global = PNG.Id_Nota_Credito_Global
		WHERE NG.Tipo_Factura='Factura_Venta'
		GROUP BY NG.Id_Factura, Id_Producto_Factura_Venta
	)NG ON NG.Id_Factura = FV.Id_Factura_Venta and NG.Id_Producto_Factura_Venta = PFV.Id_Producto_Factura_Venta
         			
	WHERE DATE_FORMAT(FV.Fecha_Documento, '%Y-%m')=DATE_FORMAT(NOW(), '%Y-%m')
	GROUP BY DATE_FORMAT(FV.Fecha_Documento, '%Y-%m'), Tipo, PFV.Id_Producto, FV.Id_Factura_Venta
	) Rep";

$oCon = new consulta();
$oCon->setQuery($query);
$indicadores = $oCon->getData();
unset($oCon);

$resultado = [
    "Indicadores" => $indicadores
];

echo json_encode($resultado);

