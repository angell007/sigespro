<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require('../contabilidad/funciones.php');

$tipo = $_REQUEST['Tipo'];

$resultado = getListaTerceros($tipo);
$encabezado = $resultado[0];

$contenido = '
<table border="1" style="border-collapse: collapse;">
    <thead>';
    $contenido .= '<tr>';
    foreach ($encabezado as $nombre => $value) {
        $contenido .= "<th>$nombre</th>";
    }
    $contenido .= '</tr>';
    
$style='mso-number-format:"@";';
$contenido .='
    </thead>
    <tbody>';

    foreach ($resultado as $key => $value) {
        $contenido .= '<tr>';
        foreach ($value as $valor) {
             $contenido .= "<td style='$style'>$valor</td>";
        }
        $contenido .= '</tr>';
    }
    
$contenido .= '
    </tbody>
</table>
';
try {
    header("Content-Description: PHP Generated Data");
    header('Content-Type: application/x-msexcel');
    header('Content-Disposition: attachment;filename="Clientes.xls"');
    header('Cache-Control: max-age=0');
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    echo $contenido;
} catch (\Throwable $th) {
    echo $th->getMessage();
}
exit;



?>