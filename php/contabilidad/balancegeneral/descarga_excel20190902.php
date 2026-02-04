<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

$tipo_reporte = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$nivel_reporte = ( isset( $_REQUEST['nivel'] ) ? $_REQUEST['nivel'] : '' );
$fecha_corte = ( isset( $_REQUEST['fecha_corte'] ) ? $_REQUEST['fecha_corte'] : '' );
$centro_costo = ( isset( $_REQUEST['centro_costo'] ) ? $_REQUEST['centro_costo'] : '' );
$ultimo_dia_mes = getUltimoDiaMes($fecha_corte);

/* $condicion = getStrCondiciones();

$condicion = $condicion != "" ? " AND $condicion" : ''; // Formateando..

$query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe) AS Debito, SUM(Haber) AS Credito FROM sigesproph_db.Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE DATE(Fecha_Movimiento) BETWEEN '$fecha_ini' AND '$fecha_fin' GROUP BY MC.Id_Plan_Cuenta";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $movimientos = $oCon->getData();
        unset($oCon); */


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

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Balance General.xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Balance General');

$objSheet->getCell('A1')->setValue("01/01/2019 - 31/01/2019 | BALANCE DE GENERAL | CENTRO COSTO: NO APLICA");
$objSheet->getStyle('A1')->getFont()->setBold(true);
$objSheet->mergeCells('A1:C2');
$objSheet->getStyle('A1:C2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$column = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';

$query = "SELECT 
PC.Id_Plan_Cuentas,
PC.Codigo,
PC.Nombre,
Codigo_Niif,
Nombre_Niif,
PC.Naturaleza,
IFNULL(SUM(BIC.Debito_PCGA), (SELECT IFNULL(SUM(Debito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Debito_PCGA,
IFNULL(SUM(BIC.Credito_PCGA), (SELECT IFNULL(SUM(Credito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Credito_PCGA,
IFNULL(SUM(BIC.Debito_NIIF), (SELECT IFNULL(SUM(Debito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Debito_NIIF,
IFNULL(SUM(BIC.Credito_NIIF), (SELECT IFNULL(SUM(Credito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Credito_NIIF,
PC.Estado,
PC.Movimiento,
PC.Tipo_P
FROM
Plan_Cuentas PC
    LEFT JOIN
 (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
 WHERE (PC.Codigo LIKE '1%' OR Codigo LIKE '2%' OR Codigo LIKE '3%') ".getStrCondiciones()."
 GROUP BY PC.Id_Plan_Cuentas
HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
ORDER BY PC.$column";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $balance = $oCon->getData();
        unset($oCon);

        $cod_temp = '';
        $cod_clase_temp = '';
        $acum_saldos = 0;
        $acum_total_pasivo = 0;
        $total_activo = 0;
        $total_pasivo=0;
        $total_patrimonio=0;
	
	$j = 2;
	foreach ($balance as $i => $value) {

		$codigo = $tipo_reporte == 'Pcga' ? $value['Codigo'] : $value['Codigo_Niif'];
		$nombre_cuenta = $tipo_reporte == 'Pcga' ? $value['Nombre'] : $value['Nombre_Niif'];

        $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
        $debito = 0;
        $credito = 0;
        $nuevo_saldo = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);

        if ($nivel_reporte > 1) { // Solo mostrar cuando se quiere consultar niveles superiores a 1
            if (substr($codigo,0,1) != $cod_clase_temp) {

            /* if ($cod_clase_temp != '') {$j++; // Para mostrar los totales de cada cuenta clase.

                $objSheet->getCell('A'.$j)->setValue('');
                $objSheet->getCell('B'.$j)->setValue('TOTAL ' . getNombreCuentaClase($cod_clase_temp));
                $objSheet->getStyle('B'.$j)->getFont()->setSize(9);
                $objSheet->getStyle('B'.$j)->getFont()->setBold(true);
                $objSheet->getCell('C'.$j)->setValue($acum_saldos);
                $objSheet->getStyle('C'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
                
                $acum_saldos = 0; // Resetear el acumulador
            } */
                
                
            $j++; // Para colocar el titulo de la cuenta CLASE.
              
            $objSheet->getCell('A'.$j)->setValue('');
            $objSheet->getCell('B'.$j)->setValue(getNombreCuentaClase($codigo));
            $objSheet->getStyle('B'.$j)->getFont()->setSize(12);
            $objSheet->getStyle('B'.$j)->getFont()->setBold(true);
            $objSheet->getStyle('B'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
            $objSheet->getStyle('B'.$j)->getFont()->getColor()->setARGB('FFFFFFFF');
            $objSheet->getCell('C'.$j)->setValue('');
              $cod_clase_temp = substr($codigo,0,1);
            }
        } else {
            $acum_saldos = 0; // Resetear el acumulador
        }

        /* if ($nivel_reporte > 2) { // Solo mostrar cuando se quiere consultar niveles superiores a 2
            if (substr($codigo,0,2) != $cod_temp) {$j++; // Para colocar el titulo de la cuenta GRUPO.
                $objSheet->getCell('A'.$j)->setValue('');
                $objSheet->getCell('B'.$j)->setValue(getNombreCuentaGrupo($codigo,$tipo_reporte));
                $objSheet->getStyle('B'.$j)->getFont()->setSize(11);
                $objSheet->getStyle('B'.$j)->getFont()->setBold(true);
                $objSheet->getCell('C'.$j)->setValue('');
                $cod_temp = substr($codigo,0,2);
            }
        } */

		if ($nuevo_saldo != 0) {$j++;
			$objSheet->getCell('A'.$j)->setValue($codigo);
			$objSheet->getCell('B'.$j)->setValue($value['Nombre']);
			$objSheet->getCell('C'.$j)->setValue($nuevo_saldo);
            $objSheet->getStyle('C'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
            
            $acum_saldos += $nuevo_saldo;
            if ($codigo === '1') {
                $total_activo = $nuevo_saldo;
            }
            if ($codigo === '2') { // Para acumular el total de cuenta pasivo.
                $total_pasivo = $nuevo_saldo;
            } 
            if($codigo === '3') {
                $total_patrimonio = $nuevo_saldo;
            }
        }
        
        if ($i == (count($balance)-1)) {$j++; // Para mostrar el total de la ultima cuenta clase.
            $objSheet->getCell('A'.$j)->setValue('');
            $objSheet->getCell('B'.$j)->setValue('TOTAL PATRIMONIO');
            $objSheet->getStyle('B'.$j)->getFont()->setSize(9);
            $objSheet->getStyle('B'.$j)->getFont()->setBold(true);
            $objSheet->getCell('C'.$j)->setValue($total_patrimonio);
            $objSheet->getStyle('C'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
        }

    }
    
    $resultado_ejercicio = saldoResultadoEjercicio($tipo_reporte);
    $total_patrimonio_utilidad_ejercicio = $total_patrimonio + $resultado_ejercicio;
    $total_pasivo_y_patrimonio = $total_pasivo + $total_patrimonio_utilidad_ejercicio;

    $j++;
    $objSheet->getCell('A'.$j)->setValue('');
    $objSheet->getCell('B'.$j)->setValue('RESULTADOS EJERCICIO');
    $objSheet->getStyle('B'.$j)->getFont()->setSize(9);
    $objSheet->getStyle('B'.$j)->getFont()->setBold(true);
    $objSheet->getCell('C'.$j)->setValue($resultado_ejercicio);
    $objSheet->getStyle('C'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $j++;
    $objSheet->getCell('A'.$j)->setValue('');
    $objSheet->getCell('B'.$j)->setValue('TOTAL PATRIMONIO CON LA UTILIDAD DEL EJERCICIO');
    $objSheet->getStyle('B'.$j)->getFont()->setSize(9);
    $objSheet->getStyle('B'.$j)->getFont()->setBold(true);
    $objSheet->getCell('C'.$j)->setValue($total_patrimonio_utilidad_ejercicio);
    $objSheet->getStyle('C'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    $j++;
    $objSheet->getCell('A'.$j)->setValue('');
    $objSheet->getCell('B'.$j)->setValue('TOTAL PASIVO Y PATRIMONIO');
    $objSheet->getStyle('B'.$j)->getFont()->setSize(9);
    $objSheet->getStyle('B'.$j)->getFont()->setBold(true);
    $objSheet->getCell('C'.$j)->setValue($total_pasivo_y_patrimonio);
    $objSheet->getStyle('C'.$j)->getNumberFormat()->setFormatCode("#,##0.00");
    


	/* $objSheet->getStyle('A3:F3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objSheet->getStyle('A3:F3')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
	$objSheet->getStyle('A3:F3')->getFont()->setBold(true);
	$objSheet->getStyle('A3:F3')->getFont()->getColor()->setARGB('FFFFFFFF'); */

	$objSheet->getColumnDimension('A')->setAutoSize(true);
	$objSheet->getColumnDimension('B')->setAutoSize(true);
	$objSheet->getColumnDimension('C')->setAutoSize(true);
	$objSheet->getStyle('A3:C3')->getAlignment()->setWrapText(true);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

function obtenerSaldoAnterior($naturaleza, $array, $index, $tipo_reporte)
{
  global $fecha_corte;

  $value = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
  
    $saldo_anterior = 0;
    $tipo_reporte = strtoupper($tipo_reporte);
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $saldo_anterior = $array[$index]["Debito_$tipo_reporte"] - $array[$index]["Credito_$tipo_reporte"];
    } else {
        $saldo_anterior = $array[$index]["Credito_$tipo_reporte"] - $array[$index]["Debito_$tipo_reporte"];
    }

    $fecha1 = date('Y-m-d', strtotime($fecha_corte));

    # VALIDACIÓN POR SI LA FECHA DE INICIO NO ES EL DÍA UNO (1) DEL MES Y SE TOQUE SACAR EL SALDO DE LA DIFERENCIA DEL ULTIMO BALANCE INICIAL.

    if ($fecha1 != '2019-01-01') { 
        // $fecha1 = date('Y-m-01', strtotime($fecha_corte)); // Primer día del mes
        $fecha1 = '2019-01-01';
        $fecha2 = $fecha_corte;
        $movimientos = getMovimientosCuenta($fecha1,$fecha2);
        $codigo = $array[$index][$value];
        $tipo =$array[$index]['Tipo_P'];
        $debito = calcularDebito($codigo,$tipo,$movimientos);
        $credito = calcularCredito($codigo,$tipo,$movimientos);
        $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
    }

    return $saldo_anterior;
}

function compararCuenta($codigo, $nivel, $cod_cuenta_actual)
{
    $str_comparar = substr($cod_cuenta_actual,0,$nivel);

    if (strpos($str_comparar, $codigo) !== false) {
        return true;
    }

    return false;
}


function calcularDebito($codigo, $tipo_cuenta, $movimientos)
{
    // return '0'; // Esto es temporal.

    $codigos_temp = [];
    global $tipo_reporte;

    foreach ($movimientos as $mov) {
        $nivel = strlen($mov['Codigo']);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;
        $cod_mov = $tipo_reporte == 'Pcga' ? $mov['Codigo'] : $mov['Codigo_Niif'];

        if (compararCuenta($codigo, $nivel2, $cod_mov)) {
            $codigos_temp[$cod_mov] = $mov['Debito'];
            while($nivel > $nivel2){
                if ($nivel > 2) {
                    $restar_str += 2;
    
                    // echo "cod superior A.N -- " . $cod_superior . "<br>";
                    // echo "Nivel -- " . $nivel . " -- Resta -- " . $restar_str . "<br>";
                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    // echo "cod superior -- " . $cod_superior . "<br>";
                    
                    
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito'];
                    }
                    $nivel -= 2;
                } else {
                    $restar_str += 1;
                    // echo "cod superior A.N -- " . $cod_superior . "<br>";
                    // echo "Nivel -- " . $nivel . " -- Resta -- " . $restar_str . "<br>";
                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    // echo "cod superior -- " . $cod_superior . "<br><br>";
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito'];
                    }
                    $nivel -= 1;
                }
            }
        }

    }

    /* echo "<pre>";
    var_dump($codigos_temp);
    echo "</pre>";
    exit; */

    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';

    
}

function calcularCredito($codigo, $tipo_cuenta, $movimientos)
{
    // return '0'; // Esto es temporal.

    $codigos_temp = [];

    foreach ($movimientos as $mov) {
        $nivel = strlen($cod_mov);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;
        $cod_mov = $tipo_reporte == 'Pcga' ? $mov['Codigo'] : $mov['Codigo_Niif'];

        if (compararCuenta($codigo, $nivel2, $cod_mov)) {
            $codigos_temp[$cod_mov] = $mov['Credito'];
            while($nivel > $nivel2){
                if ($nivel > 2) {
                    $restar_str += 2;
    
                    // echo "cod superior A.N -- " . $cod_superior . "<br>";
                    // echo "Nivel -- " . $nivel . " -- Resta -- " . $restar_str . "<br>";
                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    // echo "cod superior -- " . $cod_superior . "<br>";
                    
                    
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Credito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Credito'];
                    }
                    $nivel -= 2;
                } else {
                    $restar_str += 1;
                    // echo "cod superior A.N -- " . $cod_superior . "<br>";
                    // echo "Nivel -- " . $nivel . " -- Resta -- " . $restar_str . "<br>";
                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    // echo "cod superior -- " . $cod_superior . "<br><br>";
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Credito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Credito'];
                    }
                    $nivel -= 1;
                }
            }
        }

    }

    /* echo "<pre>";
    var_dump($codigos_temp);
    echo "</pre>";
    exit; */

    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';
}

function calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito)
{
    $nuevo_saldo = 0;
    
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $nuevo_saldo = ($saldo_anterior + $debito) - $credito;
    } else {
        $nuevo_saldo = ($saldo_anterior + $credito) - $debito;
    }

    return $nuevo_saldo;
}

function getNombreCuentaClase($codigo)
{
  $codigo = substr($codigo,0,1);

  $cuentas_clase = [
    "ACTIVO",
    "PASIVO",
    "PATRIMONIO"
  ];

  return $cuentas_clase[$codigo-1];
}

function getNombreCuentaGrupo($codigo, $tipo_reporte)
{
  $codigo = substr($codigo,0,2);

  $column = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';

  $query = "SELECT Nombre, Nombre_Niif FROM Plan_Cuentas WHERE $column LIKE '$codigo'";

  $oCon = new consulta();
  $oCon->setQuery($query);
  $cuenta = $oCon->getData();
  unset($oCon);

  if ($tipo_reporte == 'Pcga') {
    return $cuenta['Nombre'];
  } else {
    return $cuenta['Nombre_Niif'];
  }
}

function saldoResultadoEjercicio($tipo_reporte)
{
  global $ultimo_dia_mes;
  global $fecha_corte;

  $column = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';

  $query = "SELECT 
  PC.Id_Plan_Cuentas,
  PC.Codigo,
  PC.Nombre,
  Codigo_Niif,
  Nombre_Niif,
  PC.Naturaleza,
  IFNULL(SUM(BIC.Debito_PCGA), (SELECT IFNULL(SUM(Debito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Debito_PCGA,
  IFNULL(SUM(BIC.Credito_PCGA), (SELECT IFNULL(SUM(Credito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Credito_PCGA,
  IFNULL(SUM(BIC.Debito_NIIF), (SELECT IFNULL(SUM(Debito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Debito_NIIF,
  IFNULL(SUM(BIC.Credito_NIIF), (SELECT IFNULL(SUM(Credito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%'))) AS Credito_NIIF,
  PC.Estado,
  PC.Movimiento,
  PC.Tipo_P
FROM
  Plan_Cuentas PC
      LEFT JOIN
   (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
   WHERE $column LIKE '360505'
   GROUP BY PC.Id_Plan_Cuentas
HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
ORDER BY PC.$column";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $saldo = $oCon->getData();
    unset($oCon);

    ## Calculando saldo anterior

    $saldo_anterior = 0;
    $tipo_reporte = strtoupper($tipo_reporte);
    if ($saldo['Naturaleza'] == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $saldo_anterior = $saldo["Debito_$tipo_reporte"] - $saldo["Credito_$tipo_reporte"];
    } else {
        $saldo_anterior = $saldo["Credito_$tipo_reporte"] - $saldo["Debito_$tipo_reporte"];
    }
    # ---

    $fecha1 = date('d', strtotime($fecha_corte));

    # VALIDACIÓN POR SI LA FECHA DE INICIO NO ES EL DÍA UNO (1) DEL MES Y SE TOQUE SACAR EL SALDO DE LA DIFERENCIA DEL ULTIMO BALANCE INICIAL.

    if ($fecha1 != '01') { 
        $fecha1 = date('Y-m-01', strtotime($fecha_corte)); // Primer día del mes
        $fecha2 = $fecha_corte;
        $movimientos = getMovimientosCuenta($fecha1,$fecha2);
        $codigo = '360505';
        $tipo =$saldo['Tipo_P'];
        $debito = calcularDebito($codigo,$tipo,$movimientos);
        $credito = calcularCredito($codigo,$tipo,$movimientos);
        $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
    }

    $nuevo_saldo = $saldo_anterior;

    return $nuevo_saldo;
}

function getUltimoDiaMes($fecha_inicio)
{
    // $ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha_inicio)),1,date("Y",strtotime($fecha_inicio)))-1));
    $ultimo_dia_mes = '2018-12-31'; // Modificado 16-07-2019 -- KENDRY

    return $ultimo_dia_mes;
}

function getStrCondiciones()
{
    global $tipo_reporte;
    global $nivel_reporte;

    $condicion = '';

    $column = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
    
    if (isset($nivel_reporte) && $nivel_reporte != '') {
        $condicion .= " AND CHAR_LENGTH($column) BETWEEN 1 AND $nivel_reporte";
    }

    return $condicion;
}

function getMovimientosCuenta($fecha1, $fecha2)
{
    $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe) AS Debito, SUM(Haber) AS Credito FROM sigesproph_db.Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE DATE(Fecha_Movimiento) BETWEEN '$fecha1' AND '$fecha2' AND MC.Estado != 'Anulado' GROUP BY MC.Id_Plan_Cuenta";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $movimientos = $oCon->getData();
    unset($oCon);

    return $movimientos;
}

?>