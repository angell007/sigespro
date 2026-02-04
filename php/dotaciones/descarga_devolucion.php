<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

/* FUNCIONES BASICAS */
function fecha($str)
{
    $parts = explode(" ",$str);
    $date = explode("-",$parts[0]);
    return $date[2] . "/". $date[1] ."/". $date[0];
}

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
/* FIN FUNCIONES BASICAS*/


ob_start(); // Se Inicializa el gestor de PDF
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
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
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

function MesString($mes_index){
    global $meses;

    return  $meses[($mes_index-1)];
}



$query = 'SELECT DD.*, CONCAT_WS(" ",F1.Nombres, F1.Apellidos) as Entrega, CONCAT_WS(" ",F2.Nombres, F2.Apellidos) as Recibe

FROM Devolucion_Dotacion DD
INNER JOIN Funcionario F1 ON F1.Identificacion_Funcionario = DD.Identificacion_Funcionario
INNER JOIN Funcionario F2 ON F2.Identificacion_Funcionario = DD.Funcionario_Recibe
WHERE DD.Id_Dotacion='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$funcionario = $oCon->getData();
unset($oCon);




        
        $codigos ='
                      <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;">Devolución de Dotación</h4>
                      <h4 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">DD000'.$funcionario["Id_Dotacion"].'</h4>
                      <h6 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">'.fecha($funcionario["Fecha"]).'</h6>
        ';
        $contenido = '<table style="border:1px solid #cccccc;"  cellpadding="0" cellspacing="0">
            <tr style="width:600px;" >
                            <td  style="width:110px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Funcionario Entrega</td>
                            <td style="width:250px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario['Entrega'].'</td>
                            <td  style="width:110px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Funcionario Recibe</td>
                            <td   style="width:250px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario["Recibe"].'</td>
            </tr>
            <tr style="width:600px; " >
                            <td style="width:110px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Observaciones</td>
                            <td colspan="3" style="width:600px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario["Detalles"].'</td>
            </tr>   
        </table>
        
   
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
        
                <td colspan="2" style="text-align:center;width:580px;max-width:400px;font-weight:bold;border-top:1px solid #cccccc;border-left:1px solid #cccccc;border-right:1px solid #cccccc;background:#ededed;padding:4px 0;">
                    Resumen de la Devolución
                </td>
               
                
            </tr>
            <tr>

            <td  style="text-align:center;width:580px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px 0;">
                Item
        </td>
            <td style="text-align:center;width:150px;max-width:100px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px 0;">
            Estado
        </td>
            </tr>';
         $conceptos = explode("|",$funcionario["Productos"]);
            foreach ($conceptos as $value) {
              $contenido.='
              <tr >
                    <td style="width:580px;max-width:400px;border:1px solid #cccccc; max-height:50px;padding:4px;">
                    '.$value.'
                    </td>
                    <td style="text-align:center;width:150px;max-width:100px;border:1px solid #cccccc;padding:4px;">OK</td>
              </tr>';
            }
            
            
           $contenido.= '</table><br>

            <b style="font-size:10px;">Nota: El presente documento representa la devolución de los productos de Dotación y/o EPP antes mencionado, y su firma representa la aceptación del mismo</b>
            
            <table style="margin-top:70px">    
                <tr>
                    <td style="width:400px;padding-left:30px">
                    <table>
                    <tr>
                        <td style="width:330px;font-weight:bold; border-top:1px solid black; text-align:center;text-transform:uppercase;">'.$funcionario['Entrega'].'</td>
                        <td style="width:30px;"></td>
                        <td style="width:330px;font-weight:bold; border-top:1px solid black; text-align:center;text-transform:uppercase;">'.$funcionario["Recibe"].'</td>
                    </tr>
                    <tr>
                        <td style="width:330px;font-weight:bold; text-align:center;">Funcionario Entrega</td>    
                        <td style="width:30px;"></td>    
                        <td style="width:330px;font-weight:bold; text-align:center;">Funcionario Recibe</td>    
                    </tr>
                    
                    </table>
                    </td>    
                </tr>
            </table>';
    
 
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/logo-color.png" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:510px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:170px;text-align:right">
                        '.$codigos.'
                  </td>
                  
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >'.
                    $cabecera.
                    $contenido.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
// echo $content;
// exit;
try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array(215.9,140), 'Es', true, 'UTF-8', array(5, 5, 2, 0));
    $html2pdf->writeHTML($content);
    $direc = $id.'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc,''); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>