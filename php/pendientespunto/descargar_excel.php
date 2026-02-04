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
                            
                                IFNULL(Bod.Cantidad, 0) AS Similares_15,
                                IFNULL(Asoc.Cantidad_Disponible,0) AS Cantidad_Propharmacy,

                                CONCAT('[', ifnull(Group_concat(Concat('{', Similar.Cantidad, ',', Similar.Producto, '}')),''), ']') AS Similares -- Se arma el array de similares
                            FROM (
                                SELECT 
                                        D.Cantidad AS Cantidad_Formulada,
                                        (Act.Fecha) AS Fecha_Auditoria,  
                                        Act.Observacion AS Observacion_Auditoria,
                                        D.*
                                        FROM (
                                                    SELECT IFNULL(CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida),
                                                    CONCAT(P.Nombre_Comercial,' ',P.Laboratorio_Comercial)) AS Nombre_Producto, 
                                                    (PD.Cantidad_Formulada - Cantidad_Entregada) AS Cantidad,
                                                    P.Nombre_Comercial,
                                                    PD.Id_Producto_Dispensacion,
                                                    IFNULL(  I15.Disponible, 0) AS Bodega_15,
                                                    P.Id_Producto,
                                                    D.Paciente,
                                                    IFNULL(PDPEN.RLrazonSocial, POS.RLrazonSocial) AS Empleador,
                                                    IFNULL(PDPEN.RLnumeroDocumento, POS.RLnumeroDocumento) AS RL_Nit,
                                                    D.Numero_Documento,
                                                    PDI.Nombre AS NombrePunto,
                                                    PDI.Id_Punto_Dispensacion,
                                                    P.Codigo_Cum AS Cum,
                                                    IF(PD.Generico =1, 'Generico', NULL) AS Generico,
                                                    IFNULL(CP.Costo_Promedio, '') as 'Costo_Promedio',
                                                    DEP.Nombre AS Departamento,
                                                    MUN.Nombre AS Municipio,
                                                    D.Codigo AS Punto,
                                                    ifnull(A.Id_Auditoria, A1.Id_Auditoria) as Id_Auditoria,
                                                    ifnull(PTO.Id_Punto_Dispensacion, PDI.Id_Punto_Dispensacion) AS Id_Propharmacy,
                                                    IFNULL(PTO.Nombre, PDI.Nombre) AS Nombre_Propharmacy
                                                    FROM Producto_Dispensacion PD
                                                    INNER JOIN Dispensacion D ON D.Id_Dispensacion=PD.Id_Dispensacion
                                                    Left JOIN Auditoria A ON (A.Id_Dispensacion = D.Id_Dispensacion AND A.Estado LIKE 'Aceptar')
                                                    Left JOIN Auditoria A1 ON ( D.Id_Auditoria = A1.Id_Auditoria  AND A1.Estado LIKE 'Aceptar')
                                                    INNER JOIN Punto_Dispensacion PDI ON PDI.Id_Punto_Dispensacion=D.Id_Punto_Dispensacion
                                                    INNER JOIN Departamento DEP ON DEP.Id_Departamento = PDI.Departamento
                                                    INNER JOIN Municipio MUN ON MUN.Id_Municipio = PDI.Municipio
                                                    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
                                                    INNER JOIN Servicio SE ON SE.Id_Servicio = D.Id_Servicio
                                                    LEFT JOIN Costo_Promedio CP ON CP.Id_producto=P.Id_Producto
                                                    Left Join Positiva_Data POS on POS.Id_Dispensacion = D.Id_Dispensacion
                                                    Left Join Positiva_Data PDPEN on PDPEN.id = D.Id_Positiva_Data
                                                    LEFT JOIN Punto_Dispensacion PTO ON PTO.Id_Punto_Dispensacion= PDI.Id_Propharmacy
                                                    Left Join (SELECT SUM(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Disponible, I.Id_Producto from Inventario_Nuevo I inner join Estiba E on E.Id_Estiba = I.Id_Estiba Where  E.Id_Bodega_Nuevo = 1 Group by I.Id_Producto) I15 ON I15.Id_Producto = P.Id_Producto

                                                    WHERE 
                                                    DATE(D.Fecha_Actual) >= DATE_SUB(NOW(),INTERVAL SE.Dias_Limite_Pendiente DAY) #La fecha se filtra de acuerdo a los dias de pendiente que permite el servicio
                                                    AND PDI.Estado = 'Activo' AND PD.Id_Producto IS NOT NULL AND D.Estado_Dispensacion != 'Anulada'
                                                    AND ((PD.Cantidad_Formulada - Cantidad_Entregada) != 0)
                                                    $condicion
                                                
                                            )D
                                            INNER Join (SELECT Act.Observacion, Act.Id_Auditoria, Act.Fecha FROM Actividad_Auditoria Act WHERE Act.Detalle LIKE '%correcta%' ORDER BY Act.Id_Actividad_Auditoria DESC)Act on Act.Id_Auditoria=D.Id_Auditoria

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


                                    -- Cantidad disponible de los similares en la bodega pricipal
                                    LEFT JOIN (
                                                SELECT SUM(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)AS Cantidad,
                                                CONCAT('\"Similar\":\"', P.Nombre_Comercial, ' (', P.Codigo_Cum,')\"') AS Producto,
                                                I.Id_Producto
                                                FROM Inventario_Nuevo I
                                                INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
                                                INNER JOIN Producto P ON P.Id_Producto =I.Id_Producto
                                                Where (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0
                                                AND E.Id_Bodega_Nuevo = 1
                                                GROUP BY I.Id_Producto                                                
                                    )Bod ON  PA.Producto_Asociado LIKE CONCAT('%-',Bod.Id_Producto,'-%') -- and Similar.Id_Punto_Dispensacion=Dis.Id_Propharmacy
                                GROUP BY Dis.Id_Producto_Dispensacion
                                ORDER BY Fecha_Auditoria DESC";

header("content-type:application/json");

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
echo json_encode($productos); 
exit;

unset($oCon);
$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Pendientes Punto');
$col = "A"; /** Uso de la Variable col debido a cambios dinamicos del archivo */
$objSheet->getCell($col . '1')->setValue("Paciente");$col++;
$objSheet->getCell($col . '1')->setValue("Nit(s)");$col++;
$objSheet->getCell($col . '1')->setValue("Razon Social");$col++;
$objSheet->getCell($col . '1')->setValue("Codigo");$col++;
$objSheet->getCell($col . '1')->setValue("Punto Dispensacion");$col++;
$objSheet->getCell($col . '1')->setValue("Nombre Comercial");$col++;
$objSheet->getCell($col . '1')->setValue("Nombre_Producto");$col++;
$objSheet->getCell($col . '1')->setValue("Cum");$col++;
$objSheet->getCell($col . '1')->setValue("Generico");$col++;
$objSheet->getCell($col . '1')->setValue("Departamento");$col++;
$objSheet->getCell($col . '1')->setValue("Municipio");$col++;
$objSheet->getCell($col . '1')->setValue("Cantidad");$col++;
$objSheet->getCell($col . '1')->setValue("Cantidad Tot Requerida");$col++;
$objSheet->getCell($col . '1')->setValue("Bodega la 15");$col++;
$objSheet->getCell($col . '1')->setValue("Total similares Bodega la 15");$col++;
$objSheet->getCell($col . '1')->setValue("Fecha Auditoria");$col++;
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
    $objSheet->getCell($col . $j)->setValue($disp["Nit_Empleador"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Empleador"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Punto"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["NombrePunto"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Nombre_Comercial"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Nombre_Producto"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cum"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Generico"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Departamento"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Municipio"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cantidad"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cantidad"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Bodega_15"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Similares_15"]>0? $disp["Similares_15"]- $disp["Bodega_15"]: $disp["Similares_15"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Fecha_Auditoria"]);$col++;
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
$objSheet->getColumnDimension('C')->setWidth(50);
$objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setAutoSize(true);
$objSheet->getColumnDimension('F')->setWidth(100);
$objSheet->getColumnDimension('G')->setWidth(100);
$objSheet->getColumnDimension('H')->setAutoSize(true);
$objSheet->getColumnDimension('I')->setAutoSize(true);
$objSheet->getColumnDimension('J')->setAutoSize(true);
$objSheet->getColumnDimension('K')->setAutoSize(true);
$objSheet->getColumnDimension('L')->setAutoSize(true);
$objSheet->getColumnDimension('M')->setAutoSize(true);
$objSheet->getColumnDimension('N')->setAutoSize(true);
$objSheet->getColumnDimension('O')->setAutoSize(70);
$objSheet->getColumnDimension('P')->setAutoSize(true);
$objSheet->getColumnDimension('Q')->setWidth(70);
$objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getColumnDimension('S')->setAutoSize(true);
$objSheet->getStyle('A1:S' . $j)->getAlignment()->setWrapText(true);

try {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Reporte_Pendientes_Punto.xls"');
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
} catch (\Throwable $th) {
    echo $th->getMessage();
}

