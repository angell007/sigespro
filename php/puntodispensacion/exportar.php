<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Puntos Dispensación.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require('../contabilidad/funciones.php');

$query = "SELECT PT.Id_Punto_Dispensacion AS ID, 
    Nombre, 
    Tipo, 
    (SELECT Nombre FROM Departamento WHERE Id_Departamento = PT.Departamento) AS Departamento,
    (SELECT Nombre FROM Municipio WHERE Id_Municipio = PT.Municipio) AS Municipio,  
    Direccion, 
    Telefono, 
    Estado
FROM Punto_Dispensacion PT";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$encabezado = $resultado[0];

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
            $contenido .= "<td>$valor</td>";
        }
        $contenido .= '</tr>';
    }
    
$contenido .= '
    </tbody>
</table>
';

echo $contenido;


?>