<?php


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Contabilizacion Cierre Anio.xls"');
header('Cache-Control: max-age=0'); 

include_once('../../../class/class.querybasedatos.php');
require_once('../../../class/html2pdf.class.php');

$id_registro = ( isset( $_REQUEST['id_registro'] ) ? $_REQUEST['id_registro'] : '' );
$id_funcionario_imprime = ( isset( $_REQUEST['id_funcionario_elabora'] ) ? $_REQUEST['id_funcionario_elabora'] : '' );
$tipo_valor = ( isset( $_REQUEST['tipo_valor'] ) ? $_REQUEST['tipo_valor'] : '' );
$id_modulo = 33;
$titulo = $tipo_valor != '' ? "CONTABILIZACI&Oacute;N NIIF" : "CONTABILIZACI&Oacute;N PCGA";

$cond_adicional = ' AND MC.Detalles NOT LIKE "ACTIVOS-PASIVOS%"';

if (isset($_REQUEST['tipo_rep']) && $_REQUEST['tipo_rep'] == 'act-pas') {
    $cond_adicional = ' AND MC.Detalles LIKE "ACTIVOS-PASIVOS%"';
}


$queryObj = new QueryBaseDatos();

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

$oItem = new complex('Cierre_Contable','Id_Cierre_Contable', $id_registro);
$datos = $oItem->getData();
unset($oItem);

$query = '
SELECT
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    MC.Nit,
    MC.Tipo_Nit,
    MC.Id_Registro_Modulo,
    MC.Documento,
    MC.Debe,
    MC.Haber,
    MC.Debe_Niif,
    MC.Haber_Niif,
    (CASE
        WHEN MC.Tipo_Nit = "Cliente" THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = MC.Nit)
        WHEN MC.Tipo_Nit = "Proveedor" THEN (SELECT Nombre FROM Proveedor WHERE Id_Proveedor = MC.Nit)
        WHEN MC.Tipo_Nit = "Funcionario" THEN (SELECT CONCAT_WS(" ", Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = MC.Nit)
    END) AS Nombre_Cliente,
    "Factura Venta" AS Registro
FROM Movimiento_Contable MC
INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
WHERE
     Id_Modulo = '.$id_modulo.' AND Id_registro_Modulo ='.$id_registro.' '.$cond_adicional.' ORDER BY Debe DESC';

$queryObj->SetQuery($query);
$movimientos = $queryObj->ExecuteQuery('multiple');


$query = '
SELECT
    SUM(MC.Debe) AS Debe,
    SUM(MC.Haber) AS Haber,
    SUM(MC.Debe_Niif) AS Debe_Niif,
    SUM(MC.Haber_Niif) AS Haber_Niif
FROM Movimiento_Contable MC
WHERE
    Id_Modulo = '.$id_modulo.' AND Id_registro_Modulo ='.$id_registro.' '.$cond_adicional;

$queryObj->SetQuery($query);
$movimientos_suma = $queryObj->ExecuteQuery('simple');

$query = '
SELECT
    CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Funcionario
FROM Funcionario
WHERE
    Identificacion_Funcionario ='.$id_funcionario_imprime;

$queryObj->SetQuery($query);
$imprime = $queryObj->ExecuteQuery('simple');

$query = '
SELECT
    CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Funcionario
FROM Funcionario
WHERE
    Identificacion_Funcionario = '.$datos['Identificacion_Funcionario'];

$queryObj->SetQuery($query);
$elabora = $queryObj->ExecuteQuery('simple');

unset($queryObj);


$contenido = '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td colspan="6" align="center" style="font-size:14px"><strong>PRODUCTOS HOSPITALARIOS S.A.</strong></td>
    </tr>
    <tr>
        <td colspan="6" align="center" style="font-size:14px"><strong>Nit: '.$config["NIT"].'</strong></td>
    </tr>
    <tr>
        <td colspan="6" align="center" style="font-size:14px"><strong>'.$titulo.'</strong></td>
    </tr>
    <tr>
        <td colspan="6" align="center" style="font-size:14px"><strong>'.$datos['Codigo'].'</strong></td>
    </tr>
    <tr>
        <td colspan="6" align="center" style="font-size:14px"><strong>Fecha '.fecha($datos['Created_At']).'</strong></td>
    </tr>
    <tr>
            <td bgcolor="#CECECE" style="width:78px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
                <strong>Cuenta '.$tipo_valor.'</strong>
            </td>   
            <td bgcolor="#CECECE" style="width:170px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               <strong>Nombre Cuenta '.$tipo_valor.'</strong>
            </td>
            <td bgcolor="#CECECE" style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               <strong>Documento</strong>
            </td>
            <td bgcolor="#CECECE" style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                <strong>Nit</strong>
            </td>
            <td bgcolor="#CECECE" style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                <strong>Debitos '.$tipo_valor.'</strong>
            </td>
            <td bgcolor="#CECECE" style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                <strong>Cr&eacute;dito '.$tipo_valor.'</strong>
            </td>
        </tr>';

    if (count($movimientos) > 0) {
        
        foreach ($movimientos as $value) {

            if ($tipo_valor != '') {
                $codigo = $value['Codigo_Niif'];
                $nombre_cuenta = $value['Nombre_Niif'];
                $debe = $value['Debe_Niif'];
                $haber = $value['Haber_Niif'];
                $total_debe = $movimientos_suma["Debe_Niif"];
                $total_haber = $movimientos_suma["Haber_Niif"];
            } else {
                $codigo = $value['Codigo'];
                $nombre_cuenta = $value['Nombre'];
                $debe = $value['Debe'];
                $haber = $value['Haber'];
                $total_debe = $movimientos_suma["Debe"];
                $total_haber = $movimientos_suma["Haber"];
            }
        
            $contenido .= '
            <tr>
            <td style="width:78px;padding:4px;text-align:left;border:1px solid #cccccc;">
                '.$codigo.'
            </td>
            <td style="width:150px;padding:4px;text-align:left;border:1px solid #cccccc;">
                '.$nombre_cuenta.'
            </td>
            <td style="width:100px;padding:4px;text-align:right;border:1px solid #cccccc;">
                '.$value["Documento"].'
            </td>
            <td style="width:100px;padding:4px;text-align:right;border:1px solid #cccccc;">
               '.$value['Nombre_Cliente'].' - '.$value["Nit"].'
            </td>
            <td style="width:100px;padding:4px;text-align:right;border:1px solid #cccccc;">
                '.number_format($debe, 2, ",", "").'
            </td>
            <td style="width:100px;padding:4px;text-align:right;border:1px solid #cccccc;">
                '.number_format($haber, 2, ",", "").'
            </td>
        </tr>
            ';
        }

        $contenido .= '
            <tr>
                <td colspan="4" style="padding:4px;text-align:center;border:1px solid #cccccc;">
                    TOTAL
                </td>
                <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    '.number_format($total_debe, 2, ",", "").'
                </td>
                <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    '.number_format($total_haber, 2, ",", "").'
                </td>
            </tr>';
    }

    
    $contenido .= '</table>
    
    <table style="margin-top:10px;" cellpadding="0" cellspacing="0">

        <tr>
            <td style="font-weight:bold;width:170px;border:1px solid #cccccc;padding:4px">
                Elabor&oacute;:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Imprimi&oacute;:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Revis&oacute;:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Aprob&oacute;:
            </td>
        </tr>

        <tr>
            <td style="font-size:10px;width:170px;border:1px solid #cccccc;padding:4px">
            '.$elabora['Nombre_Funcionario'].'
            </td>
            <td style="font-size:10px;width:168px;border:1px solid #cccccc;padding:4px">
            '.$imprime['Nombre_Funcionario'].'
            
            </td>
            <td style="font-size:10px;width:168px;border:1px solid #cccccc;padding:4px">
            
            </td>
            <td style="font-size:10px;width:168px;border:1px solid #cccccc;padding:4px">
            
            </td>
        </tr>

    </table>
    
    ';

    echo $contenido;

?>