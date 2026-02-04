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
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

/* DATOS DEL ARCHIVO A MOSTRAR */
$oItem = new complex($tipo,"Id_".$tipo,$id);
$data = $oItem->getData();
unset($oItem);

$query="SELECT D.*, (SELECT CONCAT(F.Nombres,' ',F.Apellidos) FROM Funcionario F WHERE F.Identificacion_Funcionario=D.Identificacion_Funcionario) as Funcionario, (SELECT Firma FROM Funcionario WHERE Identificacion_Funcionario=D.Identificacion_Funcionario) as Firma FROM Devolucion_Interna D WHERE D.Id_Devolucion_Interna=$id";

$oCon= new consulta();
$oCon->setQuery($query);
$data = $oCon->getData();
unset($oCon);
/* FIN DATOS DEL ARCHIVO A MOSTRAR */

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
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/


$query = 'SELECT PR.Cantidad, CONCAT_WS(" ",P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida) as Nombre,P.Nombre_Comercial, P.Laboratorio_Generico, P.Embalaje,P.Codigo_Cum
FROM Producto_Devolucion_Interna PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
WHERE PR.Id_Devolucion_Interna='.$id.' ORDER BY Nombre_Comercial';
        
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);       
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha"]).'</h5>
            <h6 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">DEVOLUCIÓN INTERNA </h6>
        
        ';
        $contenido = '<table cellspacing="0" cellpadding="0">
        <tr>
            <td  style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;width:365px;">Origen</td>
            <td  style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;width:370px;">Destino</td>
        </tr>
        <tr>
            <td  style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
            '.$data["Nombre_Origen"].'
            </td>
            <td  style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
            '.$data["Nombre_Destino"].'
            </td> 
     
         </tr>
        </table>
        <table style="margin-top:10px" cellspacing="0" cellpadding="0">
            <tr style="background:#e9eef0;border-radius:5px;">
                <td style="font-size:10px;width:718px;padding:8px">
                    <strong>Observaciones</strong><br>
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
		<td style="width:10px;background:#cecece;;border:1px solid #cccccc;"></td>
                <td style="width:500px;max-width:500px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:150px;max-width:150px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Laboratorio
                </td>
               
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Cantidad
                </td>
            </tr>';
            
            $max=0;
            foreach($productos as $prod){  $max++;
                $contenido .='<tr>
                    <td style="width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;vertical-align:middle">'.$max.'</td>
                    <td style="padding:3px 2px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;"><strong>'.$prod["Nombre_Comercial"] .'</strong> <br>   '.$prod['Nombre'].' <br> <strong>Codigo Cum: </strong> '.$prod['Codigo_Cum'].'</td>
                     <td style="padding:3px 2px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Laboratorio_Generico"].'</td>
                  
                    <td style="font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
                </tr>';
            }
            
         $contenido .= '</table>';
         $imagen='';
         $firma1='';
         $firma2='';

	if($elabora['Firma']!=''){
        $imagen='<img src="'.$MY_FILE . "DOCUMENTOS/".$elabora["Identificacion_Funcionario"]."/".$elabora['Firma'].'"  width="230">';
    }else{
        $imagen='<br><br><br><br>';
    }
   

	$contenido .='<table style="margin-top:10px;font-size:10px;" cellpadding="0" cellspacing="0">
	<tr>
	<td style="width:742px;border:1px solid #cccccc;">
		<strong>Persona Elaboró</strong><br>'.$imagen.'<br>
		'.$elabora["Nombres"]." ".$elabora["Apellidos"].'
	</td> 
   
	</tr>
	</table>';
	


/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
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

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>