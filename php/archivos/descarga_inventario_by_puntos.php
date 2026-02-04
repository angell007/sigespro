<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');

date_default_timezone_set("America/Bogota");

$permiso = permiso();

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';



$objPHPExcel = new PHPExcel;


$sin_inventario = $_REQUEST['sin_inventario'];
$condicion_sin_inventario = '';

if ($sin_inventario == "false") {
    $condicion_sin_inventario = " AND (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) > 0";
} else if ($sin_inventario == "true") {
    $condicion_sin_inventario = "";
} else if ($sin_inventario == "") {
    $condicion_sin_inventario = "";
}

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    $condicion .= " AND (PRD.Principio_Activo LIKE '%$_REQUEST[nom]%' OR PRD.Presentacion LIKE '%$_REQUEST[nom]%' OR PRD.Concentracion LIKE '%$_REQUEST[nom]%' OR PRD.Nombre_Comercial LIKE '%$_REQUEST[nom]%')";
}



if (isset($_REQUEST['lab']) && $_REQUEST['lab'] != "") {
    $condicion .= " AND PRD.Laboratorio_Comercial LIKE '%$_REQUEST[lab]%'";
}

if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen'] != "") {
    $condicion .= " AND PRD.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
}

if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
    $condicion .= " AND GE.Nombre LIKE '%$_REQUEST[grupo]%'";
}


if (isset($_REQUEST['lote']) && $_REQUEST['lote'] != "") {
    $condicion .= " AND Lote LIKE '%$_REQUEST[lote]%'";
}


if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
    $condicion .= " AND I.Codigo_CUM LIKE '%$_REQUEST[cum]%'";
}

if (isset($_REQUEST['bod']) && $_REQUEST['bod'] != "") {
    $condicion .= " AND b.Nombre LIKE '%$_REQUEST[bod]%'";
}


if (isset($_REQUEST['cant']) && $_REQUEST['cant'] != "") {
    $condicion .= " AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)=$_REQUEST[cant]";
}

if (isset($_REQUEST['cant_apar']) && $_REQUEST['cant_apar'] != "") {
    $condicion .= " AND I.Cantidad_Apartada=$_REQUEST[cant_apar]";
}

if (isset($_REQUEST['cant_sel']) && $_REQUEST['cant_sel'] != "") {
    $condicion .= " AND I.Cantidad_Seleccionada=$_REQUEST[cant_sel]";
}


if (isset($_REQUEST['costo']) && $_REQUEST['costo'] != "") {
    $condicion .= " AND I.Costo=$_REQUEST[costo]";
}

if (isset($_REQUEST['invima']) && $_REQUEST['invima'] != "") {
    $condicion .= " AND PRD.Invima LIKE '%$_REQUEST[invima]%'";
}

if (isset($_REQUEST['iva']) && $_REQUEST['iva'] != "") {
    $condicion .= " AND PRD.Gravado='$_REQUEST[iva]'";
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND I.Fecha_Vencimiento BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$condicion_principal = '';

$id=(isset($_REQUEST['id'])? $_REQUEST['id']:'');
if (isset($_REQUEST['id']) && ($_REQUEST['id'] != "" && $_REQUEST['id'] != "0")) {
    $condicion_principal.=  ' AND B.Id_Punto_Dispensacion =' . $_REQUEST['id'];
}else{
    $condicion_principal.=  ' AND B.Id_Punto_Dispensacion != 0';
}
 

if ($sin_inventario == "false") {
    $condicion_sin_inventario = " AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) > 0";
}



$query = "SELECT

GROUP_CONCAT(I.Id_Inventario_Nuevo) AS Id_Inventario_Nuevo,
I.Fecha_Vencimiento, I.Lote, I.Cantidad, I.Codigo_CUM, GE.Nombre AS Nombre_Grupo, I.Id_Producto,B.Nombre as NombrePunto, B.Id_Punto_Dispensacion,
SUM(I.Cantidad_Apartada) AS Cantidad_Apartada,
SUM(I.Cantidad_Seleccionada) AS Cantidad_Seleccionada,
PRD.Laboratorio_Generico,

CONCAT_WS(' ',PRD.Principio_Activo,PRD.Presentacion,PRD.Concentracion,PRD.Cantidad,PRD.Unidad_Medida) AS Nombre_Producto,

PRD.Tipo,PRD.Nombre_Comercial,PRD.Laboratorio_Comercial,PRD.Invima,

(SELECT CPM.Costo_Promedio FROM Costo_Promedio CPM WHERE CPM.Id_Producto = PRD.Id_Producto) AS Costo,
CONCAT(PRD.Embalaje,' Categoria: ',SubC.Nombre) AS Embalaje,
SUM(
IF(
    (
        I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada
    ) < 0,
    0,
    (
        I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada
    )
) ) AS Cantidad_Disponible,
C.Nombre AS Nombre_Categoria,


GROUP_CONCAT(CONCAT ( ' Estiba ', E.Nombre , '  : ',                    
                        IF(
                            (
                                I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada
                            ) < 0,
                            0,
                            (
                                I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada
                            )
                         )   
                    )  
            ) AS 'Nombre_Estiba',
SubC.Nombre as SubCategoria,

#Precio.Precio as Precio1, 


(SELECT CONCAT('Fecha : ',DATE(AR.Fecha_Creacion)) FROM Producto_Acta_Recepcion PAR 
 	INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion 
 	WHERE PAR.Id_Producto=I.Id_Producto AND AR.Tipo_Acta='Bodega' Order BY PAR.Id_Producto_Acta_Recepcion DESC Limit 1 ) as Fecha1,
 	
(SELECT CONCAT('Acta: ', AR.Codigo) FROM Producto_Acta_Recepcion PAR 
 	INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion 
 	WHERE PAR.Id_Producto=I.Id_Producto AND AR.Tipo_Acta='Bodega' Order BY PAR.Id_Producto_Acta_Recepcion DESC Limit 1 ) as Acta1,
 
 	
(SELECT PAR.Precio FROM Producto_Acta_Recepcion PAR 
 	INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion 
 	WHERE PAR.Id_Producto=I.Id_Producto AND AR.Tipo_Acta='Bodega' Order BY PAR.Id_Producto_Acta_Recepcion DESC Limit 1 ) as Precio1,
 	
PRD.Gravado
FROM Inventario_Nuevo I
INNER JOIN Producto PRD ON I.Id_Producto = PRD.Id_Producto
INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
INNER JOIN Grupo_Estiba GE ON GE.Id_Grupo_Estiba = E.Id_Grupo_Estiba
INNER JOIN Punto_Dispensacion B ON B.Id_Punto_Dispensacion = E.Id_Punto_Dispensacion
INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria
INNER JOIN Categoria_Nueva C ON SubC.Id_Categoria_Nueva = C.Id_Categoria_Nueva";

$query .= $condicion_principal . ' ' . $condicion . $condicion_sin_inventario . ' 
          GROUP BY B.Id_Punto_Dispensacion, 
          I.Id_Producto,
          I.Lote,
          I.Fecha_Vencimiento, 
          I.Codigo_Cum' . ' 
          ORDER BY PRD.Nombre_Comercial  ';
$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$inventario['inventarios'] = $oCon->getData();

// print_r($inventario);
// exit;
unset($oCon);
$i = -1;

$inventario['numReg'] = $numReg;


$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();

$objSheet->setTitle('Productos_Lista_'.$id);
$col='A';
$objSheet->getCell($col.'1')->setValue('Nombre Comercial');$col++;
$objSheet->getCell($col.'1')->setValue('Nombre Producto');$col++;
$objSheet->getCell($col.'1')->setValue('Lote');$col++;
$objSheet->getCell($col.'1')->setValue('Embalaje');$col++;
$objSheet->getCell($col.'1')->setValue('Laboratorio Comercial');$col++;
$objSheet->getCell($col.'1')->setValue('Laboratorio Generico');$col++;
$objSheet->getCell($col.'1')->setValue("Codigo Cum");$col++;
$objSheet->getCell($col.'1')->setValue("Invima");$col++;
if($permiso){
	$objSheet->getCell($col.'1')->setValue("Precio");$col++;
}

$objSheet->getCell($col.'1')->setValue("Precio");$col++;
$objSheet->getCell($col.'1')->setValue("Cantidad_Disponible");$col++;
$objSheet->getCell($col.'1')->setValue("Estiba");$col++;
$objSheet->getCell($col.'1')->setValue("Fecha Vencimiento");$col++;
$objSheet->getCell($col.'1')->setValue("Gravado");$col++;
$objSheet->getCell($col.'1')->setValue("Nombre Punto");$col++;
$objSheet->getCell($col.'1')->setValue("Categoria");$col++;
$objSheet->getCell($col.'1')->setValue("Subcategoria");$col++;
$objSheet->getCell($col.'1')->setValue("Acta");$col++;
$objSheet->getCell($col.'1')->setValue("Fecha");


$objSheet->getStyle("A1:$col".'1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle("A1:$col".'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle("A1:$col".'1')->getFont()->setBold(true);
$objSheet->getStyle("A1:$col".'1')->getFont()->getColor()->setARGB('FFFFFFFF');

$j=1;


foreach($inventario['inventarios'] as $prod){ $j++;
    $col='A';
	$objSheet->getCell($col.$j)->setValue($prod["Nombre_Comercial"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Nombre_Producto"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Lote"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Embalaje"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Laboratorio_Comercial"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Laboratorio_Generico"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Codigo_CUM"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Invima"]);$col++;

    if($permiso){
	   $objSheet->getCell($col.$j)->setValue($prod["Precio1"]);$col++;
    }
    $objSheet->getCell($col.$j)->setValue($prod["Precio1"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Cantidad_Disponible"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Nombre_Estiba"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Fecha_Vencimiento"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Gravado"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["NombrePunto"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Nombre_Categoria"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["SubCategoria"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Acta1"]);$col++;
	$objSheet->getCell($col.$j)->setValue($prod["Fecha1"]);
	

}


$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
//$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setWidth(50);

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
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(true);
$objSheet->getColumnDimension('P')->setAutoSize(true);
$objSheet->getColumnDimension('Q')->setAutoSize(true);
$objSheet->getStyle('A1:Q'.$j)->getAlignment()->setWrapText(true);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Productos_Lista_'.$id.'.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');



function permiso(){
	$identificacion_funcionario = $_SESSION["user"];
	if($identificacion_funcionario==''){
		$identificacion_funcionario=$_REQUEST['funcionario'];
	}
	$query = 'SELECT Ver_Costo 
	               FROM Funcionario 
	               WHERE Ver_Costo="Si" AND Identificacion_Funcionario='.$identificacion_funcionario; 
	$oCon= new consulta();
	$oCon->setQuery($query);
	$permisos = $oCon->getData();
	unset($oCon);


	$status = false; // Sin permisos

	if ($permisos) {
		$status = true;
	}

	return $status;
}




