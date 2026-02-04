<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/class/class.querybasedatos.php');


require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';



    
class GenerarExcel{
    private $query;
    private $query_total;

      function __construct($query,$query_total){
        $this->queryObj = new QueryBaseDatos();
        $this->query=$query;    
        $this->query_total=$query_total;    
      }

      function __destruct(){
        $this->queryObj = null;
        unset($queryObj);	
      }
   

    public function CrearExcel(){ 
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Contabilizacion.xls"');
        header('Cache-Control: max-age=0');   
        $objPHPExcel = new PHPExcel;
        $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        $objSheet = $objPHPExcel->getActiveSheet(); 
        $objSheet->getCell('A1')->setValue("Cuenta");
        $objSheet->getCell('B1')->setValue("Nombre Cuenta");
        $objSheet->getCell('C1')->setValue("Documento");
        $objSheet->getCell('D1')->setValue("Nit");
        $objSheet->getCell('E1')->setValue("Detalles");
        $objSheet->getCell('F1')->setValue("Debitos");
        $objSheet->getCell('G1')->setValue("Creditos");
        $datos=$this->GetCuentas();
        $tieneCentroCosto = !empty($datos) && isset($datos[0]['Centro_Costo']);
        if ($tieneCentroCosto) {
            $objSheet->getCell('H1')->setValue("Centro Costo");
        }
        $headerRange = $tieneCentroCosto ? 'A1:H1' : 'A1:G1';
        $objSheet->getStyle($headerRange)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objSheet->getStyle($headerRange)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
        $objSheet->getStyle($headerRange)->getFont()->setBold(true);
        $objSheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');
        $total=$this->GetTotales();
        $j=1;
        foreach($datos as $value){ $j++;
            $objSheet->getCell('A'.$j)->setValue($value['Codigo']);
            $objSheet->getCell('B'.$j)->setValue($value["Nombre"]);
            $objSheet->getCell('C'.$j)->setValue($value["Documento"]);
            $objSheet->getCell('D'.$j)->setValue($value["Nit"]);
            $objSheet->getCell('E'.$j)->setValue($value["Detalles"]);
            $objSheet->getCell('F'.$j)->setValue($value["Debe"]);
            $objSheet->getCell('G'.$j)->setValue($value['Haber']);   
            if ($tieneCentroCosto) {
                $objSheet->getCell('H'.$j)->setValue($value['Centro_Costo']);
            }
            $objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");        
            $objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");        
            
        }
        $j++;
        $objSheet->getCell('F'.$j)->setValue($total["Debe"]);
        $objSheet->getCell('G'.$j)->setValue($total['Haber']);
        $objSheet->getStyle('F'.$j)->getNumberFormat()->setFormatCode("#,##0.00");        
        $objSheet->getStyle('G'.$j)->getNumberFormat()->setFormatCode("#,##0.00");     

        $objSheet->getColumnDimension('A')->setAutoSize(true);
        $objSheet->getColumnDimension('B')->setAutoSize(true);
        $objSheet->getColumnDimension('C')->setAutoSize(true);
        $objSheet->getColumnDimension('D')->setAutoSize(true);
        $objSheet->getColumnDimension('E')->setAutoSize(true);
        $objSheet->getColumnDimension('F')->setAutoSize(true);
        $objSheet->getColumnDimension('G')->setAutoSize(true);
        if ($tieneCentroCosto) {
            $objSheet->getColumnDimension('H')->setAutoSize(true);
        }
  
        $dataRange = $tieneCentroCosto ? 'A1:H'.$j : 'A1:G'.$j;
        $objSheet->getStyle($dataRange)->getAlignment()->setWrapText(true);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    
    }

    private function GetCuentas(){
        $this->queryObj->SetQuery($this->query);
        $datos=$this->queryObj->ExecuteQuery('multiple'); 
        return $datos;
    }
    private function GetTotales(){
        $this->queryObj->SetQuery($this->query_total);
        $datos=$this->queryObj->ExecuteQuery('simple'); 
        return $datos;
    }

    
      
}

?>
