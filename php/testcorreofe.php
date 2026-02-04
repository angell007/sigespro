<?php

    require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
    include_once('../class/class.lista.php');
    include_once('../class/class.complex.php');
    include_once('../class/class.consulta.php');
    require_once('../class/class.qr.php'); 
    require_once('../class/class.php_mailer.php'); 

$oItem=new complex('Factura_Administrativa','Id_Factura_Administrativa',3);
$factura=$oItem->getData();

$oItem=new complex('Cliente','Id_Cliente',$factura['Id_Cliente']);
$cliente=$oItem->getData();


$oItem=new complex('Resolucion','Id_Resolucion',$factura['Id_Resolucion']);
$resolucion=$oItem->getData();


$oItem=new complex('Configuracion','Id_Configuracion',1);
$configuracion=$oItem->getData();

var_dump(EnviarMail());

    function EnviarMail(){
            global $factura;
            
            //$destino = (($cliente["Correo_Persona_Contacto"] != "" && cliente["Correo_Persona_Contacto"] != "NULL") ? cliente["Correo_Persona_Contacto"] : "facturacionelectronicacont@prohsa.com" );
            //$destino="sistemas@prohsa.com";
            $destino="augustoacarrillo@hotmail.com"; 
            $asunto = "Su Factura Electrónica: ".$factura["Codigo"];
            $contenido = GetHtmlFactura();
            $xml = getXml();
            $fact = getFact();
            
            $email = new EnviarCorreo();
            $respuesta = $email->EnviarFacturaDian($destino,$asunto,$contenido,$xml,$fact);
            
            return($respuesta);
        }
        
        
         function getXml(){
             global $resolucion;
             var_dump($resolucion);
            $xml = '/home/sigesproph/api-dian.192.168.40.201/api-dian/storage/app/xml/1/'.$resolucion["resolution_id"].'/fv'.getNombre().'.xml';
            echo $xml;
            return($xml);
        }
        
        
         function getFact(){
            $fact =  $_SERVER['DOCUMENT_ROOT']."/ARCHIVOS/FACTURA_ELECTRONICA_PDF/fv".getNombre().'.pdf';
            
            return($fact);
        }
        
        
        
         function getNombre(){
            global $resolucion,$factura;
          $nit=getNit();
          $codigo=(INT)str_replace($resolucion['Codigo'],"", $factura['Codigo']);
          var_dump($resolucion);
          $nombre=str_pad($nit, 10, "0", STR_PAD_LEFT)."000".date("y").str_pad($codigo, 8, "0", STR_PAD_LEFT);
          return $nombre;
        }
        
        
        
        
        function getNit(){
             global $configuracion;
            $nit=explode("-",$configuracion['NIT']);
            $nit=str_replace(".","", $nit[0]);
            return $nit;
        }
        
        
        
        
        
        
        
        
        
        function GetHtmlFactura(){
            global $configuracion,$factura;
            $html='<!doctype html>
		<html>		
		<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<meta name="viewport" content="width=device-width" />
			
			<title>Facturación Electrónica - Productos Hospitalarios (Pro H) S.A</title>
			<style>
				img{border:none;-ms-interpolation-mode:bicubic;max-width:100%}body{background-color:#f6f6f6;font-family:sans-serif;-webkit-font-smoothing:antialiased;font-size:14px;line-height:1.4;margin:0;padding:0;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}.body{background-color:#f6f6f6;width:100%}.container{display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px}.content{box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px}.main{background:#fff;border-radius:3px;width:100%}.wrapper{box-sizing:border-box;padding:20px}.content-block{padding-bottom:10px;padding-top:10px}.footer{clear:both;margin-top:10px;text-align:center;width:100%}.footer a,.footer p,.footer span,.footer td{color:#999;font-size:12px;text-align:center}h5{font-size:14px;font-weight:700;text-align:left;color:#3c5dc6}p{font-family:sans-serif;font-size:11px;font-weight:400;margin:0;margin-bottom:15px;text-align:justify}span{color:#000;font-family:sans-serif;font-weight:600}a{color:#3c5dc6;text-decoration:none}.logo{border:0;outline:0;text-decoration:none;display:block;text-align:center}.align-center{text-align:center!important}.preheader{color:transparent;display:none;height:0;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;visibility:hidden;width:0}.powered-by a{text-decoration:none;text-align:center!important}hr{border:0;border-bottom:1px solid #eeeef0;margin:8px 0}@media all{.ExternalClass{width:100%}.ExternalClass,.ExternalClass div,.ExternalClass font,.ExternalClass p,.ExternalClass span,.ExternalClass td{line-height:100%}.apple-link a{color:inherit!important;font-family:inherit!important;font-size:inherit!important;font-weight:inherit!important;line-height:inherit!important;text-decoration:none!important}#MessageViewBody a{color:inherit;text-decoration:none;font-size:inherit;font-family:inherit;font-weight:inherit;line-height:inherit}}
			</style>
		</head>
		
		<body class="">
		    <span class="preheader">Factura Electronica '.$factura["Codigo"].'</span>
		    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
		        <tr>
		            <td>&nbsp;</td>
		            <td class="container">
		                <div class="content">		
		                    <table role="presentatioran" class="main">		
		                        <tr>
		                            <td class="wrapper">
		                                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
		                                    <tr>
		                                        <td>
		                                            <img alt="ProH" height="100" border="0" class="logo" src="https://192.168.40.201/assets/images/LogoProh.jpg" />
		                                            <hr>
		                                            <p>Estimado, <span>'.$cliente["Nombre"].'</span></p>
		                                            <p>Ha recibido un documento electrónico generedo y enviado mediante el sistema de Facturación Electrónica de Productos Hospitalarios S.A. con la siguiente información:</p>
		                                            <hr>
		                                            <h5>Datos del Emisor</h5>
		                                            <hr>
		                                            <p><span>Nombre: </span>'.$configuracion["Nombre_Empresa"].'</p>
		                                            <p><span>Identificación: </span>'.$configuracion["NIT"].'</p>
		                                            <hr>
		                                            <h5>Información del Documento</h5>
		                                            <hr>
		                                            <p><span>Fecha: </span>'.$factura["Fecha_Documento"].'</p>
		                                            <p><span>Tipo: Factura de Venta</span></p>
		                                            <p><span>Numero: </span>'.$factura["Codigo"].'</p>
		                                            <p><span>Moneda: </span>COP</p>
		                                            <p><span>Valor Total: </span> 0 </p>
		                                            <hr>
		                                            <h5>Respuesta de la DIAN</h5>
		                                            <hr>
		                                            <p>0</p>
		                                            <hr>
		                                            <p>Adjunto encontrará la representación gráfica del documento en formato PDF y el documento electrónico en formato XML.</p>
		                                            <p class="content-block powered-by">Nota: No responda este mensaje, ha sido enviado desde una dirección de correo electrónico no monitoreada.</p>
		                                        </td>
		                                    </tr>
		                                </table>
		                            </td>
		                        </tr>
		
		                    </table>
		
		                    <div class="footer">
		                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
		                            <tr>
		                                <td class="content-block">
		                                    <span class="apple-link align-center">Productos Hospitalarios S.A</span>
		                                </td>
		                            </tr>
		                            <tr>
		                                <td class="content-block powered-by align-center">
		                                    Desarrollado por <a href="https://www.corvuslab.co/">Corvus Lab</a>.
		                                </td>
		                            </tr>
		                        </table>
		                    </div>
		
		                </div>
		            </td>
		            <td>&nbsp;</td>
		        </tr>
		    </table>
		</body>
		</html>';
            
            return($html);
        }