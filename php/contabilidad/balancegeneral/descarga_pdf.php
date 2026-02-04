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


        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">BALANCE GENERAL</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Corte. '.fecha($fecha_corte).'</h5>
        ';

        $column = $tipo_reporte == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
        // $column = $tipo_reporte == 'Pcga' ? 'Codigo_PCGA' : 'Codigo_NIIF';
        $condiciones = getStrCondiciones();

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
                    LEFT JOIN (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas 
            WHERE 
              (PC.$column LIKE '1%' OR PC.$column LIKE '2%' OR PC.$column LIKE '3%') $condiciones 
              
            GROUP BY PC.Id_Plan_Cuentas
            HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
            ORDER BY PC.$column";
      
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $balance = $oCon->getData();
        unset($oCon);

    $contenido = '
    <table cellspacing="0">
    ';

    $cod_temp = '';
    $cod_clase_temp = '';
    $acum_saldos = 0;
    $acum_total_pasivo = 0;
    $total_activo = 0;
    $total_pasivo = 0;
    $total_patrimonio = 0;

    foreach ($balance as $i => $value) {
      $codigo = $tipo_reporte == 'Pcga' ? $value['Codigo'] : $value['Codigo_Niif'];
      $nombre_cuenta = $tipo_reporte == 'Pcga' ? $value['Nombre'] : $value['Nombre_Niif'];

      $nuevo_saldo = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);

      if ($nivel_reporte > 1) { // Solo mostrar cuando se quiere consultar niveles superiores a 1
        if (substr($codigo,0,1) != $cod_clase_temp) { // Para colocar el titulo de la cuenta CLASE.
          /* if ($cod_clase_temp != '') { // Para mostrar los totales de cada cuenta clase.
            $contenido .= '<tr>
            <td style="width:500px;border-left:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right:font-weight:bold;padding:3px" colspan="2">TOTAL '.getNombreCuentaClase($cod_clase_temp).'</td>
            <td style="width:130px;border-right:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right;font-weight:bold;padding:3px">$ '.number_format($acum_saldos,2,",",".").'</td>
          </tr>';
          $acum_saldos = 0; // Resetear el acumulador
          } */
          $contenido .= '
          <tr>
            <td colspan="3"><h4>'.getNombreCuentaClase($codigo).'</h4></td>
          </tr>';
          
          $cod_clase_temp = substr($codigo,0,1);
        }
      } else {
        $acum_saldos = 0; // Resetear el acumulador
      }

      /* if ($nivel_reporte > 2) { // Solo mostrar cuando se quiere consultar niveles superiores a 2
        if (substr($codigo,0,2) != $cod_temp) { // Para colocar el titulo de la cuenta GRUPO.
          $contenido .= '<tr>
          <td colspan="3" style="font-weight:bold;padding:3px">'.getNombreCuentaGrupo($codigo, $tipo_reporte).'</td>
        </tr>';
        $cod_temp = substr($codigo,0,2);
        }
      } */

      if ($nuevo_saldo != 0 && ($value['Movimiento'] == 'S' || $value['Tipo_P'] == 'GRUPO' ) ) {
        if($value['Tipo_P'] == 'GRUPO'){
            $contenido.='
            <tr >
                 <td  style="padding-left:15px "colspan="3"  ><h5 >'.$value['Nombre'].' </h5></td>
            </tr>';
            
            # var_dump($contenido);exit;
        }
          
          #<td style="'.($value['Tipo_P'] != 'CLASE' ? 'width:75px;padding-left:15px' : 'width:100px;').' ">'. $codigo.'</td>
          
        $contenido .= '<tr >
        <td style="width:75px;padding-left:15px">'. $codigo.'</td>
        <td style="width:490px;">'.$nombre_cuenta.'</td>
        <td style="width: 120px;text-align:right">$ '.number_format($nuevo_saldo,2,",",".").'</td>
      </tr>';
   
      
       // if($value['Tipo_P'] == 'GRUPO'){
          //    exit;
     //   }
      
   /*   if($value['Tipo_P'] == 'GRUPO'){
              $contenido.='';
          }*/
          
        $acum_saldos += $nuevo_saldo;
        if ($codigo === '1') {
          $total_activo += $nuevo_saldo;
        }
        if ($codigo === '2') { // Para acumular el total de cuenta pasivo.
            $total_pasivo += $nuevo_saldo;
        } 
        if($codigo === '3') {
            $total_patrimonio += $nuevo_saldo;
        }
      }

      if ($i == (count($balance)-1)) { // Para mostrar el total de la ultima cuenta clase.
        $contenido .= '<tr>
          <td style="width:500px;border-left:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right:font-weight:bold;padding:3px" colspan="2">TOTAL PATRIMONIO</td>
          <td style="width:130px;border-right:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right;font-weight:bold;padding:3px">$ '.number_format($total_patrimonio,2,",",".").'</td>
        </tr>';
      }
      
    }

    $resultado_ejercicio = saldoResultadoEjercicio($tipo_reporte);
    $total_patrimonio_utilidad_ejercicio = $total_patrimonio + $resultado_ejercicio;
    $total_pasivo_y_patrimonio = $total_patrimonio + $total_patrimonio_utilidad_ejercicio;

    $contenido .= '<tr>
          <td style="width:500px;border-left:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right:font-weight:bold;padding:3px" colspan="2">RESULTADO EJERCICIO</td>
          <td style="width:130px;border-right:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right;font-weight:bold;padding:3px">$ '.number_format($resultado_ejercicio,2,",",".").'</td>
        </tr>';
    $contenido .= '<tr>
          <td style="width:500px;border-left:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right:font-weight:bold;padding:3px" colspan="2">TOTAL PATRIMONIO CON LA UTILIDAD DEL EJERCICIO</td>
          <td style="width:130px;border-right:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right;font-weight:bold;padding:3px">$ '.number_format($total_patrimonio_utilidad_ejercicio,2,",",".").'</td>
        </tr>';
    $contenido .= '<tr>
          <td style="width:500px;border-left:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right:font-weight:bold;padding:3px" colspan="2">TOTAL PASIVO Y PATRIMONIO</td>
          <td style="width:130px;border-right:1px solid #000;border-bottom:1px solid #000;border-top:1px solid #000;text-align:right;font-weight:bold;padding:3px">$ '.number_format($total_pasivo_y_patrimonio,2,",",".").'</td>
        </tr>';

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
#echo $contenido;exit;
try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

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
        $fecha1 = '2019-01-01';
        $fecha2 = $fecha_corte;
        $movimientos_lista = getMovimientosCuenta($fecha1,$fecha2);
        $codigo = $array[$index][$value];
        $tipo =$array[$index]['Tipo_P'];
        $debito = calcularDebito($codigo,$tipo,$movimientos_lista);
        $credito = calcularCredito($codigo,$tipo,$movimientos_lista);
        $saldo_anterior = calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito);
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
    $naturaleza = $saldo['Naturaleza'];
    
    ## Calculando saldo anterior

    $saldo_anterior = 0;
    $tipo_reporte = strtoupper($tipo_reporte);
    if ($saldo['Naturaleza'] == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $saldo_anterior = $saldo["Debito_$tipo_reporte"] - $saldo["Credito_$tipo_reporte"];
    } else {
        $saldo_anterior = $saldo["Credito_$tipo_reporte"] - $saldo["Debito_$tipo_reporte"];
    }
    # ---

    $fecha1 = date('Y-m-d', strtotime($fecha_corte));

    # VALIDACIÓN POR SI LA FECHA DE INICIO NO ES EL DÍA UNO (1) DEL MES Y SE TOQUE SACAR EL SALDO DE LA DIFERENCIA DEL ULTIMO BALANCE INICIAL.

    if ($fecha1 != '2019-01-01') { 
          $fecha1 = '2019-01-01';
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
    $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre, PC.Codigo_Niif, PC.Nombre_Niif, SUM(Debe) AS Debito, SUM(Haber) AS Credito FROM Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE DATE(Fecha_Movimiento) BETWEEN '$fecha1' AND '$fecha2' AND MC.Estado != 'Anulado' GROUP BY MC.Id_Plan_Cuenta";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $movimientos = $oCon->getData();
    unset($oCon);

    return $movimientos;
}

?>