<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Estado_Resultados.xls"');
header('Cache-Control: max-age=0');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : '' );
$fecha_inicio = ( isset( $_REQUEST['Fecha_Inicial'] ) ? $_REQUEST['Fecha_Inicial'] : '' );
$fecha_fin = ( isset( $_REQUEST['Fecha_Final'] ) ? $_REQUEST['Fecha_Final'] : '' );
$id_centro_costo = ( isset( $_REQUEST['Centro_Costo'] ) ? $_REQUEST['Centro_Costo'] : '' );
include('./querys.php');
include('./funciones.php');

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

              

    $campo = getCampo();
    $condicion = strCondicions();
    $condicion_fecha = strCondicionFecha();

    $query = ingresosOperacionales($condicion, $condicion_fecha,$id_centro_costo);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $ingresos = $oCon->getData();
    unset($oCon);
    
    $query = costosVentas($condicion, $condicion_fecha,$id_centro_costo);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $costosVentas = $oCon->getData();
    unset($oCon);
    
    $query = gastosAdmin($condicion, $condicion_fecha, $id_centro_costo);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $gastosAdmin = $oCon->getData();
    unset($oCon);
    
    $query = gastosVentas($condicion, $condicion_fecha, $id_centro_costo);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $gastosVentas = $oCon->getData();
    unset($oCon);
    
    $query = ingresosNoOperacionales($condicion, $condicion_fecha, $id_centro_costo);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $ingresosNoOper = $oCon->getData();
    unset($oCon);
    
    $query = gastosNoOperacionales($condicion, $condicion_fecha, $id_centro_costo);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $gastosNoOper = $oCon->getData();
    unset($oCon);
    
    $query = impuestos($condicion, $condicion_fecha, $id_centro_costo);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $impuestos = $oCon->getData();
    unset($oCon);

    $total_ingresos_operacionales = 0;
    $total_no_ingresos_operacionales = 0;
    $total_devoluciones = 0;
    $total_costos_ventas = 0;
    $total_gastos_admin = 0;
    $total_gastos_ventas = 0;
    $total_gastos_no_operacionales = 0;
    $total_impuestos = 0;



    $contenido = '<table class="table">
      <tr>
        <td colspan="3" align="center">
          <strong>ESTADO DE RESULTADOS | '.fecha($fecha_inicio).' - '.fecha($fecha_fin).'</strong>
        </td>
      </tr>
      <tr>
        <td style="width:100px;font-weight:bold"></td>
        <td style="width:490px;"><strong>INGRESOS OPERACIONALES</strong></td>
        <td style="width: 120px;"></td>
      </tr>';

     foreach ($ingresos as $i => $ingreso) {
       $saldo = calcularNuevoSaldo($ingreso['Naturaleza'],0,$ingreso[$campo['debe']],$ingreso[$campo['haber']]);
       if ($saldo != 0) {
        $contenido .= '<tr>
        <td style="width: 100px">'.$ingreso[$campo['codigo']].'</td>
        <td style="width:490px;">'.$ingreso[$campo['nombre']].'</td>
        <td style="width: 80px;text-align:right">'.number_format($saldo,2,",",".").'</td>
      </tr>';
       }
        if ($ingreso['Tipo_Cta'] == 'Ingreso') {
          $total_ingresos_operacionales += $saldo;
        } elseif ($ingreso['Tipo_Cta'] == 'Devolucion') {
          $total_devoluciones += $saldo;
        } else {
          $total_ingresos_operacionales += $saldo;
        }
     }
     
     $ingresos_operacionales_netos = ($total_ingresos_operacionales + $total_devoluciones);

     // Arreglar este total...
     $contenido .= '<tr>
        <td style="width:100px;font-weight:bold"></td>
        <td style="width:490px;"><strong>INGRESOS OPERACIONALES NETOS</strong></td>
        <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($ingresos_operacionales_netos,2,",",".").'</b></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>COSTO DE VENTAS</strong></td>
        <td style="width: 120px;"></td>
      </tr>
      ';

      foreach ($costosVentas as $i => $costo) {
        $saldo = calcularNuevoSaldo($costo['Naturaleza'],0,$costo[$campo['debe']],$costo[$campo['haber']]);
        if ($saldo != 0) {
          $contenido .= '<tr>
          <td style="width: 100px">'.$costo[$campo['codigo']].'</td>
          <td style="width:490px;">'.$costo[$campo['nombre']].'</td>
          <td style="width: 80px;text-align:right">'.number_format($saldo,2,",",".").'</td>
        </tr>';
        }

         $total_costos_ventas += $saldo;
      }

      $utilidad_bruta_ventas = $ingresos_operacionales_netos - $total_costos_ventas;

      $contenido .= '
      <tr>
        <td style="width:100px;font-weight:bold">&nbsp;</td>
        <td style="width:490px;font-weight:bold">&nbsp;</td>
        <td style="width: 120px;">&nbsp;</td>
      </tr>
      <tr>
        <td style="width:100px;font-weight:bold"></td>
        <td style="width:490px;"><strong>Utilidad Bruta en Ventas</strong></td>
        <td style="width: 120px;text-align:right;border-top:1px solid #000"><strong>'.number_format($utilidad_bruta_ventas,2,",",".").'</strong></td>
      </tr>
      <tr>
      <td style="width:100px;font-weight:bold"></td>
        <td style="width:490px;font-weight:bold"></td>
        <td style="width: 120px;"></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>GASTOS OPERACIONALES</strong></td>
        <td style="width: 120px;"></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>De Admistraci&oacute;n</strong></td>
        <td style="width: 120px;"></td>
      </tr>
      ';

      foreach ($gastosAdmin as $i => $gastoAdm) {
        $saldo = calcularNuevoSaldo($gastoAdm['Naturaleza'],0,$gastoAdm[$campo['debe']],$gastoAdm[$campo['haber']]);
        if ($saldo != 0) {
          $contenido .= '<tr>
          <td style="width: 100px">'.$gastoAdm[$campo['codigo']].'</td>
          <td style="width:490px;">'.$gastoAdm[$campo['nombre']].'</td>
          <td style="width: 80px;text-align:right">'.number_format($saldo,2,",",".").'</td>
        </tr>';
        }

         $total_gastos_admin += $saldo;
      }

      $contenido .= '<tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>Total Gastos de administraci&oacute;n</strong></td>
        <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($total_gastos_admin,2,",",".").'</b></td>
      </tr>
      <tr>
      <td style="width:100px;"></td>
        <td style="width:490px;"></td>
        <td style="width: 120px;"></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>De Ventas</strong></td>
        <td style="width: 120px;"></td>
      </tr>';

      foreach ($gastosVentas as $i => $gastoVta) {
        $saldo = calcularNuevoSaldo($gastoVta['Naturaleza'],0,$gastoVta[$campo['debe']],$gastoVta[$campo['haber']]);
        if ($saldo != 0) {
          $contenido .= '<tr>
          <td style="width: 100px">'.$gastoVta[$campo['codigo']].'</td>
          <td style="width:490px;">'.$gastoVta[$campo['nombre']].'</td>
          <td style="width: 80px;text-align:right">'.number_format($saldo,2,",",".").'</td>
        </tr>';
        }

         $total_gastos_ventas += $saldo;
      }

      $total_gastos_operacionales = $total_gastos_admin+$total_gastos_ventas;

      $utilidad_operacional =  $utilidad_bruta_ventas - $total_gastos_operacionales;

      $contenido .= '<tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>Total Gastos de Ventas<strong></td>
        <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($total_gastos_ventas,2,",",".").'</b></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"></td>
        <td style="width: 120px;"></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>TOTAL GASTOS OPERACIONALES</strong></td>
        <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($total_gastos_operacionales,2,",",".").'</b></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"></td>
        <td style="width: 120px;"></td>
      </tr>
      <tr>
        <td style="width:100px;font-weight:bold"></td>
        <td style="width:490px;"><strong>UTILIDAD OPERACIONAL</strong></td>
        <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($utilidad_operacional,2,",",".").'</b></td>
      </tr>
      <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"></td>
        <td style="width: 120px;"></td>
      </tr>
      <tr>
        <td style="width:100px;font-weight:bold"></td>
        <td style="width:490px;"><strong>INGRESOS NO OPERACIONALES</strong></td>
        <td style="width: 120px;"></td>
      </tr>';

      foreach ($ingresosNoOper as $i => $ingresoNoOp) {
        $saldo = calcularNuevoSaldo($ingresoNoOp['Naturaleza'],0,$ingresoNoOp[$campo['debe']],$ingresoNoOp[$campo['haber']]);
        if ($saldo != 0) {
          $contenido .= '<tr>
          <td style="width: 100px">'.$ingresoNoOp[$campo['codigo']].'</td>
          <td style="width:490px;">'.$ingresoNoOp[$campo['nombre']].'</td>
          <td style="width: 80px;text-align:right">'.number_format($saldo,2,",",".").'</td>
        </tr>';
        }

         $total_no_ingresos_operacionales += $saldo;
      }

      $contenido .= '<tr>
      <td style="width:100px;"></td>
      <td style="width:490px;"><strong>Total Ingresos no Operacionales</strong></td>
      <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($total_no_ingresos_operacionales,2,",",".").'</b></td>
    </tr>
    <tr>
      <td style="width:100px;"></td>
      <td style="width:490px;"></td>
      <td style="width: 120px;"></td>
    </tr>
    <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>GASTOS NO OPERACIONALES</strong></td>
        <td style="width: 120px;"></td>
      </tr>';

      foreach ($gastosNoOper as $i => $gastoNoOp) {
        $saldo = calcularNuevoSaldo($gastoNoOp['Naturaleza'],0,$gastoNoOp[$campo['debe']],$gastoNoOp[$campo['haber']]);
        if ($saldo != 0) {
          $contenido .= '<tr>
          <td style="width: 100px">'.$gastoNoOp[$campo['codigo']].'</td>
          <td style="width:490px;">'.$gastoNoOp[$campo['nombre']].'</td>
          <td style="width: 80px;text-align:right">'.number_format($saldo,2,",",".").'</td>
        </tr>';
        }

         $total_gastos_no_operacionales += $saldo;
      }

      $utilidad_antes_impuestos = $utilidad_operacional + $total_no_ingresos_operacionales - $total_gastos_no_operacionales;

      $contenido .= '<tr>
      <td style="width:100px;"></td>
      <td style="width:490px;"><strong>Total Gastos no Operacionales</strong></td>
      <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($total_gastos_no_operacionales,2,",",".").'</b></td>
    </tr>
    <tr>
      <td style="width:100px;"></td>
      <td style="width:490px;"></td>
      <td style="width: 120px;"></td>
    </tr>
    <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>Utilidad Antes de Impuestos</strong></td>
        <td style="width: 120px;text-align:right">'.number_format($utilidad_antes_impuestos,2,",",".").'</td>
      </tr>
      <tr>
      <td style="width:100px;"></td>
      <td style="width:490px;"></td>
      <td style="width: 120px;"></td>
    </tr>';

    foreach ($impuestos as $i => $impuesto) {
      $saldo = calcularNuevoSaldo($impuesto['Naturaleza'],0,$impuesto[$campo['debe']],$impuesto[$campo['haber']]);
      if ($saldo != 0) {
        $contenido .= '<tr>
        <td style="width: 100px">'.$impuesto[$campo['codigo']].'</td>
        <td style="width:490px;">'.$impuesto[$campo['nombre']].'</td>
        <td style="width: 80px;text-align:right">'.number_format($saldo,2,",",".").'</td>
      </tr>';
      }

        $total_impuestos += $saldo;
    }

    $utilidad_del_ejercicio = $utilidad_antes_impuestos - $total_impuestos;

    $contenido .= '
    <tr>
      <td style="width:100px;"></td>
      <td style="width:490px;"><strong>Total Impuestos</strong></td>
      <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($total_impuestos,2,",",".").'</b></td>
    </tr>
    <tr>
      <td style="width:100px;"></td>
      <td style="width:490px;"></td>
      <td style="width: 120px;"></td>
    </tr>
    <tr>
        <td style="width:100px;"></td>
        <td style="width:490px;"><strong>UTILIDAD DEL EJERCICIO</strong></td>
        <td style="width: 120px;text-align:right;border-top:1px solid #000"><b>'.number_format($utilidad_del_ejercicio,2,",",".").'</b></td>
      </tr>
      <tr>
      <td style="width:100px;font-weight:bold">&nbsp;</td>
      <td style="width:490px;font-weight:bold">&nbsp;</td>
      <td style="width: 120px;">&nbsp;</td>
      </tr>
      <tr>
      <td style="width:100px;font-weight:bold">&nbsp;</td>
      <td style="width:490px;font-weight:bold">&nbsp;</td>
      <td style="width: 120px;">&nbsp;</td>
      </tr></table>';
      

      $contenido .= '
      <table style="margin-top:50px;">
      <tr>
      <td style="width:235px;font-weight:bold;text-align:center">
      <strong>_____________________________</strong> <br>
      <strong>FIRMA GERENTE</strong>
      </td>
      <td style="width:235px;font-weight:bold;text-align:center">
      <strong>_____________________________</strong> <br>
      <strong>FIRMA CONTADOR</strong>
      </td>
      <td style="width:235px;font-weight:bold;text-align:center">
      <strong>_____________________________</strong> <br>
      <strong>FIRMA REVISOR FISCAL</strong>
      </td>
      </tr>
      </table>
      ';

      echo $contenido;

?>