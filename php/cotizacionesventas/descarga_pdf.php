<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : '' );
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
    case 'Cotizacion_Venta':{
        $query = 'SELECT P.Nombre_Comercial, 
                    P.Laboratorio_Comercial, 
                    P.Laboratorio_Generico, 
                    CONCAT_WS(" ",P.Principio_Activo, P.Presentacion, P.Concentracion, P.Cantidad, P.Unidad_Medida) as producto, 
                    P.Id_Producto, 
                    P.Codigo_Cum as Cum,
                    P.Embalaje, 
                    P.Cantidad_Presentacion, 
                    P.Invima, 
                    (CASE WHEN P.Gravado="Si" THEN "19%" ELSE "0%" END) AS Impuesto, 
                    PCV.Cantidad as Cantidad, 
                    PCV.Descuento,
                    PCV.Precio_Venta as Precio_Venta, 
                    PCV.Iva, 
                    PCV.Subtotal as Subtotal, 
                    PCV.Observacion,
                    PCV.Id_Producto_Cotizacion_Venta as idPcv
                FROM Producto P 
                Inner JOIN Producto_Cotizacion_Venta PCV ON P.Id_Producto=PCV.Id_Producto 
                WHERE PCV.Id_Cotizacion_Venta =  '.$id ;
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);

        $query = 'SELECT 
        CV.Fecha_Documento as Fecha , CV.Observacion_Cotizacion_Venta as Observaciones, CV.Codigo as Codigo, CV.Fecha_Documento_Edicion as FechaEdicion,
        CV.Condiciones_Comerciales, CV.Codigo_Qr, CV.Id_Funcionario,
        C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, M.Nombre as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente,
        LG.Nombre as NombreGanancia, LG.Porcentaje as PorcentajeGanancia, LG.Id_Lista_Ganancia as IdLG,
        B.Nombre as NombreBodega , B.Id_Bodega as IdBodega
        FROM Cotizacion_Venta CV, Cliente C , Lista_Ganancia LG, Bodega B, Municipio M
        WHERE  CV.Id_Lista_Ganancia = LG.Id_Lista_Ganancia 
        AND CV.Id_Lista_Ganancia = LG.Id_Lista_Ganancia 
        AND CV.Id_Cliente = C.Id_Cliente 
        AND M.Id_Municipio = C.Ciudad
        AND CV.Id_Cotizacion_Venta = '.$id ;
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $cotizacion = $oCon->getData();
        unset($oCon);
               
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$cotizacion["Id_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$cotizacion["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($cotizacion["Fecha"]).'</h5>
        ';

        $observaciones = $cotizacion["Observaciones"] == "" ? "Sin Observaciones" : $cotizacion["Observaciones"];
        
        $contenido = '<table style="background:#E6E6E6; font-size: 10px" cellpadding="0" >
            <tr style="background:#E6E6E6">
                <td style="width:335px;padding:10px;padding-top:0">
                    <strong>BODEGA: </strong> '.$cotizacion['NombreBodega'].'
                </td>
                <td style="width:335px;padding:10px;padding-top:0">
                    <strong>CLIENTE: </strong> '.$cotizacion['NombreCliente'].'
                </td>
                
            </tr>
            <tr style="background:#E6E6E6">
                <td style="width:335px;padding:10px;padding-top:0">
                    <strong>GANANCIA APLICADA: </strong> '.strtoupper($cotizacion['NombreGanancia']).'
                </td>
                <td style="width:335px;padding:10px;padding-top:0">
                    <strong>DIRECCI脫N: </strong> '.$cotizacion['DireccionCliente'].'
                </td>
                
            </tr>
            <tr style="background:#E6E6E6">
                <td style="width:335px;padding:10px;padding-top:0">
                </td>
                <td style="width:335px;padding:10px;padding-top:0">
                    <strong>CIUDAD: </strong> '.$cotizacion['CiudadCliente'].'
                </td>
                
            </tr>
            <tr style="background:#E6E6E6">
                <td style="width:335px;padding:10px;padding-top:0">
                </td>
                <td style="width:335px;padding:10px;padding-top:0">
                    <strong>TELEFONO: </strong> '.$cotizacion['TelefonoCliente'].'
                </td>
                
            </tr>
        </table>
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:710px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$observaciones.'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr><
        <td style="width:10px;max-width:10px;background:#cecece;font-weight:bold;border:1px solid #cccccc;">
            Nro.
        </td>
                <td style="width:230px;max-width:230px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Observacion
                </td>
                <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Codigo CUM
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Laboratorio
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Cantidad
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Precio
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Impuesto
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Subtotal
                </td>
            </tr>';

            
            $max = 0;
            foreach($productos as $prod){  $max++;
                $contenido .='<tr>
                    <td style="width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">'.$max.'</td>
                    <td style="padding:3px 2px;width:230px;max-width:230px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;"><b>'.$prod["Nombre_Comercial"].'</b>
                    <p style="color:gray; font-size:8px; margin:0; padding:0">' .$prod["Embalaje"].'</p></td>
                    <td style="width:80px;font-size:8px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'. $prod["Observacion"].'</td>
                    <td style="width:80px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cum"].'</td>
                    <td style="width:80px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.($prod["Laboratorio_Generico"]?$prod["Laboratorio_Generico"]:$prod["Laboratorio_Comercial"]).'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$ '.number_format($prod["Precio_Venta"],2,",",".").'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Impuesto"].'</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$ '.number_format($prod["Subtotal"],2,",",".").'</td>
                </tr>';

                $impuesto = str_replace("%","",$prod["Impuesto"]); // Quitar el signo de porcentaje
                $subt = $prod["Cantidad"] * $prod["Precio_Venta"];
                $subtotal += $subt;
                $iva_t = ($subt) * ($impuesto/100);
                $iva += $iva_t;
                $total += $subt + $iva_t;
            }
            
         $contenido .= '</table>';

         $contenido .= '<table style="margin-top:10px">
         <tr>
             <td style="font-size:10px;width:670px;background:#e9eef0;border-radius:5px;padding:8px;text-align:right;padding:30px 20px">
                 
                 <strong>SubTotal: </strong>$ '.number_format($subtotal,2,",",".").'<br><br>
                 <strong>Iva: </strong>$ '.number_format($iva,2,",",".").'<br><br>
                 <strong>Total: </strong>$ '.number_format($total,2,",",".").'
             </td>
         </tr>
     </table>';

	
        $contenido .='<table style="margin-top:10px;font-size:10px;">
        <tr>
        <td style="width:240px;border:1px solid #cccccc;">
            <strong>Persona Elabor贸</strong><br><br><br><br><br><br><br>
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
                  <img src="'.($cotizacion["Codigo_Qr"] !=='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$cotizacion["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
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
    $direc = $cotizacion["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>