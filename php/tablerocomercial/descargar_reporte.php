<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$tipo = isset($_REQUEST['Tipo']) ? $_REQUEST['Tipo'] : false;

$condicion = '';

if (isset($_REQUEST['Fechas']) && $_REQUEST['Fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['Fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['Fechas'])[1]);
	$condicion .= ' AND DATE(FV.Fecha_Documento) BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}


if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {
	$condicion .= " AND FV.Id_Cliente=$_REQUEST[cliente]";
}


require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

$objPHPExcel = new PHPExcel;


$query = getQuery($tipo, $condicion);

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);



$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Facturaci贸n');

switch ($tipo) {
	case 'Productos':
			$col="A";
			$objSheet->getCell($col.'1')->setValue("Factura");$col++;
			$objSheet->getCell($col.'1')->setValue("Fecha Factura");$col++;
			$objSheet->getCell($col.'1')->setValue("Vendedor");$col++;
			$objSheet->getCell($col.'1')->setValue("NIT Cliente");$col++;
			$objSheet->getCell($col.'1')->setValue("Nombre Cliente");$col++;
			$objSheet->getCell($col.'1')->setValue("CUM");$col++;
			$objSheet->getCell($col.'1')->setValue("Producto");$col++;
			$objSheet->getCell($col.'1')->setValue("Laboratorio Comercial");$col++;
			$objSheet->getCell($col.'1')->setValue("Bodega");$col++;
			$objSheet->getCell($col.'1')->setValue("Cantidad");$col++;
			$objSheet->getCell($col.'1')->setValue("Costo");$col++;
			$objSheet->getCell($col.'1')->setValue("Precio Venta");$col++;
			$objSheet->getCell($col.'1')->setValue("IVA");$col++;
			$objSheet->getCell($col.'1')->setValue("Descuento");$col++;
			$objSheet->getCell($col.'1')->setValue("Subtotal");$col++;
			$objSheet->getCell($col.'1')->setValue("Nota Credito");$col++;
			$objSheet->getCell($col.'1')->setValue("Neto Factura");$col++;
			$objSheet->getCell($col.'1')->setValue("Subcategoria");$col++;
			$objSheet->getCell($col.'1')->setValue("Categoria Nueva");$col++;
			$objSheet->getCell($col.'1')->setValue("Lote");$col++;
			$objSheet->getCell($col.'1')->setValue("Costo Lote");
			
				
			
			$objSheet->getStyle('A1:'.$col.'1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objSheet->getStyle('A1:'.$col.'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
			$objSheet->getStyle('A1:'.$col.'1')->getFont()->setBold(true);
			$objSheet->getStyle('A1:'.$col.'1')->getFont()->getColor()->setARGB('FFFFFFFF');
			
			
			$j=1;
			$cod='';
			
			foreach($productos as $disp){ $j++;
				$col="A";
				$neto_factura = ($disp["Subtotal"] + $disp['IVA']- $disp['Nota_Credito']);
				$objSheet->getCell($col.$j)->setValue($disp["Factura"]);$col++;
				$objSheet->getColumnDimension($col)->setAutoSize(true);


				$objSheet->getCell($col.$j)->setValue(fecha($disp["Fecha_Factura"]));
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Zona_Comercial"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["NIT_Cliente"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Nombre_Cliente"]);
						$objSheet->getColumnDimension($col)->setWidth(100);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Codigo_Cum"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Nombre_Producto"]);
						$objSheet->getColumnDimension($col)->setWidth(100);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Laboratorio_Comercial"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Bodega"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Cantidad"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Costo"]);
					$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("#,##0.00");

						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Precio_Venta"]); //$col++;
					$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("#,##0.00");
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["IVA"]);//$col++;
					$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("#,##0.00");
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Descuento"]);
					$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("0%");
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Subtotal"]);//$col++;
					$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("#,##0.00");
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Nota_Credito"]);//$col++;
					$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("#,##0.00");
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($neto_factura);//$col++;
					$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("#,##0.00");
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Nombre_Subcategoria"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Nombre_Categoria_Nueva"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;

				$objSheet->getCell($col.$j)->setValue($disp["Lote"]);
						$objSheet->getColumnDimension($col)->setAutoSize(true);$col++;
				
				$objSheet->getCell($col.$j)->setValue($disp["Costo_Lote"]);
						$objSheet->getStyle($col.$j)->getNumberFormat()->setFormatCode("#,##0.00");
							$objSheet->getColumnDimension($col)->setAutoSize(true);
	

				
			}
			
			$objSheet->getColumnDimension('M')->setAutoSize(true);
			$objSheet->getStyle('A1:'.$col.$j)->getAlignment()->setWrapText(true);
		break;
	
	case 'Facturas':
			$objSheet->getCell('A1')->setValue("Factura");
			$objSheet->getCell('B1')->setValue("Fecha Factura");
			$objSheet->getCell('C1')->setValue("NIT Cliente");
			$objSheet->getCell('D1')->setValue("Nombre Cliente");
			$objSheet->getCell('E1')->setValue("Zona Comercial");
			$objSheet->getCell('F1')->setValue("Gravada");
			$objSheet->getCell('G1')->setValue("Excluida");
			$objSheet->getCell('H1')->setValue("IVA");
			$objSheet->getCell('I1')->setValue("Total Neto");
			$objSheet->getCell('J1')->setValue("Estado");

			$objSheet->getStyle('A1:J1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objSheet->getStyle('A1:J1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
			$objSheet->getStyle('A1:J1')->getFont()->setBold(true);
			$objSheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFFFFFF');

			$j=1;
			$cod='';
			
			foreach($productos as $disp){ $j++;

				$neto = $disp["Gravada"] + $disp["Excenta"] + $disp["Iva"]-$disp['Nota_Credito'];
				
				$objSheet->getCell('A'.$j)->setValue($disp["Factura"]);
				$objSheet->getCell('B'.$j)->setValue(fecha($disp["Fecha_Factura"]));
				$objSheet->getCell('C'.$j)->setValue($disp["NIT_Cliente"]);
				$objSheet->getCell('D'.$j)->setValue($disp["Nombre_Cliente"]);
				$objSheet->getCell('E'.$j)->setValue($disp["Zona_Comercial"]);
				$objSheet->getCell('F'.$j)->setValue($disp["Gravada"]);
				$objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
				$objSheet->getCell('G'.$j)->setValue($disp["Excenta"]);
				$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
				$objSheet->getCell('H'.$j)->setValue($disp["Iva"]);
				$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
				$objSheet->getCell('I'.$j)->setValue($neto);
				$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
				$objSheet->getCell('J'.$j)->setValue($disp["Estado"]);
				
			}
			
			$objSheet->getColumnDimension('J')->setAutoSize(true);
			$objSheet->getStyle('A1:J'.$j)->getAlignment()->setWrapText(true);
		break;
}


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Facturacion.xls"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function getQuery($tipo, $condicion) {
	$query = '';
	switch ($tipo) {
		case 'Productos':
			header("content-type:application/json");
			$query ="SELECT
			SU.Nombre AS Nombre_Subcategoria,
			CN.Nombre AS Nombre_Categoria_Nueva, 
			FV.Codigo AS Factura,
			FV.Fecha_Documento AS Fecha_Factura, 
			FV.Id_Cliente AS NIT_Cliente, 
			C.Nombre AS Nombre_Cliente,
			Z.Nombre AS Zona_Comercial, 
			P.Nombre_Comercial AS Nombre_Producto, 
			CONCAT_WS(' ',P.Principio_Activo, P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida) AS Nombre_Generico_Producto, 
			P.Laboratorio_Generico,
			P.Laboratorio_Comercial, 
			P.Codigo_Cum,
			Group_concat(DISTINCT R.Codigo) AS Rem,
			PR.Id_Producto,
			Group_concat(DISTINCT R.Id_Remision) AS Id_Remision,
			PR.Costo,
			PFV.Precio_Venta, 
			PFV.Cantidad,
			SUM(PR.Cantidad) as Cantidad_Remision,
			PFV.Subtotal, 
			
			ROUND(IFNULL(NG.PrecNota,0)  + IFNULL(NC.PrecNota, 0) , 2) AS Nota_Credito,
			ROUND( (PFV.Subtotal) -(IFNULL(NG.PrecNota,0)  + IFNULL(NC.PrecNota, 0)) , 2)AS FacturaNeto,
			GROUP_CONCAT(distinct PR.Lote) AS Lote,
			GROUP_CONCAT(distinct PAR.Precio) AS Costo_Lote,			
			
			(PR.Descuento/100) as Descuento,
			Round((PFV.Cantidad*PFV.Precio_Venta)*(PFV.Impuesto)/100, 2) AS IVA,  
			CONCAT_WS(' ', F.Nombres, F.Apellidos)  AS Vendedor,
			 R.Nombre_Origen AS Bodega, 
			P.Laboratorio_Comercial
			FROM Producto_Factura_Venta PFV
			INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta
			INNER JOIN Producto_Remision PR ON PR.Id_Producto_Factura_Venta= PFV.Id_Producto_Factura_Venta -- AND PR.Id_Remision=R.Id_Remision
			LEFT JOIN Remision R ON R.Id_Remision= PR.Id_Remision
			INNER JOIN Cliente C ON C.Id_Cliente = FV.Id_Cliente
			LEFT JOIN Zona Z ON Z.Id_Zona = C.Id_Zona
			INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
			LEFT JOIN Subcategoria SU ON P.Id_Subcategoria = SU.Id_Subcategoria
			LEFT JOIN Categoria_Nueva CN ON SU.Id_Categoria_Nueva = CN.Id_Categoria_Nueva
			LEFT JOIN Funcionario F ON F.Identificacion_Funcionario=FV.Id_Funcionario
			
			LEFT JOIN (
					SELECT * FROM 
							((
								SELECT
								PAR.Id_Producto, 
								ROUND( PAN.Precio_Unitario_Pesos, 2) AS Precio,
								PAR.Lote
								
								FROM Producto_Acta_Recepcion_Internacional PAR
								INNER JOIN Producto_Nacionalizacion_Parcial PAN ON PAN.Id_Producto_Acta_Recepcion_Internacional = PAR.Id_Producto_Acta_Recepcion_Internacional
								WHERE PAR.Id_Producto_Acta_Recepcion_Internacional IN (SELECT MAX(PAR.Id_Producto_Acta_Recepcion_Internacional) FROM Producto_Acta_Recepcion_Internacional PAR GROUP BY PAR.Lote, PAR.Id_Producto )
							)	UNION ALL 
							(	SELECT
												PAR.Id_Producto, 
												PAR.Precio,
												PAR.Lote
												
												FROM Producto_Acta_Recepcion PAR
												WHERE PAR.Id_Producto_Acta_Recepcion IN (SELECT MAX(PAR.Id_Producto_Acta_Recepcion) FROM Producto_Acta_Recepcion PAR GROUP BY PAR.Lote, PAR.Id_Producto )
							))P
							GROUP BY Lote, Id_Producto
			) PAR ON PAR.Lote = PR.Lote AND PAR.Id_Producto = PR.Id_Producto
			
			LEFT JOIN (
				SELECT
				FV.Id_Factura_Venta,
				PFV.Id_Producto_Factura_Venta,
				SUM(PNC.Cantidad) AS CantNota,
				SUM(PNC.Valor_Nota_Credito) AS SubTotalNota, 
				Round(SUM(PNC.Cantidad * PNC.Precio_Nota_Credito*(1+(PNC.Impuesto)/100)),2) AS PrecNota
				FROM Producto_Nota_Credito_Global PNC
				LEFT JOIN Nota_Credito_Global NC ON PNC.Id_Nota_Credito_Global = NC.Id_Nota_Credito_Global AND NC.Tipo_Factura='Factura_Venta'
				INNER JOIN Factura_Venta FV ON FV.Id_Factura_Venta = NC.Id_Factura
				LEFT JOIN Producto_Factura_Venta PFV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta AND PFV.Id_Producto_Factura_Venta = PNC.Id_Producto
				WHERE PNC.Tipo_Producto='Producto_Factura_Venta'
				GROUP BY PFV.Id_Producto_Factura_Venta
			)NG ON NG.Id_Factura_Venta = FV.Id_Factura_Venta AND NG.Id_Producto_Factura_Venta = PFV.Id_Producto_Factura_Venta
			LEFT JOIN (
				SELECT 
				FV.Id_Factura_Venta, 
				PFV.Id_Producto_Factura_Venta, 
				sum(PNC.Cantidad) AS CantNota,
				sum(PNC.Subtotal) AS SubTotalNota,
				Round(SUM(PNC.Cantidad * PNC.Precio_Venta*(1+(PNC.Impuesto)/100)),2) AS PrecNota
				FROM Producto_Nota_Credito PNC
				Left JOIN Nota_Credito NC ON PNC.Id_Nota_Credito = NC.Id_Nota_Credito  
				INNER JOIN Factura_Venta FV ON  FV.Id_Factura_Venta = NC.Id_Factura
				Left JOIN Producto_Factura_Venta PFV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta AND PFV.Id_Producto = PNC.Id_Producto 
				Where PNC.Lote = PFV.Lote
				GROUP BY PFV.Id_Producto_Factura_Venta 
			)NC ON NC.Id_Factura_Venta = FV.Id_Factura_Venta AND NC.Id_Producto_Factura_Venta = PFV.Id_Producto_Factura_Venta
					
			
			WHERE FV.Estado != 'Anulada' 
			-- condicion
			$condicion
			-- end
			GROUP BY FV.Id_Factura_Venta, PFV.Id_Producto_Factura_Venta  ";
			// echo $query; exit;
			break;
		
		case 'Facturas':
			$query = 'SELECT FV.Codigo as Factura, FV.Fecha_Documento as Fecha_Factura, FV.Id_Cliente as NIT_Cliente, C.Nombre as Nombre_Cliente, Z.Nombre as Zona_Comercial,FV.Estado, 
			IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
			FROM Producto_Factura_Venta PFV INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
			WHERE PFV.Id_Factura_Venta = FV.Id_Factura_Venta AND P.Gravado="Si"),0) as Gravada, 
			IFNULL((SELECT SUM(PFV.Cantidad*PFV.Precio_Venta)
			FROM Producto_Factura_Venta PFV INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
			WHERE PFV.Id_Factura_Venta = FV.Id_Factura_Venta AND P.Gravado="No"),0) as Excenta,
			IFNULL((SELECT SUM((PFV.Cantidad*PFV.Precio_Venta)*(19/100))
			FROM Producto_Factura_Venta PFV INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
			WHERE PFV.Id_Factura_Venta = FV.Id_Factura_Venta AND P.Gravado="Si"),0) as Iva
			
			FROM Factura_Venta FV
			INNER JOIN Cliente C
			ON C.Id_Cliente = FV.Id_Cliente
			INNER JOIN Zona Z
			ON Z.Id_Zona = C.Id_Zona WHERE FV.Estado != "Anulada" ' . $condicion;
			break;
	}
	// echo $query; exit;
	return $query;
}

function fecha($fecha) {
	return date('d/m/Y', strtotime($fecha));
}

?>
