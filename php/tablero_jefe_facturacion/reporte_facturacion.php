<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set("America/Bogota");

$queryString = http_build_query($_GET);
header("Location: reporte_facturacion_plano.php?$queryString");

exit;

$condicion = '';
$condicion_capita = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'WHERE DATE_FORMAT(F.Fecha_Documento, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
	$condicion_capita .= 'WHERE DATE_FORMAT(FC.Fecha_Documento, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}


if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Identificacion_Facturador=$_REQUEST[func]";
		$condicion_capita .= " AND F.Identificacion_Funcionario=$_REQUEST[func]";
	} else {
		$condicion .= "WHERE D.Identificacion_Facturador=$_REQUEST[func]";
		$condicion_capita .= "WHERE F.Identificacion_Funcionario=$_REQUEST[func]";
	}
}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Tipo='$_REQUEST[tipo]'";
	} else {
		$condicion .= "WHERE D.Tipo='$_REQUEST[tipo]'";
	}
}

if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {
	if ($condicion != "") {
		$condicion .= " AND F.Id_Cliente =$_REQUEST[cliente]";
		$condicion_capita .= " AND C.Id_Cliente =$_REQUEST[cliente]";
	} else {
		$condicion .= "WHERE C.Id_Cliente = $_REQUEST[cliente]";
		$condicion_capita .= "WHERE C.Id_Cliente = $_REQUEST[cliente]";
	}
}

if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
	$cond_estado = $_REQUEST['estado'] == "Activa" ? "(F.Estado_Factura = 'Sin Cancelar' OR F.Estado_Factura = 'Pagada')" : "F.Estado_Factura = 'Anulada'";
	$cond_estado2 = $_REQUEST['estado'] == "Activa" ? "(FC.Estado_Factura = 'Sin Cancelar' OR FC.Estado_Factura = 'Pagada')" : "FC.Estado_Factura = 'Anulada'";
	if ($condicion != "") {
		$condicion .= " AND $cond_estado";
		$condicion_capita .= " AND $cond_estado2";
	} else {
		$condicion .= "WHERE $cond_estado";
		$condicion_capita .= "WHERE $cond_estado2";
	}
}


 require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

$objPHPExcel = new PHPExcel;

/* header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Facturacion.xls"');
header('Cache-Control: max-age=0'); 
 */
$query ='(SELECT 
F.Codigo as Factura, F.Tipo as Tipo_Factura,
F.Fecha_Documento as Fecha_Factura, 
CONCAT(FU.Nombres," ",FU.Apellidos) as Funcionario_Facturador, 
D.Codigo as Dis, 
F.Id_Cliente, 
C.Nombre as Razon_Social, 
P.Tipo_Documento, 
P.Id_Paciente, 
P.Primer_Apellido, P.Segundo_Apellido, P.Primer_Nombre, P.Segundo_Nombre, 
P.Fecha_Nacimiento, TIMESTAMPDIFF(YEAR,P.Fecha_Nacimiento,CURDATE()) as Edad, 
P.Genero, R.Nombre as Regimen, P.EPS, 
P.Cod_Departamento, M.Codigo_Dane as Codigo_Municipio, M.Nombre as Nombre_Municipio, 
PD.Numero_Autorizacion, PD.Fecha_Autorizacion, 
PD.Numero_Prescripcion, D.Fecha_Formula, 
D.Fecha_Actual as Fecha_Dispensacion, 
D.CIE, D.Tipo, TS.Nombre as Tipo_Servicio, PR.Codigo_Cum AS Cum, 
CONCAT_WS(" ", PR.Nombre_Comercial,"(",PR.Principio_Activo,PR.Concentracion,PR.Presentacion,PR.Embalaje) as Nombre_Producto,
IFNULL(PD.Lote, "") AS Lote_Producto,
IFNULL(PD.Costo, "") AS Costo_Producto,
PF.Cantidad, PF.Precio,  PF.Subtotal, PF.Descuento, PF.Impuesto, F.Cuota, C.Tipo_Valor
FROM Producto_Factura PF
INNER JOIN Factura F
ON F.Id_Factura = PF.Id_Factura
INNER JOIN (SELECT Id_Dispensacion, Codigo, Numero_Documento, Tipo_Servicio, Fecha_Formula, Fecha_Actual, CIE, Tipo FROM Dispensacion WHERE Estado_Dispensacion != "Anulada" AND Tipo != "Capita" AND Estado_Facturacion = "Facturada") D
ON D.Id_Dispensacion = F.Id_Dispensacion
INNER JOIN Paciente P
ON P.Id_Paciente = D.Numero_Documento
LEFT JOIN Funcionario FU
ON FU.Identificacion_Funcionario = F.Id_Funcionario
INNER JOIN Cliente C
ON C.Id_Cliente = F.Id_Cliente
INNER JOIN Regimen R
ON R.Id_Regimen = P.Id_Regimen
LEFT JOIN Municipio M
ON M.Id_Municipio = P.Codigo_Municipio
LEFT JOIN Producto_Dispensacion PD
ON PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
INNER JOIN Producto PR
ON PR.Id_Producto = PF.Id_Producto
LEFT JOIN Tipo_Servicio TS
ON TS.Id_Tipo_Servicio = D.Tipo_Servicio '.$condicion.' 
ORDER BY F.Id_Factura ASC, PF.Subtotal DESC) 
UNION ALL (SELECT
FC.Codigo AS Factura,"Capita" as Tipo_Factura,
FC.Fecha_Documento as Fecha_Factura, 
CONCAT(F.Nombres," ",F.Apellidos) as Funcionario_Facturador,
"" AS Dis,
FC.Id_Cliente, 
C.Nombre as Razon_Social, 
"" AS Tipo_Documento, 
"" AS Id_Paciente, 
"" AS Primer_Apellido, "" AS Segundo_Apellido, "" AS Primer_Nombre, "" AS Segundo_Nombre, 
"" AS Fecha_Nacimiento, "" as Edad, 
"" AS Genero, (SELECT Nombre FROM Regimen WHERE Id_Regimen = FC.Id_Regimen) as Regimen, "" AS EPS,
(SELECT Codigo FROM Departamento WHERE Id_Departamento = FC.Id_Departamento) AS Cod_Departamento,
"" as Codigo_Municipio, "" as Nombre_Municipio, "" AS Numero_Autorizacion, "" AS Fecha_Autorizacion, 
"" AS Numero_Prescripcion, "" AS Fecha_Formula,
"" as Fecha_Dispensacion, 
"" AS CIE, "Capita" AS Tipo, "Capita" as Tipo_Servicio, "" AS Cum,
"" AS Nombre_Producto,
"" AS Lote_Producto,
"" AS Costo_Producto,
DFC.Cantidad, DFC.Precio,  DFC.Total AS Subtotal, 0 AS Descuento, 0 AS Impuesto, FC.Cuota_Moderadora AS Cuota, C.Tipo_Valor
FROM
Descripcion_Factura_Capita DFC
INNER JOIN Factura_Capita FC ON DFC.Id_Factura_Capita = FC.Id_Factura_Capita
INNER JOIN Cliente C ON C.Id_Cliente = FC.Id_Cliente
INNER JOIN Funcionario F ON FC.Identificacion_Funcionario = F.Identificacion_Funcionario '.$condicion_capita.' ORDER BY FC.Id_Factura_Capita, DFC.Total DESC)';

echo $query; exit;
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

$objSheet->getCell('A1')->setValue("N Factura");
$objSheet->getCell('B1')->setValue("Fecha Factura");
$objSheet->getCell('C1')->setValue("Facturador");
$objSheet->getCell('D1')->setValue("N Dispensacion");
$objSheet->getCell('E1')->setValue("Nit Cliente");
$objSheet->getCell('F1')->setValue("Razon Social");
$objSheet->getCell('G1')->setValue("Tipo ID Paciente");
$objSheet->getCell('H1')->setValue("Id Paciente");
$objSheet->getCell('I1')->setValue("Primer Apellido");
$objSheet->getCell('J1')->setValue("Segundo Apellido");
$objSheet->getCell('K1')->setValue("Primer Nombre");
$objSheet->getCell('L1')->setValue("Segundo Nombre");
$objSheet->getCell('M1')->setValue("Fecha Nacimiento");
$objSheet->getCell('N1')->setValue("Edad Paciente");
$objSheet->getCell('O1')->setValue("Genero Paciente");
$objSheet->getCell('P1')->setValue("Regimen Paciente");
$objSheet->getCell('Q1')->setValue("EPS Paciente");
$objSheet->getCell('R1')->setValue("Cod Departamento");
$objSheet->getCell('S1')->setValue("Cod Ciudad");
$objSheet->getCell('T1')->setValue("Nom Ciudad");
$objSheet->getCell('U1')->setValue("N Autorizacion");
$objSheet->getCell('V1')->setValue("F Autorizacion");
$objSheet->getCell('W1')->setValue("N Prescripcion");
$objSheet->getCell('X1')->setValue("Fecha Formula");
$objSheet->getCell('Y1')->setValue("Fecha Dispensacion");
$objSheet->getCell('Z1')->setValue("Codigo DX");
$objSheet->getCell('AA1')->setValue("Tipo Servicio 1");
$objSheet->getCell('AB1')->setValue("Tipo Servicio 2");
$objSheet->getCell('AC1')->setValue("Codigo Cum");
$objSheet->getCell('AD1')->setValue("Nombre Producto");
$objSheet->getCell('AE1')->setValue("Lote");
$objSheet->getCell('AF1')->setValue("Costo");
$objSheet->getCell('AG1')->setValue("Cantidad");
$objSheet->getCell('AH1')->setValue("Precio Unitario");
$objSheet->getCell('AI1')->setValue("Subtotal");
$objSheet->getCell('AJ1')->setValue("Descuento");
$objSheet->getCell('AK1')->setValue("Iva");
$objSheet->getCell('AL1')->setValue("Cuota_Recuperacion");
$objSheet->getCell('AM1')->setValue("Total_Neto_Facturado");
$objSheet->getCell('AN1')->setValue("Tipo");


$objSheet->getStyle('A1:AN1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:AN1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:AN1')->getFont()->setBold(true);
$objSheet->getStyle('A1:AN1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
$cod='';

foreach($productos as $disp){ $j++;

	$decimales = 2;

	if ($disp['Tipo_Valor'] == 'Cerrada') {
		$decimales = 0;
	}

	$cuota = 0;
    
    if($cod!=$disp["Factura"]){
        $cod=$disp["Factura"];
        
        $rec = $disp["Cuota"] - $disp["Subtotal"];
        
        if($rec<=0){
            $cuota=$disp["Cuota"];
        }else{
            $cuota = $disp["Subtotal"];
        }
        $fin=$disp["Cuota"]-$cuota;
    }else{

        $rec = $fin - $disp["Subtotal"];
        
        if($rec<=0){
            $cuota=$fin;
        }else{
            $cuota = $disp["Subtotal"];
        }
        $fin=$fin-$cuota;
    }
	$total_descuento = $disp["Cantidad"]*$disp["Descuento"];
	$subtotal = $disp['Cantidad']*$disp['Precio'];
	$iva = ($subtotal-$total_descuento)*($disp["Impuesto"]/100);
    $final = $subtotal-$total_descuento-$cuota + $iva;
	$objSheet->getCell('A'.$j)->setValue($disp["Factura"]);
	$objSheet->getCell('B'.$j)->setValue(fecha($disp["Fecha_Factura"]));
	$objSheet->getCell('C'.$j)->setValue($disp["Funcionario_Facturador"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Dis"]);
	$objSheet->getCell('E'.$j)->setValue($disp["Id_Cliente"]);
	$objSheet->getCell('F'.$j)->setValue($disp["Razon_Social"]);
	$objSheet->getCell('G'.$j)->setValue($disp["Tipo_Documento"]);
	$objSheet->getCell('H'.$j)->setValue($disp["Id_Paciente"]);
	$objSheet->getCell('I'.$j)->setValue($disp["Primer_Apellido"]);
	$objSheet->getCell('J'.$j)->setValue($disp["Segundo_Apellido"]);
	$objSheet->getCell('K'.$j)->setValue($disp["Primer_Nombre"]);
	$objSheet->getCell('L'.$j)->setValue($disp["Segundo_Nombre"]);
	$objSheet->getCell('M'.$j)->setValue($disp["Fecha_Nacimiento"]);
	$objSheet->getCell('N'.$j)->setValue($disp["Edad"]);
	$objSheet->getCell('O'.$j)->setValue($disp["Genero"]);
	$objSheet->getCell('P'.$j)->setValue($disp["Regimen"]);
	$objSheet->getCell('Q'.$j)->setValue($disp["EPS"]);
	$objSheet->getCell('R'.$j)->setValue($disp["Cod_Departamento"]);
	$objSheet->getCell('S'.$j)->setValue($disp["Codigo_Municipio"]);
	$objSheet->getCell('T'.$j)->setValue($disp["Nombre_Municipio"]);
	$objSheet->getCell('U'.$j)->setValue($disp["Numero_Autorizacion"]);
	$objSheet->getCell('V'.$j)->setValue($disp["Fecha_Autorizacion"]);
	$objSheet->getCell('W'.$j)->setValue($disp["Numero_Prescipcion"]);
	$objSheet->getCell('X'.$j)->setValue($disp["Fecha_Formula"]);
	$objSheet->getCell('Y'.$j)->setValue(fecha($disp["Fecha_Dispensacion"]));
	$objSheet->getCell('Z'.$j)->setValue($disp["CIE"]);
	$objSheet->getCell('AA'.$j)->setValue($disp["Tipo"]);
	$objSheet->getCell('AB'.$j)->setValue($disp["Tipo_Servicio"]);
	$objSheet->getCell('AC'.$j)->setValue($disp["Cum"]); 
	$objSheet->getCell('AD'.$j)->setValue($disp["Nombre_Producto"]);
	$objSheet->getCell('AE'.$j)->setValue($disp["Lote_Producto"]);
	$objSheet->getCell('AF'.$j)->setValue($disp["Costo_Producto"] !== "" ? number_format($disp["Costo_Producto"],$decimales,".","") : "");
	$objSheet->getStyle('AF'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getCell('AG'.$j)->setValue($disp["Cantidad"]);
	$objSheet->getCell('AH'.$j)->setValue(number_format($disp["Precio"],$decimales,".",""));
	$objSheet->getStyle('AH'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getCell('AI'.$j)->setValue(number_format(($disp['Cantidad']*$disp['Precio']),$decimales,".",""));
	$objSheet->getStyle('AI'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$decimales_dcto = $decimales;
	if ($disp["Id_Cliente"] == 890500890) { // SI ES NORTE DE SANTANDER
		$decimales_dcto = 0;
	}
	$objSheet->getCell('AJ'.$j)->setValue(number_format(($disp["Descuento"]*$disp["Cantidad"]),$decimales_dcto,".",""));
	$objSheet->getStyle('AJ'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getCell('AK'.$j)->setValue(number_format($iva,$decimales,".",""));
	$objSheet->getStyle('AK'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getCell('AL'.$j)->setValue($cuota);
	$objSheet->getStyle('AL'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	$objSheet->getCell('AM'.$j)->setValue($final);
	$objSheet->getStyle('AM'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
	// $objSheet->getCell('AK'.$j)->setValue(number_format($final,$decimales,".",""));
	$objSheet->getCell('AN'.$j)->setValue($disp["Tipo_Factura"]);
	
}

$objSheet->getColumnDimension('AD')->setAutoSize(true);
$objSheet->getColumnDimension('AE')->setAutoSize(true);
$objSheet->getStyle('A1:AM'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function fecha($fecha) {
	return date('d/m/Y', strtotime($fecha));
}

?>
