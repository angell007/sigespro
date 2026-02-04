<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/class.barcode.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);

$oItem = new complex('Inventario_Inicial',"Id_Inventario_Inicial",$id);
$inventario = $oItem->getData();
unset($oItem);

$oItem = new complex("Producto","Id_Producto",$inventario["Id_Producto"]);
$producto = $oItem->getData();
unset($oItem);

$nom=$producto['Nombre_Comercial']." ".$producto['Laboratorio_Generico'];

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

</style>';
/* FIN HOJA DE ESTILO PARA PDF*/
$cantidad = ($inventario["Cantidad"]/$producto["Cantidad_Presentacion"])/2;

$barras= generabarras($inventario["Codigo"]);
$lote=$inventario['Lote'];
$fecha=$inventario['Fecha_Vencimiento'];
//echo $barras.'<br>'.$lote.'-FV '.$fecha.'<br>'.$inventario["Codigo"]."<br><br>".$nom;

/*for($h=0;$h<$cantidad;$h++){*/
    $content.= '<page backtop="0mm" backbottom="0mm">
    <div class="page-content" style="width:103mm;height:23mm;" >
    <table style="width:98mm;height:23mm;" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:50%;">
    <div style="width:50mm;height:22mm;padding:3px;text-align:center;vertical-align:middle;text-transform:uppercase;font-weight:bold;"><span style="font-size:7px;line-height:8px;">'.$barras.'<br>L-'.$lote.'-F.V. '.$fecha.'<br>'.$inventario["Codigo"]."<br>".$nom.'</span>
    </div>
    </td>
    <td style="width:50%">
     <div style="width:50mm;height:22mm;padding:3px;text-align:center;vertical-align:middle;text-transform:uppercase;font-weight:bold;"><span style="font-size:7px;line-height:8px;">'.$barras.'<br>L-'.$lote."-FV ".$fecha.'<br>'.$inventario["Codigo"]."<br>".$nom.'</span>
    </div>
    </td>
    </tr>
    
    </table>
    
    </div>
    </page>';
/*}*/

/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array('105','25'), 'Es', true, 'UTF-8', array(0,0,0,0));
    $html2pdf->writeHTML($content);
    $direc = 'sticker.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}


?>