<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    $tmpDir = sys_get_temp_dir();
    $logPath = rtrim($tmpDir, "/\\") . '/movimientos_factura_nopos_pdf.log';
    ini_set('log_errors', '1');
    ini_set('error_log', $logPath);
    error_log('[debug] movimientos_factura_nopos_pdf.php start');
    register_shutdown_function(function () use ($logPath) {
        $error = error_get_last();
        if ($error) {
            $message = sprintf(
                '[debug] shutdown error: %s in %s:%d',
                $error['message'],
                $error['file'],
                $error['line']
            );
            error_log($message);
            if (is_writable($logPath)) {
                @file_put_contents($logPath, $message . PHP_EOL, FILE_APPEND);
            }
        }
    });
}

include_once('../../../class/class.querybasedatos.php');
$debug && error_log('[debug] after includes');
$autoloadPath = rtrim($_SERVER['DOCUMENT_ROOT'], "/\\") . '/vendor/autoload.php';
require_once($autoloadPath);
$debug && error_log('[debug] class_exists ' . (class_exists('Spipu\\Html2Pdf\\Html2Pdf') ? 'true' : 'false'));

$id_registro = ( isset( $_REQUEST['id_registro'] ) ? $_REQUEST['id_registro'] : '' );
$id_funcionario_imprime = ( isset( $_REQUEST['id_funcionario_elabora'] ) ? $_REQUEST['id_funcionario_elabora'] : '' );
$tipo_valor = ( isset( $_REQUEST['tipo_valor'] ) ? $_REQUEST['tipo_valor'] : '' );
$titulo = $tipo_valor != '' ? "CONTABILIZACIÓN NIIF" : "CONTABILIZACIÓN PCGA";


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

$oItem = new complex('Factura','Id_Factura', $id_registro);
$datos = $oItem->getData();
unset($oItem);
$debug && error_log('[debug] after factura data');

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$projectRoot = realpath(__DIR__ . '/../../../');
$projectRoot = $projectRoot ? str_replace('\\', '/', $projectRoot) : rtrim($_SERVER['DOCUMENT_ROOT'], "/\\");
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

    $query = '
        SELECT
        PC.Codigo,
        PC.Nombre,
        PC.Codigo_Niif,
        PC.Nombre_Niif,
        MC.Nit,
        MC.Fecha_Movimiento AS Fecha,
        MC.Estado,
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
            MC.Estado = "Activo" AND Id_Modulo IN (12,13,14,17,19,20,36) AND MC.Id_Registro_Modulo ='.$id_registro.' ORDER BY Debe DESC';

            // echo $query; exit;
    $queryObj->SetQuery($query);
    $movimientos = $queryObj->ExecuteQuery('multiple');
    $debug && error_log('[debug] after movimientos ' . count($movimientos));

    if (count($movimientos) === 0) {
        http_response_code(404);
        echo 'No hay movimientos para el registro solicitado.';
        exit;
    }


    $query = '
        SELECT
        SUM(MC.Debe) AS Debe,
        SUM(MC.Haber) AS Haber,
        SUM(MC.Debe_Niif) AS Debe_Niif,
        SUM(MC.Haber_Niif) AS Haber_Niif
        FROM Movimiento_Contable MC
        WHERE
            MC.Estado = "Activo" AND Id_Modulo IN (12,13,14,17,19,20,36) AND Id_registro_Modulo ='.$id_registro;

    $queryObj->SetQuery($query);
    $movimientos_suma = $queryObj->ExecuteQuery('simple');
    $debug && error_log('[debug] after movimientos_suma');

    $query = '
        SELECT
            CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Funcionario
        FROM Funcionario
        WHERE
            Identificacion_Funcionario ='.$id_funcionario_imprime;

    $queryObj->SetQuery($query);
    $imprime = $queryObj->ExecuteQuery('simple');
    $debug && error_log('[debug] after imprime');

    $query = '
        SELECT
            CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Funcionario
        FROM Funcionario
        WHERE
            Identificacion_Funcionario ='.$datos['Id_Funcionario'];

    $queryObj->SetQuery($query);
    $elabora = $queryObj->ExecuteQuery('simple');
    $debug && error_log('[debug] after elabora');

    unset($queryObj);
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">'.$titulo.'</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">Factura No Pos</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">'.$movimientos[0]['Documento'].'</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha '.fecha($movimientos[0]['Fecha']).'</h5>
        ';
        

        $contenido = '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:78px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
                Cuenta '.$tipo_valor.'
            </td>   
            <td style="width:170px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Nombre Cuenta '.$tipo_valor.'
            </td>
            <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Documento
            </td>
            <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Nit
            </td>
            <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Debitos '.$tipo_valor.'
            </td>
            <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Crédito '.$tipo_valor.'
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
                        $ '.number_format($debe, 2, ".", ",").'
                    </td>
                    <td style="width:100px;padding:4px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($haber, 2, ".", ",").'
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
                    $ '.number_format($total_debe, 2, ".", ",").'
                </td>
                <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($total_haber, 2, ".", ",").'
                </td>
            </tr>';
    }
    
    $contenido .= '</table>
    
    <table style="margin-top:10px;" cellpadding="0" cellspacing="0">

        <tr>
            <td style="font-weight:bold;width:170px;border:1px solid #cccccc;padding:4px">
                Elaboró:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Imprimió:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Revisó:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Aprobó:
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

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$projectRoot.'/assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
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

$marca_agua = '';

if ($movimientos[0]['Estado'] == 'Anulado') {
    $marca_agua = 'backimg="'.$projectRoot.'/assets/images/anulada.png"';
}

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm" '.$marca_agua.'>
                <div class="page-content" >'.
                    $cabecera.
                    $contenido.
                    '
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

// var_dump($content);
// exit;

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $debug && error_log('[debug] before writeHTML');
    $html2pdf->writeHTML($content);
    $debug && error_log('[debug] after writeHTML');
    $codigo_archivo = '';
    if (isset($datos["Codigo"]) && $datos["Codigo"] !== '') {
        $codigo_archivo = $datos["Codigo"];
    } else {
        $codigo_archivo = $movimientos[0]["Documento"];
    }
    $direc = $codigo_archivo.'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    if (ob_get_length()) {
        ob_end_clean();
    }
    $debug && error_log('[debug] before Output ' . $direc);
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
    $debug && error_log('[debug] after Output');
}catch(\Spipu\Html2Pdf\Exception\Html2PdfException $e) {
    if ($debug) {
        error_log('[debug] Spipu Html2Pdf exception: ' . $e);
    }
    echo $e;
    exit;
}catch(\Throwable $e) {
    if ($debug) {
        error_log('[debug] Throwable: ' . $e);
    }
    echo $e;
    exit;
}

?>
