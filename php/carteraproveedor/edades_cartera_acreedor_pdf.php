<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

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

$condiciones = SetCondiciones();


        $query = "
        SELECT 
        DATE(MC.Fecha_Movimiento) AS Fecha,
        MC.Documento AS Factura,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo,
        PC.Naturaleza AS Nat,
        MC.Nit,
        IF(CONCAT_WS(' ',
                    C.Primer_Nombre,
                    C.Segundo_Nombre,
                    C.Primer_Apellido,
                    C.Segundo_Apellido) != '',
            CONCAT_WS(' ',
                    C.Primer_Nombre,
                    C.Segundo_Nombre,
                    C.Primer_Apellido,
                    C.Segundo_Apellido),
            C.Razon_Social) AS Nombre_Proveedor,
        IF(IFNULL(IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
                            DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
                            0),
                        0),
                    0) = 0,
            (CASE PC.Naturaleza
                WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                ELSE (SUM(MC.Debe) - SUM(MC.Haber))
            END),
            0) AS Sin_Vencer,
            
            IF(IFNULL(IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
                            DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
                            0),
                        0),
                    0) BETWEEN 1 AND 30, (CASE PC.Naturaleza
                WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                ELSE (SUM(MC.Debe) - SUM(MC.Haber))
            END), 0) AS first_thirty_days,
            
            IF(IFNULL(IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
                            DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
                            0),
                        0),
                    0) BETWEEN 31 AND 60, (CASE PC.Naturaleza
                WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                ELSE (SUM(MC.Debe) - SUM(MC.Haber))
            END), 0) AS thirtyone_sixty,
            
            IF(IFNULL(IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
                            DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
                            0),
                        0),
                    0) BETWEEN 61 AND 90, (CASE PC.Naturaleza
                WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                ELSE (SUM(MC.Debe) - SUM(MC.Haber))
            END), 0) AS sixtyone_ninety,
            
            IF(IFNULL(IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
                            DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
                            0),
                        0),
                    0) BETWEEN 91 AND 180, (CASE PC.Naturaleza
                WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                ELSE (SUM(MC.Debe) - SUM(MC.Haber))
            END), 0) AS ninetyone_onehundeight,
            
            IF(IFNULL(IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
                            DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
                            0),
                        0),
                    0) BETWEEN 181 AND 360, (CASE PC.Naturaleza
                WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                ELSE (SUM(MC.Debe) - SUM(MC.Haber))
            END), 0) AS onehundeight_threehundsix,
            
            IF(IFNULL(IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
                            DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
                            0),
                        0),
                    0) > 360, (CASE PC.Naturaleza
                WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                ELSE (SUM(MC.Debe) - SUM(MC.Haber))
            END), 0) AS mayor_threehundsix
            
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
            INNER JOIN
        Proveedor C ON C.Id_Proveedor = MC.Nit
        WHERE
        MC.Estado != 'Anulado'
            AND PC.Codigo LIKE '2335%'
            $condiciones
        GROUP BY MC.Id_Plan_Cuenta , MC.Documento, MC.Nit
        HAVING Valor_Saldo != 0
        ORDER BY MC.Fecha_Movimiento
        ";
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $edades = $oCon->getData();
        unset($oCon);
               
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">EDADES CARTERA</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">PROVEEDOR</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Corte.'.fecha($_REQUEST['Fechas']).'</h5>
        ';
        

        $contenido = '<table style="font-size:8px;margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:50px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
                PROVEEDOR
            </td>
            <td style="width:130px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               RAZÓN SOCIAL
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               FACTURA
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                FECHA FACTURA
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               SALDO
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                SIN VENCER
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                1 - 30
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                31 - 60
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                61 - 90
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                91 - 180
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                181 - 360
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                MAYOR DE 360
            </td>
        </tr>';
    
        $totales = [
            "saldo" => 0,
            "sin_vencer" => 0,
            "1_30" => 0,
            "31_60" => 0,
            "61_90" => 0,
            "91_180" => 0,
            "181_360" => 0,
            "360_mas" => 0
        ];
    
        foreach ($edades as $edad) {
            
            $contenido .= '<tr>
            <td style="width:50px;padding:1px;text-align:center;border:1px solid #cccccc;">
                '.$edad['Nit'].'
            </td>
            <td style="width:130px;text-align:center;padding:1px;border:1px solid #cccccc;">
                '.$edad['Nombre_Proveedor'].'
            </td>
            <td style="width:80px;text-align:center;padding:1px;border:1px solid #cccccc;">
            '.$edad['Factura'].'
            </td>
            <td style="width:80px;text-align:center;padding:1px;border:1px solid #cccccc;">
                '.fecha($edad['Fecha']).'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
            $.'.number_format($edad['Valor_Saldo'],2,",",".").'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
                $.'.number_format($edad['Sin_Vencer'],2,",",".").'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
                $.'.number_format($edad['first_thirty_days'],2,",",".").'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
                $.'.number_format($edad['thirtyone_sixty'],2,",",".").'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
                $.'.number_format($edad['sixtyone_ninety'],2,",",".").'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
                $.'.number_format($edad['ninetyone_onehundeight'],2,",",".").'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
                $.'.number_format($edad['onehundeight_threehundsix'],2,",",".").'
            </td>
            <td style="width:80px;text-align:right;padding:1px;border:1px solid #cccccc;">
                $.'.number_format($edad['mayor_threehundsix'],2,",",".").'
            </td>
        </tr>';
    
        $totales["saldo"] += $edad['Valor_Saldo'];
        $totales["sin_vencer"] += $edad['Sin_Vencer'];
        $totales["1_30"] += $edad['first_thirty_days'];
        $totales["31_60"] += $edad['thirtyone_sixty'];
        $totales["61_90"] += $edad['sixtyone_ninety'];
        $totales["91_180"] += $edad['ninetyone_onehundeight'];
        $totales["181_360"] += $edad['onehundeight_threehundsix'];
        $totales["360_mas"] += $edad['mayor_threehundsix'];
            
        }
    
        $contenido .= '<tr>
        <td colspan="4" style="padding:1px;text-align:left;border:1px solid #cccccc;font-weight:bold;font-size:12px">Totales:</td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["saldo"],2,",",".").'
        </td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["sin_vencer"],2,",",".").'
        </td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["1_30"],2,",",".").'
        </td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["31_60"],2,",",".").'
        </td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["61_90"],2,",",".").'
        </td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["91_180"],2,",",".").'
        </td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["181_360"],2,",",".").'
        </td>
        <td style="width:80px;padding:1px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
            $.'.number_format($totales["360_mas"],2,",",".").'
        </td>
        </tr>';
    
        $contenido .= '</table>';

	

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
                  <td style="width:580px;text-align:right">
                        '.$codigos.'
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
    // $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf = new HTML2PDF('L', 'A4', 'en', true, 'UTF-8');
    $html2pdf->writeHTML($content);
    $direc = 'Edades.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function SetCondiciones(){

    $condicion = '';

    if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor']) {
        $condicion .= " AND MC.Nit = $_REQUEST[proveedor]";
    }

    if (isset($_REQUEST['Fechas']) && $_REQUEST['Fechas']) {        
        $fecha = $_REQUEST['Fechas'];
		$condicion .= " AND (DATE(MC.Fecha_Movimiento) <= '$fecha')";
    }

    return $condicion;
}

?>