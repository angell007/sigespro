<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');

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


$query = "SELECT AI.*, (SELECT CONCAT(F.Nombres,' ', F.Apellidos) FROM Funcionario F WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) AS Funcionario, (SELECT C.Nombre FROM Funcionario F INNER JOIN Cargo C ON F.Id_Cargo=C.Id_Cargo WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) AS Cargo_Funcionario,
(SELECT F.Firma FROM Funcionario F WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) as Firma
 FROM `Ajuste_Individual` AI WHERE AI.Id_Ajuste_Individual=$id";

$oCon= new consulta();
$oCon->setQuery($query);
$encabezado = $oCon->getData();
unset($oCon);

$query = "SELECT P.Nombre_Comercial, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida, ' ') as Nombre_Producto, PAI.Lote, P.Laboratorio_Comercial, PAI.Fecha_Vencimiento, PAI.Cantidad, PAI.Observaciones FROM Producto_Ajuste_Individual PAI INNER JOIN Producto P ON PAI.Id_Producto=P.Id_Producto WHERE PAI.Id_Ajuste_Individual=$id";
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);


/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$tipo="Factura";
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

$codigos ='
    <span style="margin:-5px 0 0 0;font-size:16px;line-height:16px;">Ajuste Individual</span>
    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">'.$encabezado["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">Fecha:'.fecha($encabezado["Fecha"]).'</h5>
    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">Tipo:'.$encabezado["Tipo"].'</h3>
';

        
/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:460px;font-weight:thin;font-size:13px;line-height:18px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($encabezado["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$encabezado["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
                  </td>
                </tr>
              </tbody>
            </table>
            ';
            
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

$contenido = '<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:20px;">
<tr>
    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    <strong>Funcionario:</strong>
    </td>
    <td style="font-size:10px;width:430px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    '.trim($encabezado["Funcionario"]).'
    </td>
    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    <strong>Cargo:</strong>
    </td>
    <td style="font-size:10px;width:120px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    '.$encabezado["Cargo_Funcionario"].'
    </td>
</tr>
</table><br>
<hr style="border:1px dotted #ccc;width:730px;"><br><br>';


$contenido .= '<table  cellspacing="0" cellpadding="0" >
	        	    <tr>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;">Descripción</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;">Laboratorio</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;">Lote</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;">F. Venc.</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;">Cantidad</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;">Observaciones</td>
                    </tr>';
                    

    foreach ($productos as $producto) {
        $contenido.='<tr>
                    <td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:280px;vertical-align:middle;">
                    <strong>
                    '.$producto["Nombre_Comercial"].'
                    </strong><br>
                    <span style="color: gray">'.$producto['Nombre_Producto'].'</span>
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;text-align:left;width:70px;vertical-align:middle;">
	        		'.$producto["Laboratorio_Comercial"].'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:50px;vertical-align:middle;"> 
	        		'.$producto["Lote"].'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:35px;vertical-align:middle;">
	        		'.fecha($producto["Fecha_Vencimiento"]).'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		'.$producto["Cantidad"].'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:150px;vertical-align:middle;">
	        		'.$producto["Observaciones"].'
	        		</td>
                </tr>';
    }

    if($encabezado['Firma']!=''){
      $imagen='<img src="'.$MY_FILE . "DOCUMENTOS/".$encabezado["Identificacion_Funcionario"]."/".$encabezado['Firma'].'"  width="130"><br>';
  }else{
      $imagen='<br><br>______________________________<br>';
  }
	
                
                $contenido .= '</table><br><br><br>';  
                    
	             $contenido.='<table>
                 <tr>
                     <td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">'.$imagen.'
                     
                         Elaborado Por<br>'.$encabezado['Funcionario'].'
                     </td>
                     <td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
                     <br><br>______________________________<br>
                         Recibí Conforme<br>
                     </td>
                 </tr>
                </table>';
	             
	             
/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
		
                <div class="page-content">
                '.$cabecera.'
	             '.$contenido.'
               </div>
        </page>';
            
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/


try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
   $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(2, 2, 2, 2));
   $html2pdf->writeHTML($content);
   $direc = $encabezado['Codigo'].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
   $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>