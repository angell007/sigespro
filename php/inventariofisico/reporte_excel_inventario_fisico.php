<?php
	ini_set('memory_limit', '2048M');
	set_time_limit(0);

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
	header('Content-Disposition: attachment;filename="Inventario Fisico Puntos.xls"');
	header('Cache-Control: max-age=0'); 

	$objPHPExcel = new PHPExcel;

	$id_inventario_fisico = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
	$query = 'SELECT INF.*
	FROM Inventario_Fisico INF WHERE INF.Id_Inventario_Fisico='.$id_inventario_fisico;
   $oCon= new consulta();
   $oCon->setQuery($query);
   $datos = $oCon->getData();
   unset($oCon);

   if($datos['Tipo_Inventario']=='Barrido'){
	   $query="SELECT GROUP_CONCAT(Id_Inventario_Fisico) as Id_Inventario_Fisico FROM Inventario_Fisico WHERE Estado='Terminado' AND Bodega=$datos[Bodega] AND Categoria=$datos[Categoria] AND Fecha_Fin='$datos[Fecha_Fin]' ";
	   $oCon= new consulta();
	   $oCon->setQuery($query);
	   $id_inven = $oCon->getData();
	   unset($oCon);

	   $id_inventario_fisico=$id_inven['Id_Inventario_Fisico'];
   }

	$query = '
		SELECT 
			INF.*,
			P.Nombre_Comercial,
			DATE_FORMAT(Fecha_Inicio, "%d/%m/%Y %r") AS f_inicio, 
			DATE_FORMAT(Fecha_Fin, "%d/%m/%Y %r") AS f_fin, 
			(SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Bodega,
			(SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Digitador, 
			(SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Cuenta) AS Funcionario_Cuenta, 
			(SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Autorizo,
			SUM(PIF.Cantidad_Inventario) AS Primer_Conteo,
			SUM(PIF.Segundo_Conteo) AS Segundo_Conteo,
			(SUM(PIF.Segundo_Conteo) - SUM(PIF.Cantidad_Inventario)) AS Diferencia,
			SUM(PIF.Segundo_Conteo) AS Cantidad_Final,
			(SELECT AVG(Costo) FROM Inventario WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0) AS Costo_Promedio,
			(SUM(PIF.Cantidad_Inventario) * (SELECT AVG(Costo) FROM Inventario WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0 )) AS Valor_Inicial,
			(SUM(PIF.Segundo_Conteo) * (SELECT AVG(Costo) FROM Inventario WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0)) AS Valor_Final,
			P.Codigo_Cum,
			IFNULL(P.Invima, "No Registrado") AS Invima, PIF.Lote
	FROM Inventario_Fisico INF
	INNER JOIN Producto_Inventario_Fisico PIF ON INF.Id_Inventario_Fisico = PIF.Id_Inventario_Fisico 
	INNER JOIN Producto P ON PIF.Id_Producto = P.Id_Producto
	WHERE
		INF.Id_Inventario_Fisico IN ('.$id_inventario_fisico
		.') AND INF.Estado = "Terminado"
	 GROUP BY PIF.Id_Producto, PIF.Lote
	 ORDER BY Nombre_Comercial ASC';

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('multiple');
	$datos = $oCon->getData();
	unset($oCon);

	$total = count($datos);

	$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
	$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
	$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
	$objSheet = $objPHPExcel->getActiveSheet();
	$objSheet->setTitle('Inventario Fisico');

	$objSheet->getCell('A1')->setValue("Bodega");
	$objSheet->getCell('B1')->setValue($datos[0]['Bodega']);
	$objSheet->getCell('C1')->setValue("Letras");
	$objSheet->getCell('D1')->setValue($datos['Letras']);
	$objSheet->getCell('E1')->setValue("Funcionario Cuenta");
	$objSheet->getCell('F1')->setValue($datos[0]["Funcionario_Digitador"]);
	$objSheet->getCell('G1')->setValue("Funcionario Digita");
	$objSheet->getCell('H1')->setValue($datos[0]["Funcionario_Cuenta"]);
	$objSheet->getCell('A2')->setValue("Nro.");
	$objSheet->getCell('B2')->setValue("Producto"); 
	$objSheet->getCell('C2')->setValue("Cantidad Inventario");
	$objSheet->getCell('D2')->setValue("Segundo Conteo");
	$objSheet->getCell('E2')->setValue("Diferencia");
	$objSheet->getCell('F2')->setValue("Cantidad Final");
	$objSheet->getCell('G2')->setValue("Costo del Producto");
	$objSheet->getCell('H2')->setValue("Valor Inicial");
	$objSheet->getCell('I2')->setValue("Valor Final");
	$objSheet->getCell('J2')->setValue("Codigo Cum");
	$objSheet->getCell('K2')->setValue("Invima");
	$objSheet->getCell('L2')->setValue("Lote");
	$objSheet->getStyle('A2:L2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A2:L2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A2:L2')->getFont()->setBold(true);
	$objSheet->getStyle('A2:L2')->getFont()->getColor()->setARGB('FFFFFFFF');
	  

	$j=2;
	foreach($datos as $value){ $j++;
		//var_dump($value);
		$objSheet->getCell('A'.$j)->setValue($j);
		$objSheet->getCell('B'.$j)->setValue($value["Nombre_Comercial"]);
		$objSheet->getCell('C'.$j)->setValue($value["Primer_Conteo"]);
		$objSheet->getCell('D'.$j)->setValue($value['Segundo_Conteo']);
		$objSheet->getCell('E'.$j)->setValue($value["Diferencia"]);
		$objSheet->getCell('F'.$j)->setValue($value["Cantidad_Final"]);
		$objSheet->getCell('G'.$j)->setValue($value["Costo_Promedio"]);
		$objSheet->getCell('H'.$j)->setValue($value['Valor_Inicial']);
		$objSheet->getCell('I'.$j)->setValue($value['Valor_Final']);
		$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");

		$objSheet->getCell('J'.$j)->setValue($value["Codigo_Cum"]); 
		$objSheet->getCell('K'.$j)->setValue($value["Invima"]); 
		$objSheet->getCell('L'.$j)->setValue($value["Lote"]); 
	}
	$objSheet->getColumnDimension('A')->setAutoSize(true);
	$objSheet->getColumnDimension('B')->setAutoSize(true);
	$objSheet->getColumnDimension('C')->setAutoSize(true);
	$objSheet->getColumnDimension('D')->setAutoSize(true);
	$objSheet->getColumnDimension('E')->setAutoSize(true);
	$objSheet->getColumnDimension('F')->setAutoSize(true);
	$objSheet->getColumnDimension('G')->setAutoSize(true);
	$objSheet->getColumnDimension('H')->setAutoSize(true);
	$objSheet->getColumnDimension('I')->setAutoSize(true);
	$objSheet->getColumnDimension('J')->setAutoSize(true);
	$objSheet->getStyle('A1:L'.$j)->getAlignment()->setWrapText(true);
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');


?>