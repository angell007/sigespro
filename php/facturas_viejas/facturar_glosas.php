<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');

ini_set('max_execution_time', 1500);

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '1' );
$tipo = isset($_REQUEST['Tipo']) ? $_REQUEST['Tipo'] : false;

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
$file='';
$directorio = opendir("./FACTURAS_GLOSAS"); 
$i=0;
while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
{   
    if ($file != "." && $file != ".."){
        $i++;
        $file.="'".str_replace(".pdf","",$archivo)."',";
    }
}
$file=trim($file,",");

$query = 'SELECT FG.Codigo, 
          DATE(F.Fecha_Documento) AS Fecha, 
          F.Id_Cliente, D.Numero_Documento AS Id_Paciente, 
          CONCAT_WS(" ", PC.Primer_Nombre, PC.Segundo_Nombre, PC.Primer_Apellido, PC.Segundo_Apellido) AS Nombre_Paciente, 
          PC.Id_Departamento AS Departamento, Cod_Municipio_Dane AS Ciudad, IF(Id_Regimen = 1, "Contributivo","Subsidiado") AS Regimen, IF(TS.Nombre != "EVENTO", TS.Nombre, "") AS Tipo_Servicio, D.Codigo AS Codigo_Dis, 
          IF(CONVERT(SUBSTRING(FG.Codigo, 3), SIGNED) <= 30000, "18762009297847", "18762016188496") AS Resolucion, C.Nombre AS NombreCliente, C.Id_Cliente AS IdCliente, C.Direccion AS DireccionCliente, C.Condicion_Pago, D.Numero_Documento, (SELECT Nombre FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS CiudadCliente,
          IF(CONVERT(SUBSTRING(FG.Codigo, 3), SIGNED) <= 30000, "2018-07-23", "2019-08-09") AS F_Inicio,
          IF(CONVERT(SUBSTRING(FG.Codigo, 3), SIGNED) <= 30000, "2020-01-23", "2021-02-09") AS F_Fin,
          IF(CONVERT(SUBSTRING(FG.Codigo, 3), SIGNED) <= 30000, "1", "30001") AS Inicio,
          IF(CONVERT(SUBSTRING(FG.Codigo, 3), SIGNED) <= 30000, "30000", "120000") AS Fin,
          "NP" AS Prefijo,
          "4645" AS Actividad,
          DATE_ADD(DATE(F.Fecha_Documento), INTERVAL CONVERT(C.Condicion_Pago, SIGNED) DAY) AS Fecha_Pago
          FROM Facturas_Glosas FG 
          INNER JOIN Factura F ON F.Codigo = FG.Codigo 
          INNER JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion 
          INNER JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio 
          INNER JOIN Paciente PC ON PC.Id_Paciente = D.Numero_Documento
          INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
          WHERE FG.Codigo NOT IN ('.$file.')
          LIMIT 20';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$facturas = $oCon->getData();

foreach($facturas as $cliente){
$i++;
$query = "SELECT CONCAT_WS(' ',P.Nombre_Comercial, P.Presentacion, P.Concentracion, ' (', P.Principio_Activo,') ', P.Cantidad,' ', P.Unidad_Medida ) AS producto, P.Invima, P.Codigo_Cum AS Cum, PF.Fecha_Vencimiento, PF.Lote, PF.Cantidad, PF.Precio, PF.Descuento, PF.Impuesto, PF.Subtotal FROM Producto_Factura PF INNER JOIN Producto P ON PF.Id_Producto = P.Id_Producto INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura WHERE Codigo = '$cliente[Codigo]'" ;
/* $query = 'SELECT 
    PFV.Nombre_Producto as producto, 
    PFV.Invima,
    PFV.Cum as Cum, 
    PFV.Fecha_Vencimiento, 
    PFV.Lote,  
    PFV.Cantidad as Cantidad,
    PFV.Precio as Precio,
    PFV.Iva as Impuesto,
    PFV.Descuento as Descuento,
    PFV.Total as Subtotal
   FROM Z_Factura_Vieja PFV 
   WHERE PFV.Codigo =  "'.$cliente["Codigo"].'"'  ; */
 
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon); 


$oItem = new complex("Funcionario","Identificacion_Funcionario",1102380914);
$func = $oItem->getData();
unset($oItem);


/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$tipo="Factura";
$style='<style>
   
.page-content{
width:750px;
pading:0;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:10px;
    line-height: 11px;
}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/


$titulo = 'Factura NO POS';

$codigos ='
    <span style="margin:-5px 0 0 0;font-size:13px;line-height:13px;">'.$titulo.'</span>
    <h3 style="margin:0 0 0 0;font-size:15px;line-height:15px;">'.$cliente["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">F. Expe.:'.fecha($cliente["Fecha"]).'</h5>
    <h4 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">F. Venc.:'.fecha($cliente["Fecha_Pago"]).'</h4>
    <h4 style="margin:0 0 0 0;font-size:13px;line-height:13px">'.$cliente["Cod_Dis"].'</h4>
    <h4 style="margin:0 0 0 0;font-size:13px;line-height:13px">'.$cliente["Tipo_Servicio"].'</h4>
';

$condicion_pago = $cliente["Condicion_Pago"] == "CONTADO" ? $cliente["Condicion_Pago"] : $cliente["Condicion_Pago"] . " Días";

$nombre_paciente = $cliente["Nombre_Paciente"];
$regimen = $cliente['Regimen'];
      
/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:50px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:460px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'<br>
                    REGIMEN COMÚN
                  </td>
                  <td style="width:150px;text-align:right;">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($cliente["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$cliente["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-8px;" />
                  </td>
                </tr>
                <tr>
                     <td colspan="2" style="font-size:9px;">
                     NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA
                     </td>
                     <td colspan="2" style="font-size:9px;text-align:right;vertical-align:top;">
                     <strong >ORIGINAL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
              </tbody>
            </table>
            
            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:8px;">
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["NombreCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.number_format($cliente["IdCliente"],0,",",".").'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["DireccionCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$cliente["Telefono"].'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        '.trim($cliente["CiudadCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Cond. Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$condicion_pago .'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Paciente: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        '.trim($nombre_paciente) . ' - <strong>' .$regimen.'</strong>
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Nº Documento:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '. $cliente["Numero_Documento"] .'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:700px;">';
           
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
if($func['Firma']!=''){
    $imagen='<img src="'.$MY_FILE . "DOCUMENTOS/".$func["Identificacion_Funcionario"]."/".$func['Firma'].'"  width="110"><br>';
}else{
    $imagen='<br><br>______________________________<br>';
}
/* PIE DE PAGINA */

$pie='<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:4px;">
	<tr>
		<td style="font-size:7px;width:778px;background:#f3f3f3;vertical-align:middle;padding:1px 5px;height:10px;">
			<strong>Resolución Facturación:</strong> Facturacion por Computador # '.$cliente["Resolucion"].' Desde '.fecha($cliente["F_Inicio"]).' Hasta '.fecha($cliente["F_Fin"]).' '.$cliente["Prefijo"].' Habilita Del No. '.$cliente["Inicio"].' Al No. '.$cliente["Fin"].' Actividad economica principal '.$cliente["Actividad"].'<br>
		</td>
	
	</tr>
	<tr>
	   <td style="font-size:7px;width:778px;background:#c6c6c6;vertical-align:middle;padding:1px 5px;text-align:center;">
		<strong>Esta Factura se asimila en sus efectos legales a una letra de cambio Art. 774 del Codigo de Comercio</strong>
	   </td>
	</tr>
	<tr>
	   <td style="font-size:7px;width:778px;background:#f3f3f3;vertical-align:middle;padding:1px 5px;">
		<strong>Nota:</strong> No se aceptan devoluciones de ningun medicamento de cadena de frio o controlados.<br>
		<strong>Cuentas Bancarias:</strong> Banco Corpbanca Cta Cte 229 032 776 - Banco Occidente 657 034 583 - Bancolombia Cta Cte 302 786 049 52
	   </td>
	</tr>
</table>
<table>
 <tr>
 	<td style="font-size:8px;width:365px;vertical-align:middle;padding:2px 5px;text-align:center;background-image:url('.$_SERVER["DOCUMENT_ROOT"].'assets/images/sello-proh.png);background-repeat:no-repeat;background-size:cover;background-position:center">
'.$imagen.'
 		Elaborado Por<br>'.$func["Nombres"]." ".$func["Apellidos"].'
 	</td>
 	<td style="font-size:8px;width:365px;vertical-align:middle;padding:2px 5px;text-align:center;">
 	<br><br>______________________________<br>
 		Recibí Conforme<br>
 	</td>
 </tr>
</table>
';

$col_desc = '';
$width_prod = 'width:430px;';
$width_prod2 = 'width:285px;';


    $col_desc = '<td style="font-size:7px;line-height:8px;background:#c6c6c6;text-align:center;">Descuento</td>';
    $width_prod = 'width:392px;';
    $width_prod2 = 'width:225px;';


$contenido = '<table  cellspacing="0" cellpadding="0" >
	        	    <tr>
	        		<td style="'.$width_prod.'font-size:8px;background:#c6c6c6;text-align:center;">Descripción</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Lote</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">F. Venc.</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Und</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Iva</td>
	        		'.$col_desc.'
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Precio</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Total</td>
	        	    </tr>';
			    $total_iva = 0;
                $total_desc = 0;
                $subtotal = 0;
                $subtotal_acum = 0;

                    $decimales = 2;

                    if ($cliente['Tipo_Valor'] == 'Cerrada') {
                        $decimales = 0;
                    }
                
	        	    foreach($productos as $prod){
                        
	        	    	$contenido.='<tr>
	        		<td style="'.$width_prod.'padding:2px 3px;font-size:7px;text-align:left;border:1px solid #c6c6c6;vertical-align:middle;line-height:9px;height:auto;">
                    '.trim($prod["producto"]).' | INV: '.trim($prod['Invima']).' | CUM: '.trim($prod['Cum']).'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:50px;vertical-align:middle;line-height:9px;height:auto;"> 
	        		'.$prod["Lote"].'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:35px;vertical-align:middle;line-height:9px;height:auto;">
	        		'.fecha($prod["Fecha_Vencimiento"]).'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:20px;vertical-align:middle;line-height:9px;height:auto;">
	        		'.number_format($prod["Cantidad"],0,"",".").'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;line-height:9px;height:auto;">
	        		'.($prod["Impuesto"]).'%
                    </td>';
                    
                    $descuento = 0;
                        $decimales_dcto = 2;

                        if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
                            $decimales_dcto = 0;
                        }
                        $descuento = number_format($prod["Descuento"],$decimales_dcto,".","");
                        $contenido .= '<td style="padding:2px 3px;font-size:7px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;width:50px;line-height:9px;height:auto;">
                        $ '.number_format($descuento,$decimales_dcto,",",".").'
                        </td>';

                    $precio = number_format($prod['Precio'],$decimales,".","");
                    $subtotal = $precio * $prod['Cantidad'];
                    $total_iva += (($subtotal-($descuento*$prod['Cantidad']))*($prod["Impuesto"]/100));
	        		
	        		$contenido .= '<td style="padding:2px 3px;font-size:7px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:60px;line-height:9px;height:auto;">
	        		$ '.number_format($prod["Precio"],$decimales,",",".").'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:right;border:1px solid #c6c6c6;width:60px;vertical-align:middle;line-height:9px;height:auto;">
	        		$ '.number_format($subtotal,$decimales,",",".").'
	        		</td>
                    </tr>';
                    $total_desc += $descuento * $prod["Cantidad"];
                    $subtotal_acum += $subtotal;
                    }
                    $total_dcto = number_format($total_desc,$decimales,".","");

                    if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
                        $total_dcto = number_format($total_desc,0,"","");
                    }
                    $subtotal_acum = number_format($subtotal_acum,$decimales,".","");
                    $total_iva = number_format($total_iva,$decimales,".","");
                    $total = $subtotal_acum+$total_iva-$total_dcto-$cliente['Cuota'];
                    
                    $numero = number_format($total, $decimales, '.','');
                    $letras = NumeroALetras::convertir($numero);
                    

	             $contenido.='</table>
	             <table style="margin-top:8px;margin-bottom:0;" >
	             	<tr>
	             	   <td colspan="2" style="padding:2px 3px;font-size:8px;border:1px solid #c6c6c6;width:585px;"><strong>Valor a Letras:</strong><br>'.$letras.' PESOS MCTE</td>
	             	   <td rowspan="3" style="padding:3px;font-size:8px;border:1px solid #c6c6c6;width:150px;">
	             	   	<table cellpadding="0" cellspacing="0">
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Subtotal</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($subtotal_acum,$decimales,",",".").'</td>
                            </tr>';
                               
                            $decimales_dcto = $decimales;

                            if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
                                $decimales_dcto = 0;
                            }
                            $contenido .= '<tr>
                            <td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Dcto.</strong></td>
                            <td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($total_desc,$decimales_dcto,",",".").'</td>
                        </tr>';
	             	   	   
                        
	             	   	 $contenido .='<tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Iva 19%</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($total_iva,$decimales,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Cuotas Moderadora</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($cliente['Cuota'],$decimales,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Total</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;"><strong>$ '.number_format($total,$decimales,",",".").'</strong></td>
	             	   	   </tr>
	             	   	</table>
	             	   </td>
	             	</tr>
	             	<tr>
	             	   <td style="padding:2px 3px;font-size:7px;border:1px solid #c6c6c6;width:446px;line-height:8px;">
	             	   	<strong>Observaciones:</strong><br>
	             	   	'.$cliente["observacion"].'
	             	   </td>
	             	   <td style="padding:2px 3px;font-size:7px;border:1px solid #c6c6c6;width:90px;line-height:8px;"></td>
	             	</tr>
                 </table>';


/* $contenido .= '

<div style="border:1px solid #000;width:793px;height:10px;text-align:center">

<img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/SelloNoPos.png" alt="Pro-H Software" />

</div>

'; */

$marca_agua = 'backimg="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/SelloNoPos.png" backimgw="50%"';
$marca_agua = '';            
	             
/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="190px" backbottom="105px" '.$marca_agua.'>
		<page_header>'.
                    $cabecera.
		'</page_header>
		<page_footer>'.$pie.'</page_footer>
                <div class="page-content">
                <br>
	             '.$contenido.'
               </div>
            </page>
            ';
            
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/


try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
   $html2pdf = new HTML2PDF('L', array(215.9,140), 'Es', true, 'UTF-8', array(2, 0, 2, 0));
   $html2pdf->writeHTML($content);
   $direc = $_SERVER["DOCUMENT_ROOT"]."php/facturas_viejas/FACTURAS_GLOSAS/".$cliente["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
   echo $cliente["Codigo"].'.pdf<br>';
   $html2pdf->Output($direc,"F"); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

}
echo $i;


?>