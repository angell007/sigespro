<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.utility.php');

$util = new Utility();

date_default_timezone_set("America/Bogota");

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Cartera_Cliente.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$condiciones = SetCondiciones();

$query = 'SELECT 
	r.*
	FROM
	(
		(SELECT
		C.Id_Cliente, C.Nombre
		FROM
		Factura_Venta FV
		INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente  
		WHERE 
		FV.Estado = "Pendiente" '.$condiciones["condicion"].'
		GROUP BY FV.Id_Cliente)
		UNION  (
		SELECT
		C.Id_Cliente, C.Nombre
		FROM
		Factura FV
		INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente 
		WHERE 
		FV.Estado_Factura = "Sin Cancelar" '.$condiciones["condicion"].'
		GROUP BY FV.Id_Cliente
		)

		UNION (
		SELECT
		C.Id_Cliente, C.Nombre
		FROM
		Factura_Capita FV
		INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente 
		WHERE 
		FV.Estado_Factura = "Sin Cancelar" '.$condiciones["condicion"].'
		GROUP BY FV.Id_Cliente
		)
		
		/*UNION ALL 
			(
			SELECT
			FP.Nit_Cliente AS Id_Cliente,
			P.Nombre
			FROM
			Facturas_Cliente_Mantis FP
			INNER JOIN Cliente P 
			ON FP.Nit_Cliente = P.Id_Cliente
			WHERE FP.Estado = "Pendiente" 
			'.$condicion2.'
			GROUP BY FP.Nit_Cliente
		)*/
	) r';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$clientes= $oCon->getData();
unset($oCon);


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Cartera Cliente');

$facturas_cli = [];

$j=0;
foreach($clientes as $cli){ $j++;

	$q = "(SELECT

	FV.Codigo AS Factura,
	FV.Fecha_Documento AS Fecha_Factura,

	
	/*(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) AS Gravado,*/
	
	SUM(IF(PAR.Impuesto!=0 ,(PAR.Cantidad*PAR.Precio_Venta),0 )) AS Gravado,


	/*(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto=0) AS Excento,*/

	SUM(IF(PAR.Impuesto=0 ,(PAR.Cantidad*PAR.Precio_Venta),0 )) AS Excento,
	
	0 AS Descuentos,
	
	/* (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) AS Iva, */
	
	SUM(IF(PAR.Impuesto!=0 ,( (PAR.Cantidad*PAR.Precio_Venta) * (PAR.Impuesto/100) ),0 )) AS Iva,



 /* 	((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) + 
	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto=0)) AS Total_Factura, */
	
	SUM(IFNULL( PAR.Cantidad*PAR.Precio_Venta , 0) ) AS Total_Factura,

												
	
	
	# ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	#FROM Producto_Factura_Venta PAR
	#WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	#FROM Producto_Factura_Venta PAR
	#WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
	#FROM Producto_Factura_Venta PAR
	#WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) /* - (SELECT IFNULL(SUM(PNC.Cantidad*PNC.Precio_Venta*(1+(PNC.Impuesto/100))),0) 
	#FROM Producto_Nota_Credito PNC INNER JOIN Nota_Credito NC ON PNC.Id_Nota_Credito = NC.Id_Nota_Credito WHERE NC.Id_Factura = FV.Id_Factura_Venta)
	#DEVOLUCION  */) AS Neto_Factura,
	
	SUM(IFNULL( ( PAR.Cantidad*PAR.Precio_Venta ) + ROUND( (PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100) ,2 ) , 0 ))  AS Neto_Factura,

	'Comercial' AS Tipo

	FROM
	Factura_Venta FV
	INNER JOIN Producto_Factura_Venta PAR ON PAR.Id_Factura_Venta = FV.Id_Factura_Venta
	WHERE Estado = 'Pendiente' AND FV.Id_Cliente = $cli[Id_Cliente] 
	".$condiciones['condicion_fechas']."
	)
	
	UNION (
	SELECT
	
	FV.Codigo AS Factura,
	FV.Fecha_Documento AS Fecha_Factura,

 /* 	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) AS Gravado,
	 */

	SUM(IF(PAR.Impuesto!=0 ,(PAR.Cantidad*PAR.Precio),0 )) AS Gravado,

	/*(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto=0) AS Excento,*/

	SUM(IF(PAR.Impuesto=0 ,(PAR.Cantidad*PAR.Precio),0 )) AS Excento,
	
	/*(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Descuento),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura) AS Descuentos,*/
	
	SUM(IF(PAR.Impuesto!=0 ,( PAR.Cantidad*PAR.Descuento ),0 )) AS Descuentos,
	
	/*(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio*(PAR.Impuesto/100))),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) AS Iva,*/

	SUM(IF(PAR.Impuesto!=0 ,( (PAR.Cantidad*PAR.Precio) * (PAR.Impuesto/100) ),0 )) AS Iva,
	
	/*((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto=0) - (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Descuento),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura)) AS Total_Factura,*/

	SUM(IFNULL( PAR.Cantidad*PAR.Precio , 0) ) AS Total_Factura,
	
	/*((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio*(PAR.Impuesto/100))),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) - (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Descuento),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura)) AS Neto_Factura,*/

		
	SUM(IFNULL( ( PAR.Cantidad*PAR.Precio ) + ROUND( (PAR.Cantidad*PAR.Precio)*(PAR.Impuesto/100) ,2 ) , 0 ))  AS Neto_Factura,

	'NoPos' AS Tipo
	FROM
	Factura FV
	INNER JOIN Producto_Factura PAR ON PAR.Id_Factura = FV.Id_Factura
	WHERE Estado_Factura = 'Sin Cancelar'
	
	AND FV.Id_Cliente = $cli[Id_Cliente]
	".$condiciones['condicion_fechas']."
	)
	UNION(
		SELECT 
		F.Codigo,
		F.Fecha,
	/*	(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0
		) AS Gravado,*/

	SUM(IF(PAR.Impuesto!=0 ,(PAR.Cantidad*PAR.Precio_Venta),0 )) AS Gravado,



	/*	(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto = 0
		) AS Excento,*/

		
	SUM(IF(PAR.Impuesto=0 ,(PAR.Cantidad*PAR.Precio_Venta),0 )) AS Excento,

		0 AS Descuentos,
		
	/*	(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta * (PAR.Impuesto / 100))),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
		AND PAR.Impuesto != 0) AS Iva,*/

		SUM(IF(PAR.Impuesto!=0 ,( (PAR.Cantidad*PAR.Precio_Venta) * (PAR.Impuesto/100) ),0 )) AS Iva,
		
		/*((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
		AND PAR.Impuesto != 0)) AS Total_Compra,*/
		
		SUM(IFNULL( PAR.Cantidad*PAR.Precio_Venta , 0) ) AS Total_Factura,
		
 /* 		((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta) * (PAR.Impuesto / 100)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0)
		) AS Neto_Factura, */
		

		SUM(IFNULL( ( PAR.Cantidad*PAR.Precio_Venta ) + ROUND( (PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100) ,2 ) , 0 ))  AS Neto_Factura,

		'Nota Credito' AS Tipo
	FROM
		Nota_Credito F
		INNER JOIN Producto_Nota_Credito PAR ON PAR.Id_Nota_Credito = F.Id_Nota_Credito
	WHERE
		F.Estado = 'Aprobada'
			AND F.Id_Cliente = $cli[Id_Cliente]
			".$condiciones['condicion_fechas2']."
	GROUP BY F.Id_Nota_Credito
	)
	UNION(
	SELECT
		FC.Codigo AS Factura,
		FC.Fecha_Documento AS Fecha_Factura,
		
		0 AS Gravado,
	
		/* IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
		FROM Descripcion_Factura_Capita DFC
		WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) as Excento, */
		
		SUM( DFC.Cantidad*DFC.Precio ) AS Excento,

		FC.Cuota_Moderadora AS Descuentos,
	
		0 AS Iva,
	
		/* IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
		FROM Descripcion_Factura_Capita DFC
		WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) as Total_Factura, */

		SUM( DFC.Cantidad*DFC.Precio ) AS Total_Factura,
	
		/*(IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
		FROM Descripcion_Factura_Capita DFC
		WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) - FC.Cuota_Moderadora) AS Neto_Factura,*/

		SUM( DFC.Cantidad*DFC.Precio ) AS Neto_Factura ,
		
		'Facturas Capita' AS Tipo
	
		FROM
		Factura_Capita FC
		INNER JOIN Descripcion_Factura_Capita DFC ON DFC.Id_Factura_Capita = FC.Id_Factura_Capita
		WHERE Estado_Factura = 'Sin Cancelar' AND FC.Id_Cliente = $cli[Id_Cliente]
		".$condiciones['condicion_fechas3']."
		)/*UNION ALL (
			SELECT
			FP.Factura,
			FP.Fecha_Factura,
			0 AS Gravado,
			0 AS Excento,
			0 AS Descuentos,
			0 AS Iva,
			FP.Saldo AS Total_Factura,
			FP.Saldo AS Neto_Factura
			FROM
			Facturas_Cliente_Mantis FP
			INNER JOIN Cliente P 
			ON FP.Nit_Cliente = P.Id_Cliente
			WHERE FP.Estado = 'Pendiente' AND FP.Nit_Cliente =  $cli[Id_Cliente]
			".$condiciones['condicion_fechas4']."
		)*/";

	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($q);
	$facturas_cli= $oCon->getData();
	unset($oCon);


	if (count($facturas_cli)>0) {
		
		$objSheet->getCell('A'.$j)->setValue($cli["Id_Cliente"]);
		$objSheet->getCell('B'.$j)->setValue($cli["Nombre"]);
		$j++;

		$objSheet->getCell('B'.$j)->setValue('Factura');
		$objSheet->getCell('C'.$j)->setValue('Fecha Factura');
		$objSheet->getCell('D'.$j)->setValue('Gravado');
		$objSheet->getCell('E'.$j)->setValue('Excento');
		$objSheet->getCell('F'.$j)->setValue('Descuentos');
		$objSheet->getCell('G'.$j)->setValue('Iva');
		$objSheet->getCell('H'.$j)->setValue('Total Factura');
		$objSheet->getCell('I'.$j)->setValue('Neto Factura');
		$objSheet->getCell('J'.$j)->setValue('Tipo Factura');
		
		$objSheet->getStyle('A'.$j.':J'.$j)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objSheet->getStyle('A'.$j.':J'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
		$objSheet->getStyle('A'.$j.':J'.$j)->getFont()->setBold(true);
		$objSheet->getStyle('A'.$j.':J'.$j)->getFont()->getColor()->setARGB('FFFFFFFF');


		foreach ($facturas_cli as $value) {$j++;
			$objSheet->getCell('B'.$j)->setValue($value["Factura"]);
			$objSheet->getCell('C'.$j)->setValue(fecha($value["Fecha_Factura"]));
			$objSheet->getCell('D'.$j)->setValue($value["Gravado"]);
			$objSheet->getStyle('D'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
			$objSheet->getCell('E'.$j)->setValue($value["Excento"]);
			$objSheet->getStyle('E'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
			$objSheet->getCell('F'.$j)->setValue($value["Descuentos"]);
			$objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
			$objSheet->getCell('G'.$j)->setValue($value["Iva"]);
			$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
			$objSheet->getCell('H'.$j)->setValue($value["Total_Factura"]);
			$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
			$objSheet->getCell('I'.$j)->setValue($value["Neto_Factura"]);
			$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
			$objSheet->getCell('J'.$j)->setValue($value["Tipo"]);
		}
	}
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function fecha($fecha){

	return date('d/m/Y', strtotime($fecha));
}

function SetCondiciones(){
    global $util;

    $condicion = '';
    $condicion2 = ''; 
    $condicion3 = ''; 
    $condicion4 = ''; 
    $condicion_fechas = ''; 
    $condicion_fechas2 = ''; 
    $condicion_fechas3 = ''; 
    $condiciones = array('condicion' => '', 'condicion2' => '', 'condicion3' => '');

    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente']) {
        $condicion .= " AND FV.Id_Cliente = $_REQUEST[cliente]";
        $condicion3 .= " AND FV.Id_Cliente = $_REQUEST[cliente]";
		$condicion2 .= " AND FV.Id_Cliente = $_REQUEST[cliente]";
    }

    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas']) {  
        $fechas_separadas = $util->SepararFechas($_REQUEST['fechas']);

		$condicion .= " AND (DATE(FV.Fecha_Documento) BETWEEN '$fechas_separadas[0]' AND '".$fechas_separadas[1]."')";
		$condicion2 .= " AND (DATE(FV.Fecha_Documento) BETWEEN '$fechas_separadas[0]' AND '".$fechas_separadas[1]."')";
		$condicion3 .= " AND (DATE(FV.Fecha_Documento) BETWEEN '$fechas_separadas[0]' AND '".$fechas_separadas[1]."')";
		$condicion4 .= " AND (DATE(FP.Fecha_Factura) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]')";
		$condicion_fechas = " AND (DATE(FV.Fecha_Documento) BETWEEN '$fechas_separadas[0]' AND '".$fechas_separadas[1]."')";
		$condicion_fechas2 = " AND (DATE(F.Fecha) BETWEEN '$fechas_separadas[0]' AND '".$fechas_separadas[1]."')";
		$condicion_fechas3 = " AND (DATE(FC.Fecha_Documento) BETWEEN '$fechas_separadas[0]' AND '".$fechas_separadas[1]."')";
		$condicion_fechas4 = " AND (DATE(FP.Fecha_Factura) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]')";
    }

    $condiciones['condicion'] = $condicion;
    $condiciones['condicion2'] = $condicion2;
    $condiciones['condicion3'] = $condicion3;
    $condiciones['condicion4'] = $condicion4;
    $condiciones['condicion_fechas'] = $condicion_fechas;
    $condiciones['condicion_fechas2'] = $condicion_fechas2;
    $condiciones['condicion_fechas3'] = $condicion_fechas3;
    $condiciones['condicion_fechas4'] = $condicion_fechas4;
    return $condiciones;
}

?>