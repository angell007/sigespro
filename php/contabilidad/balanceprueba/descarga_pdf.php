<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
set_time_limit(0);

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$fecha_ini = ( isset( $_REQUEST['fecha_ini'] ) ? $_REQUEST['fecha_ini'] : '' );
$fecha_fin = ( isset( $_REQUEST['fecha_fin'] ) ? $_REQUEST['fecha_fin'] : '' );
$tipo_reporte = ( isset( $_REQUEST['tipo_reporte'] ) ? $_REQUEST['tipo_reporte'] : '' );
$nivel_reporte = ( isset( $_REQUEST['nivel'] ) ? $_REQUEST['nivel'] : '' );
$cta_ini = ( isset( $_REQUEST['cta_ini'] ) ? $_REQUEST['cta_ini'] : '' );
$cta_fin = ( isset( $_REQUEST['cta_fin'] ) ? $_REQUEST['cta_fin'] : '' );
$ultimo_dia_mes = getUltimoDiaMes($fecha_ini);

$id_centro_costo = ( isset( $_REQUEST['centro_costo'] ) ? $_REQUEST['centro_costo'] : '' );

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

$centro_costo = false; 

if($id_centro_costo){
    $oItem = new complex('Centro_Costo',"Id_Centro_Costo",$id_centro_costo);
    $centro_costo = $oItem->getData();
    #var_dump($centro_costo);
    
    unset($oItem);
}


$query = "SELECT GROUP_CONCAT('^',Codigo_Grupo_Plan_Cuentas, '|') AS Codigos FROM Excluir_Plan_Cuentas_Centro_Costo
WHERE DATE(Excluir_Desde) <= DATE('$fecha_ini')" ;
$oCon = new consulta();
$oCon->setQuery($query);

$planes_excluir = $oCon->getData();
unset($oCon);



$cond_exluir=" NOT REGEXP ' '";    
if($planes_excluir['Codigos'] != ''){
$planes_excluir =  str_replace(',','',$planes_excluir['Codigos']);
$planes_excluir = substr($planes_excluir, 0, -1);
$cond_exluir=" NOT REGEXP '$planes_excluir' ";
}


/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$movimientos = getMovimientosCuenta($fecha_ini,$fecha_fin);

$totales = [
    "saldo_anterior" => 0,
    "debito" => 0,
    "credito" => 0,
    "nuevo_saldo" => 0,
    "clases" => []
];

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style='<style>
.page-content{
width:750px;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:15px;
    line-height: 20px;
}
.titular{
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

        $tipo_balance = strtoupper($tipo);
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">BALANCE DE PRUEBA</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">'.$tipo_balance.'</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">'.strtoupper($tipo_reporte).'</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Ini. '.fecha($fecha_ini).'</h5>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Fin. '.fecha($fecha_fin).'</h5>
        ';
        
    $contenido = '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:50px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
            Cuenta
        </td>   
        <td style="width:250px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Nombre Cuenta
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Saldo Anterior
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Debitos
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Creditos
        </td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Nuevo Saldo
        </td>
    </tr>';

    $totalCant = 0;
    $totalCosto = 0;
    $column_1 = 'Codigo';
    $column_2 = 'Codigo_Niif';
    
    $column = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';

    if ($tipo == 'General') {
        #expresión regular para exlcuir cuentas
        $centroCond = ($centro_costo != false ? "AND BI.Id_Centro_Costo = $centro_costo[Id_Centro_Costo] AND BI.Codigo_Cuenta $cond_exluir " : "" );
   
        $query = "SELECT 
    
        PC.Codigo,
        PC.Nombre,
        Codigo_Niif,
        Nombre_Niif,
        PC.Naturaleza,
        (SELECT IFNULL(SUM(BI.Debito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                                        ".$centroCond."  ) AS Debito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Credito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                                        ".$centroCond.") AS Credito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Debito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                                         ".$centroCond." ) AS Debito_NIIF,
                                         
        (SELECT IFNULL(SUM(BI.Credito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                                        ".$centroCond.") AS Credito_NIIF,
         PC.Estado,
        PC.Movimiento,
        PC.Tipo_P
        FROM
        Plan_Cuentas PC
            LEFT JOIN
         (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC 
         ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas ".($centro_costo != false ? "AND Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND Codigo_Cuenta $cond_exluir  " : "" )."
         ".getStrCondiciones()."
         GROUP BY PC.Id_Plan_Cuentas
        HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
        ORDER BY PC.$column";
 
 
        $oCon = new consulta();
        $oCon->setQuery($query);
        //echo $query; exit;
        $oCon->setTipo('Multiple');
        $balance = $oCon->getData();
        unset($oCon);
       /* echo "<pre>";
        var_dump($balance);
        echo "</pre>";
        exit; */

        $acum_saldo_anterior = 0;
        $acum_debito = 0;
        $acum_credito = 0;
        $acum_nuevo_saldo = 0;

        foreach ($balance as $i => $value) {
            
            $codigo = $tipo_reporte == 'Pcga' ? $value['Codigo'] : $value['Codigo_Niif'];
            $nombre_cta = $tipo_reporte == 'Pcga' ? $value['Nombre'] : $value['Nombre_Niif'];
            
            $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
            $debito = calcularDebito($codigo,$value['Tipo_P'],$movimientos);
            $credito = calcularCredito($codigo,$value['Tipo_P'],$movimientos);
            $nuevo_saldo = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);

            if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
                $contenido .= '
                <tr>
                    <td style="padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$codigo.'
                    </td>
                    <td style="width:250px;padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$nombre_cta.'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($saldo_anterior, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($debito, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($credito, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($nuevo_saldo, 2, ",", ".").' 
                    </td>
                </tr>';

                /* $acum_saldo_anterior += $saldo_anterior;
                $acum_debito += $debito;
                $acum_credito += $credito;
                $acum_nuevo_saldo += $nuevo_saldo; */

                if ($value['Tipo_P'] == 'CLASE') {
                    $totales['clases'][$value['Codigo']]['saldo_anterior'] = $saldo_anterior;
                    $totales['clases'][$value['Codigo']]['nuevo_saldo'] = $nuevo_saldo;
                    //La formula para estos campos: 1+2+3+4+5+6+8 (A nivel de cuentas)
                    $totales['debito'] += $debito;
                    $totales['credito'] += $credito;
                    
                }
            }
        }

        $totales['saldo_anterior'] = getTotal($totales,'saldo_anterior'); //uno

        $totales['nuevo_saldo'] = getTotal($totales,'nuevo_saldo'); //uno
        
        /** AGREGADO POR AUGUSTO 12 NOV 2024 para solucionar un problema temporal   QUITAR PRONTO**/
        if($totales['nuevo_saldo']!=0){
         $totales['credito']=$totales['credito']+$totales['nuevo_saldo'];
         $totales['nuevo_saldo']=0;
        }

        $contenido .= '
        <tr>
                
                <td colspan="2" style="background:#cecece;padding:4px;text-align:left;border:1px solid #cccccc;">
                    Total
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($totales['saldo_anterior'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['debito'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['credito'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['nuevo_saldo'], 2, ",", ".").'
                </td>
            </tr>';
        
    } elseif ($tipo == 'Nits') {
         $centroCond = ($centro_costo != false ? "AND BI.Id_Centro_Costo = $centro_costo[Id_Centro_Costo] AND BI.Codigo_Cuenta $cond_exluir " : "" );
  
        $query = "SELECT 
        PC.Id_Plan_Cuentas,
        PC.Codigo,
        PC.Nombre,
        Codigo_Niif,
        Nombre_Niif,
        PC.Naturaleza,
        IFNULL(SUM(BIC.Debito_PCGA), (SELECT IFNULL(SUM(BI.Debito_PCGA),0) FROM Balance_Inicial_Contabilidad BI
                WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%')  ".$centroCond."  )) AS Debito_PCGA,
                
        IFNULL(SUM(BIC.Credito_PCGA), (SELECT IFNULL(SUM(BI.Credito_PCGA),0) FROM Balance_Inicial_Contabilidad BI
                WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%')  ".$centroCond." )) AS Credito_PCGA  ,
                
        IFNULL(SUM(BIC.Debito_NIIF), (SELECT IFNULL(SUM(BI.Debito_NIIF),0) FROM Balance_Inicial_Contabilidad BI
                WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_2,'%')  ".$centroCond." )) AS Debito_NIIF ,
                
        IFNULL(SUM(BIC.Credito_NIIF), (SELECT IFNULL(SUM(BI.Credito_NIIF),0) FROM Balance_Inicial_Contabilidad BI
                WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_2,'%')  ".$centroCond." )) AS Credito_NIIF  ,
         PC.Estado,
        PC.Movimiento,
        PC.Tipo_P
    FROM
        Plan_Cuentas PC
            LEFT JOIN
         (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' ".($centro_costo != false ? " AND Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND Codigo_Cuenta $cond_exluir " : "" )." ) BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
            ".getStrCondiciones()."
         GROUP BY PC.Id_Plan_Cuentas
    HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
    ORDER BY PC.$column";
    

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $balance = $oCon->getData();
        unset($oCon);
        
          /*echo "<pre>";
        var_dump($query);
        echo "</pre>";
        exit; */

        foreach ($balance as $i => $value) {
            $balance[$i]['nits'] = nitsPorCuentasContables($value['Id_Plan_Cuentas']);
        }

        foreach ($balance as $i => $value) {

            $codigo = $tipo_reporte == 'Pcga' ? $value['Codigo'] : $value['Codigo_Niif'];
            $nombre_cta = $tipo_reporte == 'Pcga' ? $value['Nombre'] : $value['Nombre_Niif'];
            
            $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
            $debito = calcularDebito($codigo,$value['Tipo_P'],$movimientos);
            $credito = calcularCredito($codigo,$value['Tipo_P'],$movimientos);
            $nuevo_saldo = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);

            if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
                $contenido .= '
                <tr>
                    <td style="padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$codigo.'
                    </td>
                    <td style="width:250px;padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$nombre_cta.'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($saldo_anterior, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($debito, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($credito, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($nuevo_saldo, 2, ",", ".").'
                    </td>
                </tr>';
    
                /* $acum_saldo_anterior += $saldo_anterior;
                $acum_debito += $debito;
                $acum_credito += $credito;
                $acum_nuevo_saldo += $nuevo_saldo; */

                if ($value['Tipo_P'] == 'CLASE') {
                    $totales['clases'][$value['Codigo']]['saldo_anterior'] = $saldo_anterior;
                    $totales['clases'][$value['Codigo']]['nuevo_saldo'] = $nuevo_saldo;
                    //La formula para estos campos: 1+2+3+4+5+6+8 (A nivel de cuentas)
                    $totales['debito'] += $debito;
                    $totales['credito'] += $credito;
                    
                }

                // $nits = nitsPorCuentasContables($value['Id_Plan_Cuentas']);
            }

            $nits = $value['nits'];

            foreach ($nits as $j => $nit) {

                $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $nits, $j, $tipo_reporte, $nit['Nit'], $value['Id_Plan_Cuentas']);
                $debito = $nit['Total_Debito_'.$tipo_reporte];
                $credito = $nit['Total_Credito_'.$tipo_reporte];
                $nuevo_saldo = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);

                if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
                    $contenido .= '<tr>
                    <td style="font-size:9px;color:gray;padding:2px;text-align:left;border:1px solid #cccccc;">
                        '.$nit['Nit'].'
                    </td>
                    <td style="width:250px;font-size:9px;color:gray;padding:2px;text-align:left;border:1px solid #cccccc;">
                        '.$nit['Nombre'].'
                    </td>
                    <td style="font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($saldo_anterior, 2, ",", ".").'
                    </td>
                    <td style="font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($debito, 2, ",", ".").'
                    </td>
                    <td style="font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($credito, 2, ",", ".").'
                    </td>
                    <td style="font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($nuevo_saldo, 2, ",", ".").'
                    </td>
                </tr>';
                }
            }

        }

        $totales['saldo_anterior'] = getTotal($totales,'saldo_anterior'); //dos

        $totales['nuevo_saldo'] = getTotal($totales,'nuevo_saldo'); //dos
        
        /** AGREGADO POR AUGUSTO 12 NOV 2024 para solucionar un problema temporal   QUITAR PRONTO**/
        if($totales['nuevo_saldo']!=0){
         $totales['credito']=$totales['credito']+$totales['nuevo_saldo'];
         $totales['nuevo_saldo']=0;
        }

        $contenido .= '
        <tr>
                
                <td colspan="2" style="background:#cecece;padding:4px;text-align:left;border:1px solid #cccccc;">
                    Total
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($totales['saldo_anterior'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['debito'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['credito'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['nuevo_saldo'], 2, ",", ".").'
                </td>
            </tr>';
    } elseif ($tipo == 'Tipo') {

        $query = "SELECT 
    
        PC.Id_Plan_Cuentas,
        PC.Codigo,
        PC.Nombre,
        Codigo_Niif,
        Nombre_Niif,
        PC.Naturaleza,
        IFNULL(SUM(BIC.Debito_PCGA), (SELECT IFNULL(SUM(Debito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%'))) AS Debito_PCGA,
        IFNULL(SUM(BIC.Credito_PCGA), (SELECT IFNULL(SUM(Credito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%'))) AS Credito_PCGA,
        IFNULL(SUM(BIC.Debito_NIIF), (SELECT IFNULL(SUM(Debito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column_2,'%'))) AS Debito_NIIF,
        IFNULL(SUM(BIC.Credito_NIIF), (SELECT IFNULL(SUM(Credito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column_2,'%'))) AS Credito_NIIF,
        PC.Estado,
        PC.Movimiento,
        PC.Tipo_P
    FROM
        Plan_Cuentas PC
            LEFT JOIN
         (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
         ".getStrCondiciones()."
         GROUP BY PC.Id_Plan_Cuentas
    HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
    ORDER BY PC.$column";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $balance = $oCon->getData();
        unset($oCon);
       

        foreach ($balance as $i => $value) {
            $balance[$i]['tipos'] = getMovimientosPorTipo($fecha_ini,$fecha_fin,$value['Id_Plan_Cuentas'],$value['Movimiento']);
        }

        $acum_saldo_anterior = 0;
        $acum_debito = 0;
        $acum_credito = 0;
        $acum_nuevo_saldo = 0;

        foreach ($balance as $i => $value) {
            
            $codigo = $tipo_reporte == 'Pcga' ? $value['Codigo'] : $value['Codigo_Niif'];
            $nombre_cta = $tipo_reporte == 'Pcga' ? $value['Nombre'] : $value['Nombre_Niif'];
            
            $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
            $debito = calcularDebito($codigo,$value['Tipo_P'],$movimientos);
            $credito = calcularCredito($codigo,$value['Tipo_P'],$movimientos);
            $nuevo_saldo = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);

            if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
                $contenido .= '
                <tr>
                    <td style="padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$codigo.'
                    </td>
                    <td style="width:250px;padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$nombre_cta.'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($saldo_anterior, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($debito, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($credito, 2, ",", ".").'
                    </td>
                    <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($nuevo_saldo, 2, ",", ".").' 
                    </td>
                    
                </tr>';

                /* $acum_saldo_anterior += $saldo_anterior;
                $acum_debito += $debito;
                $acum_credito += $credito;
                $acum_nuevo_saldo += $nuevo_saldo; */

                if ($value['Tipo_P'] == 'CLASE') {
                    $totales['clases'][$value['Codigo']]['saldo_anterior'] = $saldo_anterior;
                    $totales['clases'][$value['Codigo']]['nuevo_saldo'] = $nuevo_saldo;
                    //La formula para estos campos: 1+2+3+4+5+6+8 (A nivel de cuentas)
                    $totales['debito'] += $debito;
                    $totales['credito'] += $credito;
                    
                }
            }

            $tipos = $value['tipos'];

            if (count($tipos) > 0) {
                $debe = $tipo_reporte == 'Pcga' ? 'Debe' : 'Debe_Niif';
                $haber = $tipo_reporte == 'Pcga' ? 'Haber' : 'Haber_Niif';
                $contenido .= '
                    <tr>
                        <td style="padding:4px;text-align:left;border:1px solid #cccccc;">
                        </td>
                        <td colspan="5" style="padding:4px;text-align:left;border:1px solid #cccccc;">
                            <table>';
                                foreach ($tipos as $z => $value) {
                                    $contenido .= '
                                    <tr>
                                        <td style="width:50px;font-size:9px;color:gray;padding:2px;text-align:left;">
                                        '.$value['Prefijo'].'
                                        </td>
                                        <td style="width:160px;font-size:9px;color:gray;padding:2px;text-align:left;">
                                        '.$value['Tipo_Documento'].'
                                        </td>
                                        <td style="width:100px;font-size:9px;color:gray;padding:2px;text-align:right;">
                                        0,00
                                        </td>
                                        <td style="width:100px;font-size:9px;color:gray;padding:2px;text-align:right;">
                                        $ '.number_format($value[$debe],2,",",".").'
                                        </td>
                                        <td style="width:100px;font-size:9px;color:gray;padding:2px;text-align:right;">
                                        $ '.number_format($value[$haber],2,",",".").'
                                        </td>
                                        <td style="width:90px;font-size:9px;color:gray;padding:2px;text-align:right;">
                                        0,00
                                        </td>
                                    </tr>';
                                }
                            $contenido .= '</table>
                        </td>
                    </tr>
                ';
            }
        }

        $totales['saldo_anterior'] = getTotal($totales,'saldo_anterior'); //tres
 
        $totales['nuevo_saldo'] = getTotal($totales,'nuevo_saldo'); // tres
        
        /** AGREGADO POR AUGUSTO 12 NOV 2024 para solucionar un problema temporal   QUITAR PRONTO**/
        if($totales['nuevo_saldo']!=0){
         $totales['credito']=$totales['credito']+$totales['nuevo_saldo'];
         $totales['nuevo_saldo']=0;
        }


        $contenido .= '
        <tr>
                
                <td colspan="2" style="background:#cecece;padding:4px;text-align:left;border:1px solid #cccccc;">
                    Total
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($totales['saldo_anterior'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['debito'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['credito'], 2, ",", ".").'
                </td>
                <td style="background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;">
                $ '.number_format($totales['nuevo_saldo'], 2, ",", ".").'
                </td>
            </tr>';
        
    }
    
    $contenido .= '</table>';


	

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:250px;text-align:right">
                        '.$codigos.'
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >'.
                    $cabecera.
                    $contenido.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = 'Balance Prueba.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function getStrCondiciones()
{
    global $tipo_reporte;
    global $nivel_reporte;
    global $cta_ini;
    global $cta_fin;
    global $centro_costo;
    global $cond_exluir;
    

    $condicion = '';

 
    $column = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
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
    }else{

        if ($condicion == '') {
            $condicion .= "WHERE Codigo $cond_exluir ";
        } else {
            $condicion .= " AND Codigo $cond_exluir ";
        }
     
       
    }


    return $condicion;
}

function obtenerSaldoAnterior($naturaleza, $array, $index, $tipo_reporte, $nit = null, $plan = null)
{
    global $fecha_ini;
    global $movimientos;

    $value = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';

    $saldo_anterior = 0;
    $tipo_reporte = strtoupper($tipo_reporte);
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $saldo_anterior = $array[$index]["Debito_$tipo_reporte"] - $array[$index]["Credito_$tipo_reporte"];
    } else {
        $saldo_anterior = $array[$index]["Credito_$tipo_reporte"] - $array[$index]["Debito_$tipo_reporte"];
    }

    $fecha1 = date('Y-m-d', strtotime($fecha_ini));

    # VALIDACIÓN POR SI LA FECHA DE INICIO NO ES EL DÍA UNO (1) DEL MES Y SE TOQUE SACAR EL SALDO DE LA DIFERENCIA DEL ULTIMO BALANCE INICIAL.

    if ($fecha1 != '2019-01-01') {
        
        if ($nit === null) {
            // $fecha1 = date('Y-01-01', strtotime($fecha_ini)); // Primer día del mes de enero
            $fecha1 = '2019-01-01';
            $fecha2 = strtotime('-1 day', strtotime($fecha_ini)); // Un día antes de la fecha de inicio para sacar el corte de saldo final.
            $fecha2 = date('Y-m-d', $fecha2);
            $movimientos_lista = getMovimientosCuenta($fecha1,$fecha2);
            $codigo = $array[$index][$value];
            $tipo =$array[$index]['Tipo_P'];
            $debito = calcularDebito($codigo,$tipo,$movimientos_lista);
            $credito = calcularCredito($codigo,$tipo,$movimientos_lista);
            $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
        } else {
            // $fecha1 = date('Y-01-01', strtotime($fecha_ini)); // Primer día del mes de enero
            $fecha1 = '2019-01-01';
            $fecha2 = strtotime('-1 day', strtotime($fecha_ini)); // Un día antes de la fecha de inicio para sacar el corte de saldo final.
            $fecha2 = date('Y-m-d', $fecha2);
            $movimientos_lista = getMovimientosCuenta($fecha1,$fecha2,$nit,$plan);
            $debito = $movimientos_lista['Debito'];
            $credito = $movimientos_lista['Credito'];
            $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
        }
        

    }
  
    return $saldo_anterior;
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

function calcularDebito($codigo, $tipo_cuenta, $movimientos)
{
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

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito'];
                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito'];
                    }
                    $nivel -= 2;
                } else {
                    $restar_str += 1;

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
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
        $cod_mov = $tipo_reporte == 'Pcga' ? $mov['Codigo'] : $mov['Codigo_Niif'];

        /* echo "++". $mov['Codigo'] ."<br>";
        echo "--". $codigo ."<br>";

        var_dump(compararCuenta($codigo, $nivel2, $cod_mov));
        echo "<br>"; */

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
    echo "</pre>"; */
    // exit;

    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';
}

function calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito)
{
    $nuevo_saldo = 0;
    
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $nuevo_saldo = ( (float)$saldo_anterior + (float)$debito) - (float)$credito;
    } else {
        $nuevo_saldo = ( (float)$saldo_anterior + (float)$credito) - (float)$debito;
    }

    return $nuevo_saldo;
}

function nitsPorCuentasContables($id_plan_cuentas)
{
global $fecha_ini;
global $fecha_fin;
global $nivel_reporte;
global $centro_costo;
global  $cond_exluir ;
$query = "SELECT
r.Nit,
r.Nombre,
SUM(r.Debito_PCGA) AS Debito_PCGA,
SUM(r.Credito_PCGA) AS Credito_PCGA,
SUM(r.Debito_NIIF) AS Debito_NIIF,
SUM(r.Credito_NIIF) AS Credito_NIIF,
SUM(r.Total_Debito_Pcga) AS Total_Debito_Pcga,
SUM(r.Total_Credito_Pcga) AS Total_Credito_Pcga,
SUM(r.Total_Debito_Niif) AS Total_Debito_Niif,
SUM(r.Total_Credito_Niif) AS Total_Credito_Niif,
0 as Id_Centro_Costo
FROM
(
(SELECT 
    
    BIC.Nit,
    (
        CASE BIC.Tipo
            WHEN 'Cliente' THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = BIC.Nit LIMIT 1)
            WHEN 'Proveedor' THEN (SELECT IF(Nombre = '' OR Nombre IS NULL,CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Nombre) FROM Proveedor WHERE Id_Proveedor = BIC.Nit LIMIT 1)
            WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ',Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = BIC.Nit LIMIT 1)
            WHEN 'Caja_Compensacion' THEN (SELECT Nombre FROM Caja_Compensacion WHERE Nit = BIC.Nit LIMIT 1)
        END
    ) AS Nombre,
    BIC.Debito_PCGA,
    BIC.Credito_PCGA,
    BIC.Debito_NIIF,
    BIC.Credito_NIIF,
    0 AS Total_Debito_Pcga,
    0 AS Total_Credito_Pcga,
    0 AS Total_Debito_Niif,
    0 AS Total_Credito_Niif,
    0 as Id_Centro_Costo
FROM
Balance_Inicial_Contabilidad BIC
WHERE
    BIC.Id_Plan_Cuentas = $id_plan_cuentas AND BIC.Nit != 0
     ".($centro_costo != false ? "AND BIC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND BIC.Codigo_Cuenta $cond_exluir " : "" )."
ORDER BY BIC.Nit)
UNION ALL
(
SELECT 
    
    M.Nit,
    (
        CASE M.Tipo_Nit
            WHEN 'Cliente' THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = M.Nit LIMIT 1)
            WHEN 'Proveedor' THEN (SELECT IF(Nombre = '' OR Nombre IS NULL,CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Nombre) FROM Proveedor WHERE Id_Proveedor = M.Nit LIMIT 1)
            WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ',Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = M.Nit LIMIT 1)
            WHEN 'Caja_Compensacion' THEN (SELECT Nombre FROM Caja_Compensacion WHERE Nit = M.Nit LIMIT 1)
        END
    ) AS Nombre,
    0 AS Debito_PCGA,
    0 AS Credito_PCGA,
    0 AS Debito_NIIF,
    0 AS Credito_NIIF,
    0 AS Total_Debito_Pcga,
    0 AS Total_Credito_Pcga,
    0 AS Total_Debito_Niif,
    0 AS Total_Credito_Niiff,
    M.Id_Centro_Costo
FROM
Movimiento_Contable M
INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = M.Id_Plan_Cuenta
WHERE
    M.Id_Plan_Cuenta = $id_plan_cuentas AND M.Nit != 0  AND M.Estado != 'Anulado'
    And M.Id_Modulo != 33
     ".($centro_costo != false ? "AND M.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo $cond_exluir  " : "" )."
GROUP BY M.Nit, M.Id_Plan_Cuenta
ORDER BY M.Nit
)

UNION ALL
(
SELECT 
    M.Nit,
    (
        CASE M.Tipo_Nit
            WHEN 'Cliente' THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = M.Nit LIMIT 1)
            WHEN 'Proveedor' THEN (SELECT IF(Nombre = '' OR Nombre IS NULL,CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Nombre) FROM Proveedor WHERE Id_Proveedor = M.Nit LIMIT 1)
            WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ',Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = M.Nit  LIMIT 1)
            WHEN 'Caja_Compensacion' THEN (SELECT Nombre FROM Caja_Compensacion WHERE Nit = M.Nit LIMIT 1)
        END
    ) AS Nombre,
    0 AS Debito_PCGA,
    0 AS Credito_PCGA,
    0 AS Debito_NIIF,
    0 AS Credito_NIIF,
    SUM(M.Debe) AS Total_Debito_Pcga,
    SUM(M.Haber) AS Total_Credito_Pcga,
    SUM(M.Debe_Niif) AS Total_Debito_Niif,
    SUM(M.Haber_Niif) AS Total_Credito_Niif,
    M.Id_Centro_Costo
FROM
Movimiento_Contable M
INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = M.Id_Plan_Cuenta
WHERE
    M.Id_Plan_Cuenta = $id_plan_cuentas AND M.Nit != 0 AND DATE(M.Fecha_Movimiento) BETWEEN '$fecha_ini' AND '$fecha_fin' AND M.Estado != 'Anulado'
      -- And M.Id_Modulo != 33
    ".($centro_costo != false ? "AND M.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo $cond_exluir " : "" )."
GROUP BY M.Nit, M.Id_Plan_Cuenta
ORDER BY M.Nit
)
) r
GROUP BY r.Nit";
//echo $query; exit;

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $resultado = $oCon->getData();
        unset($oCon);

        return $resultado;
}

function getMovimientosCuenta($fecha1, $fecha2, $nit = null, $plan = null)
{
    global $tipo_reporte, $centro_costo,$cond_exluir ;
    
    $rep=($tipo_reporte == "Niif" ? "_Niif" : "");

    $tipo = $tipo_reporte != 'Pcga' ? '_Niif' : '';

    if ($nit === null) {
        $query = "SELECT MC.Id_Plan_Cuenta, MC.Id_Centro_Costo, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe$tipo) AS Debito, SUM(Haber$tipo) AS Credito FROM 
        Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE DATE(Fecha_Movimiento)
        BETWEEN '$fecha1' AND '$fecha2' AND MC.Estado != 'Anulado' 
         ".($centro_costo != false ? "AND MC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo$rep $cond_exluir " : "" )."
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
         ".($centro_costo != false ? "AND MC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo" . $cond_exluir : "" )."
        ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        //echo $query; exit;
        $movimientos = $oCon->getData();
        unset($oCon);
    }


    return $movimientos;
}

function getUltimoDiaMes($fecha_inicio)
{
     //$ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha_inicio)),1,date("Y",strtotime($fecha_inicio)))-1));
    $ultimo_dia_mes = '2018-12-31'; // Modificado 16-07-2019 -- KENDRY

    return $ultimo_dia_mes;
}

function getMovimientosPorTipo($fecha_ini,$fecha_fin,$id_plan_cuenta, $movimiento) {
       global $centro_costo,$cond_exluir;
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
         ".($centro_costo != false ? "AND MC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo $cond_exluir " : "" )."
        GROUP BY MC.Id_Modulo";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $resultado = $oCon->getData();
        unset($oCon);

        return $resultado;
    }
}

function armarTotales($totales) {
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
        ],
        "7" => [
            "saldo_anterior" => isset($totales['clases']['7']) ? $totales['clases']['7']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['7']) ? $totales['clases']['7']['nuevo_saldo'] : 0
        ],
        "8" => [
            "saldo_anterior" => isset($totales['clases']['8']) ? $totales['clases']['8']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['8']) ? $totales['clases']['8']['nuevo_saldo'] : 0
        ],
        "9" => [
            "saldo_anterior" => isset($totales['clases']['9']) ? $totales['clases']['9']['saldo_anterior'] : 0,
            "nuevo_saldo" => isset($totales['clases']['9']) ? $totales['clases']['9']['nuevo_saldo'] : 0
        ]
    ];

    return $cuentas_clases;
}

function getTotal($totales, $tipo) {
    $cuentas_clases = armarTotales($totales);
    $total = 0;

    if ($tipo == 'saldo_anterior') {
        $total = ($cuentas_clases["1"]["saldo_anterior"] - $cuentas_clases["2"]["saldo_anterior"] - $cuentas_clases["3"]["saldo_anterior"]) - ($cuentas_clases["4"]["saldo_anterior"] - $cuentas_clases["5"]["saldo_anterior"] - $cuentas_clases["6"]["saldo_anterior"]- $cuentas_clases["7"]["saldo_anterior"]- $cuentas_clases["8"]["saldo_anterior"]- $cuentas_clases["9"]["saldo_anterior"]);
    } elseif ($tipo == 'nuevo_saldo') {
        $total = ($cuentas_clases["1"]["nuevo_saldo"] - $cuentas_clases["2"]["nuevo_saldo"] - $cuentas_clases["3"]["nuevo_saldo"]) - ($cuentas_clases["4"]["nuevo_saldo"] - $cuentas_clases["5"]["nuevo_saldo"] - $cuentas_clases["6"]["nuevo_saldo"] - $cuentas_clases["7"]["nuevo_saldo"] - $cuentas_clases["8"]["nuevo_saldo"] - $cuentas_clases["9"]["nuevo_saldo"]);
    }

    return $total;
}

?>