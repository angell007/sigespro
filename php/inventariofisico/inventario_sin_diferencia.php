<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id_inventario_fisico = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT  PIF.Id_Producto_Inventario_Fisico as Id_Producto_Inventario_Fisico , PIF.Id_Inventario_Fisico, PIF.Id_Inventario, P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " LAB: ", P.Laboratorio_Comercial) AS Nombre_Producto, PIF.Lote, PIF.Fecha_Vencimiento, SUM(PIF.Primer_Conteo) AS Cantidad_Encontrada, 
(
    CASE
        WHEN SUM(PIF.Primer_Conteo) < SUM(PIF.Cantidad_Inventario) THEN CONCAT("<",(SUM(PIF.Cantidad_Inventario)-SUM(PIF.Primer_Conteo)))
        WHEN SUM(PIF.Primer_Conteo) > SUM(PIF.Cantidad_Inventario) THEN CONCAT(">",(SUM(PIF.Primer_Conteo)-SUM(PIF.Cantidad_Inventario)))
    END
) AS Cantidad_Diferencial, SUM(PIF.Cantidad_Inventario) as Cantidad, PIF.Cantidad_Inventario, "" AS Cantidad_Final FROM Producto_Inventario_Fisico PIF INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto WHERE PIF.Id_Inventario_Fisico='.$id_inventario_fisico . ' 
 GROUP BY PIF.Id_Producto, PIF.Lote
 HAVING Cantidad_Encontrada=Cantidad
 ORDER BY P.Nombre_Comercial';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$listado = [];

foreach ($resultado as $i => $res) {
    $resultado[$i]['Cantidad_Encontrada'] = (int) $res['Cantidad_Encontrada'];
    $resultado[$i]['Cantidad_Inventario'] = (int) $res['Cantidad_Inventario'];
}



echo json_encode($resultado);


?>