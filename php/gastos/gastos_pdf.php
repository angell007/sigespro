<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$tipo = 'Gasto_Punto';
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

$query = "SELECT GP.Id_Gasto_Punto, GP.Fecha, GP.Identificacion_Funcionario, F.Nombre_Funcionario, PD.Nombre AS Punto_Dispensacion, GP.Codigo, GP.Fecha_Aprobacion, GP.Estado, GP.Codigo_Qr, GP.Anticipos, GP.Observaciones, GP.Observacion_Aprobacion, (SELECT CONCAT_WS(' ',Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = GP.Funcionario_Aprobacion) AS Funcionario_Aprobacion FROM Gasto_Punto GP INNER JOIN (SELECT Identificacion_Funcionario, CONCAT_WS(' ',Nombres,Apellidos) AS Nombre_Funcionario, Firma FROM Funcionario) F ON (GP.Identificacion_Funcionario = F.Identificacion_Funcionario) INNER JOIN Punto_Dispensacion PD ON GP.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion WHERE GP.Id_Gasto_Punto = $id";

$oCon = new consulta();
$oCon->setQuery($query);
$gasto = $oCon->getData();
unset($oCon);

$query = "SELECT *, (SELECT Nombre FROM Tipo_Gasto WHERE Id_Tipo_Gasto = IGP.Id_Tipo_Gasto) AS Tipo_Gasto FROM Item_Gasto_Punto IGP WHERE Id_Gasto_Punto = $id";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $gastos = $oCon->getData();
    unset($oCon);

    $gastos = addNitObject($gastos);

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

$codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha"]).'</h5>
            <h6 style="margin:5px 0 0 0;font-size:15px;line-height:14px;">'.$data['Estado'].'</h6>
         
        ';

$observaciones = [
    "funcionario" => $gasto['Observaciones'] != '' ? '<tr style=" min-height: 100px;
    background: #e6e6e6;
    padding: 15px;
    border-radius: 10px;
    margin: 0;">
       
        <td style="font-size:11px;font-weight:bold;width:100px;padding:5px">
        Observación Gasto:
        </td>

        <td style="font-size:11px;width:610px;padding:5px">
        '.$gasto['Observaciones'].'
        </td>
        
    </tr>' : '',
    "legalizacion" => $gasto['Observacion_Aprobacion'] != '' ? '<tr style=" min-height: 100px;
    background: #e6e6e6;
    padding: 15px;
    border-radius: 10px;
    margin: 0;">
       
        <td style="font-size:11px;font-weight:bold;width:100px;padding:5px">
        Observación Legalización:
        </td>

        <td style="font-size:11px;width:610px;padding:5px">
        '.$gasto['Observacion_Aprobacion'].'
        </td>
        
    </tr>' : ''
];

$contenido = '<table style="background: #e6e6e6;">
        <tr style=" min-height: 100px;
        background: #e6e6e6;
        padding: 15px;
        border-radius: 10px;
        margin: 0;">
           
            <td style="font-size:11px;font-weight:bold;width:100px;padding:5px">
            Funcionario:
            </td>

            <td style="font-size:11px;width:610px;padding:5px">
            '.$gasto['Identificacion_Funcionario'].' - '.$gasto['Nombre_Funcionario'].'
            </td>
            
        </tr>
        
        <tr style=" min-height: 100px;
        background: #e6e6e6;
        padding: 15px;
        border-radius: 10px;
        margin: 0;">
           
            <td style="font-size:11px;font-weight:bold;width:100px;padding:5px">
            Punto Dispensación:
            </td>

            <td style="font-size:11px;width:610px;padding:5px">
            '.$gasto['Punto_Dispensacion'].'
            </td>
            
        </tr>
        '.$observaciones['funcionario'].'
        '.$observaciones['legalizacion'].'
    </table>
    ';

    $contenido .= '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:120px;max-width:120px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
            Tipo Gasto
        </td>
        <td style="width:90px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Nit
        </td>
        <td style="width:130px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Factura
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Base
        </td>
        <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Iva
        </td>
        <td style="width:80px;max-width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Rte Fuente
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Total
        </td>
    </tr>';

    foreach ($gastos as $key => $value) {
        $contenido .= '<tr>
            <td style="vertical-align:middle;font-size:9px;width:50px;border:1px solid #cccccc;">
                '.$value['Tipo_Gasto'].'
            </td>
            <td style="vertical-align:middle;font-size:9px;width:90px;border:1px solid #cccccc;">
                '.$value['Nit_Nombre']['Nombre'].'
            </td>
            <td style="vertical-align:middle;text-align:center;font-size:9px;width:60px;border:1px solid #cccccc;">
                '.$value['Documento'].'
            </td>
            <td style="vertical-align:middle;text-align:right;font-size:9px;word-break:break-all;width:60px;max-width:60px;border:1px solid #cccccc;">
                $.'.number_format($value['Base'],2,",",".").'
            </td>
            <td style="vertical-align:middle;text-align:right;font-size:9px;width:60px;border:1px solid #cccccc;">
                $.'.number_format($value['Iva'],2,",",".").'
            </td>
            <td style="vertical-align:middle;text-align:right;width:60px;max-width:60px;font-size:9px;word-break:break-all;border:1px solid #cccccc;">
                $.'.number_format($value['RteFte'],2,",",".").'
            </td>
            <td style="vertical-align:middle;font-size:9px;text-align:right;width:60px;border:1px solid #cccccc;">
                $.'.number_format($value['Total'],2,",",".").'
            </td>
        </tr>';
    }

    $contenido .= '
    <tr>
        <td style="font-weight:bold;text-align:right;border:1px solid #cccccc;" colspan="6">
            <strong>Totales:</strong>
        </td>
        <td style="font-weight:bold;text-align:right;border:1px solid #cccccc;" >
            $.'.number_format(array_sum(array_column($gastos,'Total')),2,",",".").'
        </td>
    </tr>
    </table>';

    $contenido .= '

    <table style="margin-top:10px;" cellpadding="0" cellspacing="0">

        <tr>
            <td style="font-weight:bold;width:370px;border:1px solid #cccccc;padding:4px">
                Elaboró:
            </td>
            <td style="font-weight:bold;width:345px;border:1px solid #cccccc;padding:4px">
                Aprobó:
            </td>
        </tr>

        <tr>
            <td style="font-size:10px;width:370px;border:1px solid #cccccc;padding:4px">
                '.$gasto['Nombre_Funcionario'].'
            </td>
            <td style="font-size:10px;width:345px;border:1px solid #cccccc;padding:4px">
                '.$gasto['Funcionario_Aprobacion'].'
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
                  <td class="td-header" style="width:350px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:210px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
                  </td>
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

function addNitObject($gastos) {
    foreach ($gastos as $i => $value) {
        $tercero = buscarNit($value['Nit']);
        if ($tercero) {
            $gastos[$i]['Nit_Nombre'] = $tercero;
            $gastos[$i]['Tipo_Nit'] = $tercero['Tipo'];
            $gastos[$i]['Nit_Encontrado'] = "Si"; 
        } else {
            $gastos[$i]['Nit_Nombre'] = $value['Nit'];
            $gastos[$i]['Tipo_Nit'] = '';
            $gastos[$i]['Nit_Encontrado'] = "No";
        }
    }

    return $gastos;
}

function buscarNit($nit) {
    $query = 'SELECT
    r.*
    FROM
    (
    SELECT C.Id_Cliente AS ID, IF(Nombre IS NULL OR Nombre = "", CONCAT_WS(" ", C.Id_Cliente,"-",Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), CONCAT(C.Id_Cliente, " - ", C.Nombre)) AS Nombre, "Cliente" AS Tipo FROM Cliente C WHERE C.Estado != "Inactivo" 
            UNION (SELECT P.Id_Proveedor AS ID, IF(P.Nombre = "" OR P.Nombre IS NULL, CONCAT_WS(" ",P.Id_Proveedor,"-",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido),CONCAT(P.Id_Proveedor, " - ", P.Nombre)) AS Nombre, "Proveedor" AS Tipo FROM Proveedor P) 
            UNION (SELECT F.Identificacion_Funcionario AS ID, CONCAT(F.Identificacion_Funcionario, " - ", F.Nombres," ", F.Apellidos) AS Nombre, "Funcionario" AS Tipo FROM Funcionario F) 
            UNION (SELECT CC.Nit AS ID, CONCAT(CC.Nit, " - ", CC.Nombre) AS Nombre, "Caja_Compensacion" AS Tipo FROM Caja_Compensacion CC WHERE CC.Nit IS NOT NULL)
    ) r WHERE r.ID = '. $nit ;
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $tercero = $oCon->getData();
    unset($oCon);

    return $tercero;
}

?>