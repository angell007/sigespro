<?php

function getCampo() {
    $campo['codigo'] = $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
    $campo['nombre'] = $_REQUEST['Tipo'] == 'General' ? 'Nombre_PCGA' : 'Nombre_Niif';
    $campo['debe'] = $_REQUEST['Tipo'] == 'General' ? 'Debe_PCGA' : 'Debe_Niif';
    $campo['haber'] = $_REQUEST['Tipo'] == 'General' ? 'Haber_PCGA' : 'Haber_Niif';
  
    return $campo;
  }
  
  function getUltimoDiaMes($fecha_inicio)
{
    $ultimo_dia_mes = '2018-12-31'; // Modificado 16-07-2019 -- KENDRY

    return $ultimo_dia_mes;
}

  function obtenerSaldoAnterior($naturaleza, $array, $index, $tipo_nit = false)
{
    /* global $fecha_ini;
    global $movimientos; */

    $fecha_ini = $_REQUEST['Fecha_Inicial'];
    
    $saldo_anterior = 0;

    $tipo_reporte = $_REQUEST['Tipo'] == 'General' ? 'PCGA' : 'NIIF';
   
    $value = $tipo_reporte == 'PCGA' ? 'Codigo' : 'Codigo_Niif'; 

    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $saldo_anterior = $array[$index]["Debito_$tipo_reporte"] - $array[$index]["Credito_$tipo_reporte"];
    } else {
        $saldo_anterior = $array[$index]["Credito_$tipo_reporte"] - $array[$index]["Debito_$tipo_reporte"];
    }
    
    $fecha1 = date('Y-m-d', strtotime($fecha_ini));

    # VALIDACIÓN POR SI LA FECHA DE INICIO NO ES EL DÍA UNO (1) DEL MES Y SE TOQUE SACAR EL SALDO DE LA DIFERENCIA DEL ULTIMO BALANCE INICIAL.

    if ($fecha1 != '2019-01-01') {
        if ($tipo_nit === false) {
            
            // $fecha1 = date('Y-01-01', strtotime($fecha_ini)); // Primer día del mes de enero
            $fecha1 = '2019-01-01';
            $fecha2 = strtotime('-1 day', strtotime($fecha_ini)); // Un día antes de la fecha de inicio para sacar el corte de saldo final.
            $fecha2 = date('Y-m-d', $fecha2);
            $saldos = getMovimientosCuenta($fecha1,$fecha2);
            
            
            $codigo = $array[$index][$value];
            
            $tipo =$array[$index]['Tipo_P'];
            $debito = calcularDebito($codigo,$tipo,$saldos,$tipo_reporte);
            $credito = calcularCredito($codigo,$tipo,$saldos,$tipo_reporte);
            $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
        } else {
            $fecha1 = '2019-01-01';
            $fecha2 = strtotime('-1 day', strtotime($fecha_ini)); // Un día antes de la fecha de inicio para sacar el corte de saldo final.
            $fecha2 = date('Y-m-d', $fecha2);
            $movimientos_lista = getMovimientosCuenta($fecha1,$fecha2,$array[$index]["Nit"],$array[$index]["Id_Plan_Cuenta"]);
            $debito = $movimientos_lista['Debito_'.$tipo_reporte];
            $credito = $movimientos_lista['Credito_'.$tipo_reporte];
            $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
        }
    }
 
  
 
    return $saldo_anterior;
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

function getMovimientosSaldoCuenta($fecha1, $fecha2, $id_plan_cuenta, $nit = null)
{
    $condicion_nit = '';

    if ($nit !== null) {
        $condicion_nit .= " AND MC.Nit = $nit";
    }
    
    $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe) AS Debito_PCGA, SUM(Haber) AS Credito_PCGA, SUM(Debe_Niif) AS Debito_NIIF, SUM(Haber_Niif) AS Credito_NIIF FROM Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE MC.Id_Plan_Cuenta = $id_plan_cuenta $condicion_nit AND DATE(Fecha_Movimiento) AND MC.Estado != 'Anulado' BETWEEN '$fecha1' AND '$fecha2'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $saldos = $oCon->getData();
    unset($oCon);

    return $saldos;
}

function getMovimientosCuenta($fecha1, $fecha2, $nit = null, $plan = null)
{

    if ($nit === null) {
        $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe) AS Debito_PCGA,
        SUM(Haber) AS Credito_PCGA, SUM(Debe_Niif) AS Debito_NIIF, SUM(Haber_Niif) AS Credito_NIIF 
        FROM Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas 
        WHERE DATE(Fecha_Movimiento) BETWEEN '$fecha1' AND '$fecha2' AND MC.Estado != 'Anulado' GROUP BY MC.Id_Plan_Cuenta";
        
        $oCon = new consulta();
        $oCon->setQuery($query);         
        $oCon->setTipo('Multiple');
        $movimientos = $oCon->getData();
        unset($oCon);
    } else {
        $query = "SELECT MC.Id_Plan_Cuenta, SUM(Debe) AS Debito_PCGA, SUM(Haber) AS Credito_PCGA,
        SUM(Debe_Niif) AS Debito_NIIF, SUM(Haber_Niif) AS Credito_NIIF
        FROM Movimiento_Contable MC WHERE DATE(Fecha_Movimiento)
        BETWEEN '$fecha1' AND '$fecha2' AND MC.Nit = $nit AND MC.Id_Plan_Cuenta = $plan AND MC.Estado != 'Anulado'";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $movimientos = $oCon->getData();
        unset($oCon);
    }


    return $movimientos;
}

function fecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function armarDatosNit($cuentas) {
    
    
    foreach ($cuentas as $i => $cuenta) {
        $query = queryByNit($cuenta['Id_Plan_Cuenta']);
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $nits = $oCon->getData();
        unset($oCon);

        if ($nits) {
            $cuentas[$i]['Nits'] = $nits;
        } else {
            $query = queryByNit($cuenta['Id_Plan_Cuenta'], true);

            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $nits = $oCon->getData();
            unset($oCon);

            $cuentas[$i]['Nits'] = $nits;

        }


    }
    foreach ($cuentas as $i => $cuenta) {

        foreach ($cuenta['Nits'] as $j => $nit) {
            $movimientos=[];
            if($nit['Nit']){
                $query = queryMovimientosCuenta($cuenta['Id_Plan_Cuenta'], $nit['Nit']);
    
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->setTipo('Multiple');
                $movimientos = $oCon->getData();
                unset($oCon);
            }
    
            $cuentas[$i]['Nits'][$j]['Movimientos'] = $movimientos;
            $cuentas[$i]['Nits'][$j]['Id_Plan_Cuenta'] = $cuenta['Id_Plan_Cuenta'];
        }
    }

    return $cuentas;


}

function calcularDebito($codigo, $tipo_cuenta, $movimientos, $tipo_reporte)
{
    $codigos_temp = [];

    foreach ($movimientos as $mov) {
        $cod_mov = $tipo_reporte == 'PCGA' ? $mov['Codigo'] : $mov['Codigo_Niif'];
        $nivel = strlen($cod_mov);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;

        if (compararCuenta($codigo, $nivel2, $cod_mov)) {
            $codigos_temp[$cod_mov] = $mov['Debito_'.$tipo_reporte];
            while($nivel > $nivel2){
                if ($nivel > 2) {
                    $restar_str += 2;

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito_'.$tipo_reporte];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito_'.$tipo_reporte];
                    }
                    $nivel -= 2;
                } else {
                    $restar_str += 1;

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito_'.$tipo_reporte];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito_'.$tipo_reporte];
                    }
                    $nivel -= 1;
                }
            }
        }

    }

    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';

    
}

function calcularCredito($codigo, $tipo_cuenta, $movimientos, $tipo_reporte)
{

    $codigos_temp = [];

    foreach ($movimientos as $mov) {
        $cod_mov = $tipo_reporte == 'PCGA' ? $mov['Codigo'] : $mov['Codigo_Niif']; 
        $nivel = strlen($cod_mov);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;


        if (compararCuenta($codigo, $nivel2, $cod_mov)) {
            $codigos_temp[$cod_mov] = $mov['Credito_'.$tipo_reporte];
            while($nivel > $nivel2){
                if ($nivel > 2) {
                    $restar_str += 2;
    
                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Credito_'.$tipo_reporte];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Credito_'.$tipo_reporte];
                    }
                    $nivel -= 2;
                } else {
                    $restar_str += 1;
                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Credito_'.$tipo_reporte];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Credito_'.$tipo_reporte];
                    }
                    $nivel -= 1;
                }
            }
        }

    }

    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';
}

function compararCuenta($codigo, $nivel, $cod_cuenta_actual)
{
    /* var_dump(func_get_args());
    echo "<br>"; */
    $str_comparar = substr($cod_cuenta_actual,0,$nivel);

    if (strpos($str_comparar, $codigo) !== false) {
        return true;
    }

    return false;
}