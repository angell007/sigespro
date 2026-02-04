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
    case 'Orden_Compra_Nacional':{
        $query = 'SELECT 
        IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto, PRD.Embalaje,PRD.Nombre_Comercial,
         POCN.Costo as Costo , 
         POCN.Cantidad as Cantidad, 
         POCN.Iva as Iva, 
         POCN.Total as Total,
         POCN.Id_Producto as Id_Producto,
         PRD.Cantidad_Presentacion AS Presentacion,
         "0" as Rotativo
    FROM Producto_Orden_Compra_Nacional POCN 
    INNER JOIN Producto PRD 
    ON PRD.Id_Producto = POCN.Id_Producto 
    WHERE POCN.Id_Orden_Compra_Nacional ='.$id ;
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);

        /* echo "<pre>";
        var_dump($productos);
        echo "</pre>"; */

        $query = 'SELECT
                ocn.Codigo,
                DATE_FORMAT(ocn.Fecha, "%r") AS Fecha_Compra,
                ocn.Tipo,
                ocn.Estado,
                p.Nombre AS Proveedor,
                b.Nombre AS Bodega,
                DATE_FORMAT(ocn.Fecha_Entrega_Probable, "%d/%m/%Y") AS Fecha_Probable,
                ocn.Observaciones,
                ocn.Codigo_Qr
                FROM Orden_Compra_Nacional ocn
                LEFT JOIN Proveedor p ON ocn.Id_Proveedor=p.Id_Proveedor
                LEFT JOIN Bodega b ON ocn.Id_Bodega=b.Id_Bodega
                WHERE ocn.Id_Orden_Compra_Nacional='.$id ;
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $compra = $oCon->getData();
        unset($oCon);

        /* echo "<pre>";
        var_dump($compra);
        echo "</pre>";
        exit; */
               
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha_Creacion_Compra"]).'</h5>
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Estado"].'</h3>
        ';

        $contenido = '<table style="font-size:10px;margin-top:10px;padding-bottom:7px;border-bottom: 1px solid #cccccc" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:176px;max-width:176px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                Proveedor
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Bodega
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Fecha de Compra
            </td>
            <td style="width:179px;max-width:179px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Fecha Probable de Entrega
            </td>
        </tr>
        <tr>
            <td style="width:176px;max-width:176px;font-weight:bold;background:#f3f3f3;border:1px solid #cccccc;">
                '.$compra["Proveedor"].'
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$compra["Bodega"].'
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$compra["Fecha_Compra"].'
            </td>
            <td style="width:179px;max-width:179px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$compra["Fecha_Probable"].'
            </td>
        </tr></table>';
        
        $contenido .= '
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:712px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:300px;max-width:365px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:140px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Embalaje
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Cantidad
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Costo
                </td>
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    IVA
                </td>
                <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    SubTotal
                </td>
            </tr>';

            $subtotal = 0;
            $iva = 0;
            $total = 0;

            foreach ($productos as $prod) {
                
                $contenido .= '
                    <tr>
                    <td style="background:#f3f3f3; padding:3px 2px;width:300px;max-width:280px;font-size:9px;text-align:left;vertical-align:middle;border:1px solid #cccccc;word-break: break-all !important;"><b>'.$prod["Nombre_Comercial"] .'</b><br><span style="color:gray">'.$prod["Nombre_Producto"].'</span></td>
                    <td style="width:140px;max-width:70px;font-size:9px;vertical-align:center;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;"><p style="font-size:9px;color:gray">'.$prod["Embalaje"].'</p></td>
                    <td style="width:70px;max-width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.number_format($prod["Cantidad"],0,",",".").'</td>
                    <td style="width:70px;max-width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($prod["Costo"],2,",",".").'</td>
                    <td style="width:40px;max-width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Iva"].'%</td>
                    <td style="width:80px;max-width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($prod["Total"],2,",",".").'</td></tr>';

                    $subtotal += $prod["Cantidad"] * $prod["Costo"];
                    $iva += ($prod["Cantidad"] * $prod["Costo"]) * ($prod["Iva"]/100);
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
	<td style="width:740px;border:1px solid #cccccc;">
		<strong>Persona Elaboró</strong><br><br><br><br><br><br><br>
		'.$elabora["Nombres"]." ".$elabora["Apellidos"].'
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
    $html2pdf->Output($direc,'D'); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>