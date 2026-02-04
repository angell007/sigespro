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


$query="SELECT MV.*, DATE(MV.Fecha) as Fecha , (SELECT B.Nombre FROM Bodega B WHERE B.Id_Bodega=MV.Id_Bodega_Origen) as Origen, (SELECT B.Nombre FROM Bodega B WHERE B.Id_Bodega=MV.Id_Bodega_Destino) as Destino
FROM Movimiento_Vencimiento MV WHERE MV.Id_Movimiento_Vencimiento=$id";

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


 $query = 'SELECT PR.Lote, PR.Fecha_Vencimiento, IFNULL(CONCAT( P.Principio_Activo, " ",
P.Presentacion, " ",
P.Concentracion, " (", P.Nombre_Comercial,") ",
P.Cantidad," ",
P.Unidad_Medida, " " ), CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial)) AS Nombre_Producto, PR.Cantidad, P.Laboratorio_Generico, P.Embalaje
FROM Producto_Movimiento_Vencimiento PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
WHERE PR.Id_Movimiento_Vencimiento='.$id.' ORDER BY Nombre_Producto';
        
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);
        

        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">Movimiento Vencimientos</h3>
            <h6 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data['Fecha'].' </h6>
            
        ';
        $contenido = '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:360px; padding-right:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <td  style="width:360px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Origen</td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:360px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
                            Bodega '.$data["Origen"].'
                            </td>
                        </tr>
                     
                    </table>
                </td>
                <td style="width:360px; padding-leftt:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <td style="width:360px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Destino</td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:360px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
                            Bodega '.$data["Destino"].'
                            </td>
                         
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="font-size:10px;width:720px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr><
		<td style="width:10px;background:#cecece;;border:1px solid #cccccc;"></td>
                <td style="width:400px;max-width:400px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:100px;max-width:100px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Laboratorio
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Lote
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    F. Vencimiento
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Cant.
                </td>
            </tr>';
            
            $max=0;
            foreach($productos as $prod){  $max++;
                $contenido .='<tr>
                    <td style="width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">'.$max.'</td>
                    <td style="padding:3px 2px;width:380px;max-width:400px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Nombre_Producto"].'<strong> EMB: </strong>'.$prod["Embalaje"].'</td>
                     <td style="padding:3px 2px;width:100px;max-width:100px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Laboratorio_Generico"].'</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Lote"].'</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Fecha_Vencimiento"].'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
                </tr>';
            }
            
         $contenido .= '</table>';
       
 
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
                  <td style="width:250px;text-align:right">
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