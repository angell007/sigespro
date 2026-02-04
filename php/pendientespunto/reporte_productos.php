<?php
// echo "ok"; exit;
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

date_default_timezone_set("America/Bogota");

$condicion = '';

if (isset($_REQUEST['departamento']) && $_REQUEST['departamento'] != "") {
    $condicion .= " HAVING Departamento= '$_REQUEST[departamento]' ";
}

require $MY_CLASS . 'PHPExcel.php';
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

$objPHPExcel = new PHPExcel;

$query =
    " SELECT
    Compra.Total_Compra,
    Compra.Mes_Compra,
    CompraMesAct.Total_Compra AS Compra_Mes,
    IFNULL(BOD.Cantidad_Bodega, 0) as Cantidad_15,
    IFNULL(Asoc.Cantidad_Disponible, 0)AS Cantidad_Departamento,
    IFNULL(REMS.Enviado, 0) AS CANTIDAD_TRASLADADA ,
    SUM(Dis.Cantidad_Formulada) AS Total_Formulada,
    GROUP_CONCAT(Dis.Departamento) AS Dep,
    SUM(Dis.Cantidad_Pendiente) AS Total_Pendiente,
    MAX(Dis.Fecha_Max) AS Fecha_Mx,
    MIN(Dis.Fecha_Min) AS Fecha_Mn,
    concat(min(Fecha_Min), ' hasta ', max(Fecha_Max)) as Rango_Auditoria,
    Dis.*,
     ifnull(  IF(LOCATE(Dis.Id_Producto, PA.Similar_Inventario), PA.Cantidad-Asoc.Cantidad_Disponible,PA.Cantidad),0) AS Similares_Cantidad,
    IF(LOCATE(Dis.Id_Producto, PA.Similar_Inventario),REPLACE(PA.Similar_Inventario, Dis.Id_Producto, ''),PA.Similar_Inventario) AS Otros_Similares
FROM (
    SELECT 
             DEP.Nombre AS Departamento,
            IFNULL(CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida),
            CONCAT(P.Nombre_Comercial,' ',P.Laboratorio_Comercial)) AS Nombre_Generico, 
            SUM(PD.Cantidad_Formulada) AS Cantidad_Formulada,
            SUM(PD.Cantidad_Formulada - Cantidad_Entregada) AS Cantidad_Pendiente,
            P.Nombre_Comercial,
            P.Id_Producto,
            P.Codigo_Cum AS Cum,
            SUBSTRING_INDEX(P.Codigo_Cum, '-', 1) AS Expediente, 
            if(LOCATE('-', P.Codigo_Cum), SUBSTRING_INDEX(P.Codigo_Cum, '-', -1), '') AS Consecutivo,
            A.Id_Auditoria,
                MAX(DATE(Act.Fecha)) AS Fecha_Max,
                MIN(DATE(Act.Fecha)) AS Fecha_Min,
                if(PD.Generico, 'Generico', 'Comercial') AS Generico
            , Act.Mes
            FROM Producto_Dispensacion PD
            INNER JOIN Dispensacion D ON D.Id_Dispensacion=PD.Id_Dispensacion
            INNER JOIN Auditoria A ON (A.Id_Dispensacion = D.Id_Dispensacion AND A.Estado LIKE 'Aceptar')
            INNER JOIN Punto_Dispensacion PDI ON PDI.Id_Punto_Dispensacion=D.Id_Punto_Dispensacion
            INNER JOIN Departamento DEP ON DEP.Id_Departamento = PDI.Departamento
            INNER JOIN Municipio MUN ON MUN.Id_Municipio = PDI.Municipio
            INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
            INNER Join (SELECT Act.Id_Auditoria, Act.Fecha, DATE_FORMAT(Act.Fecha, '%Y-%m') AS Mes
                   FROM Actividad_Auditoria Act
                   WHERE Act.Detalle LIKE '%correcta%'
                   GROUP BY Act.Id_Actividad_Auditoria
                   ORDER BY Act.Id_Actividad_Auditoria DESC)Act on Act.Id_Auditoria=A.Id_Auditoria

            LEFT JOIN Punto_Dispensacion PTO ON PTO.Id_Punto_Dispensacion= PDI.Id_Propharmacy

            WHERE 
              
            Act.Mes = (DATE_FORMAT( DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'))
            AND PDI.Estado = 'Activo' AND PD.Id_Producto IS NOT NULL AND D.Estado_Dispensacion != 'Anulada'  
            GROUP BY Departamento , PD.Id_Producto, Generico
            

    ) Dis
    
        LEFT JOIN (
            SELECT SUM(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad_Disponible,I.Id_Producto, DEP.Nombre AS Departamento 
                FROM Punto_Dispensacion PDI 
                INNER JOIN Estiba E ON E.Id_Punto_Dispensacion =PDI.Id_Punto_Dispensacion
            INNER JOIN Inventario_Nuevo I ON E.Id_Estiba=I.Id_Estiba
            INNER JOIN Departamento DEP ON DEP.Id_Departamento = PDI.Departamento
            GROUP BY I.Id_Producto, PDI.Departamento
        ) Asoc ON Asoc.Departamento =Dis.Departamento AND Asoc.Id_Producto=Dis.Id_Producto
        
        

                                LEFT JOIN (
                                             SELECT SUM(PAR.Cantidad) AS Total_Compra, PAR.Id_Producto, DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m') AS Mes_Compra,  GROUP_CONCAT(AR.Codigo)
                                                FROM Producto_Acta_Recepcion PAR, Acta_Recepcion AR -- ,   Producto P
                                                WHERE AR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion -- AND P.Id_Producto = PAR.Id_Producto
                                                    AND AR.Estado != 'Anulada' GROUP BY PAR.Id_Producto, Date_format(AR.Fecha_Creacion, '%Y-%m')
										  ) Compra ON Compra.Id_Producto=Dis.Id_Producto AND Compra.Mes_Compra = Dis.Mes
										  
										  LEFT JOIN (
                                                SELECT SUM(PAR.Cantidad) AS Total_Compra, PAR.Id_Producto, DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m') AS Mes_Compra,  GROUP_CONCAT(AR.Codigo)
                                                FROM Producto_Acta_Recepcion PAR, Acta_Recepcion AR 
													WHERE AR.Id_Acta_Recepcion= PAR.Id_Acta_Recepcion
                                                    AND AR.Estado!='Anulada' GROUP BY PAR.Id_Producto, Date_format(AR.Fecha_Creacion, '%Y-%m')
										  ) CompraMesAct ON CompraMesAct.Id_Producto=Dis.Id_Producto AND CompraMesAct.Mes_Compra =  Date_format(CURDATE(), '%Y-%m')
     
     LEFT JOIN (
             SELECT SUM(PR.Cantidad)AS Enviado , 
           PR.Id_Producto, DEP.Nombre AS Departamento, 
           Date_format(R.Fecha, '%Y-%m')AS Mes
           FROM Producto_Remision PR
           INNER JOIN Remision R ON R.Id_Remision= PR.Id_Remision
           INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = R.Id_Destino
           INNER JOIN Departamento DEP ON DEP.Id_Departamento= PD.Departamento               
           WHERE R.Tipo_Destino = 'Punto_Dispensacion'
           AND R.Estado in ('Enviada', 'Recibida')
           AND R.Tipo_Origen='Bodega'
           GROUP BY PR.Id_Producto, PD.Departamento, Mes
     ) REMS ON REMS.Id_Producto = Dis.Id_Producto AND REMS.Departamento = Dis.Departamento AND REMS.Mes= Dis.Mes
     
     -- Consulta para relacionar los productos similares
    LEFT JOIN(
           SELECT SUM(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad,
                PA.Producto_Asociado,
                GROUP_CONCAT(DISTINCT CONCAT('-', I.Id_Producto, '-')) AS Similar_Inventario,
              DEP.Nombre AS Departamento
           FROM Inventario_Nuevo I
           INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
           INNER JOIN Producto P ON P.Id_Producto =I.Id_Producto
           INNER JOIN Punto_Dispensacion PTO ON PTO.Id_Punto_Dispensacion= E.Id_Punto_Dispensacion 
           INNER JOIN Departamento DEP ON DEP.Id_Departamento = PTO.Departamento
           INNER JOIN(
               SELECT CONCAT('-', REPLACE(REPLACE(PA.Producto_Asociado, ',', '-,-'), ' ', ''), '-') as Producto_Asociado
               FROM Producto_Asociado  PA
           )PA ON PA.Producto_Asociado LIKE CONCAT('%-',I.Id_Producto,'-%')
           GROUP BY PA.Producto_Asociado, DEP.Id_Departamento
           HAVING Cantidad>0
        )PA ON PA.Producto_Asociado LIKE CONCAT('%-',Dis.Id_Producto,'-%') AND PA.Departamento = Dis.Departamento
        
        LEFT JOIN ( SELECT SUM(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) AS Cantidad_Bodega, I.Id_Producto
                FROM Inventario_Nuevo I
                INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
                INNER JOIN Grupo_Estiba GE ON GE.Id_Grupo_Estiba= E.Id_Grupo_Estiba
                INNER JOIN Producto P ON P.Id_Producto =I.Id_Producto
                INNER JOIN Bodega_Nuevo PTO ON PTO.Id_Bodega_Nuevo= E.Id_Bodega_Nuevo
                WHERE PTO.Id_Bodega_Nuevo=1 AND GE.Nombre NOT IN ('VENCIMIENTO', 'CUARENTENA')
                GROUP BY Id_Producto
                HAVING Cantidad_Bodega>0
        ) BOD ON BOD.Id_Producto=Dis.Id_Producto
     Group BY Dis.Id_Producto,  Dis.Departamento, Dis.Generico
     
    ORDER BY Departamento,Cum DESC";

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
$objSheet->getCell($col . '1')->setValue("DEPARTAMENTO");$col++;
$objSheet->getCell($col . '1')->setValue("MES DE AUDITORIA");$col++;
$objSheet->getCell($col . '1')->setValue("EXPEDIENTE");$col++;
$objSheet->getCell($col . '1')->setValue("CONSECUTIVO");$col++;
$objSheet->getCell($col . '1')->setValue("NOMBRE COMERCIAL");$col++;
$objSheet->getCell($col . '1')->setValue("NOMBRE GENERICO");$col++;
$objSheet->getCell($col . '1')->setValue("CANTIDAD FORMULADA");$col++;
$objSheet->getCell($col . '1')->setValue("CANTIDAD PENDIENTE");$col++;
$objSheet->getCell($col . '1')->setValue("INVENTARIO 15");$col++;
$objSheet->getCell($col . '1')->setValue("INVENTARIO DEPTO");$col++;
$objSheet->getCell($col . '1')->setValue("INV DEPTO SIMILARES");$col++;
$objSheet->getCell($col . '1')->setValue("CANTIDAD A PEDIR");$col++;
$objSheet->getCell($col . '1')->setValue("COMPRA MES");$col++;
$objSheet->getCell($col . '1')->setValue("COMPRA MES EN CURSO");$col++;
$objSheet->getCell($col . '1')->setValue("CANTIDAD TRASLADADA");$col++;
$objSheet->getCell($col . '1')->setValue("TIPO");

$objSheet->getStyle('A1:' . $col . '1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A1:' . $col . '1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:' . $col . '1')->getFont()->setBold(true);
$objSheet->getStyle('A1:' . $col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');

$j = 1;
$inventario_15=[];
foreach ($productos as $disp) {$j++;
    $col = "A"; /** Uso de la Variable col debido a cambios dinamicos del archivo */
    $objSheet->getCell($col . $j)->setValue($disp["Departamento"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Mes"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Expediente"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Consecutivo"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Nombre_Comercial"]);$col++;
   $objSheet->getCell($col . $j)->setValue($disp["Nombre_Generico"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Total_Formulada"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Total_Pendiente"]);$col++;
    
    
    if(!isset($inventario_15[$disp['Id_Producto']])){
        $inventario_15[$disp['Id_Producto']]=$disp['Cantidad_15'];
    }
    $objSheet->getCell($col . $j)->setValue($inventario_15[$disp['Id_Producto']]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Cantidad_Departamento"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Similares_Cantidad"]);$col++;
    
    $pedido=(($disp['Total_Formulada']+$disp['Total_Pendiente'])-( $inventario_15[$disp['Id_Producto']]+ $disp["Cantidad_Departamento"]));
    $pedido = round($pedido, 0, PHP_ROUND_HALF_UP);
    $objSheet->getCell($col . $j)->setValue($pedido);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Total_Compra"]);$col++;
    $objSheet->getCell($col . $j)->setValue($disp["Compra_Mes"]);$col++;
    // $objSheet->getCell($col . $j)->setValue($pedido);$col++;
        
    $objSheet->getCell($col . $j)->setValue($disp["CANTIDAD_TRASLADADA"]);$col++;
    // $objSheet->getCell($col . $j)->setValue($disp["Similares_Cantidad"]);$col++;
    

    $objSheet->getCell($col . $j)->setValue($disp["Generico"]);;
   
    $inventario_15[$disp['Id_Producto']]-=$disp['Total_Pendiente'];
    if($inventario_15[$disp['Id_Producto']]<0) {
        $inventario_15[$disp['Id_Producto']]=0;
    }


}

$objSheet->getColumnDimension('A')->setAutoSize(true);
$objSheet->getColumnDimension('B')->setAutoSize(true);
$objSheet->getColumnDimension('C')->setAutoSize(true);
$objSheet->getColumnDimension('D')->setAutoSize(true);
$objSheet->getColumnDimension('E')->setWidth(70);
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
// $objSheet->getColumnDimension('Q')->setAutoSize(true);
// $objSheet->getColumnDimension('R')->setAutoSize(true);
$objSheet->getStyle('A1:'.$col . $j)->getAlignment()->setWrapText(true);

// echo ('php://output'); exit;
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Pendientes_Producto_ULTIMO_MES.xls"');
header('Cache-Control: max-age=0');
$objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
$objPHPExcel->getActiveSheet()->getProtection()->setSort(true);
$objPHPExcel->getActiveSheet()->getProtection()->setInsertRows(true);
$objPHPExcel->getActiveSheet()->getProtection()->setFormatCells(true);

$objPHPExcel->getActiveSheet()->getProtection()->setPassword('protegido');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

