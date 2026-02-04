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
    
    

    $contenido = '<p style="margin-bottom:5px;font-weight:bold;text-align:center">ACTA DE TERMINACIÓN DE CONTRATO LABORAL POR MUTUO ACUERDO   </p> ';

 
    $contenido2 = '<p> Comparecen a la suscripción de la presente Acta, por una parte MARIELA RODRÍGUEZ DE ARCINIEGAS, identificada con cedula de ciudadanía número 63.275.342 de Bucaramanga, actuando en calidad de representante legal de PRODUCTOS HOSPITALARIOS -PROH S.A, quien en adelante se denominará <b>EL EMPLEADOR</b> y, por otra parte '.$f['Funcionario'].',  identificada con cedula de ciudadanía número Nº '.number_format($f['Identificacion_Funcionario'],0,'','.').' de ciudad, actuando en nombre propio, a quien en adelante y para efectos del presente  instrumento, se denominará <b>EL TRABAJADOR</b> quienes en forma libre, voluntaria y con pleno consentimiento y completo uso de todas sus facultades, convienen en celebrar el presente instrumento al tenor  de las siguientes cláusulas:</p> <p><b>CLÁUSULA PRIMERA.</b><b>– Empleador y Trabajador</b> de mutuo acuerdo hemos fijado convenientemente y sin ningún vicio de consentimiento, la terminación por mutuo acuerdo del contrato de trabajo a término fijo suscrito entre '.$f['Funcionario'].' y PRODUCTOS HOSPITALARIOS -PROH S.A. firmado el día '.obtenerFechaEnLetra($f['Fecha_Inicio']).'. Tal y como lo establece el Código Sustantivo del Trabajo en el literal b) del numeral 1º del artículo 61, que contempla la terminación del contrato por Mutuo consentimiento.<b> CLÁUSULA SEGUNDA.- OBJETO.</b>- La suscripción de la presente acta, tiene por objeto dar por terminado por Mutuo Acuerdo el contrato de trabajo a término fijo suscrito entre '.$f['Funcionario'].' y PRODUCTOS HOSPITALARIOS -PROH S.A. firmado el día '.obtenerFechaEnLetra($f['Fecha_Inicio']).', cuyo objeto fue prestar servicios personales en el cargo de '.$f['Cargo'].' en el '.$f['Municipio'].'. <b>CLÁUSULA TERCERA.- LIQUIDACIÓN ECONÓMICA:</b> <br>
    Se realizará de conformidad con la Ley la liquidación del contrato en mención, del cual se estipuló la terminación del Contrato de Trabajo, para la fecha 04 de Enero de 2020, y a su vez el departamento financiero de la empresa, sumará como bonificación al TRABAJADOR la suma de un (1) mes de salario adicional en la liquidación. <b>CLÁUSULA CUARTA.- ACUERDO ENTRE EL TRABAJADOR Y EMPLEADOR Y SU ACEPTACIÓN.</b> <br>
    El contrato de la referencia fue celebrado entre '.$f['Funcionario'].' y PRODUCTOS HOSPITALARIOS -PROH S.A., que en su momento se creyó conveniente la celebración del mismo y que debido a la libre voluntad del Empleador y Trabajador, y su completa autonomía han decidido por mutuo acuerdo la terminación del contrato de trabajo a término fijo firmado el fecha inicio contrato  de mes de año, cuyo objeto fue prestar servicios personales en el cargo de cargo del trabajador en el Municipio de  ciudad donde trabaja, departamento donde trabaja, dan por terminado  de mutuo acuerdo el contrato celebrado entre '.$f['Funcionario'].', identificada con cedula de ciudadanía número Nº '.number_format($f['Identificacion_Funcionario'],0,'','.').' y PRODUCTOS HOSPITALARIOS -PROH S.A. identificada con NIT No. 804.016.084-5. Empleador y Trabajador dan su libre consentimiento expresamente a todo lo estipulado en este documento y aceptan todas las cláusulas contenidas en el presente instrumento; por lo que proceden a suscribirlo en dos ejemplares de igual efecto jurídico. </p>

    
    ';

    
    $contenido5 = '
    
    <table>
    
    <tr>
        <td style="width:400px;padding-left:0px">
        <table>
    
        <tr>
            <td style="width:400px;font-weight:bold">Empleador</td>
             
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
             <td style="width:400px;font-weight:bold">Trabajador</td>
              
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