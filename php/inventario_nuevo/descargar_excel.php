<?php

use PhpParser\Node\Expr\Exit_;

session_start();
ini_set('memory_limit', '2048M');
set_time_limit(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");


$condicion='';
if (isset($_REQUEST['id']) && ($_REQUEST['id'] != "" && $_REQUEST['id'] != "0")) {
    $condicion = " AND SubC.Id_Categoria_Nueva=" . $_REQUEST['id'];
} else {
    $condicion = " AND SubC.Id_Categoria_Nueva!=0";
}
if (isset($_REQUEST['sin']) && ($_REQUEST['sin'] != "" && $_REQUEST['sin'] != "true")) {
    $condicion = " AND I.Cantidad > 0";
} else {
    $condicion = " AND SubC.Id_Categoria_Nueva!=0";
}

if (isset($_REQUEST['id_bodega_nuevo']) && ($_REQUEST['id_bodega_nuevo'] != "" && $_REQUEST['id_bodega_nuevo'] != "0")) {
    $condicion.=  ' AND B.Id_Bodega_Nuevo =' . $_REQUEST['id_bodega_nuevo'];
}else{
    $condicion.=  ' AND B.Id_Bodega_Nuevo != 0';
}


//  if (isset( $_REQUEST['id'] )&& $_REQUEST['id'] != "" && $_REQUEST['id'] != "0") {
//      $condicion .= " AND I.Id_Bodega=$_REQUEST[id] ";
//  } else {
//  	$condicion .= " AND I.Id_Bodega<>0";
//  }
// var_dump($_SESSION);
// exit;

$permiso = permiso();

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';


$objPHPExcel = new PHPExcel;


 $query =
 "SELECT R.*, UC.Compras FROM(SELECT  (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad_Disponible, PRD.Laboratorio_Generico , I.Fecha_Vencimiento, I.Lote,
 CONCAT(PRD.Principio_Activo,' ',PRD.Presentacion,' ',PRD.Concentracion,' ', PRD.Cantidad,' ', PRD.Unidad_Medida, ' ') AS Nombre_Producto, 
 PRD.Tipo, PRD.Nombre_Comercial,PRD.Codigo_Cum,
 I.Id_Producto,
 PRD.Laboratorio_Comercial, PRD.Invima, PRD.Embalaje, C.Nombre as Categoria, SubC.Nombre AS Subcategoria,
 B.Nombre AS Bodega_Nuevo,
    IFNULL((SELECT Costo_Promedio From Costo_Promedio WHERE Id_Producto = PRD.Id_Producto),0) AS Costo,
 
 
 	( SELECT CONCAT('Fecha: ',DATE(F.Fecha_Documento), ' - Factura: ',F.Codigo) FROM Producto_Factura_Venta PF 
 	INNER JOIN Factura_Venta F ON PF.Id_Factura_Venta=F.Id_Factura_Venta 
 	WHERE PF.Id_Producto=I.Id_Producto ORDER BY PF.Id_Producto_Factura_Venta DESC LIMIT 1 ) as Ultima_Venta,
 	(SELECT CONCAT(DATE(R.Fecha), ' - ',R.Codigo ) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
 	WHERE R.Tipo='Interna' AND PR.Id_Producto=I.Id_Producto ORDER BY PR.Id_Producto_Remision DESC LIMIT 1) as Ultima_Rem,
 	GE.Nombre AS Nombre_Grupo_Estiba,
	 E.Nombre AS Nombre_Estiba
 
 	
 FROM Inventario_Nuevo I
 STRAIGHT_JOIN Producto PRD  On I.Id_Producto=PRD.Id_Producto
 STRAIGHT_JOIN  Estiba E ON  E.Id_Estiba = I.Id_Estiba
 STRAIGHT_JOIN  Grupo_Estiba GE ON  GE.Id_Grupo_Estiba = E.Id_Grupo_Estiba
 STRAIGHT_JOIN Subcategoria SubC ON SubC.Id_Subcategoria=PRD.Id_Subcategoria 
 STRAIGHT_JOIN Categoria_Nueva C ON SubC.Id_Categoria_Nueva=C.Id_Categoria_Nueva
 STRAIGHT_JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
  
 WHERE (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) >= 0  $condicion Order BY I.Id_Bodega,Nombre_Producto
 )R
 LEFT JOIN ( 	
	SELECT 
    CONCAT('[', GROUP_CONCAT(P.Compras), ']') AS Compras,
    P.Id_Producto, P.Lote, P.Fecha
FROM
    (SELECT 
        GROUP_CONCAT(CONCAT('{\"Prov\":\"',  P.Nombre, '\", \"Cantidad\":\"', PAR.Cantidad,  '\", \"Costo\":\"', PAR.Precio, '\", \"Acta\":\"', AR.Codigo, '\"', '}')) AS Compras,
            PAR.Id_Producto, PAR.Lote, AR.Fecha_Creacion as Fecha
    FROM
        Acta_Recepcion AR
    INNER JOIN Producto_Acta_Recepcion PAR ON AR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion
    INNER JOIN Orden_Compra_Nacional OC ON OC.Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional
    INNER JOIN Proveedor P ON P.Id_Proveedor = OC.Id_Proveedor
    WHERE
        AR.Estado IN ('Aprobada' , 'Acomodada')
    GROUP BY PAR.Id_Producto, PAR.Lote , AR.Codigo
    ORDER BY AR.Fecha_Creacion DESC) P
GROUP BY P.Id_Producto  )  UC ON UC.Id_Producto = R.Id_Producto";





$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);
$i=0;
foreach ($productos as $producto) {
	$compras= (array)json_decode($producto['Compras'], true);
	$compras= array_slice($compras, 0, 3);
	$j=1;
	foreach ($compras as $compra) {
		foreach($compra as $key=>$val){
			$productos[$i]['UC'.$j].="$key: $val, ";
		}
		$j++;
	}
	$productos[$i]['Compras']=$compras;



	$i++;
}

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Proveedores');

$objPHPExcel->getActiveSheet()->SetCellValue('A1',"Nombre Comercial");
$objPHPExcel->getActiveSheet()->SetCellValue('B1',"Nombre");
$objPHPExcel->getActiveSheet()->SetCellValue('C1',"Embalaje");
$objPHPExcel->getActiveSheet()->SetCellValue('D1',"Lab. Comercial");
$objPHPExcel->getActiveSheet()->SetCellValue('E1',"Lab. Generico");
$objPHPExcel->getActiveSheet()->SetCellValue('F1',"Invima");
$objPHPExcel->getActiveSheet()->SetCellValue('G1',"Bodega");
$objPHPExcel->getActiveSheet()->SetCellValue('H1',"Cum");
$objPHPExcel->getActiveSheet()->SetCellValue('I1',"Lote");
$objPHPExcel->getActiveSheet()->SetCellValue('J1',"Fecha Vencimiento");
$objPHPExcel->getActiveSheet()->SetCellValue('K1',"Cantidad");
$objPHPExcel->getActiveSheet()->SetCellValue('L1',"Costo");
$objPHPExcel->getActiveSheet()->SetCellValue('M1',"Categoria");
$objPHPExcel->getActiveSheet()->SetCellValue('N1',"Subcategoria");
$objPHPExcel->getActiveSheet()->SetCellValue('O1',"Estiba");
$objPHPExcel->getActiveSheet()->SetCellValue('P1',"Grupo_Estiba");
if($permiso){
	$objPHPExcel->getActiveSheet()->SetCellValue('Q1',"Última Compra 1");
	$objPHPExcel->getActiveSheet()->SetCellValue('R1',"Última Compra 2");
	$objPHPExcel->getActiveSheet()->SetCellValue('S1',"Última Compra 3");
	// $objPHPExcel->getActiveSheet()->SetCellValue('T1',"Cantidad Última Compra");
	// $objPHPExcel->getActiveSheet()->SetCellValue('T1',"Precio Última Compra");
	$objPHPExcel->getActiveSheet()->SetCellValue('T1',"Última Venta");
	$objPHPExcel->getActiveSheet()->SetCellValue('U1',"Última Remision ");
	
}

$objSheet->getStyle('A1:P1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:P1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:P1')->getFont()->setBold(true);
$objSheet->getStyle('A1:P1')->getFont()->getColor()->setARGB('FFFFFFFF');

if($permiso){
	$objSheet->getStyle('A1:U1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A1:U1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A1:U1')->getFont()->setBold(true);
	$objSheet->getStyle('A1:U1')->getFont()->getColor()->setARGB('FFFFFFFF');
}

$j=1;
foreach($productos as $disp){ $j++;
	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$j,$disp["Nombre_Comercial"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('B'.$j,$disp["Nombre_Producto"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('C'.$j,$disp["Embalaje"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('D'.$j,$disp["Laboratorio_Comercial"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('E'.$j,$disp["Laboratorio_Generico"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('F'.$j,$disp["Invima"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('G'.$j,$disp["Bodega_Nuevo"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('H'.$j,$disp["Codigo_Cum"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('I'.$j,$disp["Lote"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('J'.$j,$disp["Fecha_Vencimiento"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('K'.$j,$disp["Cantidad_Disponible"]);
	if ($permiso) {
		
		$objPHPExcel->getActiveSheet()->SetCellValue('L'.$j,$disp["Costo"]);
	}
	$objPHPExcel->getActiveSheet()->SetCellValue('M'.$j,$disp["Categoria"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('N'.$j,$disp["Subcategoria"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('O'.$j,$disp["Nombre_Estiba"]);
	$objPHPExcel->getActiveSheet()->SetCellValue('P'.$j,$disp["Nombre_Grupo_Estiba"]);
	if($permiso){
		$objPHPExcel->getActiveSheet()->SetCellValue('Q'.$j,$disp["UC1"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('R'.$j,$disp["UC2"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('S'.$j,$disp["UC3"]);
		// $objPHPExcel->getActiveSheet()->SetCellValue('T'.$j,$disp["Cantidad_Ultima_Compra"]);
		// $objPHPExcel->getActiveSheet()->SetCellValue('T'.$j,$disp["Precio_Ultima_Compra"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('T'.$j,$disp["Ultima_Venta"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('U'.$j,$disp["Ultima_Rem"]);
	}
}

$objSheet->getColumnDimension('A')->setWidth(50);
// $objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setWidth(50);
// $objSheet->getColumnDimension('B')->setAutoSize(true);
//$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setWidth(50);
$objSheet->getColumnDimension('D')->setWidth(50);
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
$objSheet->getColumnDimension('Q')->setWidth(80);
$objSheet->getColumnDimension('R')->setWidth(80);
$objSheet->getColumnDimension('S')->setWidth(80);
// $objSheet->getColumnDimension('Q')->setAutoSize(true);
// $objSheet->getColumnDimension('R')->setAutoSize(true);
// $objSheet->getColumnDimension('S')->setAutoSize(true);
$objSheet->getColumnDimension('T')->setAutoSize(true);
$objSheet->getColumnDimension('U')->setAutoSize(true);
$objSheet->getColumnDimension('V')->setAutoSize(true);
$objSheet->getStyle('A1:V'.$j)->getAlignment()->setWrapText(true);


header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reporte_Inventario.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');

function permiso(){
	$identificacion_funcionario = $_SESSION["user"];
	if($identificacion_funcionario==''){
		$identificacion_funcionario=$_REQUEST['funcionario'];
	}
	$query = 'SELECT Ver_Costo FROM Funcionario WHERE Ver_Costo="Si" AND Identificacion_Funcionario='.$identificacion_funcionario; 
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

function getActasProductos($id){
	$query=

	"SELECT AR.Id_Acta_Recepcion as Id_Acta, 
		AR.Fecha_Creacion as Fecha, 
		AR.Codigo as Codigo_Acta, 
		PAR.Cantidad, PAR.Precio, OC.Codigo as Codigo_Compra_N, OC.Id_Orden_Compra_Nacional as Id_Compra_N, OCI.Codigo as Codigo_Compra_I, OCI.Id_Orden_Compra_Internacional as Id_Compra_I, P.Nombre as Proveedor,

		FROM 
			Producto_Acta_Recepcion PAR 
		INNER JOIN 
			Acta_Recepcion AR	ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion
		LEFT JOIN 
			Orden_Compra_Nacional OC ON OC.Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional
		LEFT JOIN 
			Orden_Compra_Internacional OCI ON OCI.Id_Orden_Compra_Internacional = AR.Id_Orden_Compra_Internacional
		INNER JOIN 
			Proveedor P	ON P.Id_Proveedor = AR.Id_Proveedor
		WHERE 
		PAR.Id_Producto =".$id." AND (AR.Estado = 'Aprobada' OR AR.Estado = 'Acomodada')
		Order BY AR.Id_Acta_Recepcion DESC LIMIT 3 ";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
echo json_encode($resultado);

}
?>