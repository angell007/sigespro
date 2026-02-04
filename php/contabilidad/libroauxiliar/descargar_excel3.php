<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');



require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

require $MY_CLASS . 'PHPExcel.php';
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';


include('./funciones_dev.php');
include('./querys_dev.php');

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);



ini_set('memory_limit', '2G');
require_once __DIR__ ."/../../../vendor/box/spout/src/Spout/Autoloader/autoload.php";
// echo __DIR__ ."/../../../vendor/box/spout/src/Spout/Autoloader/autoload.php";exit;



use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\Color;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;


$tipo = ( isset( $_REQUEST['Discriminado'] ) ? $_REQUEST['Discriminado'] : '' );
$fecha_inicio = ( isset( $_REQUEST['Fecha_Inicial'] ) ? $_REQUEST['Fecha_Inicial'] : '' );
$fecha_fin = ( isset( $_REQUEST['Fecha_Final'] ) ? $_REQUEST['Fecha_Final'] : '' );
$cuenta_inicial = ( isset( $_REQUEST['Cuenta_Inicial'] ) ? $_REQUEST['Cuenta_Inicial'] : '' );
$cuenta_final = ( isset( $_REQUEST['Cuenta_Final'] ) ? $_REQUEST['Cuenta_Final'] : '' );
$ultimo_dia_mes = getUltimoDiaMes($fecha_inicio);
$tipo_reporte = $_REQUEST['Tipo'] == 'General' ? 'PCGA' : 'NIIF';


/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */


$encabezado = array('Nit'=>$config['NIT']);


$fechas = array(fecha($fecha_inicio),fecha($fecha_fin));
try{

    ArmarTablaResultados($encabezado, $fechas, $tipo);
}
catch(\Throwable $e){
    echo "Error: " . $e->getMessage();
    var_dump($e);
}

function ArmarTablaResultados($encabezado, $fechas, $tipo){
    
    

    global $tipo_reporte, $fechas, $config, $cuenta_inicial, $cuenta_final;
    $objPHPExcel = new PHPExcel;


    $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12);
    $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
    $objSheet = $objPHPExcel->getActiveSheet();
    $objSheet->setTitle('Libro Auxiliar');

    $contenido_excel = '';
    $campo = getCampo();
    $conditions = strCondicions();
    
    
    switch ($tipo) {
        case 'Cuenta':
            
            $query = queryByCuenta($conditions,true);
            $nuevo_saldo_anterior = 'init';
            $total_debe = 0;
            $total_haber = 0;
    
            
            //$query = queryByCuenta($conditions, true);
            
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $cuentas = $oCon->getData();
            // $cuentas=[];
            unset($oCon);

           

            $row =1;
            $col = "A"; /** Uso de la Variable col debido a cambios dinamicos del archivo */
            $col2='N';
            $objSheet->mergeCells("$col$row:$col2$row");
            $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
            $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');$row++;
            
            $objSheet->mergeCells("$col$row:$col2$row");
            $objSheet->getCell($col . $row)->setValue("MOVIMIENTO AUXILIAR POR CUENTA CONTABLE");
            $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
            $objSheet->getStyle("$col$row:$col2$row")->getFont()->setSize(30);
            $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');$row++;
            
            $objSheet->mergeCells("$col$row:$col2$row");
            $objSheet->getCell($col . $row)->setValue("$config[Nombre_Empresa]");
            $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
            $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');$row++;
            
            $nit = str_replace(['.'], '', $config['NIT']);
            $objSheet->mergeCells("$col$row:$col2$row");
            $objSheet->getCell($col . $row)->setValue("$nit");
            $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
            $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');$row++;

            
            $objSheet->mergeCells("$col$row:$col2$row");

            $objSheet->getStyle($col."1:$col2$row")->getFont()->getColor()->setARGB('FFFFFFFF');
           
            if (count($cuentas) > 0) {
                
                $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
                $campo_nombre = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'General' ? 'Nombre' : 'Nombre_Niif';

                $query_movimientos = queryMovimientosCuenta($cuenta_inicial, $cuenta_final);
                $oCon = new consulta();
                $oCon->setQuery($query_movimientos);
                $oCon->setTipo('Multiple');
                $movimientos = $oCon->getData();
                unset($oCon);

                
                $objSheet->getCell($col . $row)->setValue("Código contable");$col++;
                $objSheet->getCell($col . $row)->setValue("Cuenta contable");$col++;
                $objSheet->getCell($col . $row)->setValue("Comprobante");$col++;
                $objSheet->getCell($col . $row)->setValue("Fecha Elaboracion");$col++;
                $objSheet->getCell($col . $row)->setValue("Identificacion");$col++;
                $objSheet->getCell($col . $row)->setValue("Nombre del tercero");$col++;
                $objSheet->getCell($col . $row)->setValue("Descripción");$col++;
                $objSheet->getCell($col . $row)->setValue("Detalle");$col++;
                $objSheet->getCell($col . $row)->setValue("Centro de costo");$col++;
                $objSheet->getCell($col . $row)->setValue("Saldo inicial");$col++;
                $objSheet->getCell($col . $row)->setValue("Débito");$col++;
                $objSheet->getCell($col . $row)->setValue("Crédito");$col++;
                $objSheet->getCell($col . $row)->setValue("Saldo Movimiento");$col++;
                $objSheet->getCell($col . $row)->setValue("Saldo total cuenta");$col++;
                $col='A';
                
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $row++;

                $total=array("Debito"=>0, "Credito"=>0);

                foreach($cuentas as $j => $cuenta){
                    $col_r= $tipo_reporte =='PCGA' ? '': '_Niif';

                    $col = 'A';
                    $row++;
                    $objSheet->getCell($col . $row)->setValue("Cuenta contable: $cuenta[$campo_codigo]  $cuenta[$campo_nombre]");
                    $col= chr(ord($col) + 9);

                    $deb=0;
                    $cred=0;

                    $movimientos_cuenta = array_filter($movimientos, function($registro)use($cuenta, $campo_codigo){
                        return $registro[$campo_codigo] == $cuenta[$campo_codigo];
                    });
                    $debito=0;
                    $credito=0;

                    $deb = array_reduce($movimientos_cuenta, function ($acarreo, $numero)use($col_r) {
                        return $acarreo + $numero["Debito$col_r"];
                    }, $debito);

                    $cred = array_reduce($movimientos_cuenta, function ($acarreo, $numero)use($col_r) {
                        return $acarreo + $numero["Credito$col_r"];
                    }, $credito);

                    $total['Debito']+=$deb;
                    $total['Credito']+=$cred;


                    $saldo_anterior= obtenerSaldoAnterior($cuenta['Naturaleza'],$cuentas, $j); // El ultimo parametro true significa que es para calcular el saldo anterior de un nit.
                    
                    echo $saldo_anterior; exit;
                    $nuevo_saldo = calcularNuevoSaldo($cuenta['Naturaleza'],$saldo_anterior, $deb, $cred);

                    $objSheet->getCell($col . $row)->setValue(number_format($saldo_anterior, 2, ".", " "));$col++;
                    $objSheet->getCell($col . $row)->setValue(number_format($deb, 2, ".", " "));$col++;
                    $objSheet->getCell($col . $row)->setValue(number_format($cred, 2, ".", " "));$col++;
                    $col++;
                    $objSheet->getCell($col . $row)->setValue(number_format($nuevo_saldo, 2, ".", " "));$col++;
                    $objSheet->getStyle("A$row:$col2$row")->getFont()->setBold(true);
                    
                    $s=$saldo_anterior;
                    
                    foreach($movimientos_cuenta as $mov){
                        
                        $s += ($mov["Debito$col"]-$mov["Credito$col"]);
                        $col ='A';
                        $row++;
                        $objSheet->getCell($col . $row)->setValue($mov[$campo_codigo]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov[$campo_nombre]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov["Numero_Comprobante"]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov["Fecha"]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov["Nit"]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov["Nombre_Nit"]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov["Concepto"]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov["Documento"]);$col++;
                        $objSheet->getCell($col . $row)->setValue($mov["Centro_Costo"]);$col++;
                        $objSheet->getCell($col . $row)->setValue("");$col++;
                        $objSheet->getCell($col . $row)->setValue(number_format($mov["Debe$col_r"], 2, ".", " "));$col++;
                        $objSheet->getCell($col . $row)->setValue(number_format($mov["Haber$col_r"], 2, ".", " "));$col++;
                        $objSheet->getCell($col . $row)->setValue(number_format($s, 2, ".", " "));$col++;
                        
                    }
                    
                }
                $col= "A";
                $objSheet->getCell($col . $row)->setValue("Total general");
                $col= chr(ord($col) + 10);
                $objSheet->getCell($col . $row)->setValue(number_format($total['Debito'],2,".", " "));$col++;
                $objSheet->getCell($col . $row)->setValue(number_format($total['Credito'],2,".", " "));$col++;
                $objSheet->getStyle("A$row:$col2$row")->getFont()->setBold(true);
                
            }else{

                
                // echo count($cuentas); exit; 
                $row++;
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getCell($col . $row)->setValue("NO HAY DATOS PARA MOSTRAR");
            }
        
               
        
            
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
	    
	           
	    
    }

    
    try {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="libro auxiliar.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    } catch (\Throwable $th) {
        echo $th->getMessage();
    }
}

?>