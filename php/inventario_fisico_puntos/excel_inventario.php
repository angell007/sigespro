<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="INVF294.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$inventario = file_get_contents('./json.txt', FILE_USE_INCLUDE_PATH);

$inventario = json_decode($inventario, true);

$contenido = '
<table border="1" style="border-collapse:collapse">
<tr>
    <th>Producto</th>
    <th>Lab. Comercial</th>
    <th>Lote</th>
    <th>Fecha Vencimiento</th>
    <th>Primer Conteo</th>
</tr>
';

foreach ($inventario as $i => $productos) {
    unset($productos['Lotes'][count($productos['Lotes'])-1]);
    foreach ($productos['Lotes'] as $value) {
        $contenido .= '
            <tr>
                <td>'.$productos['Nombre'].'</td>
                <td>'.$productos['Laboratorio_Comercial'].'</td>
                <td>'.$value['Lote'].'</td>
                <td>'.$value['Fecha_Vencimiento'].'</td>
                <td>'.$value['Cantidad_Encontrada'].'</td>
            </tr>
        ';
    }
}

$contenido .= '</table>';

echo $contenido;

?>