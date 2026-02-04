<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Centros_Costos.xls"');
header('Cache-Control: max-age=0');

require_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');

$query = "SELECT 
CAST(CC.Codigo AS CHAR(60)) as Codigo,
CC.Nombre AS Centro_Costo,
(CASE Id_Centro_Padre
    WHEN 0 THEN 'Sin Padre'
    ELSE (SELECT 
            Nombre
        FROM
            Centro_Costo
        WHERE
            Id_Centro_Costo = CC.Id_Centro_Padre)
END) AS Centro_Padre,
IF(CC.Id_Tipo_Centro != 0, (SELECT Nombre FROM Tipo_Centro WHERE Id_Tipo_Centro = CC.Id_Tipo_Centro), '') AS Tipo_Centro,
(CASE CC.Id_Tipo_Centro
    WHEN
        1
    THEN
        (SELECT 
                Nombre
            FROM
                Cliente
            WHERE
                Id_Cliente = CC.Valor_Tipo_Centro)
    WHEN
        2
    THEN
        (SELECT 
                Nombre
            FROM
                Departamento
            WHERE
                Id_Departamento = CC.Valor_Tipo_Centro)
    WHEN
        3
    THEN
        (SELECT 
                Nombre
            FROM
                Punto_Dispensacion
            WHERE
                Id_Punto_Dispensacion = CC.Valor_Tipo_Centro)
    WHEN
        4
    THEN
        (SELECT 
                Nombre
            FROM
                Municipio
            WHERE
                Id_Municipio = CC.Valor_Tipo_Centro)
    WHEN
        5
    THEN
        (SELECT 
                Nombre
            FROM
                Zona
            WHERE
                Id_Zona = CC.Valor_Tipo_Centro)
    ELSE ''
END) AS Asignado_A,
CC.Estado
FROM
sigesproph_db.Centro_Costo CC
ORDER BY CC.Codigo";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$centros = $oCon->getData();
unset($oCon);

$contenido = '
<table>
    <thead style="border-collapse: collapse;border:1px solid #000">
        <tr>
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Centro Padre</th>
            <th>Tipo Centro</th>
            <th>Asignado a</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>';
    foreach ($centros as $value) {
$contenido .= '<tr>
                    <td>'.$value['Codigo'].'</td>
                    <td>'.$value['Centro_Costo'].'</td>
                    <td>'.$value['Centro_Padre'].'</td>
                    <td>'.$value['Tipo_Centro'].'</td>
                    <td>'.$value['Asignado_A'].'</td>
                    <td>'.$value['Estado'].'</td>
                </tr>';
    }
$contenido .='
</tbody>
</table>
';

echo $contenido;



?>