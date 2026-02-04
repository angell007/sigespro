<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');
include_once('../../class/class.utility.php');

$util = new Utility();

$id_certificado = ( isset( $_REQUEST['id_certificado'] ) ? $_REQUEST['id_certificado'] : '' );

$certificado = BuscarDatosCertificado($id_certificado);
$funcionario = BuscarDatosFuncionario($certificado['Identificacion_Funcionario']);
$texto_salario = '';

if ($certificado['Tipo']) {
    $texto_salario = ' y devengando un salario de '.number_format($funcionario['Salario'], 2, ".", ",");
}

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

$numero = number_format($funcionario['Salario'], 0, '.','');
$letras = NumeroALetras::convertir(date('d'));


/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

$firma='';

$li=getFirma();
if($li){
    $firma='<img src="'.$MY_FILE . "DOCUMENTOS/".$li["Identificacion_Funcionario"]."/".$li['Firma'].'"  width="230"><br>';
}


$c1 = '



    <p style="padding-left:210px;padding-top:50px;font-size:22px;font-weight:bold;margin-top:170px">CERTIFICACION</p>



    <p style="margin-top:30px;">Certificamos que el señor <b>'.$funcionario["Nombre_Funcionario"].'</b> identificado con cédula de ciudadanía No. 
    <b>'.$funcionario["Identificacion_Funcionario"].'</b>  se encuentra vinculado a la empresa PRODUCTOS HOSPITALARIOS S.A./PRO-H S.A. Nit. 804.016.084-5. Desempeñando el cargo de 
    <b>'.$funcionario["Cargo"]."</b>".$texto_salario.' , desde el '.obtenerFechaEnLetra($funcionario["Fecha_Ingreso"]).' . La presente certificación se expide en Bucaramanga, 
    al(los) '.$letras.' ('.date("d").') día(s) del mes de '.$util->ObtenerMesString(date('m')).' de '.date('Y').' a solicitud del interesado.</p>


    <p style="margin-top:100px;">Atentamente;</p>

    <table style="margin-top:20px">    
        <tr>
            <td style="width:300px;padding-left:10px">
            '.$firma.'
            <table>
            <tr>
                <td style="width:300px;font-weight:bold; border-top:1px solid black; text-align:center;">
                LILIANA MARCELA VEGA GÓMEZ</td>
            </tr>
            <tr>
                <td style="width:300px;font-weight:bold; text-align:center;">Jefe Recursos Humanos </td>    
            </tr>
            
            </table>
            </td>    
        </tr>
    </table>';



/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm" backimg="'.$_SERVER["DOCUMENT_ROOT"].'IMAGENES/LOGOS/membrete.jpg">
                <div class="page-content" >'.$header_imgs.'</div>
                <div class="page-content" >'.
                    $c1.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(25, 5, 25, 5));
    $html2pdf->writeHTML($content);
    $direc = 'Certificadi_Laboral.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc);
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function BuscarDatosFuncionario($idFuncionario){
    $query = '
        SELECT 
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            F.Identificacion_Funcionario,
            C.Nombre AS Cargo,
            F.Salario,
            IFNULL(CF.Fecha_Inicio_Contrato, CURDATE()) AS Fecha_Ingreso
        FROM Funcionario F
        INNER JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo
        LEFT JOIN Contrato_Funcionario CF ON F.Identificacion_Funcionario = CF.Identificacion_Funcionario
        WHERE
            F.Identificacion_Funcionario ='.$idFuncionario;

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $funcionario = $queryObj->ExecuteQuery('simple');
    unset($queryObj);

    return $funcionario;
}

function BuscarDatosCertificado($idCertificado){
    $query = '
        SELECT 
            *
        FROM Certificado_Laboral
        WHERE 
            Id_Certificado_Laboral ='.$idCertificado;

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $certificado = $queryObj->ExecuteQuery('simple');
    unset($queryObj);

    return $certificado;
}

function obtenerFechaEnLetra($fecha){
   
    $num = date("j", strtotime($fecha));
    $anno = date("Y", strtotime($fecha));
    $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes = $mes[(date('m', strtotime($fecha))*1)-1];
    return $num.' de '.$mes.' de '.$anno;
}

function getFirma(){
        $query = 'SELECT Firma, Identificacion_Funcionario FROM Funcionario WHERE Identificacion_Funcionario=1098655659 ';

    $queryObj = new QueryBaseDatos($query); 
    $func = $queryObj->ExecuteQuery('simple');
    unset($queryObj);

    return $func;
}


?>