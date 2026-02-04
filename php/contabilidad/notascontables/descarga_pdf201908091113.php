<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

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

        $query = "SELECT 
        NC.*,
        (
        CASE
        NC.Tipo_Beneficiario
        WHEN 'Cliente' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Cliente WHERE Id_Cliente = NC.Beneficiario)
        WHEN 'Proveedor' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Proveedor WHERE Id_Proveedor = NC.Beneficiario)
        WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Beneficiario)
        END
        ) AS Tercero
        FROM Documento_Contable NC WHERE NC.Id_Documento_Contable = $id";
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $data = $oCon->getData();
        unset($oCon);


        $query = "SELECT PC.Codigo, PC.Nombre AS Cuenta, PC.Nombre_Niif AS Cuenta_Niif, PC.Codigo_Niif, CNC.Concepto, CNC.Documento, CNC.Nit, CNC.Debito, CNC.Credito, CNC.Cred_Niif, CNC.Deb_Niif FROM Cuenta_Documento_Contable CNC INNER JOIN Plan_Cuentas PC ON CNC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE CNC.Id_Documento_Contable = $id";
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $cuentas = $oCon->getData();
        unset($oCon);
               
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha_Documento"]).'</h5>
        ';
        $contenido = '<table style="background: #e6e6e6;margin-top:20px">
            <tr style=" min-height: 100px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:14px;font-weight:bold;width:100px;padding:5px">
                Beneficiario:
                </td>

                <td style="width:610px;padding:5px">
                '.$data['Tercero'].'
                </td>
                
            </tr>
            
            <tr style=" min-height: 100px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:14px;font-weight:bold;width:100px;padding:5px">
                Documento:
                </td>

                <td style="width:610px;padding:5px">
                '.$data['Beneficiario'].'
                </td>
                
            </tr>
            
            <tr style=" min-height: 100px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
               
                <td style="font-size:14px;font-weight:bold;width:100px;padding:5px">
                Concepto:
                </td>

                <td style="width:610px;padding:5px">
                '.$data['Concepto'].'
                </td>
                
            </tr>
        </table>
        ';

    $contenido .= '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:70px;max-width:70px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
            Codigo '.$tipo.'
        </td>
        <td style="width:130px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Cuenta '.$tipo.'
        </td>
        <td style="width:130px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Concepto
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Documento
        </td>
        <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Nit
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Debito '.$tipo.'
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Credito '.$tipo.'
        </td>
    </tr>';

    $totalDeb = 0;

    foreach ($cuentas as $cuenta) {

        if ($tipo != '') {
            $codigo = $cuenta['Codigo_Niif'];
            $nombre_cuenta = $cuenta['Cuenta_Niif'];
            $debe = $cuenta['Deb_Niif'];
            $haber = $cuenta['Cred_Niif'];
        } else {
            $codigo = $cuenta['Codigo'];
            $nombre_cuenta = $cuenta['Cuenta'];
            $debe = $cuenta['Debito'];
            $haber = $cuenta['Credito'];
        }

        $documento = $cuenta['Documento'];
        $documento = wordwrap($documento, 17, "<br />\n", true);
        
        $contenido .= '<tr>
        <td style="width:54px;padding:4px;max-width:54px;text-align:center;border:1px solid #cccccc;">
            '.$codigo.'
        </td>
        <td style="width:114px;padding:4px;border:1px solid #cccccc;">
            '.$nombre_cuenta.'
        </td>
        <td style="width:84px;padding:4px;border:1px solid #cccccc;">
            '.$cuenta['Concepto'].'
        </td>
        <td style="word-break:break-all;width:84px;max-width:84px;padding:4px;border:1px solid #cccccc;">
            '.$documento.'
        </td>
        <td style="padding:4px;border:1px solid #cccccc;">
            '.$cuenta['Nit'].'
        </td>
        <td style="text-align:right;width:84px;padding:4px;border:1px solid #cccccc;">
            $.'.number_format($debe,2,'.',',').'
        </td>
        <td style="text-align:right;width:84px;padding:4px;border:1px solid #cccccc;">
            $.'.number_format($haber,2,'.',',').'
        </td>
    </tr>';

    $totalDeb += $debe;
    $totalCred += $haber;
        
    }

    $contenido .= '<tr>
    <td colspan="5" style="padding:4px;text-align:left;border:1px solid #cccccc;font-weight:bold;font-size:12px">Totales:</td>
    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
        $.'.number_format($totalDeb,2,".",",").'
    </td>
    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
        $.'.number_format($totalCred,2,".",",").'
    </td>
    </tr>';

    $contenido .= '</table>

    <table style="margin-top:10px;" cellpadding="0" cellspacing="0">

        <tr>
            <td style="font-weight:bold;width:170px;border:1px solid #cccccc;padding:4px">
                Elaboró:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Revisó:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Aprobó:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Beneficiario
            </td>
        </tr>

        <tr>
            <td style="font-size:10px;width:170px;border:1px solid #cccccc;padding:4px">
                '.$elabora['Apellidos'].' '.$elabora['Nombres'].'
            </td>
            <td style="width:168px;border:1px solid #cccccc;padding:4px">
            
            </td>
            <td style="width:168px;border:1px solid #cccccc;padding:4px">
            
            </td>
            <td style="width:168px;border:1px solid #cccccc;padding:4px">
            
            </td>
        </tr>

    </table>
    
    
    ';


	

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
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