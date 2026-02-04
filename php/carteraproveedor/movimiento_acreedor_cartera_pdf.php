<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

include('./funciones.php');

list($fecha_inicio, $fecha_fin) = explode(' - ', $_REQUEST['fechas']);
$ultimo_dia_mes = getUltimoDiaMes($fecha_inicio);

include('./querys.php');

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
               
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">MOV. CARTERA PROVEEDOR</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Ini. '.fecha($fecha_inicio).'</h5>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Fin. '.fecha($fecha_fin).'</h5>
        ';
     
    
        $contenido = '<table cellpadding=0 cellspacing=0>
        <thead>
          <tr style="font-size:9px;">
            <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Numero</th>
            <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Fecha</th>
            <th style="max-width:140px !important;width:140px;text-align:center;border:1px solid #000;padding:3px">Concepto</th>
            <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Factura</th>
            <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Debito</th>
            <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Credito</th>
            <th style="width:170px;text-align:center;border:1px solid #000;padding:3px">Saldo</th>
          </tr>
        </thead>
        <tbody>';
  
        $campo = getCampo();
  
        $condicion = strCondicions('Acreedor');

        $query = queryByCuentaToNit($condicion);
  
        $nuevo_saldo_anterior = 'init';
        $total_debe =0;
        $total_haber = 0;
  
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $cuentas = $oCon->getData();
        unset($oCon);
  
        $cuentas = armarDatosNit($cuentas);
  
        foreach ($cuentas as $i => $cuenta) {
          $contenido .= '
            <tr style="font-size:12px">
              <td style="word-wrap:break-word;padding:3px;border-bottom:1px solid red">'.$cuenta[$campo['codigo']].'</td>
              <td style="word-wrap:break-word;padding:3px;border-bottom:1px solid red" colspan="6">'.$cuenta[$campo['nombre']].'</td>
            </tr>
          ';
  
          foreach ($cuenta['Nits'] as $j => $nit) {
            $saldo_anterior= obtenerSaldoAnterior($cuenta['Naturaleza'],$cuenta['Nits'], $j, true); // El ultimo parametro true significa que es para calcular el saldo anterior de un nit.
            $contenido .= '
              <tr style="font-weight:bold;font-size:11px;padding:3px;">
                <td style="word-wrap:break-word;width:80px;">'.$nit['Nit'].'</td>
                <td colspan="3" style="word-wrap:break-word;background:#c3c4c5;">'.wordwrap($nit['Nombre_Nit'],50,"<br>").'</td>
                <td style="word-wrap:break-word;text-align:right;">Saldo Anterior:</td>
                <td style="word-wrap:break-word;text-align:right;" colspan="2">$ '.number_format($saldo_anterior,2,",",".").'</td>
              </tr>
            ';
  
            foreach ($nit['Movimientos'] as $value) {
              $debe = $value[$campo['debe']];
              $haber = $value[$campo['haber']];
              $nuevo_saldo = $nuevo_saldo_anterior === 'init' ? calcularNuevoSaldo($cuenta['Naturaleza'],$saldo_anterior,$debe,$haber) : calcularNuevoSaldo($cuenta['Naturaleza'],$nuevo_saldo_anterior,$debe,$haber);
              
              $contenido .= '
              <tr style="text-align:center;font-size:10px;border: 1px solid #ccc;">
                <td style="word-wrap:break-word;width:80px;border: 1px solid #ccc;">'.$value['Numero_Comprobante'].'</td>
                <td style="word-wrap:break-word;width:80px;border: 1px solid #ccc;">'.$value['Fecha'].'</td>
                <td style="word-break:break-all;max-width:140px !important;width:140px;border: 1px solid #ccc;">'.$value['Concepto'].'</td>
                <td style="word-wrap:break-word;width:80px;border: 1px solid #ccc;">'.$value['Documento'].'</td>
                <td style="border: 1px solid #ccc;">$ '.number_format($debe,2,",",".").'</td>
                <td style="border: 1px solid #ccc;">$ '.number_format($haber,2,",",".").'</td>
                <td style="width:170px;border: 1px solid #ccc;text-align:right;">$ '.number_format($nuevo_saldo,2,",",".").'</td>
              </tr>
              ';
  
              $total_debe += $debe;
              $total_haber += $haber;
              $nuevo_saldo_anterior = $nuevo_saldo;
            }
  
            $contenido .= '
            <tr style="font-weight:bold;font-size:10px;">
              <td colspan="4" style="text-align:right;border: 1px solid #ccc;">
                Saldo Anterior $: '.number_format($saldo_anterior,2,",",".").'
              </td>
              <td style="border: 1px solid #ccc;">$ '.number_format($total_debe,2,",",".").'</td>
              <td style="border: 1px solid #ccc;">$ '.number_format($total_haber,2,",",".").'</td>
              <td style="width:170px;border: 1px solid #ccc;text-align:right;">$ '.number_format($nuevo_saldo,2,",",".").'</td>
            </tr>
          ';
            
            $total_debe = 0;
            $total_haber = 0;
            $nuevo_saldo_anterior = 'init';
          }
  
        }
  
         $contenido .='</tbody></table>';



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


?>