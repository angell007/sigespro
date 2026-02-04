<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$cliente = ( isset( $_REQUEST['nombre'] ) ? $_REQUEST['nombre'] : '' );



$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$final=[];
 
$fecha=date('Y');

$i=-1;
for($h=1;$h<=12; $h++){ $i++;

	$query = 'SELECT SUM(MC.Valor_Medicamento+MC.Valor_Material) as Total FROM Meta_Cliente MC INNER JOIN Meta M ON MC.Id_Meta=M.Id_Meta WHERE M.Anio='.$fecha.' AND MC.Mes='.$h; 

	$oCon= new consulta();
	$oCon->setQuery($query);
	$medicamentos = $oCon->getData();
    unset($oCon);

	$query = "SELECT
	(SUM(r.Subtotal) -SUM(r.Nota_Credito)) AS Total
	FROM
	(
		SELECT PFV.Subtotal,

		((PFV.Cantidad*PFV.Precio_Venta)*(PFV.Impuesto)/100) AS IVA,
		ROUND(IFNULL(NG.Nota,0)  + IFNULL(NC.Nota, 0) , 2) AS Nota_Credito
		FROM Producto_Factura_Venta PFV 
		INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta=FV.Id_Factura_Venta 
		INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto 

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
					


		WHERE YEAR(FV.Fecha_Documento)=$fecha
		AND MONTH(FV.Fecha_Documento)=$h  
		AND FV.Estado != 'Anulada'
		
	) r"; 

	$oCon= new consulta();
	$oCon->setQuery($query);
	$material = $oCon->getData();
    unset($oCon);

    $final[$i]["Meta"] = number_format((float)$medicamentos['Total'],0,"","");
    $final[$i]["Ventas"] = number_format((float)$material['Total'],0,"","");
    $final[$i]["Mes"] = $meses[$h-1];
}

echo json_encode($final);

