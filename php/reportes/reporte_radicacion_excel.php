<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.querybasedatos.php');

$queryObj = new QueryBaseDatos();
$http_response = new HttpResponse();
$response = array();

$condiciones_capita = '';
$condiciones = SetConditions($_REQUEST);


$radicaciones = GetRadicaciones($condiciones, $condiciones_capita);

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Radicacion.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Radicaciones');

$objSheet->getCell('A1')->setValue("Fecha PreRadicado");
$objSheet->getCell('B1')->setValue("Código Radicación");
$objSheet->getCell('C1')->setValue("Código Factura");
$objSheet->getCell('D1')->setValue("Fecha Factura");
$objSheet->getCell('E1')->setValue("Código Dispensación");
$objSheet->getCell('F1')->setValue("Cliente");
$objSheet->getCell('G1')->setValue("Identificación Cliente");
$objSheet->getCell('H1')->setValue("Funcionario");
$objSheet->getCell('I1')->setValue("Identificación Funcionario");
$objSheet->getCell('J1')->setValue("Paciente");
$objSheet->getCell('K1')->setValue("Identificación Paciente");
$objSheet->getCell('L1')->setValue("Consecutivo Radicación");
$objSheet->getCell('M1')->setValue("Num. Radicado");
$objSheet->getCell('N1')->setValue("Fecha Radicación");
$objSheet->getCell('O1')->setValue("Fecha Cierre Rad.");
$objSheet->getCell('P1')->setValue("Tipo de Servicio");
$objSheet->getCell('Q1')->setValue("Estado Rad.");
$objSheet->getCell('R1')->setValue("Valor Factura");
$objSheet->getCell('S1')->setValue("Valor Glosado");
$objSheet->getCell('T1')->setValue("Valor Total");
$objSheet->getCell('U1')->setValue("Estado Factura");
$objSheet->getCell('V1')->setValue("Régimen");

$objSheet->getStyle('A1:V1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:V1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:V1')->getFont()->setBold(true);
$objSheet->getStyle('A1:V1')->getFont()->getColor()->setARGB('FFFFFFFF');

$j=2;
$i=0;

foreach($radicaciones as $r){ 
    $valor_total=$r['Valor_Factura']-$r['Total_Glosado'];
    $objSheet->getCell('A'.$j)->setValue($r['Fecha_PreRadicado']);
	$objSheet->getCell('B'.$j)->setValue($r['Codigo']);
	$objSheet->getCell('C'.$j)->setValue($r['Codigo_Factura']);
	$objSheet->getCell('D'.$j)->setValue($r['Fecha_Factura']);
	$objSheet->getCell('E'.$j)->setValue($r['Codigo_Dis']);
	$objSheet->getCell('F'.$j)->setValue($r['Nombre_Cliente']);
	$objSheet->getCell('G'.$j)->setValue(number_format($r['Id_Cliente'],0,"","."));
	$objSheet->getCell('H'.$j)->setValue($r['Nombre_Funcionario']);
	$objSheet->getCell('I'.$j)->setValue(number_format($r['Id_Funcionario'],0,"","."));
	$objSheet->getCell('J'.$j)->setValue($r['Nombre_Paciente']);
	$objSheet->getCell('K'.$j)->setValue(number_format($r['Numero_Documento'],0,"","."));
    $objSheet->getCell('L'.$j)->setValue($r['Consecutivo']);
	$objSheet->getCell('M'.$j)->setValue($r['Numero_Radicado']);
	$objSheet->getCell('N'.$j)->setValue($r['Fecha_Radicado']);
	$objSheet->getCell('O'.$j)->setValue($r['Fecha_Cierre']);
    $objSheet->getCell('P'.$j)->setValue($r['Tipo_Servicio']);
    $objSheet->getCell('Q'.$j)->setValue($r['Estado']);
    $objSheet->getCell('R'.$j)->setValue($r['Valor_Factura']);
    $objSheet->getCell('S'.$j)->setValue($r['Total_Glosado']);
    $objSheet->getCell('T'.$j)->setValue($valor_total);
    $objSheet->getCell('U'.$j)->setValue($r['Estado_Factura_Radicacion']);
    $objSheet->getCell('V'.$j)->setValue($r['Nombre_Regimen']);
    
    $objSheet->getStyle('R'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $objSheet->getStyle('S'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $objSheet->getStyle('T'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    

	$j++;$i++;
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
$objSheet->getColumnDimension('K')->setAutoSize(true);
$objSheet->getColumnDimension('L')->setAutoSize(true);
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(true);
$objSheet->getColumnDimension('P')->setAutoSize(true);
$objSheet->getColumnDimension('Q')->setAutoSize(true);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getColumnDimension('S')->setAutoSize(true);
$objSheet->getColumnDimension('T')->setAutoSize(true);
$objSheet->getColumnDimension('U')->setAutoSize(true);
$objSheet->getColumnDimension('V')->setAutoSize(true);
$objSheet->getStyle('A1:V'.$j)->getAlignment()->setWrapText(true);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function SetConditions($req){
    global $condiciones_capita;
    $condicion = '';

    if (isset($req['tipo_servicio']) && $req['tipo_servicio'] != '') {
        // if ($condicion != "") {
        //     if (strtolower($req['tipo_servicio']) == 'evento' || strtolower($req['tipo_servicio']) == 'capita') {
        //         $condicion .= " AND F.Tipo = '".$req['tipo_servicio']."'";
        //     }else{

        //         $condicion .= " AND F.Tipo_Servicio = (SELECT Id_Tipo_Servicio FROM Tipo_Servicio WHERE LOWER(Nombre) = ".strtolower($req['tipo_servicio']).")";
        //     }
        // } else {
        //     if (strtolower($req['tipo_servicio']) == 'evento' || strtolower($req['tipo_servicio']) == 'capita') {
        //         $condicion .= " WHERE F.Tipo = '".$req['tipo_servicio']."'";
        //     }else{

        //         $condicion .= " WHERE F.Tipo_Servicio = (SELECT Id_Tipo_Servicio FROM Tipo_Servicio WHERE LOWER(Nombre) = ".strtolower($req['tipo_servicio']).")";
        //     }
        // }

        if ($condiciones_capita != "") {
            $condiciones_capita .= " AND R.Id_Tipo_Servicio = ".$req['tipo_servicio'];
        } else {
            $condiciones_capita .= " WHERE R.Id_Tipo_Servicio = ".$req['tipo_servicio'];
        }

        if ($condicion != "") {
            $condicion .= " AND R.Id_Tipo_Servicio = ".$req['tipo_servicio'];
        } else {
            $condicion .= " WHERE R.Id_Tipo_Servicio = ".$req['tipo_servicio'];
        }
    }

    if (isset($req['id_funcionario']) && $req['id_funcionario']) {
        if ($condiciones_capita != "") {
            $condiciones_capita .= " AND R.Id_Funcionario = ".$req['id_funcionario'];
        } else {
            $condiciones_capita .= " WHERE R.Id_Funcionario = ".$req['id_funcionario'];
        }

        if ($condicion != "") {
            $condicion .= " AND R.Id_Funcionario = ".$req['id_funcionario'];
        } else {
            $condicion .= " WHERE R.Id_Funcionario = ".$req['id_funcionario'];
        }
    }

    if (isset($req['id_cliente']) && $req['id_cliente']) {
        if ($condiciones_capita != "") {
            $condiciones_capita .= " AND R.Id_Cliente = ".$req['id_cliente'];
        } else {
            $condiciones_capita .= " WHERE R.Id_Cliente = ".$req['id_cliente'];
        }

        if ($condicion != "") {
            $condicion .= " AND R.Id_Cliente = ".$req['id_cliente'];
        } else {
            $condicion .= " WHERE R.Id_Cliente = ".$req['id_cliente'];
        }
    }

    if (isset($req['id_paciente']) && $req['id_paciente']) {
        if ($condicion != "") {
            $condicion .= " AND DIS.Numero_Documento = ".$req['id_paciente'];
        } else {
            $condicion .= " WHERE DIS.Numero_Documento = ".$req['id_paciente'];
        }
    }

    if (isset($req['id_departamento']) && $req['id_departamento']) {
        if ($condiciones_capita != "") {
            $condiciones_capita .= " AND R.Id_Departamento = ".$req['id_departamento'];
        } else {
            $condiciones_capita .= " WHERE R.Id_Departamento = ".$req['id_departamento'];
        }

        if ($condicion != "") {
            $condicion .= " AND R.Id_Departamento = ".$req['id_departamento'];
        } else {
            $condicion .= " WHERE R.Id_Departamento = ".$req['id_departamento'];
        }
    }

    if (isset($req['id_regimen']) && $req['id_regimen']) {
        if ($condiciones_capita != "") {
            $condiciones_capita .= " AND R.Id_Regimen = ".$req['id_regimen'];
        } else {
            $condiciones_capita .= " WHERE R.Id_Regimen = ".$req['id_regimen'];
        }

        if ($condicion != "") {
            $condicion .= " AND R.Id_Regimen = ".$req['id_regimen'];
        } else {
            $condicion .= " WHERE R.Id_Regimen = ".$req['id_regimen'];
        }
    }

    if (isset($req['fechas']) && $req['fechas']) {
        $fechas = SepararFechas($req['fechas']);

        if ($condiciones_capita != "") {
            $condiciones_capita .= " AND ((DATE(R.Fecha_Registro) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."') OR (DATE(R.Fecha_Radicado) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."')) ";
        } else {
            $condiciones_capita .= " WHERE ((DATE(R.Fecha_Registro) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."') OR (DATE(R.Fecha_Radicado) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."')) ";
        }

        if ($condicion != "") {
            $condicion .= " AND ((DATE(R.Fecha_Registro) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."') OR (DATE(R.Fecha_Radicado) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."')) ";
        } else {
            $condicion .= " WHERE ((DATE(R.Fecha_Registro) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."') OR (DATE(R.Fecha_Radicado) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."')) ";
        }
    }

    if (isset($req['estado']) && $req['estado']) {
        if ($condiciones_capita != "") {
            $condiciones_capita .= " AND R.Estado = '".$req['estado']."'";
        } else {
            $condiciones_capita .= " WHERE R.Estado = '".$req['estado']."'";
        }

        if ($condicion != "") {
            $condicion .= " AND R.Estado = '".$req['estado']."'";
        } else {
            $condicion .= " WHERE R.Estado = '".$req['estado']."'";
        }
    }

    return $condicion;
}

function SepararFechas($fechas){
    $fechas_separadas = explode(" - ", $fechas);
    return $fechas_separadas;
}

function GetRadicaciones($condiciones, $condicionesCapita){
    global $queryObj;

    $query_radicaciones = '
        SELECT
            IFNULL(R.Consecutivo, "-") AS Consecutivo,
            IFNULL(R.Numero_Radicado, "-") AS Numero_Radicado,
            R.Id_Funcionario,
            IFNULL(R.Fecha_Radicado, "-") AS Fecha_Radicado,
            IFNULL(R.Fecha_Cierre, "Abierta") AS Fecha_Cierre,
            R.Id_Cliente,
            R.Fecha_Registro AS Fecha_PreRadicado,
            R.Tipo_Servicio,
            R.Codigo,
            R.Observacion,
            R.Estado,
            C.Nombre AS Nombre_Cliente,
            (CASE
                WHEN R.Id_Departamento = 0 THEN "Todos"
                ELSE D.Nombre
             END) AS Nombre_Departamento,
            RE.Nombre AS Nombre_Regimen,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            FAC.Codigo AS Codigo_Factura,
            FAC.Fecha_Documento AS Fecha_Factura,
            DIS.Codigo AS Codigo_Dis,
            UPPER(CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido)) AS Nombre_Paciente,
            (
                CASE
                    WHEN C.Tipo_Valor = "Exacta" THEN (SELECT SUM( ((Precio * Cantidad)+((Precio * Cantidad - IF(FAC.Id_Cliente = 890500890,FLOOR(Descuento*Cantidad), (Descuento*Cantidad)) ) * (Impuesto/100) )) - (IF(FAC.Id_Cliente = 890500890, FLOOR(Descuento* Cantidad), Descuento* Cantidad))) - FAC.Cuota FROM Producto_Factura WHERE Id_Factura = FAC.Id_Factura)
                    ELSE (SELECT ROUND(SUM( ((ROUND(Precio) * Cantidad)+((ROUND(Precio) * Cantidad- ROUND((Descuento*Cantidad))) * (Impuesto/100) )) - ROUND((Descuento*Cantidad)))) - FAC.Cuota FROM Producto_Factura WHERE Id_Factura = FAC.Id_Factura)
                END
            ) AS Valor_Factura,
            RF.Estado_Factura_Radicacion,
            IFNULL(RF.Observacion, "") AS Observacion_Radicado_Factura,
            IFNULL(RF.Total_Glosado, 0) AS Total_Glosado,
           
            DIS.Numero_Documento
        FROM Radicado R
        INNER JOIN Cliente C ON R.Id_Cliente = C.Id_Cliente
        LEFT JOIN Departamento D ON R.Id_Departamento = D.Id_Departamento
        INNER JOIN Regimen RE ON R.Id_Regimen = RE.Id_Regimen
        INNER JOIN Funcionario F ON R.Id_Funcionario = F.Identificacion_Funcionario
        INNER JOIN Radicado_Factura RF ON R.Id_Radicado = RF.Id_Radicado
        INNER JOIN Factura FAC ON RF.Id_Factura = FAC.Id_Factura
        INNER JOIN Dispensacion DIS ON FAC.Id_Dispensacion = DIS.Id_Dispensacion
        INNER JOIN Paciente P ON DIS.Numero_Documento = P.Id_Paciente
        '.$condiciones.' AND R.Tipo_Servicio != "CAPITA"'
        .' UNION ALL

        ( SELECT
            IFNULL(R.Consecutivo, "-") AS Consecutivo,
            IFNULL(R.Numero_Radicado, "-") AS Numero_Radicado,
            R.Id_Funcionario,
            IFNULL(R.Fecha_Radicado, "-") AS Fecha_Radicado,
            IFNULL(R.Fecha_Cierre, "Abierta") AS Fecha_Cierre,
            R.Id_Cliente,
            R.Fecha_Registro AS Fecha_PreRadicado,
            R.Tipo_Servicio,
            R.Codigo,
            R.Observacion,
            R.Estado,
            C.Nombre AS Nombre_Cliente,
            (CASE
                WHEN R.Id_Departamento = 0 THEN "Todos"
                ELSE D.Nombre
             END) AS Nombre_Departamento,
            RE.Nombre AS Nombre_Regimen,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            FAC.Codigo AS Codigo_Factura,
            FAC.Fecha_Documento AS Fecha_Factura,
            FAC.Codigo AS Codigo_Dis,
            IFNULL(DFC.Descripcion, "") AS Nombre_Paciente,
            (SUM(DFC.Total) - FAC.Cuota_Moderadora) AS Valor_Factura,
            RF.Estado_Factura_Radicacion,
            IFNULL(RF.Observacion, "") AS Observacion_Radicado_Factura,
            IFNULL(RF.Total_Glosado, 0) AS Total_Glosado,           
            "0" AS Numero_Documento
        FROM Radicado R
        INNER JOIN Cliente C ON R.Id_Cliente = C.Id_Cliente
        LEFT JOIN Departamento D ON R.Id_Departamento = D.Id_Departamento
        INNER JOIN Regimen RE ON R.Id_Regimen = RE.Id_Regimen
        INNER JOIN Funcionario F ON R.Id_Funcionario = F.Identificacion_Funcionario
        INNER JOIN Radicado_Factura RF ON R.Id_Radicado = RF.Id_Radicado
        INNER JOIN Factura_Capita FAC ON RF.Id_Factura = FAC.Id_Factura_Capita
        INNER JOIN Descripcion_Factura_Capita DFC ON FAC.Id_Factura_Capita = DFC.Id_Factura_Capita
        '.$condicionesCapita.' AND R.Tipo_Servicio = "CAPITA"  GROUP BY DFC.Id_Factura_Capita)';

    
    $queryObj->SetQuery($query_radicaciones);
    $radicaciones = $queryObj->ExecuteQuery('multiple');

    return $radicaciones;
}

?>