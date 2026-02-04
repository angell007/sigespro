<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.utility.php');

    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $util = new Utility();

    $response = array();

    $condiciones = SetConditions();

    $inventario = GetInventarioImportacion($condiciones);

    require($MY_CLASS . 'PHPExcel.php');
    include $MY_CLASS . 'PHPExcel/IOFactory.php';
    include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Reporte_Inventario_Importacion.xls"');
    header('Cache-Control: max-age=0');

    $objPHPExcel = new PHPExcel;

    $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
    $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
    $objSheet = $objPHPExcel->getActiveSheet();
    $objSheet->setTitle('Reporte Inventario Importacion');

    $objSheet->getCell('A1')->setValue("Nombre Producto");
    $objSheet->getCell('B1')->setValue("Orden Compra");
    $objSheet->getCell('C1')->setValue("Codigo CUM");
    $objSheet->getCell('D1')->setValue("Embalaje");
    $objSheet->getCell('E1')->setValue("Invima");
    $objSheet->getCell('F1')->setValue("Cantidad Disponible");
    $objSheet->getCell('G1')->setValue("Cantidad en parciales");
    $objSheet->getCell('H1')->setValue("Lote");
    $objSheet->getCell('I1')->setValue("Costo(USD)");
    $objSheet->getCell('J1')->setValue("Fecha Vencimiento");

    $objSheet->getStyle('A1:J1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objSheet->getStyle('A1:J1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
    $objSheet->getStyle('A1:J1')->getFont()->setBold(true);
    $objSheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFFFFFF');

    $j=2;
    $i=0;

    foreach($inventario as $r){ 
    	$objSheet->getCell('A'.$j)->setValue($r['Nombre_Comercial']);
    	$objSheet->getCell('B'.$j)->setValue($r['Codigo_Orden']);
    	$objSheet->getCell('C'.$j)->setValue($r['Codigo_Cum']);
    	$objSheet->getCell('D'.$j)->setValue($r['Embalaje']);
    	$objSheet->getCell('E'.$j)->setValue($r['Invima']);
    	$objSheet->getCell('F'.$j)->setValue(number_format($r['Cantidad'],0,"",""));
    	$objSheet->getCell('G'.$j)->setValue(number_format($r['Cantidad_Parciales'],0,"",""));
    	$objSheet->getCell('H'.$j)->setValue($r['Lote']);
        $objSheet->getCell('I'.$j)->setValue(number_format($r['Precio'],4,",","."));
    	$objSheet->getCell('J'.$j)->setValue($r['Fecha_Vencimiento']);

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
    $objSheet->getStyle('A1:J'.$j)->getAlignment()->setWrapText(true);

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    function SetConditions(){
        global $util;
        $condicion = "";
        $req = $_REQUEST;

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND P.Nombre_Comercial LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE P.Nombre_Comercial LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['lote']) && $req['lote']) {
            if ($condicion != "") {
                $condicion .= " AND IMP.Lote LIKE '%".$req['lote']."%'";
            } else {
                $condicion .= " WHERE IMP.Lote LIKE '%".$req['lote']."%'";
            }
        }

        if (isset($req['orden_compra']) && $req['orden_compra']) {
            if ($condicion != "") {
                $condicion .= " AND OCI.COdigo LIKE '%".$req['orden_compra']."%'";
            } else {
                $condicion .= " WHERE OCI.COdigo LIKE '%".$req['orden_compra']."%'";
            }
        }

        if (isset($req['codigo_cum']) && $req['codigo_cum']) {
            if ($condicion != "") {
                $condicion .= " AND P.Codigo_Cum LIKE '%".$req['codigo_cum']."%'";
            } else {
                $condicion .= " WHERE P.Codigo_Cum LIKE '%".$req['codigo_cum']."%'";
            }
        }

        if (isset($req['fecha_vencimiento']) && $req['fecha_vencimiento']) {
            $fechas = $util->SepararFechas($req['fecha_vencimiento']);

            if ($condicion != "") {
                $condicion .= " AND IMP.Fecha_Vencimiento BETWEEN '".$fechas[0]."' AND '".$fechas[1]."' ";
            } else {
                $condicion .= " WHERE IMP.Fecha_Vencimiento BETWEEN '".$fechas[0]."' AND '".$fechas[1]."' ";
            }
        }

        return $condicion;
    }

    function GetInventarioImportacion($condiciones){
        global $queryObj;

        $query = "SELECT 
                IMP.*,
                P.Nombre_Comercial,
                IFNULL(P.Nombre_Listado, 'English name not set') AS Nombre_Ingles,
                P.Imagen,
                IFNULL(P.Invima, 'No Registrado') AS Invima,
                P.Codigo_Cum,
                IFNULL((SELECT SUM(PNP.Cantidad)  FROM Producto_Nacionalizacion_Parcial PNP INNER JOIN Nacionalizacion_Parcial NP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial WHERE PNP.Id_Producto_Acta_Recepcion_Internacional = IMP.Id_Producto_Acta_Recepcion_Internacional AND NP.Estado !='Anulado'), 0) AS Cantidad_Parciales,
                OCI.Codigo AS Codigo_Orden,
                P.Gravado,
                P.Embalaje
            FROM Importacion IMP
            INNER JOIN Producto P ON IMP.Id_Producto = P.Id_Producto
            INNER JOIN Producto_Acta_Recepcion_Internacional PARI ON IMP.Id_Producto_Acta_Recepcion_Internacional = PARI.Id_Producto_Acta_Recepcion_Internacional
            INNER JOIN Acta_Recepcion_Internacional ARI ON PARI.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
            INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
            $condiciones Order By OCI.Id_Orden_Compra_Internacional asc";

        $queryObj->SetQuery($query);
        $inventario = $queryObj->ExecuteQuery('multiple');

        return $inventario;
    }

?>