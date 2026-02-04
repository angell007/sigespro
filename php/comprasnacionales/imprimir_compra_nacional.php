<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

//$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
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
$query = 'SELECT
ocn.Codigo,
DATE_FORMAT(ocn.Fecha, "%d/%m/%Y") AS Fecha_Compra,
ocn.Tipo,
ocn.Estado,
ocn.Identificacion_Funcionario,
p.Nombre AS Proveedor,
b.Nombre AS Bodega,
DATE_FORMAT(ocn.Fecha_Entrega_Probable, "%d/%m/%Y") AS Fecha_Probable,
ocn.Observaciones,
ocn.Subtotal_Final,
ocn.Iva_Final,
ocn.Total_Final,
ocn.Codigo_Qr
FROM Orden_Compra_Nacional ocn
LEFT JOIN Proveedor p ON ocn.Id_Proveedor=p.Id_Proveedor
LEFT JOIN Bodega b ON ocn.Id_Bodega=b.Id_Bodega
WHERE ocn.Id_Orden_Compra_Nacional='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$compra = $oCon->getData();
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

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

        $query2 = 'SELECT 
                CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ) as Nombre, 
                POCN.Costo as Costo , 
                POCN.Cantidad as Cantidad, 
                POCN.Iva as Iva, 
                POCN.Total as Total,
                POCN.Id_Producto as Id_Producto,
                "0" as Rotativo
           FROM Producto_Orden_Compra_Nacional POCN 
           INNER JOIN Producto P 
           ON P.Id_Producto = POCN.Id_Producto 
           WHERE POCN.Id_Orden_Compra_Nacional ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$compra["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$compra["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.$compra["Fecha_Compra"].'</h5>
            <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;">Tipo '.$compra["Tipo"].'</h4>
        ';
        $contenido = '<table style="">
            <tr>
                <td style="width:350px; padding-right:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <td  style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Proveedor</td>
                            <td  style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Bodega</td>
                            <td  style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Fecha de Compra</td>
                            <td  style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Fecha Probable de Entrega</td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$compra["Proveedor"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$compra["Bodega"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$compra["Fecha_Compra"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$compra["Fecha_Probable"].'
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:710px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$compra["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr><
		        <td style="width:10px;background:#cecece;;border:1px solid #cccccc;"></td>
                <td style="width:480px;max-width:500px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Cantidad
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Costo
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Iva
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Subtotal
                </td>
            </tr>';
            
            $max=0;
            foreach($productos as $prod){  $max++;
                $contenido .='<tr>
                    <td style="width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">'.$max.'</td>
                    <td style="padding:3px 2px;width:480px;max-width:500px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Nombre"].'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">'.number_format($prod["Costo"],2).'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">'.number_format($prod["Iva"],2).'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">'.number_format($prod["Total"],2).'</td>
                </tr>';
            }
            
         $contenido .= '</table>
                         <table cellspacing="0" cellpadding="0" style="margin-top:10px; text-transform:uppercase;">
                            <tr>
                                <td style="Width:83%"></td>
                                <td  style="font-size:10px;font-weight:bold;text-align:right;border:1px solid #cccccc;">Subtotal</td>
                                <td  style="width:50px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;"">'.number_format($compra["Subtotal_Final"],2).'</td>
                            </tr>
                            <tr>
                                <td style="Width:83%"></td>
                                <td  style="font-size:10px;font-weight:bold;text-align:center;border:1px solid #cccccc;">Iva</td>
                                <td  style="width:50px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;"">'.number_format($compra["Iva_Final"],2).'</td>
                            </tr>
                            <tr>
                                <td style="Width:83%"></td>
                                <td  style="font-size:10px;font-weight:bold;text-align:center;border:1px solid #cccccc;">Total</td>
                                <td  style="width:50px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;"">'.number_format($compra["Total_Final"],2).'</td>
                            </tr>
                        </table>';

	
	$contenido .='<table style="margin-top:10px;font-size:10px;">
                	<tr>
                	<td style="width:240px;border:1px solid #cccccc;">
                		<strong>Persona Elaboró</strong><br><br><br><br><br><br><br>
                		'.$elabora["Nombres"]." ".$elabora["Apellidos"].'
                	</td> 
                	<td style="width:240px;border:1px solid #cccccc;">
                		<strong>Persona Recibio</strong><br><br><br><br><br><br><br>
                	</td>
                	<td style="width:240px;border:1px solid #cccccc;">
                		<strong>Persona Factura</strong><br><br><br><br><br><br><br>
                	</td>
                	</tr>
                </table>';
                	
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="https://sigesproph.com.co/assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
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
                  <img src="'.($compra["Codigo_Qr"] =='' ? $URL.'assets/images/sinqr.png' : $URL.'IMAGENES/QR/'.$compra["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
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
    $direc = $compra["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc,'D'); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>