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
    case 'Nota_Credito':{

            $query = 'SELECT NC.*, FV.Codigo as Factura, FV.Fecha_Documento as Fecha_Factura, C.Nombre as Cliente , "Nota_Credito" AS Tipo , "Factura_Venta"  AS Tipo_Factura ,   Id_Factura  
            FROM Nota_Credito NC
            INNER JOIN  Factura_Venta FV 
            ON NC.Id_Factura=FV.Id_Factura_Venta
            INNER JOIN Cliente C
            ON NC.Id_Cliente=C.Id_Cliente
            WHERE NC.Id_Nota_Credito ='.$id;

  
        $oCon= new consulta();
        //$oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $datos = $oCon->getData();
        unset($oCon);
        
        
         $query="SELECT F.Codigo, R.Tipo_Resolucion 
        FROM  $datos[Tipo_Factura]  F
        INNER JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion

        WHERE F.Id_$datos[Tipo_Factura] = $datos[Id_Factura]   AND R.Tipo_Resolucion = 'Resolucion_Electronica'       
        " ; 
   
        $oCon = new consulta();
        $oCon->setQuery($query);
        $res = $oCon->getData();
        

        $query2 = 'SELECT PFV.*, (PFV.Subtotal) as Subtotal,  
        IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
            CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,
            CONCAT_WS(" // ",PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico  ) as Laboratorios,
        
        PRD.Nombre_Comercial, PRD.Embalaje, PRD.Invima
        FROM Producto_Nota_Credito PFV
        INNER JOIN Producto PRD
        ON PFV.Id_Producto=PRD.Id_Producto
        WHERE PFV.Id_Nota_Credito = '.$id;
            
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query2);
        $productos = $oCon->getData();        
        unset($oCon);
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $recibe = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <span style="margin:5px 0 0 0;font-size:16px;line-height:10px;">Nota Crédito Electronica</span>
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha"]).'</h5>
           
        ';
        $contenido = '<table style="">
            <tr>
                <td style="width:720px; padding-right:0px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <th  style=" width:230px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Cliente</th>
                            <th  style=" width:250px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Factura</th>
                            <th  style=" width:250px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Fecha Factura</th>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
                            '.$datos["Cliente"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
                            '.$datos["Factura"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
                            '.fecha($datos['Fecha_Factura']).'
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
                    '.$datos["Observacion"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
		<td style="width:10px;background:#cecece;;border:1px solid #cccccc;"></td>
                <td style="width:275px;max-width:275px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Laboratorios
             </td>
                <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Lote
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Fecha Vencimiento
                </td>
                <td style="width:30px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Cantidad
                </td>
                <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Precio Venta
                </td>
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Impuesto
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Total
                </td>
            </tr>';
            
            $max=0;
            foreach($productos as $prod){  $max++;
                $contenido .='<tr>
                    <td style="vertical-align:middle;width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">'.$max.'</td>
                    <td style="vertical-align:middle;padding:3px 2px;width:275px;max-width:275px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Nombre_Producto"].'</td>
                    <td style="vertical-align:middle;width:100px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Laboratorios"].'</td>
                    <td style="vertical-align:middle;width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Lote"].'</td>
                    <td style="vertical-align:middle;width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Fecha_Vencimiento"].'</td>
                    <td style="vertical-align:middle;width:30px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
                    <td style="vertical-align:middle;width:60px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($prod["Precio_Venta"],2,",",".").'</td>
                    <td style="vertical-align:middle;width:40px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Impuesto"].'</td>
                    <td style="vertical-align:middle;width:50px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($prod["Subtotal"],2,",",".").'</td>
                </tr>';

                $iva+=($prod['Precio_Venta']*$prod['Cantidad'])*($prod['Impuesto']/100);
    $subtotal+=($prod['Subtotal']);
            }
              $contenido.='<tr>
                <td colspan="7" style="text-align:center">CUDE:'.$datos["Cude"].'</td>
            </tr>';
            $total=$iva+$subtotal;
         $contenido .= '</table>';
         $contenido .= '<table style="margin-top:10px">
         <tr>
             <td style="font-size:10px;width:663px;background:#e9eef0;border-radius:5px;padding:8px;text-align:right;padding:30px 20px">
                 
                 <strong>Subtotal: </strong> $'.number_format($subtotal,2,",",".").'<br><br>
                 <strong>Iva: </strong> $'.number_format($iva,2,",",".").'<br><br>
                 <strong>Total: </strong> $'.number_format($total,2,",",".").'
             </td>
         </tr>
     </table>';

	
	$contenido .='<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
	<tr>
	<td style="width:720px;border:1px solid #cccccc;">
		<strong>Persona Elabora</strong><br><br><br><br><br>
		'.$recibe["Nombres"]." ".$recibe["Apellidos"].'
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
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:350px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:185px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:130px;">';
                  
                   if($res["Tipo_Resolucion"]!="Resolucion_Electronica"){
                        $nombre_fichero =  $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$datos["Codigo_Qr"];
                    }else{
                        $nombre_fichero =  $_SERVER["DOCUMENT_ROOT"].'ARCHIVOS/FACTURACION_ELECTRONICA/'.$datos["Codigo_Qr"];
                    }
             
                
                    if($datos["Codigo_Qr"] =='' || !file_exists($nombre_fichero)){
                        
                    $cabecera.='<img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png'.'" style="max-width:100%;margin-top:-10px;" />';
                    }else{
                        
                    $cabecera.='<img src="'.$nombre_fichero.'" style="max-width:100%;margin-top:-10px;" />';
                    }

                  
                    
          $cabecera.='</td>
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
//echo $content;exit;
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