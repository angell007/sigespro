<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Cartera_Proveedor.xls"');
header('Cache-Control: max-age=0');

$condicion = '';
$condicion2 = '';
$condicion3 = '';
$condicion_fechas = '';
$condicion_fechas2 = '';
$condicion_fechas3 = '';

if (isset($_REQUEST['Fechas']) && $_REQUEST['Fechas'] != "" && $_REQUEST['Fechas'] != "undefined") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['Fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['Fechas'])[1]);
	$condicion .= " AND (DATE(AR.Fecha_Creacion) BETWEEN '2019-01-01' AND '$fecha_fin')";
	$condicion2 .= " AND (DATE(FP.Fecha_Factura) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
	$condicion3 .= " AND (DATE(FP.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
	$condicion_fechas .= " AND (DATE(AR.Fecha_Creacion) BETWEEN '2019-01-01' AND '$fecha_fin')";
	$condicion_fechas2 .= " AND (DATE(FP.Fecha_Factura) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
	$condicion_fechas3 .= " AND (DATE(F.Fecha) BETWEEN '2019-01-01' AND '$fecha_fin')";
}

/** SE COLOCA ESTATICO LA FECHA 2019-01-01 YA QUE EN EL SIGESPRO SE DEBE MOSTRAR FACTURAS DESDE ENERO. */

if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != '') {
	$condicion .= " AND AR.Id_Proveedor = $_REQUEST[proveedor]";
	$condicion2 .= " AND FP.Nit_Proveedor = $_REQUEST[proveedor]";
	$condicion3 .= " AND FP.Id_Proveedor = $_REQUEST[proveedor]";
}

$objPHPExcel = new PHPExcel;

$query = '
SELECT
r.*
FROM
(
	(SELECT AR.Id_Proveedor, 
P.Nombre 
FROM Factura_Acta_Recepcion FAR 
INNER JOIN Producto_Acta_Recepcion PAR 
ON FAR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura 
INNER JOIN Acta_Recepcion AR 
ON AR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion 
INNER JOIN Proveedor P 
ON AR.Id_Proveedor = P.Id_Proveedor
WHERE FAR.Estado  = "Pendiente" 
'.$condicion.' GROUP BY AR.Id_Proveedor) 
UNION ALL 
(
	SELECT
	FP.Id_Proveedor AS Id_Proveedor,
	P.Nombre
	FROM
	Devolucion_Compra FP
	INNER JOIN Proveedor P 
	ON FP.Id_Proveedor = P.Id_Proveedor
	'.$condicion3.'
	GROUP BY FP.Id_Proveedor
)
/*UNION ALL 
(
	SELECT
	FP.Nit_Proveedor AS Id_Proveedor,
	P.Nombre
	FROM
	Facturas_Proveedor_Mantis FP
	INNER JOIN Proveedor P 
	ON FP.Nit_Proveedor = P.Id_Proveedor
	WHERE FP.Estado = "Pendiente" 
	'.$condicion2.'
	GROUP BY FP.Nit_Proveedor
)*/
) r
GROUP BY r.Id_Proveedor
';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$proveedores= $oCon->getData();
unset($oCon);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Cartera Proveedor');

$facturas_prov = [];

$j=0;
foreach($proveedores as $prov){ $j++;

	$q = "(SELECT
	FAR.Factura, FAR.Fecha_Factura, AR.Codigo AS Acta_Recepcion, (SELECT Codigo FROM Orden_Compra_Nacional WHERE Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional) AS Orden_Compra,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) as Gravado,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0) as Excento,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)  as Iva,
	
	((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) AS Total_Factura,

	(

		SELECT 
		IFNULL(SUM(MC.Debe),0)
		FROM
		Movimiento_Contable MC
		WHERE
			MC.Nit = AR.Id_Proveedor AND MC.Documento = FAR.Factura AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 272

	) AS Abono,
	
	((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) + (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) as Neto_Factura,

	(SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1) AS Rte_Fuente,

	(SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1) AS Rte_Ica
	 
	FROM
	Factura_Acta_Recepcion FAR
	INNER JOIN Acta_Recepcion AR
	ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
	INNER JOIN Proveedor P
	ON AR.Id_Proveedor = P.Id_Proveedor
	INNER JOIN Producto_Acta_Recepcion PAR
	ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
	WHERE AR.Id_Proveedor = $prov[Id_Proveedor] AND FAR.Estado = 'Pendiente'
	$condicion_fechas
	GROUP BY FAR.Id_Acta_Recepcion, FAR.Factura ORDER BY FAR.Fecha_Factura DESC)
	UNION(
		SELECT 
		F.Codigo,
		F.Fecha,
		'' AS Acta,
		'' AS Orden,
 		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0) AS Gravado,

		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto = 0) AS Excento,

		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo * (PAR.Impuesto / 100))),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0) AS Iva,
		((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0)) AS Total_Compra,

		
		0 AS Abono,
		
		((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo) * (PAR.Impuesto / 100)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0)) AS Neto_Factura,
					0 AS Rte_Fte,
		0 AS Rte_Ica
	FROM
		Devolucion_Compra F
	WHERE
		 F.Id_Proveedor = $prov[Id_Proveedor]
			$condicion_fechas3
	)
	/*UNION ALL (
		SELECT
		FP.Factura,
		FP.Fecha_Factura,
		'DESDE MANTIS' AS Acta_Recepcion,
		'DESDE MANTIS' AS Orden_Compra,
		0 AS Gravado,
		0 AS Excento,
		0 AS Iva,
		FP.Saldo AS Total_Factura,
		FP.Saldo AS Neto_Factura
		FROM
		Facturas_Proveedor_Mantis FP
		INNER JOIN Proveedor P 
		ON FP.Nit_Proveedor = P.Id_Proveedor
		WHERE FP.Estado = 'Pendiente' AND FP.Nit_Proveedor =  $prov[Id_Proveedor]
		$condicion_fechas2
	)*/";


	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($q);
	$facturas_prov= $oCon->getData();
	unset($oCon);

	$objSheet->getCell('A'.$j)->setValue($prov["Id_Proveedor"]);
	$objSheet->getCell('B'.$j)->setValue($prov["Nombre"]);
	$j++;

	$objSheet->getCell('B'.$j)->setValue('Factura');
	$objSheet->getCell('C'.$j)->setValue('Fecha Factura');
	$objSheet->getCell('D'.$j)->setValue('Acta Recepción');
	$objSheet->getCell('E'.$j)->setValue('Orden Compra');
	$objSheet->getCell('F'.$j)->setValue('Gravado');
	$objSheet->getCell('G'.$j)->setValue('Excento');
	$objSheet->getCell('H'.$j)->setValue('Iva');
	$objSheet->getCell('I'.$j)->setValue('Rte Fuente 2.5%');
	$objSheet->getCell('J'.$j)->setValue('Rte Ica 0.5%');
	$objSheet->getCell('K'.$j)->setValue('Total Factura');
	$objSheet->getCell('L'.$j)->setValue('Abono');
	$objSheet->getCell('M'.$j)->setValue('Neto Factura');
	
	$objSheet->getStyle('A'.$j.':M'.$j)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A'.$j.':M'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A'.$j.':M'.$j)->getFont()->setBold(true);
	$objSheet->getStyle('A'.$j.':M'.$j)->getFont()->getColor()->setARGB('FFFFFFFF');


	foreach ($facturas_prov as $value) {$j++;
		$total_factura = $value["Total_Factura"]+$value["Iva"]-$value["Rte_Fuente"]-$value["Rte_Ica"];
		$neto = $total_factura - $value["Abono"];
		$objSheet->getCell('B'.$j)->setValue($value["Factura"]);
		$objSheet->getCell('C'.$j)->setValue(fecha($value["Fecha_Factura"]));
		$objSheet->getCell('D'.$j)->setValue($value["Acta_Recepcion"]);
		$objSheet->getCell('E'.$j)->setValue($value["Orden_Compra"]);
		$objSheet->getCell('F'.$j)->setValue($value["Gravado"]);
		$objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('G'.$j)->setValue($value["Excento"]);
		$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('H'.$j)->setValue($value["Iva"]);
		$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('I'.$j)->setValue($value["Rte_Fuente"]);
		$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('J'.$j)->setValue($value["Rte_Ica"]);
		$objSheet->getStyle('J'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('K'.$j)->setValue($total_factura);
		$objSheet->getStyle('K'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('L'.$j)->setValue($value["Abono"]);
		$objSheet->getStyle('L'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getCell('M'.$j)->setValue($neto);
		$objSheet->getStyle('M'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	}
	
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function fecha($date) {
	return date('d/m/Y', strtotime($date));
}

?>