<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Libro_auxiliar_cuenta.xls"');
header('Cache-Control: max-age=0'); 

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


$encabezado = array('Nit'=>$config['NIT']);


$fechas = array(fecha($fecha_inicio),fecha($fecha_fin));

ArmarTablaResultados($encabezado, $fechas);

function ArmarTablaResultados($encabezado, $fechas){

    global $tipo_reporte;

    $contenido_excel = '';
    $conditions = strCondicions('Acreedor');

    $campo = getCampo();

    $query = queryByCuentaToNit($conditions);

            $nuevo_saldo_anterior = 'init';
            $total_debe =0;
            $total_haber = 0;
    
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $cuentas = $oCon->getData();
            unset($oCon);
    
            $cuentas = armarDatosNit($cuentas);

	        $contenido_excel = '
	        <table border=1>
	            <tr>
	                <td colspan="7" align="center"><strong>PRODUCTOS HOSPITALARIOS S.A.</strong></td>
	            </tr>
	            <tr>
	                <td colspan="7" align="center"><strong>Nit: '.$encabezado["Nit"].'</strong></td>
	            </tr>
	            <tr>
	                <td colspan="7" align="center"><strong>Libro Auxiliar por Nit - '.$tipo_reporte.'</strong></td>
	            </tr>
	            <tr>
	                <td colspan="7" align="center"><strong>Desde: '.$fechas[0].' - Hasta: '.$fechas[1].'</strong></td>
	            </tr>
	            <tr>
	                <td colspan="7" align="center"><strong>MOVIMIENTOS</strong></td>
	            </tr>
	            <tr>
	                <td align="center"><strong>Numero</strong></td>
	                <td align="center"><strong>Fecha</strong></td>
	                <td align="center"><strong>Concepto</strong></td>
	                <td align="center"><strong>Factura</strong></td>
	                <td align="center"><strong>Debito</strong></td>
	                <td align="center"><strong>Credito</strong></td>
	                <td align="center"><strong>Saldo</strong></td>
	            </tr>';
	    
	        if (count($cuentas) > 0) {
	            foreach ($cuentas as $i => $cuenta) {

	                $contenido_excel .= '
	                    <tr>
	                        <td align="center"><strong>'.$cuenta[$campo['codigo']].'</strong></td>
	                        <td align="left" colspan=6><strong>'.$cuenta[$campo['nombre']].'</strong></td>
	                    </tr>
	                ';

	                foreach ($cuenta['Nits'] as $j => $nit) {
                        $saldo_anterior= obtenerSaldoAnterior($cuenta['Naturaleza'],$cuenta['Nits'], $j, true); // El ultimo parametro true significa que es para calcular el saldo anterior de un nit.
	                    $contenido_excel .= '
		                    <tr>
		                        <td><strong>'.$nit["Nit"].'</strong></td>
		                        <td colspan=3 bgcolor="#C6C6C6"><strong>'.$nit["Nombre_Nit"].'</strong></td>
		                        <td><strong>Saldo Anterior: </td>
		                        <td colspan=2 style="text-align:right">'.number_format($saldo_anterior,2,",","").'</td>
		                    </tr>
		                ';

		                foreach ($nit['Movimientos'] as $value) {

                            $debe = $value[$campo['debe']];
                            $haber = $value[$campo['haber']];
                            $nuevo_saldo = $nuevo_saldo_anterior === 'init' ? calcularNuevoSaldo($cuenta['Naturaleza'],$saldo_anterior,$debe,$haber) : calcularNuevoSaldo($cuenta['Naturaleza'],$nuevo_saldo_anterior,$debe,$haber);

		                    $contenido_excel .= '
			                    <tr>
			                        <td align="center">'.$value["Numero_Comprobante"].'</td>
			                        <td align="center">'.$value["Fecha"].'</td>
			                        <td align="center">'.$value["Concepto"].'</td>
			                        <td align="center">'.$value["Documento"].'</td>
			                        <td align="right">'.number_format($debe,2,",","").'</td>
			                        <td align="right">'.number_format($haber,2,",","").'</td>
			                        <td align="right">'.number_format($nuevo_saldo,2,",","").'</td>
			                    </tr>
                            ';
                            
                            $nuevo_saldo_anterior = $nuevo_saldo;

                        }
                        $total_debe = 0;
                        $total_haber = 0;
                        $nuevo_saldo_anterior = 'init';
	                }
	            }
	        }else{
	    
	            $contenido_excel .= '
	            <tr>
	                <td colspan="7" align="center">SIN RESULTADOS PARA MOSTRAR</td>
	            </tr>';
	        }
	    
	           
	    
	        $contenido_excel .= '
	        </table>';
    

    echo $contenido_excel;
}

?>