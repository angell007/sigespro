<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/class/class.querybasedatos.php');


require( 'html2pdf.class.php');




    
class GenerarPDF{

      function __construct(){
       
      }

      function __destruct(){
      }
   

      public function CrearPdf($contenido,$funcioanrio,$nombre){ 

        ob_start();

         $style='<style>
         .page-content{
         width:750px;
         text-align:justify;
         word-wrap:break-word;
         
         }
         .row{
         display:inlinie-block;
         width:750px;
         }
         .td-header{
             font-size:15px;
             line-height: 20px;
         }
         .titular{
             font-size: 11px;
             text-transform: uppercase;
             margin-bottom: 0;
           }
         
         </style>';


         $content = '<page backtop="0mm" backbottom="0mm" backimg="'.$_SERVER["DOCUMENT_ROOT"].'IMAGENES/LOGOS/membrete.jpg" >
                <div class="page-content" style="text-align:justify;word-wrap:break-word; 
               
                background-size: cover; 
                background-position: center; 
                opacity:0.5;
                  ">'.
                    $contenido.'
                </div>
            </page>';


            try{
              /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
              $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(20, 20, 20, 20));
              $html2pdf->writeHTML($content);
              $direc = $_SERVER["DOCUMENT_ROOT"].'/DOCUMENTOS/'.$funcioanrio.'/'.$nombre; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
   
              $html2pdf->Output($direc,'F'); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
              
          }catch(HTML2PDF_exception $e) {
              echo $e;
              exit;
          }
    
    }


    
      
}

?>