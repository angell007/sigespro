<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
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
switch($tipo){
    case 'Remision':{
        $query = 'SELECT PR.Lote, PR.Fecha_Vencimiento, IFNULL(CONCAT( P.Principio_Activo, " ",
P.Presentacion, " ",
P.Concentracion, " (", P.Nombre_Comercial,") ",
P.Cantidad," ",
P.Unidad_Medida, " " ), CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial)) AS Nombre_Producto, PR.Cantidad, PR.Precio, PR.Descuento, PR.Impuesto, PR.Subtotal, P.Laboratorio_Generico, P.Embalaje
FROM Producto_Remision PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
WHERE PR.Id_Remision='.$id.' ORDER BY Nombre_Producto';
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);
        


        $oItem = new complex($data["Tipo_Origen"],"Id_".$data["Tipo_Origen"],$data["Id_Origen"]);
        $origen = $oItem->getData();
        unset($oItem);
        
        $oItem = new complex($data["Tipo_Destino"],"Id_".$data["Tipo_Destino"],$data["Id_Destino"]);
        $destino = $oItem->getData();
        unset($oItem);
        
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha"]).'</h5>
            <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;">Tipo '.$data["Tipo"].'</h4>
        ';
        $contenido = '<table style="">
            <tr>
                <td style="width:350px; padding-right:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <td colspan="2" style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Origen</td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$origen["Nombre"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$origen["Direccion"].'
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Tel.:</strong> '.$origen["Telefono"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Correo:</strong> '.$origen["Correo"].'
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:350px; padding-leftt:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <td colspan="2" style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Destino</td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$destino["Nombre"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            '.$destino["Direccion"].'
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Tel.:</strong> '.$destino["Telefono"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Correo:</strong> '.$destino["Correo"].'
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
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr><
		<td style="width:10px;background:#cecece;;border:1px solid #cccccc;"></td>
                <td style="width:280px;max-width:280px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
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
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Precio
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Desc.
                </td>
                <td style="width:30px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    IVA
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Subtotal
                </td>
            </tr>';
            
            $max=0;
            foreach($productos as $prod){  $max++;
                $subtotal += ($prod["Subtotal"]);
                $contenido .='<tr>
                    <td style="width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">'.$max.'</td>
                    <td style="padding:3px 2px;width:280px;max-width:280px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Nombre_Producto"].'</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Lote"].'</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Fecha_Vencimiento"].'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$ '.number_format($prod["Precio"],2,',','.').'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Descuento"].'% </td>
                    <td style="width:30px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Impuesto"].'</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;text-align:right">$ '.number_format($prod["Subtotal"],2,',','.').'</td>
                </tr>';

                
                $iva += ($prod["Cantidad"] * $prod["Precio"]) * ($prod["Impuesto"]/100);
                $total = $subtotal+$iva;
            }
            
         $contenido .= '</table>';

         $contenido .= '<table style="margin-top:10px">
         <tr>
             <td style="font-size:10px;width:670px;background:#e9eef0;border-radius:5px;padding:8px;text-align:right;padding:30px 20px">
                 
                 <strong>SubTotal: </strong> $'.number_format($subtotal,2,",",".").'<br><br>
                 <strong>Iva: </strong> $'.number_format($iva,2,",",".").'<br><br>
                 <strong>Total: </strong> $'.number_format($total,2,",",".").'
             </td>
         </tr>
     </table>';

	
	$contenido .='<table style="margin-top:10px;font-size:10px;">
	<tr>
	<td style="width:240px;border:1px solid #cccccc;">
		<strong>Persona Elaboró</strong><br><br><br><br><br><br><br>
		'.$elabora["Nombres"]." ".$elabora["Apellidos"].'
	</td> 
	<td style="width:240px;border:1px solid #cccccc;">
		<strong>Alistamiento Fase 1</strong><br><br><br><br><br><br><br>
	</td>
	<td style="width:240px;border:1px solid #cccccc;">
		<strong>Alistamiento Fase 2</strong><br><br><br><br><br><br><br>
	</td>
	</tr>
	</table>';
	
        break;
    }
}
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
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
                  <img src="'.(($data["Codigo_Qr"] ==''  || !file_exists($_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ))? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
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