<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
//$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );


/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style='<style>
.page-content{
width:700px;
text-align:justify;
word-wrap:break-word;
font-size: 12px;
}
.row{
display:inlinie-block;
width:700px;
}
.td-header{
    font-size:15px;
    line-height: 20px;
}
.titular{
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

// consulta para traer los datos del funcionario

$query='SELECT * FROM Funcionario_Carta WHERE Estado = "Activo"';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$funcionarios = $oCon->getData();
unset($oCon);

$numero = (number_format($funcionario['Salario'], 0, '.',''));
$letras = NumeroALetras::convertir($numero);
if($numero==0){
    $letras="CERO ";
}
$dia =date("Y",strtotime($funcionario['Fecha_Fin_Contrato']));




function obtenerFechaEnLetra($fecha){

    $num = date("j", strtotime($fecha));
    $anno = date("Y", strtotime($fecha));
    $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes = $mes[(date('m', strtotime($fecha))*1)-1];
    return $num.' de '.$mes.' de '.$anno;
}

$content='';

foreach ($funcionarios as  $f) {
    
    

    $contenido = '<br><table style="text-transform:uppercase;margin-top:45px;">
    
    <tr>
        <td style="width:600px;">
        <table>
    
        <tr>
            <td style="width:600px;font-weight:bold; padding-left:0px">Señor(a).</td>
            
            
        </tr>
        <tr>
        <td style="width:600px;font-weight:bold"></td>
        
        
    </tr>
        <tr>
            <td style="width:600px;font-weight:bold; padding-left:0px">'.$f['Funcionario'].'</td>    
            
        </tr>
        <tr>
            <td style="width:600px; padding-left:0px">'.$f['Cargo'].'</td>
            
        </tr>
        <tr>
          
            <td style="width:600px; padding-left:0px">PRO-H S.A</td>    
        </tr>
        <tr>
            <td style="width:600px; padding-left:0px">Asunto: Terminación de Contrato de Trabajo por Mutuo Acuerdo.</td>
              
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>    
  
    ';

    $contenido2= '
    
   
    <p >De manera atenta, manifestamos que con ocasión de la liquidación de SALUD VIDA S.A. E.P.S. ordenada por la SÚPER INTENDENCIA NACIONAL DE SALUD mediante Resolución N° 008896 de Octubre 01 de 2019, se hace necesario dar por terminado de mutuo acuerdo el contrato de trabajo celebrado a término fijo suscrito por '.$f['Funcionario'].' y PRO-H S.A. firmado el día '.obtenerFechaEnLetra($f["Fecha_Inicio"]).'.</p>   

    <p style=" font-size:12px">Por lo anterior, no existe razón para continuar la ejecución del contrato de trabajo celebrado con ocasión de los servicios que Pro H S.A. prestaba a Salud Vida E.P.S. y que originó la suscripción del contrato de trabajo.
    </p> 

    <p >Por ende, se abre paso la terminación del Contrato de Trabajo por mutuo consentimiento y se fija la fecha del 04 de Enero de 2020 para dicho efecto.</p> <br><br>
   
    ';

    
    $contenido5 = '
    
    <table>
    
    <tr>
        <td style="width:400px;padding-left:0px">
        <table>
    
        <tr>
            <td style="width:400px;">Cordialmente,</td>
             
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    
    <table style="margin-top:50px;margin-bottom:0px">
    
    <tr>
        <td style="width:400px;padding-left:0px">
        <table>
        <tr>
            <td style="width:300px;font-weight:bold">MARIELA RODRIGUEZ DE ARCINIEGAS</td>
            
        </tr>
        <tr>
            <td style="width:300px;font-weight:bold">C.C 63.275.342</td>
            
        </tr>
        <tr>
            <td style="width:400px;font-weight:bold">REPRESENTANTE LEGAL PRODUCTOS HOSPITALARIOS S.A
            </td>
            
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
     <br><br>
     <table>
    
     <tr>
         <td style="width:400px;padding-left:0px">
         <table>
     
         <tr>
             <td style="width:400px;">Acepto y conforme:</td>
              
         </tr>
         
         </table>
         </td>    
     </tr>
     
     
     </table>
     
     <table style="margin-top:50px;margin-bottom:0px">
     
     <tr>
         <td style="width:400px;padding-left:0px">
         <table>
         <tr>
             <td style="width:300px;font-weight:bold">'.$f['Funcionario'].'</td>
             
         </tr>
         <tr>
             <td style="width:300px;font-weight:bold">C.C '.number_format($f['Identificacion_Funcionario'],0,'','.').' </td>
             
         </tr>
        
         
         </table>
         </td>    
     </tr>
     
     
     </table>
    ';

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content.= '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" style="text-align:justify;  word-wrap:break-word;">'.
                    $contenido.$contenido2.$contenido3.$contenido4.$contenido5.'
                </div>
            </page>';
}
 



/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */


/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(20, 20, 20, 20));
    $html2pdf->writeHTML($content);
    $direc = 'Contrato_Laboral.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
    
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>