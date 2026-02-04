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

// include $MY_CLASS . 'PHPExcel.php';
// include $MY_CLASS . 'PHPExcel/IOFactory.php';
// include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

include '../../class/PHPExcel.php';
include '../../class/PHPExcel/IOFactory.php';
include '../../class/PHPExcel/Writer/Excel5.php';


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="LitaNoPos.xls"');
header('Cache-Control: max-age=0'); 
$objPHPExcel = new PHPExcel;


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query= 'SELECT PP.*, IFNULL(PP.Cum_Homologo, "No tiene Homologo") as Cum_Homologo,
         IFNULL(PP.Precio_Homologo, 0) as Precio_Homologo , P.Nombre_Comercial, 
         CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida, " ") as Nombre_Producto, 
         IFNULL(
            (SELECT PR.Nombre_Comercial FROM Producto PR WHERE PR.Codigo_Cum=PP.Cum_Homologo), "Sin Homologo") as Nombre_Comercial_Homologo,
            (SELECT CONCAT(PR.Principio_Activo," ",PR.Presentacion," ",PR.Concentracion," ", PR.Cantidad," ", PR.Unidad_Medida, " ") 
         FROM Producto PR WHERE PR.Codigo_Cum=PP.Cum_Homologo ) as Nombre_Homologo  FROM Producto_NoPos PP 
         INNER JOIN Producto P ON PP.Cum=P.Codigo_Cum  WHERE PP.Id_Lista_Producto_Nopos='.$id;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$regulados['Productos'] = $oCon->getData();
$productos = $oCon->getData();
unset($oCon);   


//$productos = (array) json_decode($productos, true);

// $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
// $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
// $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
// $objSheet  = $objPHPExcel->getActiveSheet();
// $objSheet->setTitle('Inventario Diferencia');

// $objSheet->getCell('A1')->setValue("Nombre");
// $objSheet->getCell('B1')->setValue("Nombre Comercial");

// $objSheet->getCell('C1')->setValue("Cum");
// $objSheet->getCell('D1')->setValue("Precio");
// $objSheet->getCell('E1')->setValue("Precio Anterior");
// $objSheet->getCell('F1')->setValue("Nombre Homologo");
// $objSheet->getCell('G1')->setValue("Precio Homologo");
 
// $objSheet->getStyle('A2:L2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
// $objSheet->getStyle('A1:M1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
// $objSheet->getStyle('A1:M1')->getFont()->setBold(true);
// $objSheet->getStyle('A1:M1')->getFont()->getColor()->setARGB('FFFFFFFF');

$j = 1;
$valor_inicial = 0;
$valor_final   = 0;
$table         = '';

foreach($productos as $value){ $j++;

    // echo $value["Nombre_Producto"];
    // echo $value["Nombre_Comercial"];
    // echo $value["Cum"];
    // echo $value["Precio"];
    // echo $value['Precio_Anterior'];
    // echo $value["Nombre_Homologo"];
    // echo $value["Precio_Homologo"];

    $table .= '<tr>
                    <td>'.$value["Nombre_Producto"].'</td>
                    <td>'.$value["Nombre_Comercial"].'</td>
                    <td>'.$value["Cum"].'</td>
                    <td>'.$value["Precio"].'</td>
                    <td>'.$value["Precio_Anterior"].'</td>
                    <td>'.$value["Nombre_Homologo"].'</td>
                    <td>'.$value["Precio_Homologo"].'</td>
                </tr>';

    // $objSheet->getCell('A'.$j)->setValue($value["Nombre_Producto"]);
    // $objSheet->getCell('B'.$j)->setValue($value["Nombre_Comercial"]);

	// $objSheet->getCell('C'.$j)->setValue($value["Cum"]);
	// $objSheet->getCell('D'.$j)->setValue($value["Precio"]);
	// $objSheet->getCell('E'.$j)->setValue($value['Precio_Anterior']);
	// $objSheet->getCell('F'.$j)->setValue($value["Nombre_Homologo"]);
    // $objSheet->getCell('G'.$j)->setValue($value["Precio_Homologo"]);
    
	// $objSheet->getCell('G'.$j)->setValue($value["Costo"]);
	// $objSheet->getCell('H'.$j)->setValue($valor_inicial);
	// $objSheet->getCell('I'.$j)->setValue($valor_final);

}// FINAL FOREACH

echo $html = '  <table class="table"> 
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Nombre Comercial</th>
                            <th scope="col">Cum</th>
                            <th scope="col">Precio</th>
                            <th scope="col">Precio Anterior</th>
                            <th scope="col">Nombre Homologo</th>
                            <th scope="col">Precio Homologo</th>

                        </tr>
                    </thead>
                    <tbody>
                    '.$table.'
                    </tbody>
                </table>';

// $objSheet->getColumnDimension('C')->setAutoSize(true);
// $objSheet->getColumnDimension('D')->setAutoSize(true);
// $objSheet->getColumnDimension('E')->setAutoSize(true);
// $objSheet->getColumnDimension('F')->setAutoSize(true);
// $objSheet->getColumnDimension('G')->setAutoSize(true);
// $objSheet->getColumnDimension('H')->setAutoSize(true);
// $objSheet->getColumnDimension('I')->setAutoSize(true);
// $objSheet->getColumnDimension('J')->setAutoSize(true);
// $objSheet->getColumnDimension('K')->setAutoSize(true);
// $objSheet->getColumnDimension('L')->setAutoSize(true);
// $objSheet->getColumnDimension('M')->setAutoSize(true);
// $objSheet->getStyle('A1:M'.$j)->getAlignment()->setWrapText(true);
// $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Productos NoPos');
// $ruta="ARCHIVOS/ExcelNoPos/ListaNoPos".$id.".xls";
// $objWriter->save($MY_FILE . $ruta);

//$objWriter->save(str_replace('.php', '.xlsx', __FILE__));

?>