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

$oItem = new complex('Remision',"Id_Remision",$id);
$rem = $oItem->getData();
unset($oItem);

$oItem = new complex($rem["Tipo_Destino"],"Id_".$rem["Tipo_Destino"],$rem["Id_Destino"]);
$destino = $oItem->getData();
unset($oItem);

$nom='';
$tel='';
if($rem["Tipo_Destino"]=="Cliente"){
	$nom=$destino["Nombre"]."<br>N.I.T.:".number_format($destino["Id_Cliente"],0,",",".");
	$tel='Tel. '.$destino["Celular"];
	
	$oItem = new complex("Municipio","Id_Municipio",$destino["Ciudad"]);
	$mun = $oItem->getData();
	unset($oItem);
	
	$oItem = new complex("Departamento","Id_Departamento",$mun["Id_Departamento"]);
	$dep = $oItem->getData();
	unset($oItem);

}else{
	$nom=$destino["Nombre"];
	$tel='Tel. '.$destino["Telefono"];
	$oItem = new complex("Departamento","Id_Departamento",$destino["Departamento"]);
	$dep = $oItem->getData();
	unset($oItem);
	
	$oItem = new complex("Municipio","Id_Municipio",$destino["Municipio"]);
	$mun = $oItem->getData();
	unset($oItem);
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

</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" style="width:238mm;height:157mm;background-image: url('.$_SERVER["DOCUMENT_ROOT"].'assets/images/fondo-sticker-01.jpg);background-attachment: fixed;background-repeat: no-repeat; background-position: left top; background-size:cover;" >
<div style="width:230mm;height:120mm;padding:15px;text-align:center;vertical-align:middle;font-size:40px;line-height:40px;text-transform:uppercase;font-weight:bold;">'.$nom.'<br>'.$destino["Direccion"].'<br>'.$mun["Nombre"]." - ".$dep["Nombre"].'<br>'.$tel.'<br><br><span style="font-size:28px;line-height:30px;">'.$rem["Codigo"].'<br></span>
</div>
</div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array('238','158'), 'Es', true, 'UTF-8', array(0,0,0,0));
    $html2pdf->writeHTML($content);
    $direc = 'sticker.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}


?>