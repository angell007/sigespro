<?php


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Libro_Inventario.xls"');
header('Cache-Control: max-age=0');
set_time_limit(0);

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
include_once('./LibroClass.php');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$l = new Libro();
$encabezado = $l->getEncabezado();

$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$tipo_reporte = (isset($_REQUEST['typeReport']) ? $_REQUEST['typeReport'] : '');
$year = (isset($_REQUEST['date']) ? $_REQUEST['date'] : '');

$meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];

$centro_costo = false;

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion', "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$contenido = '';
$fecha_ini = "$year-01-01";
$ultimo_dia_mes = getUltimoDiaMes($fecha_ini);
$fecha_fin = "$year-12-31";

$contenido .= '
    
        <tr>
            <td >CODIGO</td>   
            <td >CUENTA</td>   
            <td >SALDO AL CIERRE</td>   
        </tr>
   
        ';

$query = "SELECT GROUP_CONCAT('^',Codigo_Grupo_Plan_Cuentas, '|') AS Codigos FROM Excluir_Plan_Cuentas_Centro_Costo
        WHERE DATE(Excluir_Desde) <= DATE('$fecha_ini')";
$oCon = new consulta();
$oCon->setQuery($query);

$planes_excluir = $oCon->getData();
unset($oCon);

$cond_exluir = " NOT REGEXP ' '";
if ($planes_excluir['Codigos'] != '') {
    $planes_excluir = str_replace(',', '', $planes_excluir['Codigos']);
    $planes_excluir = substr($planes_excluir, 0, -1);
    $cond_exluir = " NOT REGEXP '$planes_excluir' ";
}

$movimientos = getMovimientosCuenta($fecha_ini, $fecha_fin);


$totales = [
    "nuevo_saldo" => 0
];


$totalCant = 0;
$totalCosto = 0;
$column_1 = 'Codigo';
$column_2 = 'Codigo_Niif';

$column = $tipo_reporte == 'PCGA' ? 'Codigo' : 'Codigo_Niif';
$formatTypeReport = $tipo_reporte == 'PCGA' ? '' : '_Niif';

#expresión regular para exlcuir cuentas
$centroCond = ($centro_costo != false ? "AND BI.Id_Centro_Costo = $centro_costo[Id_Centro_Costo] AND BI.Codigo_Cuenta $cond_exluir " : "");

$query = "SELECT 
    
        PC.Codigo,
        PC.Nombre,
        Codigo_Niif,
        Nombre_Niif,
        PC.Naturaleza,
        (SELECT IFNULL(SUM(BI.Debito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                                        " . $centroCond . "  ) AS Debito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Credito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                                        " . $centroCond . ") AS Credito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Debito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                                         " . $centroCond . " ) AS Debito_NIIF,
                                         
        (SELECT IFNULL(SUM(BI.Credito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                                        " . $centroCond . ") AS Credito_NIIF,
         PC.Estado,
        PC.Movimiento,
        PC.Tipo_P
        FROM
        Plan_Cuentas PC
            LEFT JOIN
         (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC 
         ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas " . ($centro_costo != false ? "AND Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND Codigo_Cuenta $cond_exluir  " : "") . "
         " . getStrCondiciones() . "
         WHERE  PC.Movimiento = 'S' and LEFT(PC.Codigo$formatTypeReport,1) BETWEEN 1 and 3 
         GROUP BY PC.Id_Plan_Cuentas
        HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
        ORDER BY PC.$column";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$balance = $oCon->getData();
unset($oCon);

$acum_saldo_anterior = 0;
$acum_debito = 0;
$acum_credito = 0;
$acum_nuevo_saldo = 0;
$totales=['1'=>0, '2'=>0, '3'=>0];
foreach ($balance as $i => $value) {

    $codigo = $tipo_reporte == 'PCGA' ? $value['Codigo'] : $value['Codigo_Niif'];
    $nombre_cta = $tipo_reporte == 'PCGA' ? $value['Nombre'] : $value['Nombre_Niif'];

    [$saldo_debito, $saldo_credito, $saldo_anterior] = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);

    $debito = calcularDebito($codigo, $value['Tipo_P'], $movimientos);
    $credito = calcularCredito($codigo, $value['Tipo_P'], $movimientos);
    $nuevo_saldo = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);
    
    $cod_cuenta = substr($codigo, 0, 1);
    

    
    

    if ($nuevo_saldo != 0) {
            
        if($totales[$cod_cuenta] ==0 && $cod_cuenta > 1){
            
            $contenido .= '
                        <tr></tr>
                        <tr></tr>
                    <tr>
                        <td colspan="2"  align="center">
                            Totales Cuenta '. ($cod_cuenta-1) .'
                        </td>
                        
                        <td align="right">
                        ' . number_format($totales[$cod_cuenta-1] , 2, ",", ".") . '
                        </td> 
                        <tr></tr>
                        <tr></tr>
                       
                    </tr>';
        }
        $contenido .= '
                <tr>
                    <td  align="center">
                        ' . $codigo . '
                    </td>
                    <td align="center">
                        ' . $nombre_cta . '
                    </td>
                    
                    <td align="right">
                    ' . number_format($nuevo_saldo, 2, ",", ".") . '
                    </td> 
                   
                   
                </tr>';

        $totales[$cod_cuenta] += $nuevo_saldo;

    }
}
$contenido .= '
                        <tr></tr>
                        <tr></tr>
                    <tr>
                        <td  align="center" colspan="2">
                            Totales Cuenta '. $cod_cuenta .'
                        </td>
                        
                        <td align="right">
                        ' . number_format($totales[$cod_cuenta] , 2, ",", ".") . '
                        </td> 
                       
                        <tr></tr>
                    </tr>';
        




/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera = '

>
    

';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '
    <table  border=1 > 
        <tr>
            <td colspan="3" align="center"><strong>PRODUCTOS HOSPITALARIOS S.A.</strong></td>
        </tr>
        <tr>
            <td colspan="3" align="center"><strong>Nit: ' . $encabezado["NIT"] . '</strong></td>
        </tr
        <tr>
        <td colspan="3" align="center"><strong>AL MES DE CIERRE AÑO: ' . $year . '</strong></td>
        </tr>
    </table>

 <table  border=1 > 
    ' .
    $contenido . ' 
    </table>';

echo $content;

function getStrCondiciones()
{
    global $tipo_reporte;
    global $nivel_reporte;
    global $cta_ini;
    global $cta_fin;
    global $centro_costo;
    global $cond_exluir;


    $condicion = '';


    $column = $tipo_reporte == 'PCGA' ? 'Codigo' : 'Codigo_Niif';
    if (isset($cta_ini) && $cta_ini != '') {
        $condicion .= " WHERE $column BETWEEN '$cta_ini' AND '$cta_fin'";
    }
    if (isset($nivel_reporte) && $nivel_reporte != '') {
        if ($condicion == '') {
            $condicion .= " WHERE CHAR_LENGTH($column) BETWEEN 1 AND $nivel_reporte";
        } else {
            $condicion .= " AND CHAR_LENGTH($column) BETWEEN 1 AND $nivel_reporte";
        }
    }
    if (!$centro_costo) {
        if ($condicion == '') {
            $condicion .= " WHERE  BETWEEN 1 AND $nivel_reporte";
        } else {
            $condicion .= " AND CHAR_LENGTH($column) BETWEEN 1 AND $nivel_reporte";
        }
    } else {

        if ($condicion == '') {
            $condicion .= "WHERE Codigo $cond_exluir ";
        } else {
            $condicion .= " AND Codigo $cond_exluir ";
        }


    }

    $condicion = $condicion == '' ? "WHERE Tipo_P = 'CUENTA' " : " AND Tipo_P = 'CUENTA' ";

    return $condicion;
}

function obtenerSaldoAnterior($naturaleza, $array, $index, $tipo_reporte, $nit = null, $plan = null)
{
    global $fecha_ini;
    global $movimientos;

    //echo json_encode($movimientos);exit;
    $value = $tipo_reporte == 'PCGA' ? 'Codigo' : 'Codigo_Niif';

    $saldo_anterior = 0;
    $tipo_reporte = strtoupper($tipo_reporte);
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $saldo_anterior = $array[$index]["Debito_$tipo_reporte"] - $array[$index]["Credito_$tipo_reporte"];
    } else {
        $saldo_anterior = $array[$index]["Credito_$tipo_reporte"] - $array[$index]["Debito_$tipo_reporte"];
    }
    $saldoBalance = $saldo_anterior;

    $fecha1 = date('Y-m-d', strtotime($fecha_ini));

    # VALIDACIÓN POR SI LA FECHA DE INICIO NO ES EL DÍA UNO (1) DEL MES Y SE TOQUE SACAR EL SALDO DE LA DIFERENCIA DEL ULTIMO BALANCE INICIAL.

    if ($fecha1 != '2019-01-01') {

        if ($nit === null) {
            // $fecha1 = date('Y-01-01', strtotime($fecha_ini)); // Primer día del mes de enero
            $fecha1 = '2019-01-01';
            $fecha2 = strtotime('-1 day', strtotime($fecha_ini)); // Un día antes de la fecha de inicio para sacar el corte de saldo final.
            $fecha2 = date('Y-m-d', $fecha2);
            $movimientos_lista = getMovimientosCuenta($fecha1, $fecha2);
            $codigo = $array[$index][$value];
            $tipo = $array[$index]['Tipo_P'];
            $debito = calcularDebito($codigo, $tipo, $movimientos_lista);
            $credito = calcularCredito($codigo, $tipo, $movimientos_lista);
            $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
        } else {
            // $fecha1 = date('Y-01-01', strtotime($fecha_ini)); // Primer día del mes de enero
            $fecha1 = '2019-01-01';
            $fecha2 = strtotime('-1 day', strtotime($fecha_ini)); // Un día antes de la fecha de inicio para sacar el corte de saldo final.
            $fecha2 = date('Y-m-d', $fecha2);
            $movimientos_lista = getMovimientosCuenta($fecha1, $fecha2, $nit, $plan);
            $debito = $movimientos_lista['Debito'];
            $credito = $movimientos_lista['Credito'];
            $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
        }


    }

    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $debito = ((float) $saldoBalance + (float) $debito);
    } else {
        $credito = ((float) $saldoBalance + (float) $credito);
    }


    return [$debito, $credito, $saldo_anterior];
}


function show($data, $e = false)
{

    echo json_encode($data);
    if ($e) {
        /*   $myfile = fopen("testing.txt", "w") or die("Unable");
        fwrite($myfile, json_encode($data));
        fclose($myfile); */
        exit;

    }
}


function compararCuenta($codigo, $nivel, $cod_cuenta_actual)
{

    $str_comparar = substr($cod_cuenta_actual, 0, $nivel);

    if (strpos($str_comparar, $codigo) !== false) {
        return true;
    }

    return false;
}

function calcularDebito($codigo, $tipo_cuenta, $movimientos)
{
    $codigos_temp = [];
    global $tipo_reporte;

    foreach ($movimientos as $mov) {
        $nivel = strlen($mov['Codigo']);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;
        $cod_mov = $tipo_reporte == 'PCGA' ? $mov['Codigo'] : $mov['Codigo_Niif'];

        if (compararCuenta($codigo, $nivel2, $cod_mov)) {
            $codigos_temp[$cod_mov] = $mov['Debito'];
            while ($nivel > $nivel2) {
                if ($nivel > 2) {
                    $restar_str += 2;

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str, 0, $count_str - $restar_str);

                    if (!array_key_exists($cod_superior, $codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito'];
                    }
                    $nivel -= 2;
                } else {
                    $restar_str += 1;

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str, 0, $count_str - $restar_str);
                    if (!array_key_exists($cod_superior, $codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito'];
                    }
                    $nivel -= 1;
                }
            }
        }

    }

    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';
}

function calcularCredito($codigo, $tipo_cuenta, $movimientos)
{
    // return '0'; // Esto es temporal.
    global $tipo_reporte;

    $codigos_temp = [];

    foreach ($movimientos as $mov) {
        $nivel = strlen($mov['Codigo']);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;
        $cod_mov = $tipo_reporte == 'PCGA' ? $mov['Codigo'] : $mov['Codigo_Niif'];


        if (compararCuenta($codigo, $nivel2, $cod_mov)) {
            $codigos_temp[$cod_mov] = $mov['Credito'];
            while ($nivel > $nivel2) {
                if ($nivel > 2) {
                    $restar_str += 2;

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str, 0, $count_str - $restar_str);


                    if (!array_key_exists($cod_superior, $codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Credito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Credito'];
                    }
                    $nivel -= 2;
                } else {
                    $restar_str += 1;
                    // echo "Nivel -- " . $nivel . " -- Resta -- " . $restar_str . "<br>";
                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str, 0, $count_str - $restar_str);
                    // echo "cod superior -- " . $cod_superior . "<br><br>";
                    if (!array_key_exists($cod_superior, $codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Credito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Credito'];
                    }
                    $nivel -= 1;
                }
            }
        }

    }

    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';
}

function calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito)
{
    $nuevo_saldo = 0;

    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $nuevo_saldo = ((float) $saldo_anterior + (float) $debito) - (float) $credito;
    } else {
        $nuevo_saldo = ((float) $saldo_anterior + (float) $credito) - (float) $debito;
    }

    return $nuevo_saldo;
}

function getMovimientosCuenta($fecha1, $fecha2, $nit = null, $plan = null)
{
    global $tipo_reporte, $centro_costo, $cond_exluir;

    $tipo = $tipo_reporte != 'PCGA' ? '_Niif' : '';

    if ($nit === null) {
        $query = "SELECT MC.Id_Plan_Cuenta, MC.Id_Centro_Costo, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe$tipo) AS Debito, SUM(Haber$tipo) AS Credito FROM 
        Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE DATE(Fecha_Movimiento)
        BETWEEN '$fecha1' AND '$fecha2' AND MC.Estado != 'Anulado'
         " . ($centro_costo != false ? "AND MC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo $cond_exluir " : "") . "
        GROUP BY MC.Id_Plan_Cuenta";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $movimientos = $oCon->getData();
        unset($oCon);
    } else {
        $query = "SELECT MC.Id_Plan_Cuenta, MC.Id_Centro_Costo,SUM(Debe$tipo) AS Debito, SUM(Haber$tipo) AS Credito 
        FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta
         WHERE DATE(Fecha_Movimiento) BETWEEN '$fecha1' AND '$fecha2' AND MC.Nit = $nit AND MC.Id_Plan_Cuenta = $plan AND MC.Estado != 'Anulado'
         " . ($centro_costo != false ? "AND MC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo_Cuenta $cond_exluir " : "") . "
        ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $movimientos = $oCon->getData();
        unset($oCon);
    }


    return $movimientos;
}

function getUltimoDiaMes($fecha_inicio)
{
    // $ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha_inicio)),1,date("Y",strtotime($fecha_inicio)))-1));
    $ultimo_dia_mes = '2018-12-31'; // Modificado 16-07-2019 -- KENDRY

    return $ultimo_dia_mes;
}

function getUltimoDiaMesReal($fecha_inicio)
{
    return date("Y-m-t", strtotime($fecha_inicio));
}

function getMovimientosPorTipo($fecha_ini, $fecha_fin, $id_plan_cuenta, $movimiento)
{
    global $centro_costo, $cond_exluir;
    if ($movimiento == 'S') {
        $query = "SELECT
        MC.Id_Modulo,
        M.Documento AS Tipo_Documento,
        M.Prefijo,
        SUM(Debe) AS Debe,
        SUM(Haber) AS Haber,
        SUM(Debe_Niif) AS Debe_Niif,
        SUM(Haber_Niif) AS Haber_Niif
        FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta
        INNER JOIN Modulo M ON MC.Id_Modulo = M.Id_Modulo
        WHERE MC.Estado != 'Anulado'
        AND DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_ini' AND '$fecha_fin'
        AND MC.Id_Plan_Cuenta = $id_plan_cuenta
         " . ($centro_costo != false ? "AND MC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo $cond_exluir " : "") . "
        GROUP BY MC.Id_Modulo";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $resultado = $oCon->getData();
        unset($oCon);

        return $resultado;
    }
}

function armarTotales($totales)
{
    $cuentas_clases = [
        "1" => [
            "saldo_anterior" => isset($totales['clases']['1']) ? $totales['clases']['1']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['1']) ? $totales['clases']['1']['nuevo_saldo'] : 0
        ],
        "2" => [
            "saldo_anterior" => isset($totales['clases']['2']) ? $totales['clases']['2']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['2']) ? $totales['clases']['2']['nuevo_saldo'] : 0
        ],
        "3" => [
            "saldo_anterior" => isset($totales['clases']['3']) ? $totales['clases']['3']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['3']) ? $totales['clases']['3']['nuevo_saldo'] : 0
        ],
        "4" => [
            "saldo_anterior" => isset($totales['clases']['4']) ? $totales['clases']['4']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['4']) ? $totales['clases']['4']['nuevo_saldo'] : 0
        ],
        "5" => [
            "saldo_anterior" => isset($totales['clases']['5']) ? $totales['clases']['5']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['5']) ? $totales['clases']['5']['nuevo_saldo'] : 0
        ],
        "6" => [
            "saldo_anterior" => isset($totales['clases']['6']) ? $totales['clases']['6']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['6']) ? $totales['clases']['6']['nuevo_saldo'] : 0
        ]
    ];

    return $cuentas_clases;
}

function getTotal($totales, $tipo)
{
    $cuentas_clases = armarTotales($totales);
    $total = 0;

    if ($tipo == 'saldo_anterior') {
        $total = ($cuentas_clases["1"]["saldo_anterior"] - $cuentas_clases["2"]["saldo_anterior"] - $cuentas_clases["3"]["saldo_anterior"]) - ($cuentas_clases["4"]["saldo_anterior"] - $cuentas_clases["5"]["saldo_anterior"] - $cuentas_clases["6"]["saldo_anterior"]);
    } elseif ($tipo == 'nuevo_saldo') {
        $total = ($cuentas_clases["1"]["nuevo_saldo"] - $cuentas_clases["2"]["nuevo_saldo"] - $cuentas_clases["3"]["nuevo_saldo"]) - ($cuentas_clases["4"]["nuevo_saldo"] - $cuentas_clases["5"]["nuevo_saldo"] - $cuentas_clases["6"]["nuevo_saldo"]);
    }

    return $total;
}

function fecha($str)
{
    $parts = explode(" ", $str);
    $date = explode("-", $parts[0]);
    return $date[2] . "/" . $date[1] . "/" . $date[0];
}
?>