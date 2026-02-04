<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';
$fecha_inicio = '';
$fecha_fin = '';

/*require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';*/

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Caja.xls"');
header('Cache-Control: max-age=0');

//$objPHPExcel = new PHPExcel;

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'WHERE DATE_FORMAT(DC.Fecha, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Id_Departamento=$_REQUEST[dep]";
	} else {
		$condicion .= "WHERE D.Id_Departamento=$_REQUEST[dep]";
	}
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	if ($condicion != "") {
		$condicion .= " AND DC.Id_Punto_Dispensacion=$_REQUEST[pto]";
	} else {
		$condicion .= "WHERE DC.Id_Punto_Dispensacion=$_REQUEST[pto]";
	}
}
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	if ($condicion != "") {
		$condicion .= " AND DP.Identificacion_Funcionario=$_REQUEST[func]";
	} else {
		$condicion .= "WHERE DP.Identificacion_Funcionario=$_REQUEST[func]";
	}
}

$query=" SELECT DC.Cuota_Ingresada,DP.*, CONCAT(F.Nombres,' ', F.Apellidos) as Funcionario, D.Nombre as Departamento, PD.Nombre as Punto, (SELECT CONCAT_WS(' ',P.Primer_Nombre,P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) FROM Paciente P WHERE P.Id_Paciente=DP.Numero_Documento) AS Paciente, (SELECT R.Nombre FROM Paciente P INNER JOIN Regimen R ON P.Id_Regimen=R.Id_Regimen WHERE P.Id_Paciente=DP.Numero_Documento) AS Regimen, (SELECT N.Nombre FROM Paciente P INNER JOIN Nivel N ON P.Id_Nivel=N.Id_Nivel WHERE P.Id_Paciente=DP.Numero_Documento) as Nivel
FROM Dispensacion DP
INNER JOIN Diario_Cajas_Dispensacion DC
ON DP.Id_Diario_Cajas_Dispensacion=DC.Id_Diario_Cajas_Dispensacion
INNER JOIN Funcionario F
ON DC.Identificacion_Funcionario=F.Identificacion_Funcionario 
INNER JOIN Punto_Dispensacion PD 
ON DC.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
INNER JOIN Departamento D 
ON PD.Departamento=D.Id_Departamento ".$condicion."
AND DP.Estado_Dispensacion != 'Anulada' 
ORDER BY DP.Id_Diario_Cajas_Dispensacion";


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);

/*$border_style= array('borders' => array('bottom' => array('style' => 
PHPExcel_Style_Border::BORDER_THICK,'color' => array('argb' => 'FFFFFFFF'),)));

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Cajas ');*/
$contenido = '<table border="1" style="border-collapse: collapse">
<tr>
	<td colspan="9"><strong>'.fecha($fecha_inicio).' - '.fecha($fecha_fin).'</strong></td>
</tr>
<tr>';
$contenido .= '<td>Codigo</td>';
$contenido .= '<td>Fecha</td>';
$contenido .= '<td>Identificacion_Paciente</td>';
$contenido .= '<td>Paciente</td>';
$contenido .= '<td>Nivel</td>';
$contenido .= '<td>Regimen </td>';
$contenido .= '<td>Cuota</td>';
$contenido .= '<td>Punto</td>';
$contenido .= '<td>Cuota_Ingresada</td>';

$contenido .= '</tr>';


$products = [];

foreach ($dispensaciones as $i => $value) {
			
	if (array_key_exists($value["Id_Diario_Cajas_Dispensacion"], $products)) {
		$array = [
		
			"Codigo" => $value["Codigo"], 
			"Fecha_Actual" => $value["Fecha_Actual"], 
			"Numero_Documento" => $value["Numero_Documento"],
			"Paciente" => $value["Paciente"],
			"Nivel" => $value["Nivel"],
			"Regimen" => $value["Regimen"],
			"Cuota"=>$value["Cuota"],
			"Punto"=>$value["Punto"],
			"Cuota_Ingresada"=>$value["Cuota_Ingresada"]
		];
		array_push($products[$value["Id_Diario_Cajas_Dispensacion"]], $array);
	} else {
		$products[$value["Id_Diario_Cajas_Dispensacion"]] = [
			[
			
				"Codigo" => $value["Codigo"], 
				"Fecha_Actual" => $value["Fecha_Actual"], 
				"Numero_Documento" => $value["Numero_Documento"],
				"Paciente" => $value["Paciente"],
				"Nivel" => $value["Nivel"],
				"Regimen" => $value["Regimen"],
				"Cuota"=>$value["Cuota"],
				"Punto"=>$value["Punto"],
				"Cuota_Ingresada"=>$value["Cuota_Ingresada"]
			]
		];
	}
}
foreach ($products as $categoria => $prod) {
	//$contenido .= '<tr>';
	foreach ($prod as $i => $value) {
		$contenido .= '<tr>';
		$contenido .= '<td>--'.($i+1).' -- '. $value['Codigo'] .'</td>';
		$contenido .= '<td>'. fecha($value['Fecha_Actual']) .'</td>';
		$contenido .= '<td>'. $value['Numero_Documento'] .'</td>';
		$contenido .= '<td>'. $value['Paciente'] .'</td>';
		$contenido .= '<td>'. $value['Nivel'] .'</td>';
		$contenido .= '<td>'. $value["Regimen"] .'</td>';
		$contenido .= '<td>'. $value["Cuota"] .'</td>';
		$contenido .= '<td>'. $value["Punto"] .'</td>';
		if ($i == 0) {
			$contenido.='<td valign="middle" style="vertical-align:middle;background-color:#f3f3f3;border:1px solid #cccccc;text-align:center;" rowspan="'.count($prod).'">'. $value["Cuota_Ingresada"] . '</td>  </tr>';
			
		}else{
			$contenido .= '</tr>';
		}
	}


	
}

$contenido .= '</table>';

echo $contenido;
    
function fecha($fecha) {
	return date('d/m/Y', strtotime($fecha));
}

?>