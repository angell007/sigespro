<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

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
$oItem = new complex("Correspondencia","Id_Correspondencia",$id);
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

$query = 'SELECT A.Id_Auditoria, A.Fecha_Preauditoria, D.Codigo, D.Tipo, D.Numero_Documento, CONCAT(P.Primer_Nombre," ", P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as Nombre_Paciente, D.Id_Dispensacion, (SELECT GROUP_CONCAT(Tipo_Soporte) FROM Soporte_Auditoria WHERE Id_Auditoria = A.Id_Auditoria GROUP BY Id_Auditoria) AS Soportes,(SELECT R.Nombre FROM Regimen R WHERE R.Id_Regimen=P.Id_Regimen ) as Regimen,(SELECT CONCAT(S.Nombre,"-",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo,
PD.Nombre AS Nombre_Punto, DP.Nombre AS Nombre_Departamento, MUN.Nombre AS Nombre_Municipio
FROM Dispensacion D
INNER JOIN Punto_Dispensacion PD
 ON D.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
INNER JOIN Departamento DP 
ON PD.Departamento = DP.Id_Departamento
INNER JOIN Municipio MUN 
ON PD.Municipio = MUN.Id_Municipio
INNER JOIN Correspondencia C
ON D.Id_Correspondencia = C.Id_Correspondencia
INNER JOIN Auditoria A
ON A.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Paciente P
ON D.Numero_Documento=P.Id_Paciente WHERE D.Id_Correspondencia = '.$id.' ORDER BY D.Id_Dispensacion DESC';
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $dispensaciones = $oCon->getData();
        unset($oCon);

        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Id_Funcionario_Envia"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">CO000'.$data["Id_Correspondencia"].'</h3>
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Estado"].'</h3>
        ';

        $contenido = '<table style="font-size:10px;margin-top:10px;padding-bottom:7px;border-bottom: 1px solid #cccccc" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:176px;max-width:176px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                Empresa envío
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Número Guía
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Folios
            </td>
            <td style="width:179px;max-width:179px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Fecha Envío
            </td>
        </tr>
        <tr>
            <td style="width:176px;max-width:176px;font-weight:bold;background:#f3f3f3;border:1px solid #cccccc;">
                '.$data["Empresa_Envio"].'
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$data["Numero_Guia"].'
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$data["Cantidad_Folios"].'
            </td>
            <td style="width:179px;max-width:179px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.fecha($data["Fecha_Envio"]).'
            </td>
        </tr>
        <tr>
            <td style="width:176px;max-width:176px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                Punto 
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Departamento
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Municipio
            </td>
        </tr>
        
        <tr>
            <td style="width:176px;max-width:176px;font-weight:bold;background:#f3f3f3;border:1px solid #cccccc;">
                '.$dispensaciones[0]["Nombre_Punto"].'
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$dispensaciones[0]["Nombre_Departamento"].'
            </td>
            <td style="width:179px;max-width:176px;font-weight:bold;background:#f3f3f3;text-align:center;border:1px solid #cccccc;">
                '.$dispensaciones[0]["Nombre_Municipio"].'
            </td>
           
        </tr>
        
        </table>';
        
        $contenido .= '
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:712px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$data["Observaciones_Envio"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:60px;max-width:60px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Codigo Aud
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Fecha Aud
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Código Disp
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Tipo
                </td>
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Regimen
            </td>
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Paciente
                </td>
                <td style="width:200px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Soportes
                </td>
            </tr>';

            

            foreach ($dispensaciones as $dis) {
                $soportes_html = '';
                $contenido .= '
                    <tr>
                    <td style="background:#f3f3f3; padding:3px 2px;width:60px;max-width:60px;font-size:9px;text-align:left;vertical-align:middle;border:1px solid #cccccc;word-break: break-all !important;"><b>AUD'.$dis["Id_Auditoria"] .'</b><br><span style="color:gray">'.$dis["Nombre_Producto"].'</span></td>
                    <td style="width:70px;max-width:70px;font-size:9px;vertical-align:center;word-wrap: break-word;vertical-align:middle;text-align:center;background:#f3f3f3;border:1px solid #cccccc;"><p style="font-size:9px">'.fecha($dis["Fecha_Preauditoria"]).'</p></td>
                    <td style="width:70px;max-width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$dis['Codigo'].'</td>
                    <td style="width:70px;max-width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$dis['Tipo'].'</td>
                    <td style="width:70px;max-width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$dis['Regimen'].'</td>
                    <td style="width:144px;max-width:120px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;padding-left:5px;">  '.number_format($dis['Numero_Documento'],0,"",".").' - '.$dis["Nombre_Paciente"].'</td>
                    ';

                    $soportes = explode(",", $dis['Soportes']);

                    $soportes_html .= "<ul style='list-style:none'>";
                    foreach ($soportes as $sop) {
                        $soportes_html .= "<li> $sop </li>";
                    }
                    $soportes_html .= "</ul>";

                    $contenido .= '<td style="width:200px;max-width:300px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;">'.$soportes_html.'</td></tr>';
            }
            
         $contenido .= '</table>';

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
                  <td style="width:250px;text-align:right">
                        '.$codigos.'
                  </td>
                </tr>
              </tbody>
            </table>';
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
    $direc = $data["Id_Correspondencia"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>