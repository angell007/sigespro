<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');


$fecha_inicio = ( isset( $_REQUEST['Fecha_Inicial'] ) ? $_REQUEST['Fecha_Inicial'] : '' );
$fecha_fin = ( isset( $_REQUEST['Fecha_Corte'] ) ? $_REQUEST['Fecha_Corte'] : '' );


/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$movimientos = getMovimientos();
$totales = [
    "Debito" => 0,
    "Credito" => 0
];

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
               
        $tipo_balance = strtoupper($tipo);
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">MOVIMIENTOS GLOBALIZADOS</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Ini. '.fecha($fecha_inicio).'</h5>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Fin. '.fecha($fecha_fin).'</h5>
        ';
     
    $contenido = '
    <table style="border-collapse: collapse">
        <tr style="font-weight:bold">
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:80px">Fecha</td>
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:100px">Documento</td>
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:80px">NIT</td>
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:250px">Nombre</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:100px">Debito</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:100px">Credito</td>
        </tr>';

    foreach ($movimientos as $i => $value) {
        if ($i == (count($movimientos)-1)) {
            $border ='border-bottom: 1px solid #000;';
        }
        $contenido .= '<tr style="font-size:11px">
                    <td style="padding:2px; width:80px">'.fecha($value['Fecha']).'</td>
                    <td style="padding:2px; width:100px">'.$value['Numero_Comprobante'].'</td>
                    <td style="padding:2px; width:80px">'.$value['NIT'].'</td>
                    <td style="padding:2px; width:250px">'.$value['Beneficiario'].'</td>
                    <td style="padding:2px; text-align:right; width:100px;'.$border.'">'.number_format($value['Debe'],2,",",".").'</td>
                    <td style="padding:2px; text-align:right; width:100px;'.$border.'">'.number_format($value['Haber'],2,",",".").'</td>
                </tr>';
        
        $totales["Debito"] += $value['Debe'];
        $totales["Credito"] += $value['Haber'];
    }

    $contenido .= '
                <tr style="font-size:11px">
                    <td colspan="4" style="padding:2px; text-align:right;font-weight:bold">Total $:</td>
                    <td style="padding:2px;text-align:right;width:100px;">'.number_format($totales["Debito"],2,",",".").'</td>
                    <td style="padding:2px;text-align:right;width:100px;">'.number_format($totales["Credito"],2,",",".").'</td>
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
                  <td style="width:250px;text-align:right">
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
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function fecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function getMovimientos() {
    $condiciones = getStrCondiciones();

    $query = "SELECT DATE(Fecha_Movimiento) AS Fecha, Numero_Comprobante, Nit AS NIT, (
        CASE
        MC.Tipo_Nit
        WHEN 'Cliente' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Cliente WHERE Id_Cliente = MC.Nit)
        WHEN 'Proveedor' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Proveedor WHERE Id_Proveedor = MC.Nit)
        WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = MC.Nit)
        END
        ) AS Beneficiario, SUM(Debe) AS Debe, SUM(Haber) AS Haber, SUM(Debe_Niif) AS Debe_Niif, SUM(Haber_Niif) AS Haber_Niif FROM Movimiento_Contable MC $condiciones GROUP BY MC.Numero_Comprobante ORDER BY MC.Fecha_Movimiento";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

function getStrCondiciones() {
    $condicion = '';
    if (isset($_REQUEST['Fecha_Inicial']) && $_REQUEST['Fecha_Inicial'] != "" && isset($_REQUEST['Fecha_Corte']) && $_REQUEST['Fecha_Corte'] != "") {
        $fecha_inicio = $_REQUEST['Fecha_Inicial'];
        $fecha_fin = $_REQUEST['Fecha_Corte'];
        $condicion .= " WHERE DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
    if (isset($_REQUEST['Fuente']) && $_REQUEST['Fuente'] != '') {
        $condicion .= " AND MC.Id_Modulo = $_REQUEST[Fuente]";
    }
    if (isset($_REQUEST['Nit']) && $_REQUEST['Nit'] != '') {
        $condicion .= " AND MC.Nit = $_REQUEST[Nit]";
    }
    if (isset($_REQUEST['Estado']) && $_REQUEST['Estado'] != '') {
        $condicion .= " AND MC.Estado = '$_REQUEST[Estado]'";
    }

    return $condicion;
}


?>