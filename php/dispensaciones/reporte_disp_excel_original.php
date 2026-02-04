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
header('Content-Disposition: attachment;filename="Reporte_Dispensacion.xls"');
header('Cache-Control: max-age=0'); 

$objPHPExcel = new PHPExcel;

$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'WHERE DATE_FORMAT(D.Fecha_Actual, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	if ($condicion != "") {
		$condicion .= " AND DP.Id_Departamento=$_REQUEST[dep]";
	} else {
		$condicion .= "WHERE DP.Id_Departamento=$_REQUEST[dep]";
	}
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[pto]";
	} else {
		$condicion .= "WHERE D.Id_Punto_Dispensacion=$_REQUEST[pto]";
	}
}
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Identificacion_Funcionario=$_REQUEST[func]";
	} else {
		$condicion .= "WHERE D.Identificacion_Funcionario=$_REQUEST[func]";
	}
}
if (isset($_REQUEST['pac']) && $_REQUEST['pac'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Id_Paciente=$_REQUEST[pac]";
	} else {
		$condicion .= "WHERE D.Id_Paciente=$_REQUEST[pac]";
	}
}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
	if ($condicion != "") {
		if ($_REQUEST['tipo'] == 'Capita' || $_REQUEST['tipo'] == 'Evento') {
			
			$condicion .= " AND D.Tipo='$_REQUEST[tipo]'";
		} else {
			$condicion .= " AND Tipo_Servicio = $_REQUEST[tipo]";
		}
	} else {
		if ($_REQUEST['tipo'] == 'Capita' || $_REQUEST['tipo'] == 'Evento') {
			
			$condicion .= "WHERE D.Tipo='$_REQUEST[tipo]'";
		} else {
			$condicion .= "WHERE Tipo_Servicio = $_REQUEST[tipo]";
		}
	}
}

if (isset($_REQUEST['pend']) && $_REQUEST['pend'] == "No") {
	if ($condicion != "") {
		$condicion .= " AND D.Pendientes=0";
	} else {
		$condicion .= "WHERE D.Pendientes=0";
	}
} elseif(isset($_REQUEST['pend']) && $_REQUEST['pend'] == "Si")  {
	if ($condicion != "") {
		$condicion .= " AND D.Pendientes<>0";
	} else {
		$condicion .= "WHERE D.Pendientes<>0";
	}
}
if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Codigo='$_REQUEST[dis]'";
	} else {
		$condicion .= "WHERE D.Codigo='$_REQUEST[dis]'";
	}
}
if (isset($_REQUEST['cte']) && $_REQUEST['cte'] != "") {
	if ($condicion != "") {
		$condicion .= " AND PA.Nit='$_REQUEST[cte]'";
	} else {
		$condicion .= "WHERE PA.Nit='$_REQUEST[cte]'";
	}
}

$query = 'SELECT 
D.Codigo, 
D.Fecha_Actual, 
"" as Numero_Factura, 
"" as Fecha_Factura, 
PA.Nit as Identif_Tercero, 
PA.EPS as Nombre_Tercero, 
PD.Cum, 
P.Nombre_Comercial, 
CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad,P.Unidad_Medida) as Nombre, 
P.Embalaje, 
P.Laboratorio_Generico, 
P.Laboratorio_Comercial, 
PD.Cantidad_Formulada, 
PD.Cantidad_Entregada, 
(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente, 
IFNULL((SELECT SUM(PF.Subtotal) FROM Producto_Factura PF WHERE PF.Id_Factura=D.Id_Factura),0) as Costo, 
CONCAT_WS(" ",F.Nombres,F.Apellidos) as Funcionario_Digita, 
PDI.Nombre  as Punto_Dispensacion, 
PA.Tipo_Documento, 
PA.Id_Paciente, 
CONCAT_WS(" ",PA.Primer_Nombre, PA.Segundo_Nombre, PA.Primer_Apellido, PA.Segundo_Apellido) as Nombre_Paciente, 
PA.Genero as Genero_Paciente, 
(SELECT R.Nombre FROM Regimen R WHERE R.Id_Regimen = PA.Id_Regimen) as Regimen_Paciente,
DP.Codigo as Departamento, 
PA.Cod_Municipio_Dane as Ciudad, "" as Codigo_DX,
 D.Tipo, 
 D.Doctor, 
 D.IPS, 
 PA.EPS as EPS_Paciente, 
 PD.Numero_Autorizacion, 
 PD.Fecha_Autorizacion, 
 PD.Numero_Prescripcion, 
 D.Fecha_Formula, 
 D.Fecha_Actual as Fecha_Entrega, 
 D.Cuota as Cuota_Moderadora, 
 "" as Cuota_Recuperacion, 
 D.Causal_No_Pago, 
 D.Estado_Dispensacion
FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D
ON PD.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Producto P
On P.Id_Producto = PD.Id_Producto
INNER JOIN (SELECT Nombres, Apellidos, Identificacion_Funcionario FROM Funcionario) F 
ON F.Identificacion_Funcionario = D.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion PDI
ON PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN (SELECT Id_Paciente, Nit, EPS, Tipo_Documento, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Genero, Id_Regimen, Cod_Municipio_Dane FROM Paciente) PA
ON PA.Id_Paciente = D.Numero_Documento
INNER JOIN Departamento DP
ON PDI.Departamento=DP.Id_Departamento
'.$condicion;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);



$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Dispensacion');

$objSheet->getCell('A1')->setValue("Codigo");
$objSheet->getCell('B1')->setValue("Fecha_Actual");
$objSheet->getCell('C1')->setValue("Numero_Factura");
$objSheet->getCell('D1')->setValue("Fecha_Factura");
$objSheet->getCell('E1')->setValue("Identif_Tercero");
$objSheet->getCell('F1')->setValue("Nombre_Tercero");
$objSheet->getCell('G1')->setValue("Cum");
$objSheet->getCell('H1')->setValue("Nombre_Comercial");
$objSheet->getCell('I1')->setValue("Nombre");
$objSheet->getCell('J1')->setValue("Embalaje");
$objSheet->getCell('K1')->setValue("Laboratorio_Generico");
$objSheet->getCell('L1')->setValue("Laboratorio_Comercial");
$objSheet->getCell('M1')->setValue("Cantidad_Formulada");
$objSheet->getCell('N1')->setValue("Cantidad_Entregada");
$objSheet->getCell('O1')->setValue("Cantidad_Pendiente");
$objSheet->getCell('P1')->setValue("Costo");
$objSheet->getCell('Q1')->setValue("Funcionario_Digita");
$objSheet->getCell('R1')->setValue("Punto_Dispensacion");
$objSheet->getCell('S1')->setValue("Tipo_Documento");
$objSheet->getCell('T1')->setValue("Id_Paciente");
$objSheet->getCell('U1')->setValue("Nombre_Paciente");
$objSheet->getCell('V1')->setValue("Genero_Paciente");
$objSheet->getCell('W1')->setValue("Regimen_Paciente");
$objSheet->getCell('X1')->setValue("Departamento");
$objSheet->getCell('Y1')->setValue("Ciudad");
$objSheet->getCell('Z1')->setValue("Codigo_DX");
$objSheet->getCell('AA1')->setValue("Tipo");
$objSheet->getCell('AB1')->setValue("Doctor");
$objSheet->getCell('AC1')->setValue("IPS");
$objSheet->getCell('AD1')->setValue("EPS_Paciente");
$objSheet->getCell('AE1')->setValue("Numero_Autorizacion");
$objSheet->getCell('AF1')->setValue("Fecha_Autorizacion");
$objSheet->getCell('AG1')->setValue("Numero_Prescripcion");
$objSheet->getCell('AH1')->setValue("Fecha_Formula");
$objSheet->getCell('AI1')->setValue("Fecha_Entrega");
$objSheet->getCell('AJ1')->setValue("Cuota_Moderadora");
$objSheet->getCell('AK1')->setValue("Cuota_Recuperacion");
$objSheet->getCell('AL1')->setValue("Causal_No_Pago");
$objSheet->getCell('AM1')->setValue("Estado_Dispensacion");
$objSheet->getStyle('A1:AM1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:AM1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:AM1')->getFont()->setBold(true);
$objSheet->getStyle('A1:AM1')->getFont()->getColor()->setARGB('FFFFFFFF');


$j=1;
foreach($dispensaciones as $disp){ $j++;
	$objSheet->getCell('A'.$j)->setValue($disp["Codigo"]);
	$objSheet->getCell('B'.$j)->setValue($disp["Fecha_Actual"]);
	$objSheet->getCell('C'.$j)->setValue($disp["Numero_Factura"]);
	$objSheet->getCell('D'.$j)->setValue($disp["Fecha_Factura"]);
	$objSheet->getCell('E'.$j)->setValue($disp["Identif_Tercero"]);
	$objSheet->getCell('F'.$j)->setValue($disp["Nombre_Tercero"]);
	$objSheet->getCell('G'.$j)->setValue($disp["Cum"]);
	$objSheet->getCell('H'.$j)->setValue($disp["Nombre_Comercial"]);
	$objSheet->getCell('I'.$j)->setValue($disp["Nombre"]);
	$objSheet->getCell('J'.$j)->setValue($disp["Embalaje"]);
	$objSheet->getCell('K'.$j)->setValue($disp["Laboratorio_Generico"]);
	$objSheet->getCell('L'.$j)->setValue($disp["Laboratorio_Comercial"]);
	$objSheet->getCell('M'.$j)->setValue($disp["Cantidad_Formulada"]);
	$objSheet->getCell('N'.$j)->setValue($disp["Cantidad_Entregada"]);
	$objSheet->getCell('O'.$j)->setValue($disp["Cantidad_Pendiente"]);
	$objSheet->getCell('P'.$j)->setValue($disp["Costo"]);
	$objSheet->getCell('Q'.$j)->setValue($disp["Funcionario_Digita"]);
	$objSheet->getCell('R'.$j)->setValue($disp["Punto_Dispensacion"]);
	$objSheet->getCell('S'.$j)->setValue($disp["Tipo_Documento"]);
	$objSheet->getCell('T'.$j)->setValue($disp["Id_Paciente"]);
	$objSheet->getCell('U'.$j)->setValue($disp["Nombre_Paciente"]);
	$objSheet->getCell('V'.$j)->setValue($disp["Genero_Paciente"]);
	$objSheet->getCell('W'.$j)->setValue($disp["Regimen_Paciente"]);
	$objSheet->getCell('X'.$j)->setValue($disp["Departamento"]);
	$objSheet->getCell('Y'.$j)->setValue($disp["Ciudad"]);
	$objSheet->getCell('Z'.$j)->setValue($disp["Codigo_DX"]);
	$objSheet->getCell('AA'.$j)->setValue($disp["Tipo"]);
	$objSheet->getCell('AB'.$j)->setValue($disp["Doctor"]);
	$objSheet->getCell('AC'.$j)->setValue($disp["IPS"]);
	$objSheet->getCell('AD'.$j)->setValue($disp["EPS_Paciente"]);
	$objSheet->getCell('AE'.$j)->setValue($disp["Numero_Autorizacion"]);
	$objSheet->getCell('AF'.$j)->setValue($disp["Fecha_Autorizacion"]);
	$objSheet->getCell('AG'.$j)->setValue($disp["Numero_Prescripcion"]);
	$objSheet->getCell('AH'.$j)->setValue($disp["Fecha_Formula"]);
	$objSheet->getCell('AI'.$j)->setValue($disp["Fecha_Entrega"]);
	$objSheet->getCell('AJ'.$j)->setValue($disp["Cuota_Moderadora"]);
	$objSheet->getCell('AK'.$j)->setValue($disp["Cuota_Recuperacion"]);
	$objSheet->getCell('AL'.$j)->setValue($disp["Causal_No_Pago"]);
	$objSheet->getCell('AM'.$j)->setValue($disp["Estado_Dispensacion"]);
}
$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getStyle('A1:AM'.$j)->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');



?>