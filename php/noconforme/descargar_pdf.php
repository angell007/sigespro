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
    case 'Devolucion_Compra':{
        $query = 'SELECT 
        IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," (",PRD.Nombre_Comercial, ") ", PRD.Cantidad," ", PRD.Unidad_Medida, " "), CONCAT(PRD.Nombre_Comercial, " LAB-", PRD.Laboratorio_Comercial)) as Nombre_Producto, POCN.* , PRD.Embalaje, PRD.Nombre_Comercial   
        FROM Producto_Devolucion_Compra POCN 
        INNER JOIN Producto PRD
        ON PRD.Id_Producto = POCN.Id_Producto 
        WHERE POCN.Id_Devolucion_Compra ='.$id ;
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);

        /* echo "<pre>";
        var_dump($productos);
        echo "</pre>"; */

        $query = 'SELECT D.*, p.Nombre as Proveedor, b.Nombre as Bodega, p.Id_Proveedor
        FROM Devolucion_Compra D
        Inner JOIN Proveedor p ON D.Id_Proveedor=p.Id_Proveedor
        LEFT JOIN Bodega b ON D.Id_Bodega=b.Id_Bodega
        WHERE D.Id_Devolucion_Compra='.$id ;
        
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
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha"]).'</h5>
            
        ';

        $contenido = '<table style="font-size:10px;margin-top:10px;padding-bottom:7px;border-bottom: 1px solid #cccccc" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:250px;max-width:250px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                Proveedor
            </td>
            <td style="width:230px;max-width:230px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Nit
            </td>
            <td style="width:250px;max-width:250px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Bodega
            </td>
        </tr>
        <tr>
            <td style="width:250px;max-width:250px;font-weight:bold;background:#f3f3f3;border:1px solid #cccccc;">
                '.$compra["Proveedor"].'
            </td>
            <td style="width:230px;max-width:230px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$compra["Id_Proveedor"].'
            </td>
            <td style="width:250px;max-width:250px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$compra["Bodega"].'
            </td>
        </tr></table>';
        
        $contenido .= '
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:715px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:220px;max-width:220px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:130px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Embalaje
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Cantidad
                </td>              
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Lote
                </td>
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Fecha Venc.
                </td>
                <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Motivo
                </td><td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Factura
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Costo
             </td>
            </tr>';

            $subtotal = 0;
            $iva = 0;
            $total = 0;

            foreach ($productos as $prod) {
                
                $contenido .= '
                    <tr>
                    <td style="background:#f3f3f3; padding:3px 2px;width:220px;max-width:220px;font-size:9px;text-align:left;vertical-align:middle;border:1px solid #cccccc;word-break: break-all !important;"><b>'.$prod["Nombre_Comercial"] .'</b><br><span style="color:gray; font-size:7px;">'.$prod["Nombre_Producto"].'</span></td>
                    <td style="width:130px;max-width:70px;font-size:9px;vertical-align:center;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;"><p style="font-size:9px;color:gray">'.$prod["Embalaje"].'</p></td>
                    <td style="width:50px;max-width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod['Cantidad'].'</td>
                   
                    <td style="width:50px;max-width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Lote"].'</td>
                    <td style="width:50px;max-width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Fecha_Vencimiento"].'</td>
                    <td style="width:60px;max-width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Motivo"].'</td>
                    <td style="width:50px;max-width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Factura"].'</td>
                    <td style="width:50px;max-width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($prod['Costo'],2,",",".").'</td></tr>';

                    $subtotal += $prod["Cantidad"] * $prod["Costo"];
                    $iva += ($prod["Cantidad"] * $prod["Costo"]) * ($prod["Impuesto"]/100);
                    $total = $subtotal+$iva;

                    
            }
            
         $contenido .= '</table>';
         $contenido .= '<table style="margin-top:10px">
         <tr>
             <td style="font-size:10px;width:665px;background:#e9eef0;border-radius:5px;padding:8px;text-align:right;padding:30px 20px">
                 
                 <strong>SubTotal: </strong> $'.number_format($subtotal,2,",",".").'<br><br>
                 <strong>Iva: </strong> $'.number_format($iva,2,",",".").'<br><br>
                 <strong>Total: </strong> $'.number_format($total,2,",",".").'
             </td>
         </tr>
     </table>';

     if($elabora['Firma']!=''){
        $firma='<img src="'.$MY_FILE . "DOCUMENTOS/".$elabora["Identificacion_Funcionario"]."/".$elabora['Firma'].'"  width="230"><br>';
    }else{
        $firma='<br><br><br><br>';
    }
	
	$contenido .='<table style="margin-top:10px;font-size:10px;">
	<tr>
	<td style="width:740px;border:1px solid #cccccc;">
		<strong>Persona Elabor¨®</strong><br><br><br>'.$firma.'
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
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>