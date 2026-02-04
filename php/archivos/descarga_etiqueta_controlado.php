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

$query='SELECT 
I.Codigo, P.Nombre_Comercial, P.Laboratorio_Generico, P.Cantidad_Presentacion, I.Cantidad, I.Lote, I.Fecha_Vencimiento
FROM Inventario_Nuevo I 
INNER JOIN Producto P
ON I.Id_Producto = P.Id_Producto
WHERE I.Id_Inventario_Nuevo ='.$id;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);




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
td{

}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

foreach($productos as $prod){

$nom=$prod['Nombre_Comercial']." ".$prod['Laboratorio_Generico'];

$cantidad = ($prod["Cantidad"]/$prod["Cantidad_Presentacion"])/2;

$barras= generabarras($prod["Codigo"]);
$lote=$prod['Lote'];
$fecha=$prod['Fecha_Vencimiento'];
//echo $barras.'<br>'.$lote.'-FV '.$fecha.'<br>'.$inventario["Codigo"]."<br><br>".$nom;

$temp = 'tempimg'.uniqid().'.jpg';
$bar2= str_replace('">','',str_replace('<img src="','',$barras));
$dataURI    = $barras;
$dataPieces = explode(',',$dataURI);
$encodedImg = $dataPieces[1];
$decodedImg = base64_decode($encodedImg);
file_put_contents($temp,$decodedImg);


/*for($h=0;$h<$cantidad;$h++){*/
    $content.= '<page backtop="0mm" backbottom="0mm">
    <div class="page-content" style="width:103mm;height:23mm;" >
    <table style="width:98mm;height:23mm;" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:50%;">
    <div style="width:50mm;height:22mm;padding:3px;text-align:center;vertical-align:middle;text-transform:uppercase;letter-spacing:1px !important;"><span style="font-size:7px;line-height:8px;"><img src="'.$temp.'"><br>L-'.$lote.'-F.V. '.$fecha.'<br>'.$prod["Codigo"]."<br>".$nom.'</span>
    </div>
    </td>
    <td style="width:50%">
     <div style="width:50mm;height:22mm;padding:3px;text-align:center;vertical-align:middle;text-transform:uppercase;letter-spacing:1px !important;"><span style="font-size:7px;line-height:8px;"><img src="'.$temp.'"><br>L-'.$lote."-FV ".$fecha.'<br>'.$prod["Codigo"]."<br>".$nom.'</span>
    </div>
    </td>
    </tr>
    
    </table>
    </div>
    </page>';
/*}*/

}
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array('105','25'), 'Es', true, 'UTF-8', array(0,0,0,0));
    $html2pdf->writeHTML($content);
    $direc = 'sticker.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
    unlink($temp);
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}


?>