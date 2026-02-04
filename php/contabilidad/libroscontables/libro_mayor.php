<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

header('Cache-Control: max-age=0');
set_time_limit(0);

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
include_once('./LibroClass.php');

$l = new Libro();
$encabezado = $l->getEncabezado();

$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$tipo_reporte = (isset($_REQUEST['typeReport']) ? $_REQUEST['typeReport'] : '');
$year = (isset($_REQUEST['date']) ? $_REQUEST['date'] : '');

$meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];

$centro_costo = false;

/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$contenido = '';
$anio_ant = $year - 1;
for ($r = 1; $r <= 12; $r++) {
    $r = str_pad($r, 2, '0', STR_PAD_LEFT);
    $fecha_ini = "$year-$r-01";
    // $fecha_ini = "$anio_ant-12-31";
    $ultimo_dia_mes = getUltimoDiaMes($fecha_ini);
    $fecha_fin = getUltimoDiaMesReal($fecha_ini);

    $contenido .= '
    <thead  style="margin-top:40px;" >   
        <tr>
            <th colspan="8" style="text-align:center;">AL MES DE ' . strtoupper($meses[$r - 1]) . ' DEL AÑO  ' . $year . '</th>   
        </tr>
        <tr>
            <th rowspan="2">CODIGO</th>   
            <th rowspan="2">CUENTA</th>   
            <th colspan="2">SALDO ANTERIOR </th>   
            <th colspan="2">MOVIMIENTO MES </th>   
            <th colspan="2"  width="600">SALDO </th>  
        </tr>
        <tr>
            <th>DEBITO</th>
            <th>CREDITO</th>
            <th>DEBITO</th>
            <th>CREDITO</th>
            <th>DEBITO</th>
            <th  >CREDITO</th>
        </tr>
    
    </thead>
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

    $totales = [
        "saldo_debito" => 0,
        "saldo_credito" => 0,
        "debito" => 0,
        "credito" => 0,
        "nuevo_saldo_debito" => 0,
        "nuevo_saldo_credito" => 0
    ];


    $totalCant = 0;
    $totalCosto = 0;
    $column_1 = 'Codigo';
    $column_2 = 'Codigo_Niif';

    $column = $tipo_reporte == 'PCGA' ? 'Codigo' : 'Codigo_Niif';

    #expresión regular para exlcuir cuentas
    $centroCond = ($centro_costo != false ? "AND BI.Id_Centro_Costo = $centro_costo[Id_Centro_Costo] AND BI.Codigo_Cuenta $cond_exluir " : "");

    $query = "SELECT 
    
        PC.Codigo,
        PC.Nombre,
        PC.Codigo_Niif,
        Nombre_Niif,
        PC.Naturaleza,
        (SELECT IFNULL(SUM(BI.Debito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                     $centroCond    )
                     + sum(MC.Debe)
                      AS Debito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Credito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                     $centroCond    )
                     + sum(MC.Haber) AS Credito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Debito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                     $centroCond    )
                     + sum(MC.Debe_Niif) AS Debito_NIIF,
                                         
        (SELECT IFNULL(SUM(BI.Credito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                     $centroCond    ) 
                     + sum(MC.Haber_Niif)AS Credito_NIIF,
         PC.Estado,
        PC.Movimiento,
        PC.Tipo_P,  
        ifnull(SUM(MCM.Debe), 0) AS DebitoMes_PCGA,
        ifnull(SUM(MCM.Haber) , 0) AS CreditoMes_PCGA, 
        ifnull(SUM(MCM.Debe_Niif), 0) AS DebitoMes_NIIF, 
        IFNULL( SUM(MCM.Haber_Niif), 0) AS CreditoMes_NIIF
        FROM Plan_Cuentas PC 
        Left JOIN (

                SELECT 
                SUM(M.Debe) AS Debe, 
                SUM(M.Haber) AS Haber, 
                SUM(M.Debe_Niif) AS Debe_Niif, 
                SUM(M.Haber_Niif) AS Haber_Niif, 
                PC.Codigo, 
                PC.Codigo_Niif

                FROM Movimiento_Contable M
                INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = M.Id_Plan_Cuenta
                WHERE date(M.Fecha_Movimiento) > '$ultimo_dia_mes' AND DATE(M.Fecha_Movimiento) <'$fecha_ini'
                AND M.Estado != 'Anulado'
                GROUP BY PC.Id_Plan_Cuentas


                ) MC ON MC.$column LIKE CONCAT(PC.$column, '%')
        Left JOIN (

            SELECT 
            SUM(M.Debe) AS Debe, 
            SUM(M.Haber) AS Haber, 
            SUM(M.Debe_Niif) AS Debe_Niif, 
            SUM(M.Haber_Niif) AS Haber_Niif, 
            PC.Codigo, 
            PC.Codigo_Niif

            FROM Movimiento_Contable M
            INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = M.Id_Plan_Cuenta
            WHERE date(M.Fecha_Movimiento) between '$fecha_ini' and '$fecha_fin'
            AND M.Estado != 'Anulado'
            GROUP BY PC.Id_Plan_Cuentas


            ) MCM ON MCM.$column LIKE CONCAT(PC.$column, '%') AND MCM.$column = MC.$column
        WHERE Tipo_P = 'CUENTA'
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



    foreach ($balance as $i => $value) {


        $codigo = $tipo_reporte == 'PCGA' ? $value['Codigo'] : $value['Codigo_Niif'];
        $nombre_cta = $tipo_reporte == 'PCGA' ? $value['Nombre'] : $value['Nombre_Niif'];
        $saldo_credito = $value["Credito_$tipo_reporte"];
        $saldo_debito = $value["Debito_$tipo_reporte"];

        $credito = $value["CreditoMes_$tipo_reporte"];
        $debito = $value["DebitoMes_$tipo_reporte"];


        $nuevo_saldo_credito = $saldo_credito + $credito;
        $nuevo_saldo_debito = $saldo_debito + $debito;




        if ($saldo_anterior != 0 || $saldo_debito != 0 || $saldo_credito != 0) {


            $contenido .= '
                <tr>
                    <td  align="center">
                        ' . $codigo . '
                    </td>
                    <td align="center">
                        ' . $nombre_cta . '
                    </td>
                    
                    <td align="right">
                    ' . number_format($saldo_debito, 2, ",", ".")  . '
                    </td> 
                    <td align="right">
                        ' . number_format($saldo_credito, 2, ",", ".")  . '
                    </td>
               
                    <td align="right">
                    ' . number_format($debito, 2, ",", ".") . '
                    </td>
                    <td align="right">
                    ' . number_format($credito, 2, ",", ".") . '
                    </td>
                    <td align="right">
                    ' . number_format($nuevo_saldo_debito, 2, ",", ".") . ' 
                    </td>
                    <td align="right">
                    ' . number_format($nuevo_saldo_credito, 2, ",", ".") . ' 
                    </td>
                   
                </tr>';



            $totales['saldo_debito']  += $saldo_debito;
            $totales['saldo_credito']  += $saldo_credito;

            $totales['debito'] += $debito;
            $totales['credito'] += $credito;

            $totales['nuevo_saldo_debito'] += $nuevo_saldo_debito;
            $totales['nuevo_saldo_credito'] += $nuevo_saldo_credito;


            $totales_anio['saldo_debito']  += $saldo_debito;
            $totales_anio['saldo_credito']  += $saldo_credito;
            $totales_anio['debito'] += $debito;
            $totales_anio['credito'] += $credito;
            $totales_anio['nuevo_saldo_debito'] += $nuevo_saldo_debito;
            $totales_anio['nuevo_saldo_credito'] += $nuevo_saldo_credito;
        }
    }



    $contenido .= '
                
            <tr>
                    <td colspan="2" align="right">
                       Totales
                    </td>
                   
                    
                    <td align="right">
                    ' .  number_format($totales['saldo_debito'], 2, ",", ".") . '
                    </td> 
                    <td align="right">
                        ' .  number_format($totales['saldo_credito'], 2, ",", ".") . '
                    </td>
               
                    <td align="right">
                    ' .  number_format($totales['debito'], 2, ",", ".") . '
                    </td>
                    <td align="right">
                    ' .  number_format($totales['credito'], 2, ",", ".")  . '
                    </td>
                    <td align="right">
                    ' . number_format($totales['nuevo_saldo_debito'], 2, ",", ".")  . ' 
                    </td>
                    <td align="right">
                    ' . number_format($totales['nuevo_saldo_credito'], 2, ",", ".")  . ' 
                    </td>
                   
            </tr>
            <tr></tr>';
}

$contenido .= '
                
            <tr>
                    <td colspan="2" align="right">
                       Totales Año ' . $year . '
                    </td>
                   
                    
                    <td align="right">
                    ' .  number_format($totales_anio['saldo_debito'], 2, ",", ".") . '
                    </td> 
                    <td align="right">
                        ' .  number_format($totales_anio['saldo_credito'], 2, ",", ".") . '
                    </td>
               
                    <td align="right">
                    ' .  number_format($totales_anio['debito'], 2, ",", ".") . '
                    </td>
                    <td align="right">
                    ' .  number_format($totales_anio['credito'], 2, ",", ".")  . '
                    </td>
                    <td align="right">
                    ' . number_format($totales_anio['nuevo_saldo_debito'], 2, ",", ".")  . ' 
                    </td>
                    <td align="right">
                    ' . number_format($totales_anio['nuevo_saldo_credito'], 2, ",", ".")  . ' 
                    </td>
                   
            </tr>
            <tr></tr>';


/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera = '

>
    

';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = "
    <table   border=1 > 
        <thead>
            <tr>
            <th colspan='8' align='center' style='text-transform:uppercase;'><strong>$encabezado[Nombre_Empresa]</strong></th>
            </tr>
            <tr>
            <th colspan='8' align='center'><strong>Nit: $encabezado[NIT] </strong></th>
            </tr>
        </thead>
        $contenido
    </table>";


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Libro_Mayor.xls"');
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


function getMovimientosCuenta($fecha1, $fecha2, $nit = null, $plan = null)
{
    global $tipo_reporte, $centro_costo, $cond_exluir;

    $tipo = $tipo_reporte != 'PCGA' ? '_Niif' : '';

    if ($nit === null) {
        $query = "SELECT MC.Id_Plan_Cuenta, MC.Id_Centro_Costo, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe$tipo) AS Debito, SUM(Haber$tipo) AS Credito, 
        MC.Fecha_Movimiento
        FROM Plan_Cuentas PC 

        WHERE DATE(Fecha_Movimiento)  BETWEEN '$fecha1' AND '$fecha2'
        AND MC.Estado != 'Anulado'

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
