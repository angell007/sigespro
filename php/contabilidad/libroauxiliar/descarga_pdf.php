<?php

header('Access-Control-Allow-Origin: *');
header('Content-type: Application/Json');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once '../../../config/start.inc.php';
include_once '../../../class/class.lista.php';
include_once '../../../class/class.complex.php';
include_once '../../../class/class.consulta.php';
require_once '../../../class/html2pdf.class.php';

ini_set("memory_limit", "3200000M");
ini_set('max_execution_time', -1);

// $id = uniqid();
//     $archivo = "$id.html";
//     unlink($archivo);
//     $handle = fopen("$archivo", 'a+');

include './funciones.php';

$tipo = (isset($_REQUEST['Discriminado']) ? $_REQUEST['Discriminado'] : '');
$fecha_inicio = (isset($_REQUEST['Fecha_Inicial']) ? $_REQUEST['Fecha_Inicial'] : '');
$fecha_fin = (isset($_REQUEST['Fecha_Final']) ? $_REQUEST['Fecha_Final'] : '');
$cuenta_inicial = (isset($_REQUEST['Cuenta_Inicial']) ? $_REQUEST['Cuenta_Inicial'] : '');
$cuenta_final = (isset($_REQUEST['Cuenta_Final']) ? $_REQUEST['Cuenta_Final'] : '');
$ultimo_dia_mes = getUltimoDiaMes($fecha_inicio);
$tipo_reporte = $_REQUEST['Tipo'] == 'General' ? 'PCGA' : 'NIIF';

include './querys.php';

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion', "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style = '<style>
.page-content{
width:650px;
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

$codigos = '
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">LIBRO AUXILIAR ' . $tipo_reporte . '</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Tipo.: ' . $tipo . '</h5>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Ini. ' . fecha($fecha_inicio) . '</h5>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Fin. ' . fecha($fecha_fin) . '</h5>
            <small>' . $cuenta_inicial . ' - ' . $cuenta_final . '</small>
        ';
$cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    ' . $config["Nombre_Empresa"] . '<br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    TEL: ' . $config["Telefono"] . '
                  </td>
                  <td style="width:250px;text-align:right">
                        ' . $codigos . '
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';

$cntnt = '<page backtop="0mm" backbottom="0mm">
            <div class="page-content" >' . $cabecera;
            // fwrite($handle, $cntnt);
if ($tipo == 'Nit') {

    $contenido = '<table cellpadding=0 cellspacing=0>
      <thead>
        <tr style="font-size:9px;">
          <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Numero</th>
          <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Fecha</th>
          <th style="max-width:140px !important;width:140px;text-align:center;border:1px solid #000;padding:3px">Concepto</th>
          <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Factura</th>
          <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Debito</th>
          <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Credito</th>
          <th style="width:80px;text-align:center;border:1px solid #000;padding:3px">Saldo</th>
        </tr>
      </thead>
      <tbody>';


    $campo = getCampo();

    $condicion = strCondicions();

    $query = queryByCuentaToNit($condicion);

    $nuevo_saldo_anterior = 'init';
    $total_debe = 0;
    $total_haber = 0;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $cuentas = $oCon->getData();
    unset($oCon);
    
    if ($cuentas) {
        $cuentas = armarDatosNit($cuentas);
    } else {
        $query = queryByCuentaToNit($condicion, true);

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $cuentas = $oCon->getData();
        unset($oCon);

        $cuentas = armarDatosNit($cuentas);
    }

    foreach ($cuentas as $i => $cuenta) {
    $contenido .= '
        <tr style="font-size:12px">
          <td style="word-wrap:break-word;padding:3px;border-bottom:1px solid red">' . $cuenta[$campo['codigo']] . '</td>
          <td style="word-wrap:break-word;padding:3px;border-bottom:1px solid red" colspan="6">' . $cuenta[$campo['nombre']] . '</td>
        </tr>
      ';

    foreach ($cuenta['Nits'] as $j => $nit) {
        $saldo_anterior = obtenerSaldoAnterior($cuenta['Naturaleza'], $cuenta['Nits'], $j, true); // Obtener el saldo anterior
        $tiene_movimientos = false; // Variable para verificar si hay movimientos que afecten el saldo actual
        $nuevo_saldo = 0; // Inicializar el saldo actual
        
        foreach ($nit['Movimientos'] as $value) {
            $debe = $value[$campo['debe']];
            $haber = $value[$campo['haber']];
            $nuevo_saldo = calcularNuevoSaldo($cuenta['Naturaleza'], $nuevo_saldo_anterior === 'init' ? $saldo_anterior : $nuevo_saldo_anterior, $debe, $haber);
            
            // Si hay movimientos que afectan el saldo actual, activamos la bandera
            if ($debe != 0 || $haber != 0) {
                $tiene_movimientos = true;
            }
        }

        // Condición para imprimir el saldo si el saldo anterior no es 0 O si tiene movimientos que afectan el saldo actual
        if ($saldo_anterior != 0 || $tiene_movimientos) {
            $contenido .= '
              <tr style="font-weight:bold;font-size:11px;padding:3px;">
                <td style="word-wrap:break-word;width:80px;">' . $nit['Nit'] . '</td>
                <td colspan="3" style="word-wrap:break-word;background:#c3c4c5;">' . wordwrap($nit['Nombre_Nit'], 50, "<br>") . '</td>
                <td style="word-wrap:break-word;text-align:right;">Saldo Anterior:</td>
                <td style="word-wrap:break-word;text-align:right;" colspan="2">$ ' . number_format($saldo_anterior, 2, ",", ".") . '</td>
              </tr>
            ';

            foreach ($nit['Movimientos'] as $value) {
                $debe = $value[$campo['debe']];
                $haber = $value[$campo['haber']];
                $nuevo_saldo = calcularNuevoSaldo($cuenta['Naturaleza'], $nuevo_saldo_anterior === 'init' ? $saldo_anterior : $nuevo_saldo_anterior, $debe, $haber);

                $contenido .= '
                  <tr style="text-align:center;font-size:10px;border: 1px solid #ccc;">
                    <td style="word-wrap:break-word;width:80px;border: 1px solid #ccc;">' . $value['Numero_Comprobante'] . '</td>
                    <td style="word-wrap:break-word;width:80px;border: 1px solid #ccc;">' . $value['Fecha'] . '</td>
                    <td style="word-break:break-all;max-width:140px !important;width:140px;border: 1px solid #ccc;">' . $value['Concepto'] . '</td>
                    <td style="word-wrap:break-word;width:80px;border: 1px solid #ccc;">' . $value['Documento'] . '</td>
                    <td style="border: 1px solid #ccc;">$ ' . number_format($debe, 2, ",", ".") . '</td>
                    <td style="border: 1px solid #ccc;">$ ' . number_format($haber, 2, ",", ".") . '</td>
                    <td style="width:100px;border: 1px solid #ccc;text-align:right;">$ ' . number_format($nuevo_saldo, 2, ",", ".") . '</td>
                  </tr>
                ';

                $total_debe += $debe;
                $total_haber += $haber;
                $nuevo_saldo_anterior = $nuevo_saldo;
            }

            $nuevo_saldo = $total_debe == 0 && $total_haber == 0 && $saldo_anterior != 0 ? $saldo_anterior : $nuevo_saldo;

            $contenido .= '
              <tr style="font-weight:bold;font-size:10px;">
                <td colspan="4" style="text-align:right;border: 1px solid #ccc;">
                  Saldo Anterior $: ' . number_format($saldo_anterior, 2, ",", ".") . '
                </td>
                <td style="border: 1px solid #ccc;">$ ' . number_format($total_debe, 2, ",", ".") . '</td>
                <td style="border: 1px solid #ccc;">$ ' . number_format($total_haber, 2, ",", ".") . '</td>
                <td style="width:170px;border: 1px solid #ccc;text-align:right;">$ ' . number_format($nuevo_saldo, 2, ",", ".") . '</td>
              </tr>
            ';

            $total_debe = 0;
            $total_haber = 0;
            $nuevo_saldo_anterior = 'init';
        }
    }
}

    $contenido .= '</tbody></table>';
    // fwrite($handle, $contenido);

} else {

    $contenido = '<table cellpadding=0 cellspacing=0>
    <thead>
      <tr style="font-size:9px;">
        <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Numero</th>
        <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Nit</th>
        <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Fecha</th>
        <th style="max-width:110px !important;width:110px;text-align:center;border:1px solid #000;padding:3px">Concepto</th>
        <th style="max-width:110px !important;width:110px;text-align:center;border:1px solid #000;padding:3px">Nombre</th>
        <th style="width:50px;max-width:50px;text-align:center;border:1px solid #000;padding:3px">Factura</th>
        <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Debito</th>
        <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Credito</th>
        <th style="width:50px;text-align:center;border:1px solid #000;padding:3px">Saldo</th>
      </tr>
    </thead><tbody>';

    $campo = getCampo();

    $condicion = strCondicions();

    //$query = queryByCuenta($condicion);

    $nuevo_saldo_anterior = 'init';
    $total_debe = 0;
    $total_haber = 0;

    $query = queryByCuenta($condicion, true);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $cuentas = $oCon->getData();
    unset($oCon);
    

    foreach ($cuentas as $i => $cuenta) {
        $query = queryMovimientosCuenta($cuenta['Id_Plan_Cuenta']);
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $movimientos = $oCon->getData();
        unset($oCon);

         $saldo_anterior = obtenerSaldoAnterior($cuenta['Naturaleza'], $cuentas, $i, isset($_REQUEST['Nit']));

        // Aplicar filtro: solo incluir cuentas con movimientos o saldo anterior distinto de 0
    if (count($movimientos) > 0 || $saldo_anterior != 0) {
        $cuentas[$i]['Movimientos'] = $movimientos;
        $cuentas[$i]['Saldo_Anterior'] = $saldo_anterior;
    } else {
        // Si no cumple con el criterio, eliminar la cuenta
        unset($cuentas[$i]);
    }
    }

    foreach ($cuentas as $i => $cuenta) {
    //   fwrite($handle, $contenido);
    //   $contenido='';
        $request_nit = isset($_REQUEST['Nit']) ? true : false;

        $saldo_anterior = obtenerSaldoAnterior($cuenta['Naturaleza'], $cuentas, $i, $request_nit);
        
        $contenido .= '
      <tr style="font-size:9px">
        <td style="width:50px;max-width:50px;word-wrap:break-word;padding:5px;background:#c3c4c5">' . $cuenta[$campo['codigo']] . '</td>
        <td style="width:360px;max-width:360px;word-wrap:break-word;padding:5px;background:#c3c4c5" colspan="5">' . $cuenta[$campo['nombre']] . '</td>
        <td style="width:50px;max-width:50px;word-wrap:break-word;padding:5px;background:#c3c4c5">Saldo Anterior </td>
        <td style="width:50px;max-width:50px;word-wrap:break-word;padding:5px;background:#c3c4c5;text-align:right;">$ ' . number_format($saldo_anterior, 2, ",", ".") . '</td>
        <td style="width:60px;max-width:80px;word-wrap:break-word;padding:5px;background:#c3c4c5"></td>
      </tr>
      ';

        foreach ($cuenta['Movimientos'] as $value) {
            $debe = $value[$campo['debe']];
            $haber = $value[$campo['haber']];
            $nuevo_saldo = $nuevo_saldo_anterior === 'init' ? calcularNuevoSaldo($cuenta['Naturaleza'], $saldo_anterior, $debe, $haber) : calcularNuevoSaldo($cuenta['Naturaleza'], $nuevo_saldo_anterior, $debe, $haber);

            $contenido .= '
        <tr style="text-align:center;font-size:8px">
        <td style="word-wrap:break-word;">' . $value['Numero_Comprobante'] . '</td>
        <td style="word-wrap:break-word;">' . $value['Nit'] . '</td>
        <td style="word-wrap:break-word;">' . $value['Fecha'] . '</td>
        <td style="max-width:110px !important;width:110px;word-break:break-all !important;">' . $value['Concepto'] . '</td>
        <td style="max-width:110px !important;width:110px;word-break:break-all !important;">' . $value['Nombre_Nit'] . '</td>
        <td style="width:50px;max-width:50px;word-wrap:break-word;">' . $value['Documento'] . '</td>
        <td style="word-wrap:break-word;text-align:right;">$ ' . number_format($debe, 2, ",", ".") . '</td>
        <td style="word-wrap:break-word;text-align:right;">$ ' . number_format($haber, 2, ",", ".") . '</td>
        <td style="word-wrap:break-word;text-align:right;">$ ' . number_format($nuevo_saldo, 2, ",", ".") . '</td>
      </tr>
        ';

            $nuevo_saldo_anterior = $nuevo_saldo;
            $total_debe += $debe;
            $total_haber += $haber;
        }

        $sald_ant = obtenerSaldoAnterior($cuenta['Naturaleza'], $cuentas, $i, $request_nit);
        $nuevo_saldo_anterior = $total_debe == 0 && $total_haber == 0 && $sald_ant != 0 ? $sald_ant : $nuevo_saldo_anterior;
        $nuevo_saldo_anterior = is_numeric($nuevo_saldo_anterior) ? $nuevo_saldo_anterior : 0;

        $contenido .= '
      <tr style="font-size:8px">
          <td rowspan="2" style="padding:3px;font-weight:bold">TOTAL CTA</td>
          <td rowspan="2" colspan="4" style="padding:3px;font-weight:bold">' . $cuenta[$campo['nombre']] . '</td>
          <td style="text-align:center;font-weight:bold">Saldo Anterior</td>
          <td style="text-align:center;font-weight:bold">Debitos</td>
          <td style="text-align:center;font-weight:bold">Creditos</td>
          <td style="text-align:center;font-weight:bold">Nuevo Saldo </td>
        </tr>
        <tr style="font-size:8px">
          <td style="text-align:right">$ ' . number_format($sald_ant, 2, ",", ".") . '</td>
          <td style="text-align:right">$ ' . number_format($total_debe, 2, ",", ".") . '</td>
          <td style="text-align:right">$ ' . number_format($total_haber, 2, ",", ".") . '</td>
          <td style="text-align:right">$ ' . number_format($nuevo_saldo_anterior, 2, ",", ".") . '</td>
        </tr>
      ';

        $total_debe = 0;
        $total_haber = 0;
        $nuevo_saldo_anterior = 'init';
    }
    

    $contenido .= '</tbody></table>';
    // fwrite($handle, $contenido);
}
$cntnt = '</div>
            </page>';
// fwrite($handle, $cntnt);


/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    ' . $config["Nombre_Empresa"] . '<br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    TEL: ' . $config["Telefono"] . '
                  </td>
                  <td style="width:250px;text-align:right">
                        ' . $codigos . '
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >' .
    $cabecera .
    $contenido . '
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try {
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(2, 2, 2, 2));

    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"] . '.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
} catch (HTML2PDF_exception $e) {
    echo $e;
    exit;
}