<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Libro_Diario.xls"');
header('Cache-Control: max-age=0'); 

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

//https://sigesproph.com.co/php/contabilidad/libroscontables/libro_diario.php?typeBook=Librio%20Diario&typeReport=PCGA&date=2022-11

include_once('./LibroClass.php');

//params
$type_book = isset($_REQUEST['typeBook']) ? $_REQUEST['typeBook'] : false;

$type_report = isset($_REQUEST['typeReport']) ? $_REQUEST['typeReport'] : false;

$date= isset($_REQUEST['date']) ? $_REQUEST['date'] : false;


$l = new Libro();
$data = $l->getLibroDiario($date,$type_report);

$encabezado = $l->getEncabezado();

$fechas = $fechas;


$contenido_excel = '<table border=1>
    <tr>
        <td colspan="4" align="center"><strong>PRODUCTOS HOSPITALARIOS S.A.</strong></td>
    </tr>
    <tr>
        <td colspan="4" align="center"><strong>Nit: '.$encabezado["NIT"].'</strong></td>
    </tr>
    <tr>
        <td colspan="4" align="center"><strong>Libro Diario por Cta '.$type_report.' </strong></td>
    </tr>
    <tr>
        <td colspan="4" align="center"><strong> '.$fechas.'</strong></td>
    </tr>
    <tr>
        <td colspan="4" align="center"><strong>MOVIMIENTOS</strong></td>
    </tr>
    <tr>
        <td align="center"><strong>CODIGO</strong></td>
        <td align="center"><strong>CUENTA</strong></td>
        <td align="center"><strong>DEBITO</strong></td>
        <td align="center"><strong>CREDITO</strong></td>
    </tr>';

    $subtotal_diario_credit = 0;
    $subtotal_diario_debit = 0;
    $subtotal_mes_debit = 0;
    $subtotal_mes_cred = 0;

    foreach ($data as $key => $value) {
        # code...

        $subtotal_diario_cred += $value['Credito'];
        $subtotal_diario_debit += $value['Debito'];
        
        $subtotal_mes_debit += $value['Credito'];
        $subtotal_mes_cred += $value['Debito'];

        $newDate =  strcmp($value['Fecha']  , $data[$key-1]['Fecha']) !== 0 ;
        if ( $newDate ){
            # code...
            $contenido_excel .="<tr>
                    <td  colspan='4' align='center'> Movimiento del dia $value[Fecha]</td>
            </tr>";
           
        }

        $contenido_excel .="<tr>
        <td align='center'>$value[Codigo]</td>
        <td align='center'>$value[Cuenta]</td>
        <td align='center'>$value[Debito]</td>
        <td align='center'>$value[Credito]</td>
        </tr>";

        if (  strcmp($value['Fecha']  , $data[$key+1]['Fecha']) !== 0 ){
            # code...
            $contenido_excel .="
            <tr>
                    <td  colspan='2' align='center'> Total Movimiento del dia $value[Fecha]</td>
                    <td  colspan='1' align='center'>  $subtotal_diario_debit</td>
                    <td  colspan='1' align='center'>  $subtotal_diario_cred</td>
            </tr>
            <tr></tr>
            
            ";

            $subtotal_diario_cred = 0;
            $subtotal_diario_debit = 0;
            
        }
    }

$contenido_excel .="
            <tr>
                    <td  colspan='2' align='center'> Total Movimiento del Mes </td>
                    <td  colspan='1' align='center'>  $subtotal_mes_debit</td>
                    <td  colspan='1' align='center'>  $subtotal_mes_cred</td>
            </tr>
    
            <tr></tr>
            
            ";
    
$contenido_excel.='</table>';


    
 echo $contenido_excel;
exit;

echo '<pre>';
echo json_encode ($contenido_excel);
?>