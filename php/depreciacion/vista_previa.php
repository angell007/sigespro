<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Depreciacion.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

require('utilidades/querys.php');
require('utilidades/funciones.php');

$tipo = isset($_REQUEST['Tipo']) ? $_REQUEST['Tipo'] : '';
$mes_hoy = isset($_REQUEST['Mes']) ? $_REQUEST['Mes'] : 1156;
$year_select = isset($_REQUEST['Year']) ? $_REQUEST['Year'] : date('Y');

$meses = ["ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];

$years = ["2019","2020"];
/*
$years = [];
$year_ini=2018;
$year_actual = date('Y');
$i=0;

for($i=$year_ini;$i<=$year_actual;$i++){
    $years = array_push($years, $i);
}
*/
/*
$pila = array("naranja", "plátano");
array_push($pila, "manzana", "arándano");
print_r($pila);
*/
$tipos_activos = getDatosTiposActivos($tipo,$mes_hoy,$year_select);
//echo json_encode($tipos_activos);exit;
$costo_historico = 0;
$totales_anio = 0;
$totales_anio_actual = 0;
$totales_depre_acum = 0;
$totales_antes_depre_acum = 0;
$totales_antes_saldo_neto = 0;
$totales_saldo_neto = 0;
$totalesDepreciadoMeses = [];

$contenido = '';

$contenido .= '
<table>
<tr>
    <td colspan="10">
        <h2>PRO - H S.A.</h2>
    </td>
    <td colspan="11">
        <h2>'.$tipo.'</h2>
    </td>
</tr>
</table>

<table border="1">

<tr>
    <td width="250"><strong>DETALLE</strong></td>
    <td><strong>FECHA ADQUISICION</strong></td>
    <td></td>
    <td></td>
    <td><strong>COSTO HISTORICO</strong></td>
    <td><strong>DEPRECIACION ACUMULADA</strong></td>
    <td><strong>SALDO</strong></td>
    <td colspan="14" align="center"><strong>DEPRECIACION '.$year_select.'</strong></td>
    <td rowspan="2" align="center"><strong>SALDO NETO</strong></td>
</tr>   

<tr>

<td width="250"></td>
<td></td>
<td align="center"><strong>DIAS</strong></td>
<td align="center"><strong>PER/MES</strong></td>
<td></td>
<td></td>
<td></td>
';  

foreach ($meses as $mes) {
    $contenido .= '<td align="center"><strong>'.$mes.'</strong></td>';
}

$contenido .= '

<td align="center"><strong>TOTAL AÑO</strong></td>
<td align="center"><strong>ACUMULADO</strong></td>

</tr>';

foreach ($tipos_activos as $i => $tipo_act) {
    $contenido .= '<tr>
    <td colspan="23">&nbsp;</td>
</tr>

<tr>
    <td colspan="23">
    <h3>'.$tipo_act['Nombre'].'</h3>
    </td>
</tr>

<tr>
    <td colspan="23">&nbsp;</td>
</tr>';

foreach ($tipo_act['activos_fijos'] as $j => $activo) {
    $costo = $activo['Costo_'.$tipo];
    $depreciacion_acum = getDepreciacionAcum($tipo, $activo['ID']);
    $saldo_activo = $costo - $depreciacion_acum;

    $totales_antes_depre_acum += $depreciacion_acum;
    $totales_antes_saldo_neto += $saldo_activo;
    //$anio_compra = date('Y', strtotime($activo['Fecha']));
    $anio_compra = date($year_select, strtotime($activo['Fecha']));

    //$vida_util = $activo['Tipo_Depreciacion'] == 1 && $anio_compra == date('Y') ? 1 : $tipo_act['Vida_Util_'.$tipo]; // Si el tipo de depreciacion es 0, se deprecia de manera normal, de lo contrario, solo se depreciará a 1 mes.


    $vida_util = $activo['Tipo_Depreciacion'] == 1 && $anio_compra == $year_select ? 1 : $tipo_act['Vida_Util_'.$tipo];

    $contenido .= '
    <tr>
        <td width="250">'.$activo['Nombre'].'</td>
        <td>'.fecha($activo['Fecha']).'</td>
        <td></td>
        <td>'.$vida_util.'</td>
        <td align="right">'.numberFormat($costo).'</td>
        <td align="right">'.numberFormat($depreciacion_acum).'</td>
        <td align="right">'.numberFormat($saldo_activo).' </td>
        ';

        foreach ($meses as $z => $mes) {
            $mesReporte = ($z+1);
            $depreciadoMes = null;
            if($tipo == 'NIIF' &&  $mesReporte == $mes_hoy && $tipo_act['Sin_Depreciacion_Niff'] == 1){
                $depreciadoMes = 0;
           
            }
            else{
                $depreciadoMes = calcularDepreciacionMes($mes_hoy, $year_select, $activo['ID'], $mesReporte, $tipo_act['Porcentaje_'.$tipo],
                                                    $costo, $vida_util,$activo['Vida_Util_Acum'],$activo['Fecha'],$activo['Depreciacion_Acum_'.$tipo], $tipo) ;
                
            }
            $contenido .= '<td align="right">'.numberFormat($depreciadoMes).'</td>';
            
            if (!array_key_exists($mes,$totalesDepreciadoMeses)) {
                $totalesDepreciadoMeses[$mes] = $depreciadoMes;
            } else {
                $totalesDepreciadoMeses[$mes] += $depreciadoMes;
            }
        

            $totales_anio_actual += $depreciadoMes;
        }
          
        $total_anio = $totales_anio_actual; // Le sumo lo depreciado del mes.
        $totalDepreciado = getDepreciacionAcum($tipo, $activo['ID']) + $total_anio;
        $saldo_activo = $costo - $totalDepreciado;
        $contenido .= '<td align="right">'.numberFormat($total_anio).'</td>
        <td align="right">'.numberFormat($totalDepreciado).'</td>
        <td align="right">'.numberFormat($saldo_activo).'</td>
    </tr>';

    $costo_historico += $costo;
    $totales_anio += $total_anio;
    $totales_depre_acum += $totalDepreciado;
    $totales_saldo_neto += $saldo_activo;
    $totales_anio_actual = 0;
}



$contenido .= '<tr>
    <td colspan="23">&nbsp;</td>
</tr>

<tr>
    <td width="250" align="center"><strong>TOTAL '.$tipo_act['Nombre'].' </strong></td>
    <td></td>
    <td></td>
    <td></td>
    <td align="right"><strong>'.numberFormat($costo_historico).'</strong></td>
    <td align="right"><strong>'.numberFormat($totales_antes_depre_acum).'</strong></td>
    <td align="right"><strong>'.numberFormat($totales_antes_saldo_neto).'</strong></td>
    ';
    foreach ($totalesDepreciadoMeses as $mes => $valor) {
        $contenido .= '<td align="right"><strong>'.numberFormat($valor).'</strong></td>';
    }
    $contenido .= '<td align="right"><strong>'.numberFormat($totales_anio).'</strong></td>
    <td align="right"><strong>'.numberFormat($totales_depre_acum).'</strong></td>
    <td align="right"><strong>'.numberFormat($totales_saldo_neto).'</strong></td>
</tr>';

    $costo_historico = 0;
    $totales_anio = 0;
    $totales_depre_acum = 0;
    $totales_saldo_neto = 0;
    $totales_antes_depre_acum = 0;
    $totales_antes_saldo_neto = 0;
    $totalesDepreciadoMeses = [];
}

$contenido .= '</table>';

echo $contenido;



?>