<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$meses= ( isset( $_REQUEST['meses'] ) ? $_REQUEST['meses'] : '' );
$ano= ( isset( $_REQUEST['ano'] ) ? $_REQUEST['ano'] : '' );
$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : 'Dispensacion';

$meses=explode("-", $meses);


require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Sismed.xls"');
header('Cache-Control: max-age=0');  

$objPHPExcel = new PHPExcel;



for ($i=0; $i < count($meses); $i++) { 
    $condicion=" WHERE PF1.Id_Producto=PF.Id_Producto AND MONTH(F1.Fecha_Documento)=".$meses[$i]." AND YEAR(F1.Fecha_Documento)='".$ano."'";

    $maximo_Factura='(SELECT F1.Codigo FROM Producto_Factura PF1 INNER JOIN Factura F1 ON PF1.Id_Factura=F1.Id_Factura '.$condicion.' AND F1.Tipo!="Homologo" AND F1.Estado_Factura != "Anulada" ORDER BY PF1.Precio DESC LIMIT 1 ) as Maximo_Factura,';


    $minimo_Factura='( SELECT F1.Codigo FROM Producto_Factura PF1 INNER JOIN Factura F1 ON PF1.Id_Factura=F1.Id_Factura '.$condicion.' AND F1.Tipo!="Homologo" AND F1.Estado_Factura != "Anulada" ORDER BY PF1.Precio ASC LIMIT 1 ) as Minimo_Factura,';
    
    $subtotal_Factura='(SELECT SUM(((PF1.Precio*PF1.Cantidad)+ ((PF1.Precio * PF1.Cantidad) * (PF1.Impuesto/100))) ) FROM Producto_Factura PF1 INNER JOIN Factura F1 ON PF1.Id_Factura=F1.Id_Factura '.$condicion.' AND F1.Tipo!="Homologo" AND F1.Estado_Factura != "Anulada" ) as Precio,';
    
    $cantidad_Factura='(SELECT SUM(PF1.Cantidad) FROM Producto_Factura PF1 INNER JOIN Factura F1 ON PF1.Id_Factura=F1.Id_Factura '.$condicion.' AND F1.Tipo!="Homologo" AND F1.Estado_Factura != "Anulada") as Cantidad,';
    
    $costo_Factura=" IFNULL((SELECT PAR.Precio FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion WHERE PAR.Id_Producto=PF.Id_Producto ORDER BY AR.Fecha_Creacion DESC LIMIT 1),0) as Costo,";
    
    $maximo_factura_venta='(SELECT F1.Codigo FROM Producto_Factura_Venta PF1 INNER JOIN Factura_Venta F1 ON PF1.Id_Factura_Venta=F1.Id_Factura_Venta '.$condicion.' AND F1.Estado != "Anulada" ORDER BY PF1.Precio_Venta DESC LIMIT 1 ) as Maximo_Factura,';
    
    $minimo_factura_venta='(SELECT F1.Codigo FROM Producto_Factura_Venta PF1 INNER JOIN Factura_Venta F1 ON PF1.Id_Factura_Venta=F1.Id_Factura_Venta '.$condicion.' AND F1.Estado != "Anulada" ORDER BY PF1.Precio_Venta ASC LIMIT 1 ) as Minimo_Factura,';
    
    $subtotal_factura_venta='(SELECT SUM((PF1.Precio_Venta*PF1.Cantidad)+((PF1.Precio_Venta*PF1.Cantidad)*(PF1.Impuesto/100))) FROM Producto_Factura_Venta PF1 INNER JOIN Factura_Venta F1 ON PF1.Id_Factura_Venta=F1.Id_Factura_Venta '.$condicion.' AND F1.Estado != "Anulada") as Precio,';
    
    $cantidad_factura_venta='(SELECT SUM(PF1.Cantidad) FROM Producto_Factura_Venta PF1 INNER JOIN Factura_Venta F1 ON PF1.Id_Factura_Venta=F1.Id_Factura_Venta '.$condicion.' AND F1.Estado != "Anulada" ) as Cantidad,';
    
    $costo_factura_venta="IFNULL((SELECT PAR.Precio FROM Producto_Acta_Recepcion PAR INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion WHERE PAR.Id_Producto=PF.Id_Producto ORDER BY AR.Fecha_Creacion DESC LIMIT 1),0) as Costo,";

    $producto='IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," (",P.Nombre_Comercial, ") ", P.Cantidad," ", P.Unidad_Medida, " "), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as Nombre_Producto';
    
    if($tipo=="Dispensacion"){
        $query2 = 'SELECT MONTH(F.Fecha_Documento) as Mes,
                  P.Codigo_Cum,P.Id_Producto,
                  PR.Precio as Precio_Regulacion,
                  MAX(PF.Precio) as Maximo,
                  MIN(PF.Precio) as Minimo,'.$maximo_Factura.$minimo_Factura.$subtotal_Factura.$cantidad_Factura.$costo_Factura.$producto.'
                  FROM Producto_Factura PF INNER JOIN Factura F ON PF.Id_Factura=F.Id_Factura  INNER JOIN Producto P ON PF.Id_Producto = P.Id_Producto
                  LEFT JOIN Precio_Regulado PR ON P.Codigo_Cum = PR.Codigo_Cum
                  WHERE MoNTH(F.Fecha_Documento)='.$meses[$i].' AND F.Estado_Factura != "Anulada" AND YEAR(F.Fecha_Documento)="'.$ano.'" AND P.Id_Categoria IN (12,8,9,3,5,10) AND P.Embalaje NOT LIKE "%Muestra Medica%" AND F.Tipo!="Homologo" GROUP by P.Codigo_Cum ORDER BY Codigo_Cum, Id_Producto';
 
    }elseif($tipo=="Cliente"){
         $query2 = 'SELECT MONTH(F.Fecha_Documento) as Mes,
                    P.Codigo_Cum, P.Id_Producto,
                    PR.Precio as Precio_Regulacion,
                    MAX(PF.Precio_Venta) as Maximo,
                    MIN(PF.Precio_Venta) as Minimo,'.$maximo_factura_venta.$minimo_factura_venta.$subtotal_factura_venta.$cantidad_factura_venta.$costo_factura_venta.$producto.'
                    FROM Producto_Factura_Venta PF INNER JOIN Factura_Venta F ON PF.Id_Factura_Venta=F.Id_Factura_Venta  INNER JOIN Producto P ON PF.Id_Producto = P.Id_Producto
                    LEFT JOIN Precio_Regulado PR ON P.Codigo_Cum = PR.Codigo_Cum
                    WHERE MoNTH(F.Fecha_Documento)='.$meses[$i].' AND F.Estado != "Anulada" AND YEAR(F.Fecha_Documento)="'.$ano.'" AND P.Id_Categoria IN (12,8,9,3,5,10) AND P.Embalaje NOT LIKE "%Muestra Medica%"  GROUP by P.Codigo_Cum  ORDER BY Codigo_Cum, Id_Producto';
  
 
    }
   


    $oCon= new consulta();
    $oCon->setQuery($query2);
    $oCon->setTipo('Multiple');
    if($i==0){
        $mes1= $oCon->getData();
    }else if ($i==1){
        $mes2= $oCon->getData();
    }else if($i==2){
        $mes3= $oCon->getData();
    }
    unset($oCon);
}
$resultado=[];


$id=0;
$i=-1;
$total=0;

foreach ($mes1 as $producto) {$i++;
    if($id!=(INT)$producto['Id_Producto']){ 
       
        $id=(INT)$producto['Id_Producto'];
        $minimo=(INT)$producto['Minimo'];
        $maximo=(INT)$producto['Maximo'];
        $cantidad=$producto['Cantidad'];
        $fac_max=$producto['Maximo_Factura'];
        $fac_min=$producto['Minimo_Factura'];
        $subtotal=$producto['Precio'];
        $total=(INT)$producto['Cantidad'];
        // $subtotal=$subtotal+$producto['Precio'];
    }else{  
        $total= $producto['Cantidad']+$total; 
        $subtotal=$producto['Precio']+$subtotal;       
        if((INT)$producto['Minimo']<$minimo){
            $minimo=(INT)$producto['Minimo'];
            $fac_min=$producto['Minimo_Factura'];
        }
        if((INT)$producto['Maximo']>$maximo){
            $maximo=(INT)$producto['Maximo'];
            $fac_max=$producto['Maximo_Factura'];
        }
        $id=(INT)$producto['Id_Producto'];
        $mes1[$i]['Maximo_Factura']=$fac_max;
        $mes1[$i]['Minimo_Factura']=$fac_min;
        $mes1[$i]['Minimo']=$minimo;
        $mes1[$i]['Maximo']=$maximo;
        $mes1[$i]['Cantidad']=$total;
        $mes1[$i]['Precio']=$subtotal;
        $total=0;
        $subtotal=0;

        unset($mes1[$i-1]);
       
    }
}
$mes1=array_values($mes1);

$id=0;
$i=-1;
$total=0;
foreach ($mes2 as $producto) {$i++;
    if($id!=(INT)$producto['Id_Producto']){        
        $id=(INT)$producto['Id_Producto'];
        $minimo=(INT)$producto['Minimo'];
        $maximo=(INT)$producto['Maximo'];
        $cantidad=(INT)$producto['Cantidad'];
        $fac_max=$producto['Maximo_Factura'];
        $fac_min=$producto['Minimo_Factura'];
        $subtotal=$producto['Precio'];
        $total=(INT)$producto['Cantidad'];
        // $subtotal=$subtotal+$producto['Precio'];
    }else{   
        $total= (INT)$producto['Cantidad']+$total; 
        $subtotal=$producto['Precio']+$subtotal;          
        if((INT)$producto['Minimo']<$minimo){
            $minimo=(INT)$producto['Minimo'];
            $fac_min=$producto['Minimo_Factura'];
        }
        if((INT)$producto['Maximo']>$maximo){
            $maximo=(INT)$producto['Maximo'];
            $fac_max=$producto['Maximo_Factura'];
        }
        $id=(INT)$producto['Id_Producto'];
        $mes2[$i]['Maximo_Factura']=$fac_max;
        $mes2[$i]['Minimo_Factura']=$fac_min;
        $mes2[$i]['Minimo']=$minimo;
        $mes2[$i]['Maximo']=$maximo;
        $mes2[$i]['Cantidad']=$total;
        $mes2[$i]['Precio']=$subtotal;
        $total=0;
        
        unset($mes2[$i-1]);
       
    }
}
$mes2=array_values($mes2);

$id=0;
$i=-1;
$total=0;
/* echo "<pre>";
var_dump($mes3);
echo "</pre>";
exit; */
foreach ($mes3 as $producto) {$i++;
    if($id!=(INT)$producto['Id_Producto']){        
        $id=(INT)$producto['Id_Producto'];
        $minimo=(INT)$producto['Minimo'];
        $maximo=(INT)$producto['Maximo'];
        $cantidad=(INT)$producto['Cantidad'];
        $fac_max=$producto['Maximo_Factura'];
        $fac_min=$producto['Minimo_Factura'];
        $subtotal=$producto['Precio'];
        $total=(INT)$producto['Cantidad'];
        // $subtotal=$subtotal+$producto['Precio'];

     
    }else{  
        $total= (INT)$producto['Cantidad']+$total; 
        $subtotal=$producto['Precio']+$subtotal;  
        if((INT)$producto['Minimo']<$minimo){
            $minimo=(INT)$producto['Minimo'];
            $fac_min=$producto['Minimo_Factura'];
        }
        if((INT)$producto['Maximo']>$maximo){
            $maximo=(INT)$producto['Maximo'];
            $fac_max=$producto['Maximo_Factura'];
        }
        $id=(INT)$producto['Id_Producto'];
        $mes3[$i]['Maximo_Factura']=$fac_max;
        $mes3[$i]['Minimo_Factura']=$fac_min;
        $mes3[$i]['Minimo']=$minimo;
        $mes3[$i]['Maximo']=$maximo;
        $mes3[$i]['Cantidad']=$total;
        $mes3[$i]['Precio']=$subtotal;
      
        $total=0;
        unset($mes3[$i-1]);
       
    }
}

 $mes3=array_values($mes3);



$resultado=array_merge($mes1,$mes2,$mes3);

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte Sismed');

$objSheet->getCell('A1')->setValue("");
$objSheet->getCell('B1')->setValue("");
$objSheet->getCell('C1')->setValue(" ");
$objSheet->getCell('D1')->setValue(" ");
$objSheet->getCell('E1')->setValue(" ");
$objSheet->getCell('F1')->setValue(" ");
$objSheet->getCell('G1')->setValue(" ");
$objSheet->getCell('H1')->setValue(" ");
$objSheet->getCell('I1')->setValue(" ");
$objSheet->getCell('J1')->setValue(" ");
$objSheet->getCell('K1')->setValue(" ");
$objSheet->getCell('L1')->setValue(" ");
$objSheet->getCell('M1')->setValue(" ");

$objSheet->getStyle('A1:M1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
/*$objSheet->getStyle('A1:F1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A1:F1')->getFont()->setBold(true);
$objSheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFFFF');*/

$j=1;
$i=0;

foreach($resultado as $item){ $j++;$i++;
	$objSheet->getCell('A'.$j)->setValue("2");
	$objSheet->getCell('B'.$j)->setValue($i);
	$objSheet->getCell('C'.$j)->setValue($item['Mes']);
	$objSheet->getCell('D'.$j)->setValue("INS");
	$objSheet->getCell('E'.$j)->setValue($item["Codigo_Cum"]);
	$objSheet->getCell('F'.$j)->setValue($item["Minimo"]);
	$objSheet->getCell('G'.$j)->setValue($item["Maximo"]);
	$objSheet->getCell('H'.$j)->setValue($item["Precio"]);
    $objSheet->getCell('I'.$j)->setValue($item["Cantidad"]);
    $factura=explode("-",$item['Minimo_Factura']);
    $factura_maxima=explode("-",$item['Maximo_Factura']);
	$objSheet->getCell('J'.$j)->setValue($item['Minimo_Factura']);
	$objSheet->getCell('K'.$j)->setValue($item['Maximo_Factura']);
	$objSheet->getCell('L'.$j)->setValue( $item['Nombre_Producto']);
    $objSheet->getCell('M'.$j)->setValue( $item['Precio_Regulacion']);
	
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
$objSheet->getStyle('A1:M'.$j)->getAlignment()->setWrapText(true);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>