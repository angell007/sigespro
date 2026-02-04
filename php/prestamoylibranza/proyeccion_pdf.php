<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
require('./funciones.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );


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
            <h4 style="margin:5px 0 0 0;font-size:18px;line-height:22px;">AMORTIZACIÓN PRESTAMO</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha"]).'</h5>
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
                Cuotas:
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
                Valor Cuota:
                </td>

                <td style="font-size:11px;width:510px;padding:5px">
                $ '.number_format($data['Cuota_Mensual'],2,",",".").'
                </td>
                
            </tr>
        </table>
        ';

    $proyecciones = proyeccionAmortizacion($data['Valor_Prestamo'],$data['Cuota_Mensual'],$data['Intereses'],$data["Tipo_Descuento"],$data["Fecha_Descuento"]);

    $contenido .= '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:60px;max-width:60px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
            Cuota
        </td>
        <td style="width:150px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Fecha Descuento
        </td>
        <td style="width:140px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Amortización
        </td>
        <td style="width:120px;max-width:120px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Intereses
        </td>
        <td style="width:120px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Total Cuota
        </td>
        <td style="width:120px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Saldo
        </td>
    </tr>';

    $totalDeb = 0;

     foreach ($proyecciones['Proyeccion'] as $i => $value) {

        $contenido .= '<tr>
        <td style="vertical-align:center;font-size:9px;width:50px;max-width:50px;text-align:center;border:1px solid #cccccc;">
            '.($i+1).'
        </td>
        <td style="vertical-align:center;text-align:center;font-size:9px;width:90px;border:1px solid #cccccc;">
            '.$value['Fecha'].'
        </td>
        <td style="vertical-align:center;text-align:right;font-size:9px;word-break:break-all;width:60px;max-width:60px;border:1px solid #cccccc;">
            $ '.number_format($value['Amortizacion'],2,",",".").'
        </td>
        <td style="width:100px;max-width:100px;text-align:right;font-size:9px;word-break:break-all;border:1px solid #cccccc;">
            $ '.number_format($value['Intereses'],2,",",".").'
        </td>
        <td style="vertical-align:center;text-align:right;font-size:9px;text-align:right;width:75px;border:1px solid #cccccc;">
            $ '.number_format($value['Valor_Cuota'],2,'.',',').'
        </td>
        <td style="vertical-align:center;text-align:right;font-size:9px;text-align:right;width:75px;border:1px solid #cccccc;">
            $ '.number_format($value['Saldo'],2,'.',',').'
        </td>
    </tr>';

    }

   $contenido .= '<tr>
    <td colspan="2" style="padding:4px;text-align:right;border:1px solid #cccccc;font-weight:bold;font-size:12px">TOTALES:</td>
    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
        $ '.number_format(getTotales($proyecciones['Proyeccion'], 'Amortizacion'),2,".",",").'
    </td>
    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
        $ '.number_format(getTotales($proyecciones['Proyeccion'], 'Intereses'),2,".",",").'
    </td>
    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
        $ '.number_format(getTotales($proyecciones['Proyeccion'], 'Valor_Cuota'),2,".",",").'
    </td>
    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
        
    </td>
    </tr>';

    $contenido .= '</table>
    <p style="margin-top:10px;">Atentamente;</p>

    <table style="margin-top:50px">    
        <tr>
            <td style="width:400px;padding-left:10px">
            <table>
            <tr>
                <td style="width:300px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$funcionario['Nombres']." ".$funcionario["Apellidos"].'</td>
                <td style="width:30px;"></td>
                <td style="width:300px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$config["Representante_Legal"].'</td>
            </tr>
            <tr>
                <td style="width:300px;font-weight:bold; text-align:center;">C.C. '.number_format($funcionario['Identificacion_Funcionario'],0,",",".").' </td>    
                <td style="width:30px;"></td>    
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