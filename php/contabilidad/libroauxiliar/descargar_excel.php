<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
// header('Content-Type: application/vnd.ms-excel');
// header('Content-Disposition: attachment;filename="Libro_auxiliar_cuenta.xls"');
// header('Cache-Control: max-age=0'); 

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

require $MY_CLASS . 'PHPExcel.php';
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';






ini_set("memory_limit","64000M");
ini_set('max_execution_time', 0);

$tipo = ( isset( $_REQUEST['Discriminado'] ) ? $_REQUEST['Discriminado'] : '' );
$fecha_inicio = ( isset( $_REQUEST['Fecha_Inicial'] ) ? $_REQUEST['Fecha_Inicial'] : '' );
$fecha_fin = ( isset( $_REQUEST['Fecha_Final'] ) ? $_REQUEST['Fecha_Final'] : '' );
$cuenta_inicial = ( isset( $_REQUEST['Cuenta_Inicial'] ) ? $_REQUEST['Cuenta_Inicial'] : '' );
$cuenta_final = ( isset( $_REQUEST['Cuenta_Final'] ) ? $_REQUEST['Cuenta_Final'] : '' );

$tipo_reporte = $_REQUEST['Tipo'] == 'General' ? 'PCGA' : 'NIIF';


/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */


$encabezado = array('Nit'=>$config['NIT']);


$fechas = array( date('d/m/Y', strtotime($fecha_inicio)), date('d/m/Y',strtotime($fecha_fin)));

try{

    ArmarTablaResultados($encabezado, $fechas, $tipo);
}
catch(\Throwable $e){
    echo "Error: " . $e->getMessage();
    var_dump($e);
}

function ArmarTablaResultados($encabezado, $fechas, $tipo){
    

    global $tipo_reporte, $fechas, $config, $cuenta_inicial, $cuenta_final;

    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12);
    $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
    $objSheet = $objPHPExcel->getActiveSheet();
    $objSheet->setTitle('Libro Auxiliar');
    
    $contenido_excel = '';


    
    switch ($tipo) {
        case 'Cuenta':
            include('./funciones_dev.php');
            include('./querys_dev.php');
            $ultimo_dia_mes = getUltimoDiaMes($fecha_inicio);
            $campo = getCampo();
            $conditions = strCondicions();
            
                
        
        

    
            //agrego true 2020-10-28
            //SOLICITADO POR Yudy, No ERA IGUAL AL PDF
            $query = queryByCuenta($conditions,true);
            
            $nuevo_saldo_anterior = 'init';
            $total_debe = 0;
            $total_haber = 0;
            
            //$query = queryByCuenta($conditions, true);
            
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Simple');
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
            $objSheet->getCell($col . $row)->setValue("MOVIMIENTO AUXILIAR POR CUENTA CONTABLE- $tipo_reporte");
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
            $objSheet->getCell($col . $row)->setValue("Desde: $fechas[0] - Hasta: $fechas[1]");
            $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
            $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');$row++;
            
            
            $objSheet->mergeCells("$col$row:$col2$row");
            
            $objSheet->getStyle($col."1:$col2$row")->getFont()->getColor()->setARGB('FFFFFFFF');
            
            if (count($cuentas) > 0) {
                
                $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
                $campo_nombre = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'General' ? 'Nombre' : 'Nombre_Niif';
                
                 foreach ($cuentas as $i => $cuenta) {
                $query_movimientos = queryMovimientosCuenta($cuenta['Id_Plan_Cuenta']);
                $oCon = new consulta();
                $oCon->setQuery($query_movimientos);
                $oCon->setTipo('Multiple');
                $movimientos = $oCon->getData();
                unset($oCon);
                
                // Asignar los movimientos a la cuenta actual
                $cuentas[$i]['Movimientos'] = $movimientos;
                
                // Calcular el saldo anterior para poder filtrar las cuentas sin movimientos o con saldo cero
        $debito = array_reduce($movimientos, function ($acarreo, $mov) {
            return $acarreo + $mov['Debe'];
        }, 0);

        $credito = array_reduce($movimientos, function ($acarreo, $mov) {
            return $acarreo + $mov['Haber'];
        }, 0);

        $saldo_anterior = obtenerSaldoAnterior($cuenta['Naturaleza'], $cuentas, $i);

        // Filtro: eliminar las cuentas que no tengan movimientos y cuyo saldo anterior sea cero
        if (empty($movimientos) && $saldo_anterior == 0) {
            unset($cuentas[$i]);
        }
                }
                $row++;
                
                
                $objSheet->getCell($col . $row)->setValue("Codigo contable");$col++;
                $objSheet->getCell($col . $row)->setValue("Cuenta contable");$col++;
                $objSheet->getCell($col . $row)->setValue("Comprobante");$col++;
                $objSheet->getCell($col . $row)->setValue("Fecha Elaboracion");$col++;
                $objSheet->getCell($col . $row)->setValue("Identificacion");$col++;
                $objSheet->getCell($col . $row)->setValue("Nombre del tercero");$col++;
                $objSheet->getCell($col . $row)->setValue("Descripcion");$col++;
                $objSheet->getCell($col . $row)->setValue("Detalle");$col++;
                $objSheet->getCell($col . $row)->setValue("Centro de costo");$col++;
                $objSheet->getCell($col . $row)->setValue("Saldo inicial");$col++;
                $objSheet->getCell($col . $row)->setValue("Debito");$col++;
                $objSheet->getCell($col . $row)->setValue("Credito");$col++;
                $objSheet->getCell($col . $row)->setValue("Saldo Movimiento");$col++;
                $objSheet->getCell($col . $row)->setValue("Saldo total cuenta");$col++;
                $col='A';

                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setWrapText(true);
                
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('fff1f4f9');
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
                        return $acarreo + $numero["Debe$col_r"];
                    }, $debito);

                    $cred = array_reduce($movimientos_cuenta, function ($acarreo, $numero)use($col_r) {
                        return $acarreo + $numero["Haber$col_r"];
                    }, $credito);

                    $total['Debito']+=$deb;
                    $total['Credito']+=$cred;
                    $naturaleza = $cuenta['Naturaleza'];

                    
                    
                    $saldo_anterior= obtenerSaldoAnterior($cuenta['Naturaleza'],$cuentas, $j); // El ultimo parametro true significa que es para calcular el saldo anterior de un nit.
                    /*
                    $tipo_reporte = $_REQUEST['Tipo'] == 'General' ? 'PCGA' : 'Niif';
                    
                    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
                        $saldo_anterior = $cuenta["Debito_$tipo_reporte"] - $cuenta["Credito_$tipo_reporte"];
                    } else {
                        $saldo_anterior = $cuenta["Credito_$tipo_reporte"] - $cuenta["Debito_$tipo_reporte"];
                    }
                    */
                    
                    $nuevo_saldo = calcularNuevoSaldo($cuenta['Naturaleza'],$saldo_anterior, $deb, $cred);
                    $objSheet->getCell($col . $row)->setValue(number_format($saldo_anterior, 2, ".", "")) ;$col++;
                    $objSheet->getCell($col . $row)->setValue(number_format($deb, 2, ".", ""));$col++;
                    $objSheet->getCell($col . $row)->setValue(number_format($cred, 2, ".", ""));$col++;
                    $col++;
                    $objSheet->getCell($col . $row)->setValue(number_format($nuevo_saldo, 2, ".", ""));$col++;
                    $objSheet->getStyle("A$row:$col2$row")->getFont()->setBold(true);
                    
                    $s=$saldo_anterior;
                   
                    foreach($cuenta['Movimientos'] as $mov){
                        //$s += ($mov["Debe$col_r"]-$mov["Haber$col_r"]);
                        
                        
                         $debe = $mov["Debe$col_r"];
                        $haber = $mov["Haber$col_r"];
                         $nuevo_saldo = $nuevo_saldo_anterior === 'init' ? calcularNuevoSaldo($cuenta['Naturaleza'], $saldo_anterior, $debe, $haber) : calcularNuevoSaldo($cuenta['Naturaleza'], $nuevo_saldo_anterior, $debe, $haber);
                        //var_dump($nuevo_saldo);
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
                        $objSheet->getCell($col . $row)->setValue(number_format($mov["Debe$col_r"], 2, ".", ""));$col++;
                        $objSheet->getCell($col . $row)->setValue(number_format($mov["Haber$col_r"], 2, ".", ""));$col++;
                        $objSheet->getCell($col . $row)->setValue(number_format($nuevo_saldo, 2, ".", ""));$col++;
                        $objSheet->getRowDimension($row)->setOutlineLevel(1);
                        
                        $nuevo_saldo_anterior = $nuevo_saldo;
                        $total_debe += $debe;
                        $total_haber += $haber;
                        
                    }
                    
                    $total_debe = 0;
                    $total_haber = 0;
                    $nuevo_saldo_anterior = 'init';
                    
                    
                }
                
                $row++;
                $row++;
                $col= "A";
                $objSheet->getCell($col . $row)->setValue("Total general");
                $col= chr(ord($col) + 10);
                $objSheet->getCell($col . $row)->setValue(number_format($total['Debito'],2,".", ""));$col++;
                $objSheet->getCell($col . $row)->setValue(number_format($total['Credito'],2,".", ""));$col++;
                $columns = range($col, $col2);
                foreach($columns as $c){
                    $objSheet->getColumnDimension($c)->setAutoSize(true);
                }
                $objSheet->getColumnDimension("B")->setWidth(40);
                $objSheet->getColumnDimension("C")->setWidth(20);
                $objSheet->getColumnDimension("F")->setWidth(60);
                $objSheet->getColumnDimension("G")->setWidth(40);
                $objSheet->getColumnDimension("H")->setWidth(20);
                $objSheet->getColumnDimension("J")->setWidth(15);
                $objSheet->getColumnDimension("K")->setWidth(15);
                $objSheet->getColumnDimension("L")->setWidth(15);
                $objSheet->getColumnDimension("M")->setWidth(15);
                $objSheet->getColumnDimension("N")->setWidth(15);
                $objSheet->getStyle("J8:$col2$row")->getNumberFormat()->setFormatCode('#,##0.00');

                
            }else{

                $row++;
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getCell($col . $row)->setValue("NO HAY DATOS PARA MOSTRAR");
            }
        
               
            try {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="libro auxiliar cuenta.xlsx"');
                header('Cache-Control: max-age=0');
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                $objWriter->save('php://output');
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
            
        case 'Nit':
                include('./funciones.php');
                include('./querys.php');
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
                
            
                $row = 1;
                $col = "A"; /** Uso de la Variable col debido a cambios din谩micos del archivo */
                $col2 = chr(ord($col) + 10);
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');
                $row++;
            
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getCell($col . $row)->setValue("MOVIMIENTO AUXILIAR POR NIT - $tipo_reporte");
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setSize(30);
                $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');
                $row++;
            
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getCell($col . $row)->setValue("$config[Nombre_Empresa]");
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');
                $row++;
            
                $nit = str_replace(['.'], '', $config['NIT']);
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getCell($col . $row)->setValue("$nit");
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');
                $row++;
            
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getCell($col . $row)->setValue("Desde: $fechas[0] - Hasta: $fechas[1]");
                $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ff00aaff');
                $row++;
                $objSheet->mergeCells("$col$row:$col2$row");
                $objSheet->getStyle($col . "1:$col2$row")->getFont()->getColor()->setARGB('FFFFFFFF');
                $row++;
                $col = 'A';
            
                if (count($cuentas) > 0) {
                    $objSheet->getCell($col . $row)->setValue("Cuenta");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Nombre Cuenta");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Nit");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Nombre");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Numero");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Fecha");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Concepto");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Factura");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Debito");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Credito");
                    $col++;
                    $objSheet->getCell($col . $row)->setValue("Saldo");
                    $col++;
            
                    $col = 'A';
                    $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setWrapText(true);
                    $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                    $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objSheet->getStyle("$col$row:$col2$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('fff1f4f9');
                    $total = array("Debito" => 0, "Credito" => 0);
            
                    foreach ($cuentas as $i => $cuenta) {
                        $col = "A";
                        $row++;
            
                        $objSheet->getCell($col . $row)->setValue($cuenta[$campo['codigo']]);
                        $col++;
                        $objSheet->getCell($col . $row)->setValue($cuenta[$campo['nombre']]);
                        $col++;
            
                        $objSheet->getStyle("A$row:$col2$row")->getFont()->setBold(true);
            
                        foreach ($cuenta['Nits'] as $j => $nit) {
                            $saldo_anterior = obtenerSaldoAnterior($cuenta['Naturaleza'], $cuenta['Nits'], $j, true); // Obtener el saldo anterior
                            
                            $tiene_movimientos = false; // Variable para verificar si hay movimientos
                            $nuevo_saldo = 0; // Inicializar el saldo actual
                            $nuevo_saldo_anterior = 'init';
                            foreach ($nit['Movimientos'] as $value) {
                                $debe = $value[$campo['debe']];
                                $haber = $value[$campo['haber']];
                                $nuevo_saldo = calcularNuevoSaldo($cuenta['Naturaleza'], $nuevo_saldo_anterior === 'init' ? $saldo_anterior : $nuevo_saldo_anterior, $debe, $haber);
                                
                                // Si hay movimientos, marcamos la variable como verdadera
                                if ($debe != 0 || $haber != 0) {
                                    $tiene_movimientos = true;
                                }
                            }
            
                            // Condici贸n para imprimir si el saldo anterior no es 0 o si hay movimientos
                            if ($saldo_anterior != 0 || $tiene_movimientos) {
                                $row++;
                                $col = "A";
            
                                $objSheet->getCell($col . $row)->setValue($nit["Nit"]);
                                $col++;
                                $objSheet->mergeCells("$col$row:" . chr(ord($col) + 2) . "$row");
                                $objSheet->getCell($col . $row)->setValue($nit["Nombre_Nit"]);
                                $objSheet->getStyle("$col$row")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('ffc6c6c6');
                                $col = chr(ord($col) + 3);
                                $objSheet->getCell($col . $row)->setValue("Saldo anterior:");
                                $col++;
                                $objSheet->mergeCells("$col$row:" . chr(ord($col) + 1) . "$row");
                                $objSheet->getCell($col . $row)->setValue(number_format($saldo_anterior, 2, ".", ""));
                                $objSheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                                $objSheet->getStyle("$col$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                                $col = chr(ord($col) + 2);
                                $objSheet->getRowDimension($row)->setOutlineLevel(1);
                                $objSheet->getStyle("A$row:$col2$row")->getFont()->setBold(true);
            
                                // Procesar movimientos
                                foreach ($nit['Movimientos'] as $value) {
                                    $row++;
                                    $col = "A";
            
                                    $debe = $value[$campo['debe']];
                                    $haber = $value[$campo['haber']];
                                    $nuevo_saldo = calcularNuevoSaldo($cuenta['Naturaleza'], $nuevo_saldo_anterior === 'init' ? $saldo_anterior : $nuevo_saldo_anterior, $debe, $haber);

                                    $objSheet->getCell($col . $row)->setValue($cuenta[$campo['codigo']]);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue($cuenta[$campo['nombre']]);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue($nit["Nit"]);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue($nit["Nombre_Nit"]);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue($value["Numero_Comprobante"]);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue($value["Fecha"]);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue($value["Concepto"]);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue($value["Documento"]);
                                    $objSheet->getStyle("A$row:$col$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue(number_format($debe, 2, ".", ""));
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue(number_format($haber, 2, ".", ""));
                                    $col++;
                                    $objSheet->getCell($col . $row)->setValue(number_format($nuevo_saldo, 2, ".", ""));
                                    $col++;
                                    $objSheet->getRowDimension($row)->setOutlineLevel(2);
                                    
                                    $total_debe += $debe;
                                    $total_haber += $haber;
                                    $nuevo_saldo_anterior = $nuevo_saldo;
                                }
                                $nuevo_saldo = $total_debe == 0 && $total_haber == 0 && $saldo_anterior != 0 ? $saldo_anterior : $nuevo_saldo;
                                $total_debe = 0;
                                $total_haber = 0;
                                $nuevo_saldo_anterior = 'init';
                            }
                        }
                        
                    }
                   
                    $col = "A";
                    $objSheet->getColumnDimension("A")->setWidth(15);
                    $objSheet->getColumnDimension("B")->setWidth(40);
                    $objSheet->getColumnDimension("C")->setWidth(15);
                    $objSheet->getColumnDimension("D")->setWidth(50);
                    $objSheet->getColumnDimension("E")->setWidth(20);
                    $objSheet->getColumnDimension("F")->setWidth(15);
                    $objSheet->getColumnDimension("G")->setWidth(45);
                    $objSheet->getColumnDimension("H")->setWidth(20);
                    $objSheet->getColumnDimension("I")->setWidth(20);
                    $objSheet->getColumnDimension("J")->setWidth(20);
                    $objSheet->getColumnDimension("K")->setWidth(20);
                    $objSheet->getStyle("I8:$col2$row")->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    
                    
            
                }else{
            
                    $row++;
                    $objSheet->mergeCells("$col$row:$col2$row");
                    $objSheet->getStyle("$col$row:$col2$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objSheet->getStyle("$col$row:$col2$row")->getFont()->setBold(true);
                    $objSheet->getCell($col . $row)->setValue("NO HAY DATOS PARA MOSTRAR");
                }
            
                   
                try {
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="libro auxiliar nits.xlsx"');
                    header('Cache-Control: max-age=0');
                    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                    $objWriter->save('php://output');
                } catch (\Throwable $th) {
                    echo $th->getMessage();
                }       
	    
    }

    

}

?>