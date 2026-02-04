<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');

$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );
$fecha = ( isset( $_REQUEST['fecha'] ) ? $_REQUEST['fecha'] : '' );
$motivo = ( isset( $_REQUEST['motivo'] ) ? $_REQUEST['motivo'] : '' );
$concepto = ( isset( $_REQUEST['concepto'] ) ? $_REQUEST['concepto'] : '' );

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
$letras = NumeroALetras::convertir($numero);

$funcionario = BuscarDatosFuncionario($id_funcionario);



/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
$header_imgs ='';

$c1 = '
    <p style="margin-top:150px;"><b>Bucaramanga '.obtenerFechaEnLetra($fecha).'</b></p>

    <p><b>Señores</b></p>

    <p>'.strtoupper($funcionario["Caja_Compensacion"]).'</p>

    <p><b>ASUNTO:</b> '.strtoupper($motivo).'</p>

    <p>Según lo dispuesto en el artículo 21 de la Ley 1429 de 2010 (que modificó el Art. 256 del Código.
    Sustantivo del Trabajo) y a la aclaración contenida en la Carta Circular 011 del 7 de Febrero de 2011 del Ministerio de la Protección Social, nos permitimos informarles que hemos autorizado el retiro de cesantías por '.strtoupper($motivo).' señalado más adelante, en las siguientes condiciones:</p>

    <p><b>Nombres del Funcionario(a):</b> '.strtoupper($funcionario["Nombre_Funcionario"]).'</p>

    <p><b>Identificación:</b> '.number_format($id_funcionario, 0, "", ".").'</p>

    <p><b>Concepto del Retiro:</b> '.strtoupper($concepto).'</p>

    <p>La empresa se compromete a vigilar la inversión de las cesantías de acuerdo con lo estipulado en las normas antes señaladas.</p>

    <p>Cordialmente,</p>

    <table style="margin-top:50px">    
        <tr>
            <td style="width:400px;padding-left:10px">
            <table>
            <tr>
                <td style="width:300px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$funcionario['Nombre_Funcionario'].'</td>
            </tr>
            <tr>
                <td style="width:300px;font-weight:bold; text-align:center;">C.C '.number_format($id_funcionario,0,"",".").' </td>    
            </tr>
            
            </table>
            </td>    
        </tr>
    </table>';

/*var_dump($c1);
exit;*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm" backimg="'.$_SERVER["DOCUMENT_ROOT"].'IMAGENES/LOGOS/membrete.jpg">
                <div class="page-content" >'.$c1.'</div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(25, 5, 25, 5));
    $html2pdf->writeHTML($content);
    $direc = 'Retiro_Cesantia.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc, 'D');
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function BuscarDatosFuncionario($idFuncionario){
    $query = '
        SELECT 
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            CC.Nombre AS Caja_Compensacion
        FROM Funcionario F
        LEFT JOIN Caja_Compensacion CC ON F.Caja_Compensacion = CC.Id_Caja_Compensacion
        WHERE
            F.Identificacion_Funcionario ='.$idFuncionario;

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $funcionario = $queryObj->ExecuteQuery('simple');

    return $funcionario;
}

function obtenerFechaEnLetra($fecha){
   
    $num = date("j", strtotime($fecha));
    $anno = date("Y", strtotime($fecha));
    $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes = $mes[(date('m', strtotime($fecha))*1)-1];
    return $num.' de '.$mes.' de '.$anno;
}


?>