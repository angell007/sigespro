<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');
include_once('../../class/class.utility.php');

$util = new Utility();

$id_certificado = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$certificado = BuscarDatosCertificado($id_certificado);

$funcionario = BuscarDatosFuncionario($certificado['Identificacion_Funcionario']);
$funcionario_memorando = BuscarDatosFuncionarioMemorando($certificado['Funcionario']);


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

$li=getFirma();

if($li){
    $firma='<img src="'.$MY_FILE . "DOCUMENTOS/".$li["Identificacion_Funcionario"]."/".$li['Firma'].'"  width="230"><br>';
}


if($funcionario['firmaFuncionario']){
    $firmaFuncionario='<img src="'.$MY_FILE . "DOCUMENTOS/".$funcionario["Identificacion_Funcionario"]."/".$funcionario["firmaFuncionario"].'"  width="230"><br>';
}



/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
$header_imgs ='
    ';

    $c1 = '<br><table style="text-transform:uppercase;margin-top:90px;">
    
    <tr>
        <td style="width:600px;">
        <table>
        <tr><td>Bucaramanga, '.obtenerFechaEnLetra($certificado['Fecha']).' </td> </tr>
        <tr><td style="width:600px;font-weight:bold"></td></tr> <tr><td style="width:600px;font-weight:bold"></td></tr>
        <tr>
            <td style="width:600px;font-weight:bold">Señor(a).</td>
            
            
        </tr>
       
        <tr>
            <td style="width:600px;font-weight:bold">'.$funcionario['Nombre_Funcionario'].'</td>    
            
        </tr>
        <tr><td style="width:600px;font-weight:bold"></td></tr>
        <tr>
          
            <td style="width:600px">PRO-H S.A</td>    
        </tr>
        <tr>
        <td style="width:600px;font-weight:bold"></td></tr>
        <tr><td style="width:600px;font-weight:bold"></td></tr>
        <tr>
            <td style="width:600px;"><b>Asunto: MEMORANDO</b></td>
              
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>    
    
    
    
    
        <p style="margin-top:30px;">Por medio de la presente me dirijo a usted de manera respetuosa para informarle que PRO-H S.A. ha decidido efectuarle una amonestación con respecto al contrato de trabajo suscrito entre usted y la sociedad.</p>
        
        <p>'.$certificado['Detalles'].'</p>
        <p>'.$certificado['Motivo'].'</p>
        <p>Así mismo le informo que Dos (2) Faltas Graves son causales para la terminación del contrato de trabajo suscrito entre usted y PRO-H S.A. y una falta leve amerita la amonestación verbal o escrita y suspensión en el trabajo sin remuneración hasta por un periodo de ocho (8) días.</p>
        <p>No siendo otro el motivo del comunicado queda informado y esperamos esto sea un motivo para mejorar en sus funciones.
        Atentamente; </p> ';
    
       $c2 = '
        
        <table style="margin-top:90px;">
        
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
             <td>
            '.$firmaFuncionario.'
            </td>
            </tr>
            <tr>
                <td style="width:330px;font-weight:bold;"> LILIANA MARCELA VEGA GÓMEZ </td>
                <td style="width:300px;font-weight:bold">'.$funcionario['Nombre_Funcionario'].'</td>    
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
$content = '<page backtop="0mm" backbottom="0mm" backimg="'.$_SERVER["DOCUMENT_ROOT"].'IMAGENES/LOGOS/membrete.jpg">
                <div class="page-content" >'.$header_imgs.'</div>
                <div class="page-content" style="text-align:justify;  word-wrap:break-word;" >'.
                    $c1.$c2.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(25, 5, 25, 5));
    $html2pdf->writeHTML($content);
    $direc = 'Memorando.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc);
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function BuscarDatosFuncionario($idFuncionario){
    $query = '
        SELECT F.Firma as firmaFuncionario,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            F.Identificacion_Funcionario,
            C.Nombre AS Cargo,
            F.Salario
        FROM Funcionario F
        INNER JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo
       
        WHERE
            F.Identificacion_Funcionario ='.$idFuncionario;

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $funcionario = $queryObj->ExecuteQuery('simple');
    // echo ($funcionario);
    unset($queryObj);

    return $funcionario;
}

function BuscarDatosFuncionarioMemorando($idFuncionario){
    $query = '
        SELECT 
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            F.Identificacion_Funcionario,
            C.Nombre AS Cargo         
        FROM Funcionario F
        INNER JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo
       
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
        SELECT M.*, CA.Nombre_Categoria NombreC
        FROM Memorando M
        INNER JOIN Categorias_Memorando CA ON M.Motivo = CA.Id_Categorias_Memorando 
        WHERE
            M.Id_Memorando ='.$idCertificado;

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