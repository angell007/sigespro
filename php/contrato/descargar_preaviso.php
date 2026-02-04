<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');
include_once('../../class/class.querybasedatos.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


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
/* FIN HOJA DE ESTILO PARA PDF*/

// consulta para traer los datos del funcionario

$query='SELECT CF.*, (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=F.Id_Cargo) as Cargo, CF.*, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario  FROM Contrato_Funcionario  CF
INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=CF.Identificacion_Funcionario WHERE CF.Id_Contrato_Funcionario='.$id;
$oCon= new consulta();
$oCon->setQuery($query);
$funcionario = $oCon->getData();
unset($oCon);


$query='SELECT UPPER(Representante_Legal) as Representante_Legal,	Identificacion_Representante, NIT  FROM Configuracion WHERE Id_COnfiguracion=1 ';
$oCon= new consulta();
$oCon->setQuery($query);
$configuracion = $oCon->getData();
unset($oCon);

$numero = number_format($funcionario['Salario'], 0, '.','');
$letras = NumeroALetras::convertir($numero);

$li=getFirma();
if($li){
    $firma='<img src="'.$MY_FILE . "DOCUMENTOS/".$li["Identificacion_Funcionario"]."/".$li['Firma'].'"  width="230"><br>';
}

function getFirma(){
    $query = 'SELECT Firma, Identificacion_Funcionario FROM Funcionario WHERE Identificacion_Funcionario=1098655659 ';

$queryObj = new QueryBaseDatos($query); 
$func = $queryObj->ExecuteQuery('simple');
unset($queryObj);

return $func;
}


function obtenerFechaEnLetra($fecha){
   
    $num = date("j", strtotime($fecha));
    $anno = date("Y", strtotime($fecha));
    $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes = $mes[(date('m', strtotime($fecha))*1)-1];
    return $num.' de '.$mes.' de '.$anno;
}
 



/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

        


    $contenido .= '<br><table style="text-transform:uppercase;margin-top:50px;">
    
    <tr>
        <td style="width:600px;">
        <table>
    
        <tr>
            <td style="width:600px;font-weight:bold">Señor(a).</td>
            
            
        </tr>
        <tr>
        <td style="width:600px;font-weight:bold"></td>
        
        
    </tr>
        <tr>
            <td style="width:600px;font-weight:bold">'.$funcionario['Funcionario'].'</td>    
            
        </tr>
        <tr>
            <td style="width:600px">'.$funcionario['Cargo'].'</td>
            
        </tr>
        <tr>
          
            <td style="width:600px">PRO-H S.A</td>    
        </tr>
        <tr>
            <td style="width:600px">Asunto: Preaviso terminación de contrato</td>
              
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>    
  
    ';

    $contenido.= '
    
    <p style="padding:6px">Cordial Saludo,</p>   
    <p style="padding:6px">De manera atenta, le comunicamos que el contrato de trabajo a término fijo inferior a un año suscrito por usted y PRO-H S.A, el día '.obtenerFechaEnLetra($funcionario["Fecha_Inicio_Contrato"]).', con una  duración  de 04 meses, con el cual se ha venido prorrogando, finaliza el próximo '.obtenerFechaEnLetra($funcionario["Fecha_Fin_Contrato"]).', no será prorrogado y en consecuencia se dará por terminado sin perjuicio de que llegada la fecha de terminación, las partes acuerden lo contrario. Lo anterior con fundamento en lo preceptuado en el numeral 1° del artículo 46 del código sustantivo del trabajo, que dice:</p>   

    <p style="padding:6px; font-size:12px">1. Si antes de la fecha de vencimiento del término estipulado, ninguna de las partes avisare por escrito a la otra su determinación de no prorrogar el contrato, con una antelación no inferior a treinta (30) días, éste se entenderá renovado por un período igual al inicialmente pactado y así sucesivamente</p> 

    <p style="padding:6px">Por lo anterior, con una anticipación no inferior a 30 días calendario, se le da aviso de nuestra decisión de manera escrita dando cumplimiento a los requerimientos legales.</p> 
   
    ';

    

    $contenido5 = '
    
    <table style="margin-top:150px;">
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
    
        <tr>
            <td style="width:330px;font-weight:bold">Atentamente</td>
            <td style="width:300px;font-weight:bold">Trabajador</td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    
    <table style="margin-top:20px;margin-bottom:0px">
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
        <tr>
        <td>
        '.$firma.'
        </td>
        </tr>
        <tr>
            <td style="width:330px;font-weight:bold;"> LILIANA MARCELA VEGA GÓMEZ </td>
            <td style="width:300px;font-weight:bold">'.$funcionario['Funcionario'].'</td>    
        </tr>
        <tr>
            <td style="width:330px;font-weight:bold">Jefe Recursos Humanos</td>
            <td style="width:300px;font-weight:bold">C.C '.number_format($funcionario['Identificacion_Funcionario'],0,"",".").' </td>    
        </tr>
           
          
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    ';

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm" backimg="'.$_SERVER["DOCUMENT_ROOT"].'IMAGENES/LOGOS/membrete.jpg" >
                <div class="page-content" style="text-align:justify;word-wrap:break-word; 
               
                background-size: cover; 
                background-position: center; 
                opacity:0.5;
                  ">'.
                    $contenido.$contenido2.$contenido3.$contenido4.$contenido5.'
                </div>
            </page>';
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