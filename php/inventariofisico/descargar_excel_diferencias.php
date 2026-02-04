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
header('Content-Disposition: attachment;filename="Inventario.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;


$productos = isset($_REQUEST['productos']) ? $_REQUEST['productos'] : false;


$productos = (array) json_decode($productos, true);
$inv=$productos[0]['Id_Inventario_Fisico'];

foreach ($productos as $key => $p) {
    $productos[$key]['Costo']=GetCostoPromedio($p['Id_Producto']);
    $productos[$key]['Codigo_Cum']=GetCodigoCum($p['Id_Producto']);
}

  $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
	$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
	$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
	$objSheet = $objPHPExcel->getActiveSheet();
	$objSheet->setTitle('Inventario Diferencia');

	$objSheet->getCell('A1')->setValue("Nombre Comercial");
	$objSheet->getCell('B1')->setValue("Producto");
	$objSheet->getCell('C1')->setValue("Cantidad Inventario");
	$objSheet->getCell('D1')->setValue("Segundo Conteo");
	$objSheet->getCell('E1')->setValue("Diferencia");
	$objSheet->getCell('F1')->setValue("Cantidad Final");
	$objSheet->getCell('G1')->setValue("Costo del Producto");
	$objSheet->getCell('H1')->setValue("Valor Inicial");
	$objSheet->getCell('I1')->setValue("Valor Final");
	$objSheet->getCell('J1')->setValue("Codigo Cum");
	$objSheet->getCell('K1')->setValue("Lote");
	$objSheet->getCell('L1')->setValue("Fecha Vencimiento");
	$objSheet->getCell('M1')->setValue("Laboratorio Comercial");
	$objSheet->getStyle('A2:L2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A1:M1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A1:M1')->getFont()->setBold(true);
	$objSheet->getStyle('A1:M1')->getFont()->getColor()->setARGB('FFFFFFFF');


    $j=1;
    $valor_inicial=0;
    $valor_final=0;
	foreach($productos as $value){ $j++;
        //var_dump($value);
        $valor_inicial=$value["Cantidad_Inventario"]*$value["Costo"];
        $valor_final=$value["Cantidad_Final"]*$value["Costo"];
		$objSheet->getCell('A'.$j)->setValue($value["Nombre_Comercial"]);
		$objSheet->getCell('B'.$j)->setValue($value["Nombre_Producto"]);
		$objSheet->getCell('C'.$j)->setValue($value["Cantidad_Inventario"]);
		$objSheet->getCell('D'.$j)->setValue($value['Cantidad_Encontrada']);
		$objSheet->getCell('E'.$j)->setValue((INT)$value["Cantidad_Diferencial"]);
		$objSheet->getCell('F'.$j)->setValue($value["Cantidad_Final"]);
		$objSheet->getCell('G'.$j)->setValue($value["Costo"]);
		$objSheet->getCell('H'.$j)->setValue($valor_inicial);
		$objSheet->getCell('I'.$j)->setValue($valor_final);
		$objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getStyle('H'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
		$objSheet->getStyle('I'.$j)->getNumberFormat()->setFormatCode("#,##0.00");

		$objSheet->getCell('J'.$j)->setValue($value["Codigo_Cum"]);
		$objSheet->getCell('K'.$j)->setValue($value["Lote"]);
		$objSheet->getCell('L'.$j)->setValue(fecha($value["Fecha_Vencimiento"]));
		$objSheet->getCell('M'.$j)->setValue(getLabComercial($value["Id_Producto"]));
	}

	$objSheet->getColumnDimension('C')->setAutoSize(true);
	$objSheet->getColumnDimension('D')->setAutoSize(true);
	$objSheet->getColumnDimension('E')->setAutoSize(true);
	$objSheet->getColumnDimension('F')->setAutoSize(true);
	$objSheet->getColumnDimension('G')->setAutoSize(true);
	$objSheet->getColumnDimension('H')->setAutoSize(true);
	$objSheet->getColumnDimension('I')->setAutoSize(true);
	$objSheet->getColumnDimension('J')->setAutoSize(true);
	$objSheet->getColumnDimension('K')->setAutoSize(true);
	$objSheet->getColumnDimension('L')->setAutoSize(true);
	$objSheet->getColumnDimension('M')->setAutoSize(true);
	$objSheet->getStyle('A1:M'.$j)->getAlignment()->setWrapText(true);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $ruta="ARCHIVOS/INVENTARIOS/INF".$inv.".xls";
	$objWriter->save($MY_FILE . $ruta);

echo json_encode($ruta);
function GetCostoPromedio($id){
    $query="SELECT AVG(Costo) as Costo FROM Inventario WHERE Id_Producto=$id AND Id_Bodega!=0 ";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $costo = $oCon->getData();
    unset($oCon);

    return $costo['Costo'];
}
function GetCodigoCum($id){
    $query="SELECT Codigo_Cum FROM Producto WHERE Id_Producto=$id  ";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum['Codigo_Cum'];
}
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
function getLabComercial($id_prod) {
	$query="SELECT Laboratorio_Comercial FROM Producto WHERE Id_Producto=$id_prod  ";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
	unset($oCon);

	return $data['Laboratorio_Comercial'];
}

?>
