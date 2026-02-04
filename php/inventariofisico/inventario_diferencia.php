<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id_inventario_fisico = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT  GROUP_CONCAT(PIF.Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico , PIF.Id_Inventario_Fisico, PIF.Id_Inventario, P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " LAB: ", P.Laboratorio_Comercial) AS Nombre_Producto, PIF.Lote, PIF.Fecha_Vencimiento, SUM(PIF.Primer_Conteo) AS Cantidad_Encontrada, 
(
    CASE
        WHEN SUM(PIF.Primer_Conteo) < SUM(PIF.Cantidad_Inventario) THEN CONCAT("",SUM(PIF.Primer_Conteo)-(SUM(PIF.Cantidad_Inventario)))
        WHEN SUM(PIF.Primer_Conteo) > SUM(PIF.Cantidad_Inventario) THEN CONCAT("+",(SUM(PIF.Primer_Conteo)-SUM(PIF.Cantidad_Inventario)))
    END
) AS Cantidad_Diferencial, PIF.Cantidad_Inventario, "" AS Cantidad_Final, PIF.Id_Producto FROM Producto_Inventario_Fisico PIF INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto WHERE PIF.Id_Inventario_Fisico='.$id_inventario_fisico . ' 
 GROUP BY PIF.Id_Producto, PIF.Lote, PIF.Fecha_Vencimiento
 HAVING Cantidad_Encontrada!=Cantidad_Inventario
 ORDER BY P.Nombre_Comercial';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos_inventario = $oCon->getData();
unset($oCon);

$listado = [];

foreach ($productos_inventario as $i => $res) {
    $productos_inventario[$i]['Cantidad_Encontrada'] = (int) $res['Cantidad_Encontrada'];
    $productos_inventario[$i]['Cantidad_Inventario'] = (int) $res['Cantidad_Inventario'];
}

$query3='SELECT GROUP_CONCAT(Id_Inventario) as Id_Inventario FROM Producto_Inventario_Fisico WHERE Id_Inventario_Fisico='.$id_inventario_fisico;
$oCon= new consulta();
$oCon->setQuery($query3);
$id_inventarios = $oCon->getData();
unset($oCon);

$query2='SELECT * FROM Inventario_Fisico WHERE Id_Inventario_Fisico='.$id_inventario_fisico;
$oCon= new consulta();
$oCon->setQuery($query2);
$inventario = $oCon->getData();
unset($oCon);
$texto='';
$letras=explode("-",$inventario['Letras']);

if($inventario['Categoria']==12){
    for ($i=0; $i < count($letras) ; $i++) { 
        $or = $i==(count($letras)-1) ? '':' OR ';
        $texto.=' SUBSTRING(P.Principio_Activo, 1, 1)="'.$letras[$i].'"'.$or;
    }
}else{
    for ($i=0; $i < count($letras) ; $i++) { 
        $or = $i==(count($letras)-1) ? '':' OR ';
        $texto.=' SUBSTRING(P.Nombre_Comercial, 1, 1)="'.$letras[$i].'"'.$or;
    }
}
if($inventario['Categoria']!=0){
    
    $query2='SELECT I.Id_Inventario, P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " LAB: ", P.Laboratorio_Comercial) AS Nombre_Producto, 0 AS Cantidad_Encontrada, CONCAT("-",I.Cantidad) AS Cantidad_Diferencial, 0 as Id_Producto_Inventario_Fisico, I.Lote, I.Fecha_Vencimiento, I.Cantidad as Cantidad_Inventario, I.Id_Producto FROM Inventario I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto WHERE I.Cantidad>0 AND  P.Id_Categoria='.$inventario['Categoria'].' AND I.Id_Bodega='.$inventario['Bodega'].' AND Id_Inventario NOT IN('.$id_inventarios['Id_Inventario'].') AND ('.$texto.') ORDER BY Nombre_Comercial ASC';
}else{

    $query2='SELECT I.Id_Inventario, P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " LAB: ", P.Laboratorio_Comercial) AS Nombre_Producto, 0 AS Cantidad_Encontrada, CONCAT("-",I.Cantidad) AS Cantidad_Diferencial, 0 as Id_Producto_Inventario_Fisico, I.Lote, I.Fecha_Vencimiento, I.Cantidad as Cantidad_Inventario, I.Id_Producto FROM Inventario I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto WHERE I.Cantidad>0 AND I.Id_Bodega='.$inventario['Bodega'].' AND Id_Inventario NOT IN('.$id_inventarios['Id_Inventario'].') AND ('.$texto.') ORDER BY Nombre_Comercial ASC';
}


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$productos_faltantes = $oCon->getData();
unset($oCon);

$j=-1;
foreach ($productos_faltantes as $value) {$j++;
    $productos_faltantes[$j]['Id_Inventario_Fisico']=$inventario['Id_Inventario_Fisico'];
    
}

$resultado=array_merge($productos_inventario,$productos_faltantes );


echo json_encode($resultado);


?>