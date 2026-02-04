<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Funcionarios.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require('../contabilidad/funciones.php');

$tipo = $_REQUEST['Tipo'];

$resultado = getListaTerceros($tipo);
$encabezado = $resultado[0];
$style='mso-number-format:"@";';
$contenido = '
<table border="1" style="border-collapse: collapse;">
    <thead>';
    $contenido .= '<tr>';
    foreach ($encabezado as $nombre => $value) {
        $contenido .= "<th>$nombre</th>";
    }
    $contenido .= '</tr>';
    
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

echo $contenido;


?>