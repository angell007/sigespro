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

$tipo_p  =  $tipo_reporte == 'Pcga' ? 'Tipo_P' : 'Tipo_Niif';

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

ob_start(); 


        $tipo_balance = strtoupper($tipo);
       


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
        PC.Tipo_P,
        PC.Tipo_Niif
        FROM
        Plan_Cuentas PC
            LEFT JOIN
         (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC 
         ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas ".($centro_costo != false ? "AND Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND Codigo_Cuenta $cond_exluir  " : "" )."
         ".getStrCondiciones()." 
        # AND PC.Codigo_Niif like '151670%'

         GROUP BY PC.Id_Plan_Cuentas
       # HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
        ORDER BY PC.$column";
        echo $query;exit;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $balance = $oCon->getData();
        unset($oCon); 
        //echo $query; exit;
            //show($balance,true);exit;

      
        $acum_saldo_anterior = 0;
        $acum_debito = 0;
        $acum_credito = 0;
        $acum_nuevo_saldo = 0;
        

        foreach ($balance as $i => $value) {

            $codigo = $tipo_reporte == 'Pcga' ? $value['Codigo'] : $value['Codigo_Niif'];
            $nombre_cta = $tipo_reporte == 'Pcga' ? $value['Nombre'] : $value['Nombre_Niif'];
            
            $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
          
            //61359802
           
            $debito = calcularDebito($codigo,$value[$tipo_p],$movimientos, true,null,true);
            
            $credito = calcularCredito($codigo,$value[$tipo_p],$movimientos);
            $nuevo_saldo = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);
            if ($value['Codigo_Niif']=='143705') {
                # code...
        
              show($value);exit;
              show([$debito,$credito,$saldo_anterior,$nuevo_saldo,$i]);exit;

            }
            
            if ($codigo=='613598') {
                # code...
                //echo 'heere';exit;
                show([$codigo,$debito,$credito,$nuevo_saldo,$tipo_p,$value[$tipo_p]],true);
            }
            
            if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
        

                if ($value[$tipo_p] == 'CLASE') {
                    $totales['clases'][$value['Codigo']]['saldo_anterior'] = $saldo_anterior;
                    $totales['clases'][$value['Codigo']]['nuevo_saldo'] = $nuevo_saldo;
                    //La formula para estos campos: 1+2+3+4+5+6+8 (A nivel de cuentas)
                    $totales['debito'] += $debito;
                    $totales['credito'] += $credito;
                    
                }
            }
           // exit;
        }

        $totales['saldo_anterior'] = getTotal($totales,'saldo_anterior');

        $totales['nuevo_saldo'] = getTotal($totales,'nuevo_saldo');
        

        
    } 

        $totales['saldo_anterior'] = getTotal($totales,'saldo_anterior');

        $totales['nuevo_saldo'] = getTotal($totales,'nuevo_saldo');

 


function show($data,$e=false){
   
    echo  json_encode($data);
    if ($e) {
        $myfile = fopen("testing.txt", "w") or die("Unable");
        fwrite($myfile, json_encode($data));
        fclose($myfile);
        exit;

    }
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
    global $tipo_p;

    //echo json_encode($movimientos);exit;
    $value = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
    //$tipo_p  =  $tipo_reporte == 'Pcga' ? 'Tipo_P' : 'Tipo_Niif';

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
        
            $fecha1 = '2019-01-01';
            $fecha2 = strtotime('-1 day', strtotime($fecha_ini)); // Un día antes de la fecha de inicio para sacar el corte de saldo final.
            $fecha2 = date('Y-m-d', $fecha2);
            $movimientos_lista = getMovimientosCuenta($fecha1,$fecha2);
            $codigo = $array[$index][$value];
            $tipo =$array[$index][$tipo_p];
            $debito = calcularDebito($codigo,$tipo,$movimientos_lista);
            $credito = calcularCredito($codigo,$tipo,$movimientos_lista);


            if ($index=='181') {
                # code...
                //show($value,true);//151670    143705
              //show([$debito,$credito,$saldo_anterior]);exit;
        
            }



    }
   /*  echo "<pre>";
        var_dump([$debito,$credito,$saldo_anterior,$naturaleza, $array, $index, $tipo_reporte, $nit , $plan ]);
    echo "</pre>";
    exit; */
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

function calcularDebito($codigo, $tipo_cuenta, $movimientos,$show=false)
{
    $codigos_temp = [];
    $inferiores = [];
    global $tipo_reporte;
    $d=[];
    $mox = [];
    $mix = [];
    foreach ($movimientos as $mov) {
        $cod_mov = $tipo_reporte == 'Pcga' ? $mov['Codigo'] : $mov['Codigo_Niif'];
        $nivel = strlen($cod_mov);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;
        //show($movimientos,true);exit;
        if ($codigo == '613598' && $cod_mov==='61359802') {
            # code...
            //show($value,true);

          //show([$codigo,$nivel, $nivel2, $cod_mov,compararCuenta($codigo, $nivel2, $cod_mov)]);exit;
        }
        if ($cod == '61359802' && $show) {
            # code...
            //show($value,true);
            //echo 'here2';exit;

          // show([$codigo,$nivel,strlen($mov['Codigo_Niif']), $nivel2, $cod_mov,compararCuenta($codigo, $nivel2, $cod_mov)]);exit;
        }
        if (compararCuenta($codigo, $nivel2, $cod_mov)) {
            //echo json_encode([$nivel,$nivel2,$cod_mov,compararCuenta($codigo, $nivel2, $cod_mov)]);      exit;

            $codigos_temp[$cod_mov] = $mov['Debito'];
            while($nivel > $nivel2){

               
                if ($nivel > 2) {
                    $restar_str += 2;

                  

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);

                    if ($cod_superior == '613598' ) {
                        # code...
                        $inferiores[]= $mov;
                        //show($cod_superior,true);
            
                      //show([$codigo, $nivel2, $cod_mov,compararCuenta($codigo, $nivel2, $cod_mov)]);exit;
                    }
                    
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito'];
                        $d[]=[$nivel,$nivel2,$cod_mov, 'movDeb1',$mov['Debito'] ];

                        if ($cod_superior=='613598') {
                            # code...
                            //echo 'her';exit;

                            //echo 'here2';exit;
                                        
                           // show($mov['Debito'],true);
                            $mox[]=$mov;
                        }

                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito'];
                        $d[]=[$nivel,$nivel2,$cod_mov, 'movDeb2',$mov['Debito'] ];

                        if ($cod_superior=='613598') {
                            # code...
                               // echo 'here2';exit;
                           
                            //show($value,true);
                            $mix[]=$mov;
                        }

                    }
                    $nivel -= 2;
                   

                } else {
                    $restar_str += 1;

                    $str = $cod_mov;
                    $count_str = strlen($str);
                    $cod_superior = substr($str,0,$count_str-$restar_str);
                    if (!array_key_exists($cod_superior,$codigos_temp)) {
                        $codigos_temp[$cod_superior] = $mov['Debito'];
                        $d[]=[$nivel,$nivel2,$cod_mov, 'movDeb3',$mov['Debito'] ];

                        if ($cod_superior=='613598') {
                            # code...
            //echo 'here2';exit;
                           
                            //show($value,true);
                            $mox[]=$mov;
                        }

                    } else {
                        $codigos_temp[$cod_superior] += $mov['Debito'];
                        $d[]=[$nivel,$nivel2,$cod_mov, 'movDeb4',$mov['Debito'] ];
                        if ($cod_superior=='613598') {
                            # code...
            //echo 'here2';exit;
                           
                           // show($value,true);
                            $mix[]=$mov;
                        }
                    }
                    $nivel -= 1;
                }
            }
        }

    }
    if ($show ) { 
        # code...
        //show($value,true);
        //show([$mox,$mix,$codigos_temp]);exit;
    }

    if ($codigo == '143705' && $show) {
        # code...
       //show([$codigo,$inferiores,$codigos_temp,$codigos_temp[$codigo]],true);

      //show([$codigo, $nivel2, $cod_mov,compararCuenta($codigo, $nivel2, $cod_mov)]);exit;
    }
    return isset($codigos_temp[$codigo]) ? $codigos_temp[$codigo] : '0';

    
}

function calcularCredito($codigo, $tipo_cuenta, $movimientos)
{
    // return '0'; // Esto es temporal.
    global $tipo_reporte;

    $codigos_temp = [];

    foreach ($movimientos as $mov) {
        $cod_mov = $tipo_reporte == 'Pcga' ? $mov['Codigo'] : $mov['Codigo_Niif'];
        $nivel = strlen($cod_mov);
        $nivel2 = strlen($codigo);
        $cod_superior = '';
        $restar_str = 0;

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
    // echo $naturaleza; exit;
    
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $nuevo_saldo = ( (float)$saldo_anterior + (float)$debito) - (float)$credito;
    } else {
        $nuevo_saldo = ( (float)$saldo_anterior + (float)$credito) - (float)$debito;
    }

    return $nuevo_saldo;
}


function getMovimientosCuenta($fecha1, $fecha2, $nit = null, $plan = null, $show=null)
{
    global $tipo_reporte, $centro_costo,$cond_exluir ;
 
    $tipo = $tipo_reporte != 'Pcga' ? '_Niif' : '';

        $query = "SELECT MC.Id_Plan_Cuenta,PC.Estado, MC.Id_Centro_Costo, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe$tipo) AS Debito, SUM(Haber$tipo) AS Credito FROM 
        Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE DATE(Fecha_Movimiento)
        BETWEEN '$fecha1' AND '$fecha2' AND MC.Estado != 'Anulado'
         ".($centro_costo != false ? "AND MC.Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND PC.Codigo $cond_exluir " : "" )."
        GROUP BY MC.Id_Plan_Cuenta";
        
        //echo $query; exit;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $movimientos = $oCon->getData();
        unset($oCon);

    return $movimientos;
}

function getUltimoDiaMes($fecha_inicio)
{
    // $ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha_inicio)),1,date("Y",strtotime($fecha_inicio)))-1));
    $ultimo_dia_mes = '2018-12-31'; // Modificado 16-07-2019 -- KENDRY

    return $ultimo_dia_mes;
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
        ]
    ];

    return $cuentas_clases;
}

function getTotal($totales, $tipo) {
    $cuentas_clases = armarTotales($totales);
    $total = 0;

    if ($tipo == 'saldo_anterior') {
        $total = ($cuentas_clases["1"]["saldo_anterior"] - $cuentas_clases["2"]["saldo_anterior"] - $cuentas_clases["3"]["saldo_anterior"]) - ($cuentas_clases["4"]["saldo_anterior"] - $cuentas_clases["5"]["saldo_anterior"] - $cuentas_clases["6"]["saldo_anterior"]);
    } elseif ($tipo == 'nuevo_saldo') {
        $total = ($cuentas_clases["1"]["nuevo_saldo"] - $cuentas_clases["2"]["nuevo_saldo"] - $cuentas_clases["3"]["nuevo_saldo"]) - ($cuentas_clases["4"]["nuevo_saldo"] - $cuentas_clases["5"]["nuevo_saldo"] - $cuentas_clases["6"]["nuevo_saldo"]);
    }

    return $total;
}

?>