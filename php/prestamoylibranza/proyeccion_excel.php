<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Proyeccion_Amortizacion.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
require('./funciones.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : false);

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

$oItem = new complex('Prestamo','Id_Prestamo',$id);
$data = $oItem->getData();
unset($oItem);

$oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
$funcionario = $oItem->getData();
unset($oItem);

$contenido = '

<table border="1">
    <tr>
        <td rowspan="4" style="text-align:center">
            <img src="https://'.$_SERVER["SERVER_NAME"].'/assets/images/logo-color.png" style="width:60px;" alt="SIGESAF" />
        </td>
        <td>
        '.$config["Nombre_Empresa"].'
        </td>
        <td rowspan="4" style="text-align:center;font-weight:bold">
            <strong>PROYECCI&Oacute;N AMORTIZACI&Oacute;N</strong> <br>
            <strong>'.fecha($data['Fecha']).'</strong>
        </td>
    </tr>
    <tr>
        <td>
            N.I.T.: '.$config["NIT"].'
        </td>
    </tr>
    <tr>
        <td>
        '.$config["Direccion"].'
        </td>
    </tr>
    <tr>
        <td>
        TEL: '.$config["Telefono"].'
        </td>
    </tr>
</table>

';

$contenido .= '

<table>
    <tr>
        <td>
        <strong>Identificaci&oacute;n Empleado:</strong>
        </td>
        <td>'.number_format($data['Identificacion_Funcionario'],0,"",".").'</td>
    </tr>
    <tr>
        <td>
            <strong>Nombre Empleado: </strong>
        </td>
        <td>
        '.$funcionario['Nombres'].' '.$funcionario['Apellidos'].'
        </td>
    </tr>
    <tr>
        <td>
            <strong>Valor Prestamo:</strong>
        </td>
        <td>'.number_format($data['Valor_Prestamo'],2,",",".").'</td>
    </tr>
    <tr>
        <td>
            <strong>Interes:</strong>
        </td>
        <td>'.number_format($data['Intereses'],2,",","").'%</td>
    </tr>
    <tr>
        <td>
            <strong>Cuotas:</strong>
        </td>
        <td>'.$data['Nro_Cuotas'].'</td>
    </tr>
    <tr>
        <td>
            <strong>Valor Cuotas:</strong>
        </td>
        <td>'.number_format($data['Cuota_Mensual'],2,",",".").'</td>
    </tr>
</table>

';

$proyecciones = proyeccionAmortizacion($data['Valor_Prestamo'],$data['Cuota_Mensual'],$data['Intereses']);

$contenido .= '
    <table style="border-collapse:collapse;margin-top:20px">
        <tr>
            <td colspan="6" bgcolor="#CCCCCC" align="center" border="1"><strong> TABLA PROYECCI&Oacute;N AMORTIZACI&Oacute;N</strong></td>
        </tr>
        <tr>
            <th border="1">Cuota</th>
            <th border="1">Fecha Descuento</th>
            <th border="1">Amortizaci&oacute;n</th>
            <th border="1">Intereses</th>
            <th border="1">Total Cuota</th>
            <th border="1">Saldo</th>
        </tr>
';

foreach ($proyecciones['Proyeccion'] as $i => $value) {
    $contenido .= '
        <tr>
            <td border="1">'.($i+1).'</td>
            <td border="1">'.fecha(calcularFechaDescuento($data['Fecha_Descuento'],($i+1))).'</td>
            <td border="1">'.number_format($value['Amortizacion'],2,",",".").'</td>
            <td border="1">'.number_format($value['Intereses'],2,",",".").'</td>
            <td border="1">'.number_format($value['Valor_Cuota'],2,',','.').'</td>
            <td border="1">'.number_format($value['Saldo'],2,',','.').'</td>
        </tr>
    ';
}

$contenido .= '
<tr>
    <td border="1" colspan="2" align="right"><strong>TOTALES:</strong></td>
    <td border="1"><strong>'.number_format(getTotales($proyecciones['Proyeccion'], 'Amortizacion'),2,",",".").'</strong></td>
    <td border="1"><strong>'.number_format(getTotales($proyecciones['Proyeccion'], 'Intereses'),2,",",".").'</strong></td>
    <td border="1"><strong>'.number_format(getTotales($proyecciones['Proyeccion'], 'Valor_Cuota'),2,",",".").'</strong></td>
    <td border="1"><strong></strong></td>
</tr>

</table>';

echo $contenido;