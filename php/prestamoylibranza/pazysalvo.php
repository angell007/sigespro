<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');
include_once('../../class/class.utility.php');

require('./funciones.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );

$util = new Utility();

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
.titular{
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

        $oItem = new complex('Prestamo','Id_Prestamo',$id);
        $data = $oItem->getData();
        unset($oItem);
               
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $funcionario = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:18px;line-height:22px;">PAZ Y SALVO</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.date("d/m/Y").'</h5>
        ';
        
        $contenido = '<table style="background: #e6e6e6;">
            <tr style=" min-height: 200px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:11px;font-weight:bold;width:200px;padding:5px">
                Identificación Empleado:
                </td>

                <td style="font-size:11px;width:510px;padding:5px">
                '.number_format($data['Identificacion_Funcionario'],0,"",".").'
                </td>
                
            </tr>
            
            <tr style=" min-height: 200px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:11px;font-weight:bold;width:200px;padding:5px">
                Nombre Empleado:
                </td>

                <td style="font-size:11px;width:510px;padding:5px">
                '.$funcionario['Nombres'].' '.$funcionario['Apellidos'].'
                </td>
                
            </tr>
            
            <tr style=" min-height: 200px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:11px;font-weight:bold;width:200px;padding:5px">
                Valor Prestamo:
                </td>

                <td style="font-size:11px;width:510px;padding:5px">
                $ '.number_format($data['Valor_Prestamo'],2,",",".").'
                </td>
                
            </tr>
            <tr style=" min-height: 200px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:11px;font-weight:bold;width:200px;padding:5px">
                Interes:
                </td>

                <td style="font-size:11px;width:510px;padding:5px">
                '.number_format($data['Intereses'],2,",",".").'%
                </td>
                
            </tr>
            <tr style=" min-height: 200px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:11px;font-weight:bold;width:200px;padding:5px">
                Cuotas Pagadas:
                </td>

                <td style="font-size:11px;width:510px;padding:5px">
                '.$data['Nro_Cuotas'].'
                </td>
                
            </tr>
            <tr style=" min-height: 200px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:11px;font-weight:bold;width:200px;padding:5px">
                Valor Cuota Pagada:
                </td>

                <td style="font-size:11px;width:510px;padding:5px">
                $ '.number_format($data['Cuota_Mensual'],2,",",".").'
                </td>
                
            </tr>
        </table>
        ';

        $dia_solicitud = date('d',strtotime($data['Fecha']));
        $mes_solicitud = date('m',strtotime($data['Fecha']));
        $anio_solicitud = date('Y',strtotime($data['Fecha']));
        $letras = NumeroALetras::convertir($dia_solicitud);   

    $contenido .= '<p style="text-align:center;"><h4>PAZ Y SALVO</h4></p>
                <p>COMERCIALIZADORA DE CARNES SAN FELIPE P.R. S.A.S. se permite certificar que el funcionario <strong>'.$funcionario['Nombres'].' '.$funcionario['Apellidos'].'</strong> identificado con C.C. '.number_format($funcionario['Identificacion_Funcionario'],0,",",".").' se encuentra a Paz y Salvo por el concepto del prestamo <strong>PE0'.$data["Id_Prestamo"].'</strong> de fecha '.fecha($data["Fecha"]).'.</p>  
                <p>
                La presente certificación se expide en Bogotá, al(los) '.$letras.' ('.$dia_solicitud.') día(s) del mes de '.$util->ObtenerMesString($mes_solicitud).' de '.$anio_solicitud.' a solicitud del interesado.
                </p>      
    
    ';

   

    $contenido .= '
    <p style="margin-top:10px;">Atentamente;</p>

    <table style="margin-top:50px">    
        <tr>
            <td style="width:400px;padding-left:10px">
            <table>
            <tr>
                <td style="width:300px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$config["Representante_Legal"].'</td>
            </tr>
            <tr>   
                <td style="width:300px;font-weight:bold; text-align:center;">Representante Legal</td>    
            </tr>
            
            </table>
            </td>    
        </tr>
    </table>';


	

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/logo-color.png" style="width:60px;" alt="SIGESAF" />
                  </td>
                  <td class="td-header" style="width:390px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:170px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

$marca_agua = '';

if ($data['Estado'] == 'Anulada') {
    $marca_agua = 'backimg="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/anulada.png"';
}

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm" '.$marca_agua.'>
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