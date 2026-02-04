<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

date_default_timezone_set("America/Bogota");

$condicion = '';

if (isset($_REQUEST['id']) && $_REQUEST['id'] != "") {
    $condicion .= " HAVING Id_Propharmacy=$_REQUEST[id] ";
}

require $MY_CLASS . 'PHPExcel.php';
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

$objPHPExcel = new PHPExcel;

$query =
    "                   SELECT
                                Dis.*,
                                IFNULL(Asoc.Cantidad_Disponible,0) AS Cantidad_Propharmacy,
                                CONCAT('[', Group_concat(Concat('{', Similar.Cantidad, ',', Similar.Producto, '}')), ']') AS Similares -- Se arma el array de similares
                            FROM (
                                SELECT 
                                        SUM(D.Cantidad) AS Cantidad_Formulada,
                                        GROUP_CONCAT(D.Punto) AS Dispensaciones,
                                        concat( MIN(DATE(Act.Fecha)), ' hasta ',MAX(DATE(Act.Fecha)) ) AS Rango_Auditoria,
                                        Act.Observacion AS Observacion_Auditoria,
                                        D.*
                                        FROM (
                                                    SELECT IFNULL(CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida),
                                                    CONCAT(P.Nombre_Comercial,' ',P.Laboratorio_Comercial)) AS Nombre_Producto, 
                                                    (PD.Cantidad_Formulada - Cantidad_Entregada) AS Cantidad,
                                                    IFNULL((SELECT IFNULL(SUM(INU.Cantidad-(INU.Cantidad_Apartada+INU.Cantidad_Seleccionada)), 0) FROM Inventario_Nuevo INU INNER JOIN Estiba E ON E.Id_Estiba = INU.Id_Estiba WHERE INU.Id_Producto = PD.Id_Producto AND E.Id_Punto_Dispensacion = PDI.Id_Punto_Dispensacion GROUP BY INU.Id_Producto), 0) AS Cantidad_Inventario,
                                                    P.Nombre_Comercial,
                                                    PD.Id_Producto_Dispensacion,
                                                    P.Laboratorio_Comercial,
                                                    P.Id_Producto,
                                                    D.Paciente,
                                                    D.Observaciones,
                                                    D.Fecha_Formula AS Fecha,
                                                    D.Numero_Documento,
                                                    PDI.Nombre AS NombrePunto,
                                                    PDI.Id_Punto_Dispensacion,
                                                    P.Codigo_Cum AS Cum,
                                                    IF(PD.Generico =1, 'Generico', NULL) AS Generico,
                                                    DEP.Nombre AS Departamento,
                                                    MUN.Nombre AS Municipio,
                                                    D.Codigo AS Punto,
                                                    A.Id_Auditoria,
                                                    ifnull(PTO.Id_Punto_Dispensacion, PDI.Id_Punto_Dispensacion) AS Id_Propharmacy,
                                                    IFNULL(PTO.Nombre, PDI.Nombre) AS Nombre_Propharmacy
                                                    FROM Producto_Dispensacion PD
                                                    INNER JOIN Dispensacion D ON D.Id_Dispensacion=PD.Id_Dispensacion
                                                    INNER JOIN Auditoria A ON (A.Id_Dispensacion = D.Id_Dispensacion AND A.Estado LIKE 'Aceptar')
                                                    INNER JOIN Punto_Dispensacion PDI ON PDI.Id_Punto_Dispensacion=D.Id_Punto_Dispensacion
                                                    INNER JOIN Departamento DEP ON DEP.Id_Departamento = PDI.Departamento
                                                    INNER JOIN Municipio MUN ON MUN.Id_Municipio = PDI.Municipio
                                                    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto

                                                    LEFT JOIN Punto_Dispensacion PTO ON PTO.Id_Punto_Dispensacion= PDI.Id_Propharmacy

                                                    WHERE 
                                                    -- DATE(D.Fecha_Actual) > '2021-08-01' AND#Se coloca la fa fecha 2021-08-01 porque en esta fecha comienza contrato positiva
                                                     PDI.Estado = 'Activo' AND PD.Id_Producto IS NOT NULL AND D.Estado_Dispensacion != 'Anulada'
                                                    AND ((PD.Cantidad_Formulada - Cantidad_Entregada) != 0)
                                                 $condicion
                                            )D
                                            INNER Join (SELECT Act.Observacion, Act.Id_Auditoria, Act.Fecha FROM Actividad_Auditoria Act WHERE Act.Detalle LIKE '%correcta%' ORDER BY Act.Id_Actividad_Auditoria DESC)Act on Act.Id_Auditoria=D.Id_Auditoria
                                            Where date(Act.Fecha) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND  DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                                            GROUP BY D.Id_Producto, D.Id_Propharmacy -- , date(Act.Fecha)
                                            HAVING Cantidad_Formulada>Cantidad_Inventario

                                ) Dis
                                -- Cantidad disponible del mismo producto en el propharmacy
                                LEFT JOIN (
                                        SELECT SUM(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad_Disponible,I.Id_Producto, PDI.Id_Punto_Dispensacion AS Id_Punto FROM Punto_Dispensacion PDI INNER JOIN Estiba E ON E.Id_Punto_Dispensacion =PDI.Id_Punto_Dispensacion
                                        INNER JOIN Inventario_Nuevo I ON E.Id_Estiba=I.Id_Estiba
                                        GROUP BY I.Id_Producto, PDI.Id_Punto_Dispensacion
                                    ) Asoc ON Dis.Id_Propharmacy=Asoc.Id_Punto AND Asoc.Id_Producto=Dis.Id_Producto

                                -- Consulta para relacionar los productos similares
                                LEFT JOIN(
                                        SELECT CONCAT('-', REPLACE(REPLACE(PA.Producto_Asociado, ',', '-,-'), ' ', ''), '-') as Producto_Asociado
                                        FROM Producto_Asociado  PA
                                    )PA ON PA.Producto_Asociado LIKE CONCAT('%-',Dis.Id_Producto,'-%')

                                -- Se buscan las cantidades disponibles de los productos SIMILARES, en el propharmacy
                                LEFT JOIN (
                                                SELECT CONCAT('\"Cantidad\":\"', SUM(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada), '\"') AS Cantidad,
                                                CONCAT('\"Similar\":\"', P.Nombre_Comercial, ' (', P.Codigo_Cum,')\"') AS Producto,
                                                I.Id_Producto, E.Id_Punto_Dispensacion
                                                FROM Inventario_Nuevo I
                                                INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
                                                INNER JOIN Producto P ON P.Id_Producto =I.Id_Producto
                                                Where (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0
                                                GROUP BY I.Id_Producto, E.Id_Punto_Dispensacion
                                    )Similar ON  PA.Producto_Asociado LIKE CONCAT('%-',Similar.Id_Producto,'-%') and Similar.Id_Punto_Dispensacion=Dis.Id_Propharmacy

                                GROUP BY Dis.Id_Producto_Dispensacion
                                ORDER BY Nombre_Propharmacy ASC";

header("content-type:application/json");
// echo $query; exit;
$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();


unset($oCon);
$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Pendientes Punto');
$col = "A"; /** Uso de la Variable col debido a cambios dinamicos del archivo */
$objSheet->getCell($col . '1')->setValue("Paciente");$col++;
$objSheet->getCell($col . '1')->setValue("Fecha Formula");$col++;
$objSheet->getCell($col . '1')->setValue("Codigo");$col++;
$objSheet->getCell($col . '1')->setValue("Otras Dispensaciones");$col++;
$objSheet->getCell($col . '1')->setValue("Punto Dispensacion");$col++;
$objSheet->getCell($col . '1')->setValue("Nombre Comercial");$col++;
$objSheet->getCell($col . '1')->setValue("Nombre_Producto");$col++;
$objSheet->getCell($col . '1')->setValue("Cum");$col++;
$objSheet->getCell($col . '1')->setValue("Laboratorio Comercial");$col++;
$objSheet->getCell($col . '1')->setValue("Generico");$col++;
$objSheet->getCell($col . '1')->setValue("Departamento");$col++;
$objSheet->getCell($col . '1')->setValue("Municipio");$col++;
$objSheet->getCell($col . '1')->setValue("Cantidad");$col++;
$objSheet->getCell($col . '1')->setValue("Cantidad Tot Requerida");$col++;
$objSheet->getCell($col . '1')->setValue("Cantidad Inventario");$col++;
$objSheet->getCell($col . '1')->setValue("Observaciones");$col++;
$objSheet->getCell($col . '1')->setValue("Rango Auditoria");$col++;
$objSheet->getCell($col . '1')->setValue("Observacion Auditoria");$col++;
$objSheet->getCell($col . '1')->setValue("Propharmacy");$col++;
$objSheet->getCell($col . '1')->setValue("Cantidad_Propharmacy");$col++;

$objSheet->getStyle('A1:' . $col . '1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:' . $col . '1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:' . $col . '1')->getFont()->setBold(true);
$objSheet->getStyle('A1:' . $col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');

$j = 1;

foreach ($productos as $disp) {$j++;
    $col = "A"; /** Uso de la Variable col debido a cambios dinamicos del archivo */
    $objSheet->getCell($col . $j)->setValue($disp["Paciente"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Fecha"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Punto"]);$col++;
    $otras_Dis=str_replace($disp["Punto"].',', '',  $disp["Dispensaciones"]);
    $otras_Dis=str_replace($disp["Punto"].'', '',  $otras_Dis);
    $objSheet->getCell($col . $j)->setValue( $otras_Dis  );$col++;
    $objSheet->getCell($col . $j)->setValue($disp["NombrePunto"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Nombre_Comercial"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Nombre_Producto"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cum"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Laboratorio_Comercial"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Generico"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Departamento"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Municipio"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cantidad"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cantidad_Formulada"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cantidad_Inventario"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Observaciones"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Rango_Auditoria"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Observacion_Auditoria"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Nombre_Propharmacy"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cantidad_Propharmacy"]);

    $colum = "$col";
    $nro_similar = 0;
    $similares=  (array) json_decode($disp['Similares'], true);
    foreach ($similares as $similar) {
        $nro_similar++;
        $colum++;
        $objSheet->getCell($colum . '1')->setValue("Similar - $nro_similar");
        # code...
        $objSheet->getCell($colum . $j)->setValue("$similar[Similar]");
        $objSheet->getColumnDimension($colum)->setWidth(70);
        $colum++;
        $objSheet->getColumnDimension($colum)->setAutoSize(true);
        $objSheet->getCell($colum . $j)->setValue("$similar[Cantidad]");
        $objSheet->getCell($colum . '1')->setValue("Cantidad");

        $objSheet->getStyle("A1:$colum" . "1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objSheet->getStyle("A1:$colum" . "1")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
        $objSheet->getStyle("A1:$colum" . "1")->getFont()->setBold(true);
        $objSheet->getStyle("A1:$colum" . "1")->getFont()->getColor()->setARGB('FFFFFFFF');
        $objSheet->getStyle("A1:$colum" . "1")->getAlignment()->setWrapText(true);
        $objSheet->getStyle("A$j:$colum$j")->getAlignment()->setWrapText(true);
    }

}

$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('D')->setWidth(70);
// $objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setAutoSize(true);
$objSheet->getColumnDimension('F')->setAutoSize(true);
$objSheet->getColumnDimension('G')->setWidth(100);
// $objSheet->getColumnDimension('F')->setAutoSize(true);
// $objSheet->getColumnDimension('G')->setAutoSize(true);
$objSheet->getColumnDimension('H')->setAutoSize(true);
$objSheet->getColumnDimension('I')->setAutoSize(true);
$objSheet->getColumnDimension('J')->setAutoSize(true);
$objSheet->getColumnDimension('K')->setAutoSize(true);
$objSheet->getColumnDimension('L')->setAutoSize(true);
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(true);
$objSheet->getColumnDimension('P')->setAutoSize(true);
// $objSheet->getColumnDimension('P')->setWidth(70);
$objSheet->getColumnDimension('Q')->setWidth(70);
$objSheet->getColumnDimension('S')->setWidth(40);
$objSheet->getColumnDimension('R')->setWidth(70);
$objSheet->getStyle('A1:Q' . $j)->getAlignment()->setWrapText(true);

// echo ('php://output'); exit;
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Pendientes_Punto_ULTIMO_MES.xls"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function GetLotesProductosimilaresPunto($productos, $Id_Punto_Dispensacion)
{

    $query = "SELECT SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible,PRD.Nombre_Comercial,
	CONCAT_WS(' ', PRD.Principio_Activo, PRD.Presentacion,PRD.Concentracion, PRD.Cantidad, PRD.Unidad_Medida) as Nombre,
	PRD.Codigo_Cum,
	 0 as Seleccionado,
	 PRD.Id_Producto
	FROM  Inventario_Nuevo I
	  INNER JOIN Producto PRD
    On I.Id_Producto=PRD.Id_Producto
    INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria

    INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
    INNER JOIN Punto_Dispensacion B ON E.Id_Punto_Dispensacion = B.Id_Punto_Dispensacion

    WHERE E.Estado = 'Disponible' AND B.Id_Punto_Dispensacion = $Id_Punto_Dispensacion
    	AND  I.Id_Producto
	IN ( $productos[Producto_Asociado])
	GROUP BY I.Id_Producto
	HAVING Cantidad_Disponible > 0
	ORDER BY Cantidad_Disponible DESC  ";
// echo json_encode($query);exit;
    $oCon = new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos = $oCon->getData();

    return $productos;
}
