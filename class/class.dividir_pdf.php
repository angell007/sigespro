<?php
require_once(__DIR__.'/../config/start.inc.php');
require_once('fpdf/fpdf.php');
require_once('fpdf/fpdi.php');
include_once('class.querybasedatos.php');
    
class Separar_Pdf{
      
      function __construct(){
        $this->queryObj = new QueryBaseDatos(); 
      }
      function __destruct(){
        $this->queryObj = null;
        unset($queryObj);	
      }
      public function dividir_pdf($ruta,$archivo,$ruta_final,$nuevo_nombre,$pag){
        $pdf = new FPDI();  
        try{
          $paginas = $pdf->setSourceFile($ruta.'/'.$archivo);
          // echo "gswin64 -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=".$ruta.'/1-'.$archivo." ".$ruta.'/'.$archivo."";exit;
        //  $salida = shell_exec( "gswin64 -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=".$ruta.'/'.$archivo." ".$ruta.'/'.$archivo."");
        //  echo json_encode($salida); exit;
        }catch(\Throwable $th){
          // $paginas = $pdf->setSourceFile($ruta.'/'.$archivo);
          echo "<td> No Paginado</td>";
          return " error";
          // echo $th->getMessage();
        }
        try{
            $new_pdf = new FPDI();
            for($i=1;$i<=$paginas;$i++){
                $clave = array_search($i, $pag);
                if($clave!==false){
                  $new_pdf->AddPage();
                  $new_pdf->setSourceFile($ruta.'/'.$archivo);
                  $new_pdf->useTemplate($new_pdf->importPage($i)); 
                }
            }
            if(!file_exists($ruta_final)){
    		    mkdir($ruta_final, 0777, true);	
    		}
            $new_pdf->Output($ruta_final."/".$nuevo_nombre,'F');
        }catch(\Throwable $e){
          echo "Error ".$e->getMessage()."<br>";
        }
      
      }

    

  }



?>