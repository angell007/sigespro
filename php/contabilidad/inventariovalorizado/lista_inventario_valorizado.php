<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = "SELECT
    B.Nombre AS Bodega,
    COUNT(DISTINCT(I.Id_Producto)) AS Cant_Producto,
    SUM(Cantidad) AS Cantidad,
    ROUND(
        SUM(
            Cantidad *(
                COALESCE( CP.Costo_Promedio,0 )
            )),
            2
        ) AS Costo
    FROM
        Inventario_Nuevo I
    INNER JOIN Estiba E ON
        E.Id_Estiba = I.Id_Estiba
    INNER JOIN Bodega_Nuevo B ON
        B.Id_Bodega_Nuevo = E.Id_Bodega_Nuevo
    LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = I.Id_Producto
    GROUP BY
        E.Id_Bodega_Nuevo";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$bodegas = $oCon->getData();
unset($oCon);

$totalBodega = 0;
foreach ($bodegas as $value) {
    $totalBodega += $value['Costo'];
}

$query = "SELECT
    PD.Departamento AS Id_Departamento,
    (
    SELECT
        Nombre
    FROM
        Departamento
    WHERE
        Id_Departamento = PD.Departamento
) AS Departamento,
COUNT(DISTINCT(I.Id_Producto)) AS Cant_Producto,
SUM(I.Cantidad) AS Cantidad,
IFNULL(ROUND(SUM(Cantidad * (
                COALESCE( CP.Costo_Promedio,0 )
            )),
2),
0) AS Costo
FROM
    Inventario_Nuevo I
INNER JOIN Punto_Dispensacion PD ON
    I.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = I.Id_Producto
WHERE
  I.Id_Punto_Dispensacion != 0
GROUP BY
    PD.Departamento";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$departamentos = $oCon->getData();
unset($oCon);

$totalDep = 0;
foreach ($departamentos as $i => $dep) {
    $query = "
            SELECT
                PD.Nombre AS Punto,
                COUNT(DISTINCT(I.Id_Producto)) AS Cant_Producto,
                SUM(I.Cantidad) AS Cantidad,
                   IFNULL(ROUND(SUM(Cantidad *(
                                 COALESCE( CP.Costo_Promedio,0 )
                            )),
                2),
                0) AS Costo
            FROM
                Inventario_Nuevo I
            INNER JOIN Punto_Dispensacion PD ON
                I.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
            LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = I.Id_Producto
            WHERE
                 I.Id_Punto_Dispensacion != 0 AND PD.Departamento = $dep[Id_Departamento]
            GROUP BY
                I.Id_Punto_Dispensacion
    ";
    
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $puntos = $oCon->getData();
    unset($oCon);

    $departamentos[$i]['Puntos'] = $puntos;
    $totalDep += $dep['Costo'];
}

$resultado['Bodegas'] = $bodegas;
$resultado['Departamentos'] = $departamentos;
$resultado['totalBodega'] = $totalBodega;
$resultado['totalDep'] = $totalDep;

echo json_encode($resultado);
          
?>