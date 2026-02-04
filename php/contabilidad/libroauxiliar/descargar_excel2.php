<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/vnd.ms-excel');
// header('Content-Disposition: attachment;filename="Libro_auxiliar_cuenta.xls"');
// header('Cache-Control: max-age=0'); 

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

include('./funciones.php');

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

$tipo = ( isset( $_REQUEST['Discriminado'] ) ? $_REQUEST['Discriminado'] : '' );
$fecha_inicio = ( isset( $_REQUEST['Fecha_Inicial'] ) ? $_REQUEST['Fecha_Inicial'] : '' );
$fecha_fin = ( isset( $_REQUEST['Fecha_Final'] ) ? $_REQUEST['Fecha_Final'] : '' );
$cuenta_inicial = ( isset( $_REQUEST['Cuenta_Inicial'] ) ? $_REQUEST['Cuenta_Inicial'] : '' );
$cuenta_final = ( isset( $_REQUEST['Cuenta_Final'] ) ? $_REQUEST['Cuenta_Final'] : '' );
$ultimo_dia_mes = getUltimoDiaMes($fecha_inicio);
$tipo_reporte = $_REQUEST['Tipo'] == 'General' ? 'PCGA' : 'NIIF';

include('./querys.php');

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */


$encabezado = array('Nit'=>$config['NIT']);


$fechas = array(fecha($fecha_inicio),fecha($fecha_fin));

ArmarTablaResultados($encabezado, $fechas, $tipo);

function ArmarTablaResultados($encabezado, $fechas, $tipo){

    global $tipo_reporte;
    
    $id = uniqid();
    $archivo = "./reportes/$id.xls";
    unlink($archivo);
    $handle = fopen($archivo, 'w+');

    $contenido_excel = '';
    $campo = getCampo();
    $conditions = strCondicions();

    
    switch ($tipo) {
        case 'Cuenta':
    
            //agrego true 2020-10-28
            //SOLICITADO POR Yudy, No ERA IGUAL AL PDF
            $query = queryByCuenta($conditions,true);
           
            $nuevo_saldo_anterior = 'init';
            $total_debe = 0;
            $total_haber = 0;
    
            
            //$query = queryByCuenta($conditions, true);
            
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $cuentas = $oCon->getData();
            unset($oCon);
           
    
            foreach ($cuentas as $i => $cuenta) {
                if($cuenta['Movimientos']>0){}
                # //agrego true 2020-10-28 Id_Plan_CuentaS
                #$query = queryMovimientosCuenta($cuenta['Id_Plan_Cuentas']);
                $query = queryMovimientosCuenta($cuenta['Id_Plan_Cuenta']);
                
                #echo $query;exit;
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->setTipo('Multiple');
                $movimientos = $oCon->getData();
                unset($oCon);
          
                $cuentas[$i]['Movimientos'] = $movimientos;
              }
            $contenido_excel = '
            <table border=1>
                <tr>
                    <td colspan="15" align="center"><strong>PRODUCTOS HOSPITALARIOS S.A.</strong></td>
                </tr>
                <tr>
                    <td colspan="15" align="center"><strong>Nit: '.$encabezado["Nit"].'</strong></td>
                </tr>
                <tr>
                    <td colspan="15" align="center"><strong>Libro Auxiliar por Cta - '.$tipo_reporte.'</strong></td>
                </tr>
                <tr>
                    <td colspan="15" align="center"><strong>Desde: '.$fechas[0].' - Hasta: '.$fechas[1].'</strong></td>
                </tr>
                <tr>
                    <td colspan="15" align="center"><strong>MOVIMIENTOS</strong></td>
                </tr>
                <tr>
                    <td align="center"><strong>Cuenta</strong></td>
                    <td align="center"><strong>Nombre Cuenta</strong></td>
                    <td align="center"><strong>Numero Comprobante</strong></td>
                    <td align="center"><strong>Nit</strong></td>
                    <td align="center"><strong>Nombre Nit</strong></td>
                    <td align="center"><strong>Fecha Movimiento</strong></td>
                    <td align="center"><strong>Concepto</strong></td>
                    <td align="center"><strong>Factura</strong></td>
                    <td align="center"><strong>Debito</strong></td>
                    <td align="center"><strong>Credito</strong></td>
                    <td align="center"><strong>Saldo</strong></td>
                </tr>';
        
            if (count($cuentas) > 0) {
                foreach ($cuentas as $i => $cuenta) {
                  if (count($cuenta['Movimientos']) > 0) {

                    $saldo_anterior= obtenerSaldoAnterior($cuenta['Naturaleza'],$cuentas, $i);
                    
                    fwrite($handle, $contenido_excel);
                    $contenido_excel = '';
                    $contenido_excel .= '
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>'.$cuenta[$campo['codigo']].'</td>
                            <td colspan="6">'.$cuenta[$campo['nombre']].'</td>
                            <td>Saldo Anterior:</td>
                            <td colspan="2"></td>
                            <td>'.number_format($saldo_anterior,2,",","").'</td>
                        </tr>
                    ';
    
                    foreach ($cuenta['Movimientos'] as $value) {
                        // echo $value["Numero_Comprobante"];
                        $debe = $value[$campo['debe']];
                        $haber = $value[$campo['haber']];
                        $nuevo_saldo = $nuevo_saldo_anterior === 'init' ? calcularNuevoSaldo($cuenta['Naturaleza'],$saldo_anterior,$debe,$haber) : calcularNuevoSaldo($cuenta['Naturaleza'],$nuevo_saldo_anterior,$debe,$haber);
                        
                        $contenido_excel .= '
                        <tr>
                            <td>'.$cuenta[$campo['codigo']].'</td>
                            <td>'.$cuenta[$campo['nombre']].'</td>
                            <td>'.$value["Numero_Comprobante"].'</td>
                            <td>'.$value["Nit"].'</td>
                            <td>'.$value["Nombre_Nit"].'</td>
                            <td>'.$value["Fecha"].'</td>
                            <td>'.$value["Concepto"].'</td>
                            <td>'.$value["Documento"].'</td>
                            <td>'.number_format($debe, 2, ",", ".").'</td>
                            <td>'.number_format($haber, 2, ",", ".").'</td>
                            <td>'.number_format($nuevo_saldo, 2, ",", ".").'</td>
                        </tr>';
    
                        $nuevo_saldo_anterior = $nuevo_saldo;
                        $total_debe += $debe;
                        $total_haber += $haber;
                    }
    
                    $total_debe = 0;
                    $total_haber = 0;
                    $nuevo_saldo_anterior = 'init';
                } 
                } 
            }else{
        
                $contenido_excel = '
                <tr>
                    <td colspan="11" align="center">SIN RESULTADOS PARA MOSTRAR</td>
                </tr>';
            }
        
               
        
            $contenido_excel .= '
            </table>';
            fwrite($handle, $contenido_excel);
                break;
            
        case 'Nit':
            $query = queryByCuentaToNit($conditions);

            $nuevo_saldo_anterior = 'init';
            $total_debe =0;
            $total_haber = 0;
    
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $cuentas = $oCon->getData();
            unset($oCon);
    
            if ($cuentas) {
                $cuentas = armarDatosNit($cuentas);
            } else {
                $query = queryByCuentaToNit($conditions, true);
            
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->setTipo('Multiple');
                $cuentas = $oCon->getData();
                unset($oCon);
        
                $cuentas = armarDatosNit($cuentas);
            }

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
	                <td align="center"><strong>Cuenta</strong></td>
	                <td align="center"><strong>Nombre Cuenta</strong></td>
	                <td align="center"><strong>Nit</strong></td>
	                <td align="center"><strong>Nombre</strong></td>
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
	                
	                fwrite($handle, $contenido_excel);
	               //echo $contenido_excel;
	                $contenido_excel='';
	                $contenido_excel .= '
	                    <tr>
	                        <td align="center"><strong>'.$cuenta[$campo['codigo']].'</strong></td>
	                        <td align="left" colspan=6><strong>'.$cuenta[$campo['nombre']].'</strong></td>
	                    </tr>
	                ';

	                foreach ($cuenta['Nits'] as $j => $nit) {
                        if(count($nit['Movimientos'])>0){
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
		                  //  echo $value["Numero_Comprobante"];

                            $debe = $value[$campo['debe']];
                            $haber = $value[$campo['haber']];
                            $nuevo_saldo = $nuevo_saldo_anterior === 'init' ? calcularNuevoSaldo($cuenta['Naturaleza'],$saldo_anterior,$debe,$haber) : calcularNuevoSaldo($cuenta['Naturaleza'],$nuevo_saldo_anterior,$debe,$haber);

		                    $contenido_excel .= '
			                    <tr>
			                        <td align="center">'.$cuenta[$campo['codigo']].'</td>
			                        <td align="center">'.$cuenta[$campo['nombre']].'</td>
			                        <td align="center">'.$nit["Nit"].'</td>
			                        <td align="center">'.$nit["Nombre_Nit"].'</td>
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
	            }
	        }else{
	    
	            $contenido_excel = '
	            <tr>
	                <td colspan="7" align="center">SIN RESULTADOS PARA MOSTRAR</td>
	            </tr>';
	        }
	    
	           
	    
	        $contenido_excel .= '</table>';
	        
	        fwrite($handle, $contenido_excel);
            break;
    }


// echo "\n\n\n <a href='$archivo'>Descargar</a>"; 

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Libro_auxiliar_cuenta.xlsx"');
header('Cache-Control: max-age=0'); 
readfile($archivo);
unlink($archivo);

// exit;
}

?>