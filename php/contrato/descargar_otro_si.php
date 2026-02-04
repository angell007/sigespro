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

$query='SELECT F.*, (SELECT Nombre FROM Cargo  WHERE Id_Cargo=F.Id_Cargo) as Cargo, CF.*, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario,C.Fecha_Aplicacion ,(SELECT Nombre FROm Municipio WHERE Id_Municipio=CF.Id_Municipio) AS Municipio, IFNULL(C.Salario,0 ) as Salario
  FROM Otrosi_Contrato C
  INNER JOIN Contrato_Funcionario CF ON C.Id_Contrato_Funcionario=CF.Id_Contrato_Funcionario
  INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario  WHERE C.Id_Otrosi_Contrato='.$id;
$oCon= new consulta();
$oCon->setQuery($query);
$funcionario = $oCon->getData();
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
$de='';
if($funcionario['Salario']>100000){
    $de=' DE ';
}
 



/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

        

    $contenido = '<p style="margin-bottom:5px;font-weight:bold;text-align:center">OTRO SÍ #1 AL CONTRATO DE TRABAJO A TERMINO FIJO INFERIOR A UN AÑO CELEBRADO ENTRE PRODUCTOS HOSPITALARIOS PROH S.A Y '.$funcionario['Funcionario'].'.   </p> ';

 
    $contenido2 = '
    <p> Entre los suscritos <b>MARIELA RODRÍGUEZ DE ARCINIEGAS </b>, identificada con cedula de ciudadanía número 63.275.342 de Bucaramanga, actuando en calidad de representante legal de <b>PRODUCTOS HOSPITALARIOS - PROH S.A. </b>, identificada con NIT 80401 6084-5, con domicilio en la ciudad de Bucaramanga Y quien en adelante será denominada <b>LA EMPLEADORA </b> Y por otra parte <b>'.$funcionario['Funcionario'].'</b>	, identificada con cedula de ciudadanía No 1.090.467.816, quien en adelante será denominado <b>LA TRABAJADOR(A)</b>, han suscrito el presente OTRO Sí al CONTRATO PRINCIPAL DE TRABAJO A TÉRMINO FIJO INFERIOR A UN AÑO, firmado el día Veintiocho  (28) de Noviembre de dos mil diecinueve (2019), para lo cual las partes han decidido modificar de común acuerdo la <b>CLAUSULA PRIMERA</b> y <b>CLAUSULA CUARTA </b> del contrato de trabajo a término fijo inferior a un año, las cuales quedaran así:    
    </p>

    <p><b> CLAUSULA PRIMERA. </b> <b>PRIMERA. OBJETO.</b> EL TRABAJADOR prestará en forma exclusiva sus servicios personales bajo la continuada dependencia y subordinación AL EMPLEADOR inicialmente en el cargo de <b>'.$funcionario['Cargo'].'</b> o en los oficios que por razón de su formación, competencias, capacitación y necesidades del proceso o procedimientos sean necesarios realizar, sin que dichos cambios de oficios o cargos implique una desmejora o cambio de sus condiciones y obligaciones laborales.</p>

    <p><b>EL TRABAJADOR</b> se compromete a no prestar directa ni indirectamente servicios laborales a otros EMPLEADORES, ni a trabajar por cuenta propia en el mismo oficio, en las instalaciones de la EMPRESA y horarios laborales, durante la vigencia de este contrato.</p>


    <p><b>CLAUSULA </b><b>CUARTA. REMUNERACION</b> Como contraprestación directa por los servicios que se obliga a prestar EL TRABAJADOR, recibirá una remuneración que equivaldrá inicialmente a la suma de '.$letras.$de.' PESOS MCTE ($'.number_format($funcionario['Salario'],2,",",".").') pagaderos quincenalmente, previa entrega del informe de actividades por parte del TRABAJADOR.	Así mismo, se entiende que en el salario convenido está incluido el valor del descanso dominical o festivo que tenga derecho EL TRABAJADOR.</p>

    <p> El presente OTRO SÍ hace parte integral del contrato suscrito entre las partes el '.obtenerFechaEnLetra($funcionario["Fecha_Fin_Contrato"]).', En consecuencia, se firma el presente documento, en el municipio de '.$funcionario['Municipio'].' a el dia '.obtenerFechaEnLetra($funcionario["Fecha_Aplicacion"]).'.   </p> <br> <br> <br> <br>

    ';

    
    $contenido5 = '
    
    <table>
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
    
        <tr>
            <td style="width:300px;font-weight:bold">Empleador</td>
            <td style="width:300px;font-weight:bold">Trabajador</td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    
    <table style="margin-top:50px;margin-bottom:0px">
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
        <tr>
            <td style="width:300px;font-weight:bold">MARIELA RODRIGUEZ DE ARCINIEGAS</td>
            <td style="width:300px;font-weight:bold">'.$funcionario['Funcionario'].'</td>    
        </tr>
        <tr>
            <td style="width:300px;font-weight:bold">C.C 63.275.342</td>
            <td style="width:300px;font-weight:bold">C.C '.number_format($funcionario['Identificacion_Funcionario'],0,"",".").' </td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    ';

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" style="text-align:justify;  word-wrap:break-word;">'.
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