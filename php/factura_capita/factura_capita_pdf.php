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
$oItem = new complex("Factura_Capita","Id_Factura_Capita",$id);
$data = $oItem->getData();
unset($oItem);

/*$oItem = new complex("Resolucion","Id_Departamento",$data['Id_Departamento']);
$fact = $oItem->getData();
unset($oItem);*/

$query='SELECT * FROM Resolucion WHERE Id_Resolucion='.$data['Id_Resolucion'];

$oCon= new consulta();
$oCon->setQuery($query);
$fact = $oCon->getData();
unset($oCon);   
/* $oItem = new complex("Cliente","Id_Cliente",$data["Id_Cliente"]);
$cliente = $oItem->getData();
unset($oItem); */

$query = 'SELECT FV.Id_Factura_Capita, FV.Cufe,
            FV.Codigo_Qr, FV.Fecha_Documento as Fecha , FV.Observacion as observacion, FV.Codigo as Codigo,
            C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, C.Ciudad as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente, FV.Cuota_Moderadora, CONCAT(FV.Mes,"-","01") AS Mes, (SELECT Nombre FROM Departamento WHERE Id_Departamento = FV.Id_Departamento) AS Departamento, FV.Id_Departamento, IF(FV.Id_Regimen=1,"Contributivo","Subsidiado") AS Regimen
          FROM Factura_Capita FV
          INNER JOIN Cliente C
           ON FV.Id_Cliente = C.Id_Cliente
           AND FV.Id_Factura_Capita = '.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);   




$query2 = 'SELECT 
            DFC.*
           FROM Descripcion_Factura_Capita DFC
           INNER JOIN Factura_Capita FC
           ON DFC.Id_Factura_Capita = FC.Id_Factura_Capita
           WHERE DFC.Id_Factura_Capita =  '.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon); 


$oItem = new complex("Funcionario","Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
$func = $oItem->getData();
unset($oItem);

$meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$m = explode("-",$cliente['Mes'])[1];
$y = explode("-",$cliente['Mes'])[0];

$mes = $meses[$m-1] . ", " . $y;


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


if($fact["Tipo_Resolucion"]=="Resolucion_Electronica"){
    $codigos ='
    <span style="margin:-5px 0 0 0;font-size:16px;line-height:16px;">Factura Electrónica de Venta (Capita)</span>
    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">F. Expe.:'.fecha($data["Fecha_Documento"]).'</h5>
    <h4 style="margin:0 0 0 0;font-size:20px;">'.$cliente["Regimen"].'</h4>
';
}else{
    $codigos ='
    <span style="margin:-5px 0 0 0;font-size:16px;line-height:16px;">Factura de Capita</span>
    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">F. Expe.:'.fecha($data["Fecha_Documento"]).'</h5>
    <h4 style="margin:0 0 0 0;font-size:20px;">'.$cliente["Regimen"].'</h4>
';
}


$condicion_pago = $cliente["Condicion_Pago"] == "CONTADO" ? $cliente["Condicion_Pago"] : "Días";
        
        
/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:310px;font-weight:thin;font-size:13px;line-height:18px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:250px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:150px;">';
                  if($fact["Tipo_Resolucion"]!="Resolucion_Electronica"){
                      $cabecera.='<img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />';
                  }else{
                      $cabecera.='<img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'ARCHIVOS/FACTURACION_ELECTRONICA/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />';
                      
                  }
                        
                  
      $cabecera.='</td>
                </tr>
                <tr>
                     <td colspan="2" style="font-size:11px">
                     <br>NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA
                     </td>
                     <td colspan="2" style="font-size:11px;text-align:right;vertical-align:top;">
                     <strong >ORIGINAL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
              </tbody>
            </table>
            
            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:10px;">
                <tr>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
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
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
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
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Departamento: </strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                        '.trim($cliente["Departamento"]).'
                    </td>
                    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Mes Facturado:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '. $mes.'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:730px;">';
            
   $cabecera2='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:310px;font-weight:thin;font-size:13px;line-height:18px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:250px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:150px;">';
                  if($fact["Tipo_Resolucion"]!="Resolucion_Electronica"){
                      $cabecera2.='<img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />';
                  }else{
                      $cabecera2.='<img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'ARCHIVOS/FACTURACION_ELECTRONICA/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />';
                      
                  }
                $cabecera2.='</td>
                </tr>
                <tr>
                     <td colspan="2" style="font-size:11px">
                     NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA
                     </td>
                     <td colspan="2" style="font-size:11px;text-align:right;">
                     <strong>CLIENTE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
              </tbody>
            </table>
            
            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:10px;">
                <tr>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
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
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
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
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Departamento: </strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                        '.trim($cliente["Departamento"]).'
                    </td>
                    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Mes Facturado:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '. $mes.'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:730px;">';
            
            $cabecera3='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:310px;font-weight:thin;font-size:13px;line-height:18px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:250px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:150px;">';
                  if($fact["Tipo_Resolucion"]!="Resolucion_Electronica"){
                      $cabecera3.='<img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />';
                  }else{
                      $cabecera3.='<img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'ARCHIVOS/FACTURACION_ELECTRONICA/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />';
                      
                  }
                        
                  
      $cabecera3.='</td>
                </tr>
                <tr>
                     <td colspan="2" style="font-size:11px">
                     NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA
                     </td>
                     <td colspan="2" style="font-size:11px;text-align:right;">
                     <strong>ARCHIVO &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
              </tbody>
            </table>
            
            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:10px;">
                <tr>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
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
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
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
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Departamento: </strong>
                    </td>
                    <td style="font-size:10px;width:470px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                        '.trim($cliente["Departamento"]).'
                    </td>
                    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Mes Facturado:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '. $mes.'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:730px;">'; 
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/


/* PIE DE PAGINA */
if($func['Firma']!=''){
    $imagen='<img src="'.$MY_FILE . "DOCUMENTOS/".$func["Identificacion_Funcionario"]."/".$func['Firma'].'"  width="210"><br>';
}else{
    $imagen='<br><br>______________________________<br>';
}

$pie='<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:10px;">';

if($fact["Tipo_Resolucion"]=="Resolucion_Electronica"){
    $pie.='<tr>
	   <td style="font-size:10px;width:770px;background:#c6c6c6;vertical-align:middle;padding:5px;text-align:center;">
		<strong>CUFE: '.$cliente["Cufe"].'</strong>
	   </td>
	</tr>';
}




	$pie.='<tr>
	    <td style="font-size:10px;width:770px;background:#f3f3f3;vertical-align:middle;padding:5px;height:40px;">
			<strong>Resolución Facturación '.($fact["Tipo_Resolucion"]=="Resolucion_Electronica" ? 'Electrónica' : '').':</strong><br>
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
        <strong>Cuentas Bancarias:</strong> '.$config['Cuenta_Bancaria'].'
         </td>
	</tr>
</table>
<table>
 <tr>
 	<td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
 	'.$imagen.'
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
	        		<td style="width:400px;font-size:10px;background:#c6c6c6;text-align:center;">Descripción</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Und</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Precio</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Total</td>
                    </tr>';
                    $subtotal=0;
	        	    foreach($productos as $prod){ 
	        	    	$contenido.='<tr>
	        		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:585px;vertical-align:middle;">
                    '.$prod["Descripcion"].'<br>
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:20px;vertical-align:middle;">
	        		'.number_format($prod["Cantidad"],0,"",".").'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:40px;">
	        		$ '.number_format($prod["Precio"],2,",",".").'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		$ '.number_format($prod["Total"],2,",",".").'
	        		</td>
                    </tr>';
                    $subtotal += $prod['Total'];
                    }
                    $total = $subtotal-$cliente['Cuota_Moderadora'];
                    $numero = number_format($total, 0, '.','');
	        	    $letras = NumeroALetras::convertir($numero);
	             $contenido.='</table>
	             <table style="margin-top:10px;margin-bottom:0;">
	             	<tr>
	             	   <td colspan="2" style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:586px;"><strong>Valor a Letras:</strong><br>'.$letras.' PESOS MCTE</td>
	             	   <td rowspan="3" style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:130px;">
	             	   	<table cellpadding="0" cellspacing="0">
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Subtotal</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ '.number_format($subtotal,2,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Cuotas Moderadora</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ '.number_format($cliente['Cuota_Moderadora'],2,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Total</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;"><strong>$ '.number_format($total,2,",",".").'</strong></td>
	             	   	   </tr>
	             	   	</table>
	             	   </td>
	             	</tr>
	             	<tr>
	             	   <td style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:486px;">
	             	   	<strong>Obsrvaciones:</strong><br>
	             	   	'.$cliente["observacion"].'
	             	   </td>
	             	   <td style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:90px;"></td>
	             	</tr>
                 </table>';
                 
        $contenido .= $pie;
	             
	             
/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="260px" backbottom="185px">
		<page_header>'.
                    $cabecera.
		'</page_header>
		
                <div class="page-content">
                <br>
	             '.$contenido.'
               </div>
            </page>
            
            <page backtop="260px" backbottom="185px">
		<page_header>'.
                    $cabecera2.
		'</page_header>
		
                <div class="page-content">
                
	             '.$contenido.'
               </div>
            </page>
            
            <page backtop="260px" backbottom="185px">
		<page_header>'.
                    $cabecera3.
		'</page_header>
		
                <div class="page-content">
                
	             '.$contenido.'
               </div>
            </page>';
            
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