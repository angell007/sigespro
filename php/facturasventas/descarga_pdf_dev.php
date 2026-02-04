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
$oItem = new complex("Factura_Venta","Id_Factura_Venta",$id);
$data = $oItem->getData();
unset($oItem);

/* $oItem = new complex("Resolucion","Id_Resolucion",2);
$fact = $oItem->getData();
unset($oItem); */

$query = "SELECT * FROM Resolucion WHERE Modulo = 'General' AND Fecha_Fin > CURDATE() ORDER BY Fecha_Fin LIMIT 1";

$oCon = new consulta();
$oCon->setQuery($query);
$fact = $oCon->getData();
unset($oCon);

/* $oItem = new complex("Cliente","Id_Cliente",$data["Id_Cliente"]);
$cliente = $oItem->getData();
unset($oItem); */

$query = 'SELECT 
FV.Fecha_Documento as Fecha , FV.Observacion_Factura_Venta as observacion, FV.Codigo as Codigo, FV.Codigo_Qr, IF(FV.Condicion_Pago=1,"CONTADO",FV.Condicion_Pago) as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago ,
C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, M.Nombre as CiudadCliente, C.Credito as CreditoCliente, C.Celular AS Telefono, FV.Id_Factura_Venta ,(SELECT R.Observaciones FROM Remision R WHERE Id_Factura = FV.Id_Factura_Venta Order By R.Id_Remision ASC LIMIT 1) as Observaciones2
FROM Factura_Venta FV
INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente
INNER JOIN Municipio M ON C.Ciudad=M.Id_Municipio
AND FV.Id_Factura_Venta ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);   




$query='SELECT 
IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n","Invima:", P.Invima, " CUM:", P.Codigo_Cum), CONCAT_WS(" ", P.Nombre_Comercial,"<br>", "Invima:",P.Invima)) as producto, 
P.Id_Producto,
IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
P.Presentacion,
P.Codigo_Cum as Cum, 
I.Fecha_Vencimiento as Vencimiento, 
I.Lote as Lote, 
I.Id_Inventario as Id_Inventario,
PFV.Precio_Venta as Costo_unitario,
PFV.Cantidad as Cantidad,
PFV.Precio_Venta as PrecioVenta,
PFV.Subtotal as Subtotal,
PFV.Id_Producto_Factura_Venta as idPFV,
(CASE  
  WHEN P.Gravado = "Si"  THEN "19" 
  ELSE "0" 
END) as Impuesto
FROM Producto_Factura_Venta PFV
INNER JOIN Inventario I
ON PFV.Id_Inventario = I.Id_Inventario
INNER JOIN Factura_Venta F 
ON PFV.Id_Factura_Venta=F.Id_Factura_Venta
INNER JOIN Cliente C 
ON F.Id_Cliente=C.Id_Cliente
LEFT JOIN Producto P ON I.Id_Producto = P.Id_Producto
WHERE PFV.Id_Factura_Venta ='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon); 

if(count($productos)==0){
    $query22 = 'SELECT 
    IFNULL(CONCAT(P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n", P.Invima, " CUM:", P.Codigo_Cum), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as producto, 
    P.Id_Producto,
    IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
    P.Presentacion,
    P.Codigo_Cum as Cum, 
    PFV.Fecha_Vencimiento as Vencimiento, 
    PFV.Lote as Lote, 
    PFV.Id_Inventario as Id_Inventario,
    PFV.Precio_Venta as Costo_unitario,
    PFV.Cantidad as Cantidad,
    PFV.Precio_Venta as PrecioVenta,
    PFV.Subtotal as Subtotal,
    PFV.Id_Producto_Factura_Venta as idPFV,
    (CASE  
      WHEN P.Gravado = "Si" THEN "19" 
      ELSE "0" 
    END) as Impuesto
    FROM Producto_Factura_Venta PFV
    LEFT JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
    WHERE PFV.Id_Factura_Venta ='.$id;
    
    $oCon= new consulta();
    $oCon->setQuery($query22);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
}



$oItem = new complex("Funcionario","Identificacion_Funcionario",$data["Id_Funcionario"]);
$func = $oItem->getData();
unset($oItem);


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

$query5 = 'SELECT SUM(Subtotal) as TotalFac FROM Producto_Factura_Venta WHERE Id_Factura_Venta = '.$id ;
$oCon= new consulta();
$oCon->setQuery($query5);
$totalFactura = $oCon->getData();
unset($oCon);



$codigos ='
    <span style="margin:-5px 0 0 0;font-size:16px;line-height:16px;">Factura de Venta</span>
    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">F. Expe.:'.fecha($data["Fecha_Documento"]).'</h5>
    <h4 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">F. Venc.:'.fecha($data["Fecha_Pago"]).'</h4>
';

$condicion_pago = $cliente["Condicion_Pago"] == "CONTADO" ? $cliente["Condicion_Pago"] : "Días";
        
        
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
                  <img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
                  </td>
                </tr>
                <tr>
                <tr>
                     <td colspan="3" style="font-size:11px"> <br>                
                           NO SOMOS GRANDES CONTRIBUYENTES<br>
                           NO SOMOS AUTORETENEDORES DE RENTA<br>
                           POR FAVOR ABSTENERSE PRACTICAR RETENCIÓN EN LA FUENTE POR ICA,<BR>
                           SOMOS GRANDES CONTRIBUYENTES DE ICA EN BUCARAMANGA. RESOLUCIÓN 3831 DE 18/04/2022
                     </td>
                     <td colspan="1" style="font-size:11px;text-align:right;vertical-align:top;">
                     <strong >ORIGINAL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
              </tbody>
            </table>
            
            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:20px;">
                <tr>
                    <td style="font-size:10px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td style="font-size:10px;width:510px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '.trim($cliente["NombreCliente"]).'
                    </td>
                    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '.number_format($cliente["IdCliente"],0,",",".").'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:10px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:10px;width:510px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '.trim($cliente["DireccionCliente"]).'
                    </td>
                    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '.$cliente["Telefono"].'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:10px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td style="font-size:10px;width:510px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                        '.trim($cliente["CiudadCliente"]).'
                    </td>
                    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Cond. Pago:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '.$condicion_pago .'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:730px;">';
            
  
             
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/


/* PIE DE PAGINA */

$pie='<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin:0px;">
	<tr>
		<td style="font-size:10px;width:770px;background:#f3f3f3;vertical-align:middle;padding:5px;height:40px;">
			<strong>Resolución Facturación:</strong><br>
			Autorizacion de Facturacion # '.$fact["Resolucion"].'<br>
			Desde '.fecha($fact["Fecha_Inicio"]).' Hasta '.fecha($fact["Fecha_Fin"]).'<br>
			Habilita Del No. '.$fact["Numero_Inicial"].' Al No. '.$fact["Numero_Final"].'<br>
			Actividad economica principal 4645<br>
		</td>
	
	</tr>
	<tr>
	   <td style="font-size:10px;width:770px;background:#c6c6c6;vertical-align:middle;padding:5px;text-align:center;">
		<strong>Esta Factura se asimila en sus efectos legales a una letra de cambio Art. 774 del Codigo de Comercio</strong>
	   </td>
	</tr>
	<tr>
	   <td style="font-size:10px;width:770px;background:#f3f3f3;vertical-align:middle;padding:5px;">
		<strong>Nota:</strong> No se aceptan devoluciones de ningun medicamento de cadena de frio o controlados.<br>
		<strong>Cuentas Bancarias:</strong> Banco Corpbanca Cta Cte 229 032 776 - Banco Occidente 657 034 583 - Bancolombia Cta Cte 302 786 049 52
	   </td>
	</tr>
</table>
<table>
 <tr>
 	<td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
 	<br><br>______________________________<br>
 		Elaborado Por<br>'.$func["Nombres"]." ".$func["Apellidos"].'
 	</td>
 	<td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
 	<br><br>______________________________<br>
 		Recibí Conforme<br>
 	</td>
 </tr>
</table>
';

$contenido = '<table  cellspacing="0" cellpadding="0" >
	        	    <tr>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Descripción</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Laboratorio</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Lote</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">F. Venc.</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Presentación</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Und</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Iva</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Precio</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Total</td>
	        	    </tr>';
			    $total_iva = 0;
	        	    foreach($productos as $prod){ $total_iva += ($prod["Subtotal"]*($prod["Impuesto"]/100));
	        	    	$contenido.='<tr>
	        		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:265px;vertical-align:middle;">
	        		'.$prod["producto"].'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;text-align:left;width:70px;vertical-align:middle;">
	        		'.$prod["Laboratorio"].'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:50px;vertical-align:middle;"> 
	        		'.$prod["Lote"].'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:35px;vertical-align:middle;">
	        		'.fecha($prod["Vencimiento"]).'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		'.$prod["Presentacion"].'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:20px;vertical-align:middle;">
	        		'.number_format($prod["Cantidad"],0,"",".").'
	        		</td>
	        		<td style="padding:4px;font-size:9px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;">
	        		'.$prod["Impuesto"].'%
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:40px;">
	        		$ '.number_format($prod["PrecioVenta"],0,",",".").'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		$ '.number_format($prod["Subtotal"],0,",",".").'
	        		</td>
	        	    </tr>';  
                    }
                    $total = $totalFactura['TotalFac']+$total_iva;
                    $numero = number_format($total, 0, '.','');
	        	    $letras = NumeroALetras::convertir($numero);
	             $contenido.='</table>
	             <table style="margin-top:20px;margin-bottom:0;">
	             	<tr>
	             	   <td colspan="2" style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:586px;"><strong>Valor a Letras:</strong><br>'.$letras.' PESOS MCTE</td>
	             	   <td rowspan="3" style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:130px;">
	             	   	<table cellpadding="0" cellspacing="0">
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Subtotal</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ '.number_format($totalFactura['TotalFac'],0,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Dcto.</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ 0</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Iva 19%</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ '.number_format($total_iva,0,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Retención</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ 0</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Total</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;"><strong>$ '.number_format($totalFactura['TotalFac']+$total_iva,0,",",".").'</strong></td>
	             	   	   </tr>
	             	   	</table>
	             	   </td>
	             	</tr>
	             	<tr>
	             	   <td style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:486px;">
	             	   	<strong>Obsrvaciones:</strong><br>
	             	   	'.$cliente["observacion"].' - '.$cliente["Observaciones2"].'
	             	   </td>
	             	   <td style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:90px;"></td>
	             	</tr>
               </table>';
               

               $marca_agua = '';

if ($data['Estado'] == 'Anulada') {
    $marca_agua = 'backimg="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/anulada.png"';
} 
	             
	             
/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="230px" backbottom="185px" '.$marca_agua.'>
		<page_header>'.
                    $cabecera.
		'</page_header>
		
		<page_footer>'.$pie.'</page_footer>
                <div class="page-content">
	             '.$contenido.'
               </div>
            </page>'
            
           ;
            
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/


try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
   $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(2, 2, 2, 2));
   $html2pdf->writeHTML($content);
   $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
   $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>