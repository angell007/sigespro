<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );


$query = 'SELECT  P.Nombre_Comercial,I.Id_Inventario_Inicial, R.Id_Reporte_Inventario, I.Lote, I.Cantidad , I.Fecha_Vencimiento,R.Cantidad as Cantidad_Reconteo, R.Tipo,R.Cantidad_Final,
IF(CONCAT( P.Principio_Activo, " ",
        P.Presentacion, " ",
        P.Concentracion, " (", P.Nombre_Comercial,") ",
        P.Cantidad," ",
        P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )="" OR CONCAT( P.Principio_Activo, " ",
        P.Presentacion, " ",
        P.Concentracion, " (", P.Nombre_Comercial,") ",
        P.Cantidad," ",
        P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ) IS NULL, CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial), CONCAT( P.Principio_Activo, " ",
        P.Presentacion, " ",
        P.Concentracion, " (", P.Nombre_Comercial,") ",
        P.Cantidad," ",
        P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )) as Nombre, P.Nombre_Comercial, P.Laboratorio_Comercial, P.Id_Producto, P.Embalaje FROM Inventario_Inicial I
        INNER JOIN Producto P
        ON I.Id_Producto=P.Id_Producto
        INNER JOIN Reporte_Inventario R
        On I.Id_Inventario_Inicial=R.Id_Inventario
        WHERE I.Id_Bodega='.$id.' 
        UNION SELECT  P1.Nombre_Comercial,I1.Id_Inventario_Inicial, R1.Id_Reporte_Inventario, I1.Lote, I1.Cantidad , I1.Fecha_Vencimiento,R1.Cantidad as Cantidad_Reconteo, R1.Tipo,R1.Cantidad_Final,
IF(CONCAT( P1.Principio_Activo, " ",
        P1.Presentacion, " ",
        P1.Concentracion, " (", P1.Nombre_Comercial,") ",
        P1.Cantidad," ",
        P1.Unidad_Medida, " LAB-", P1.Laboratorio_Comercial )="" OR CONCAT( P1.Principio_Activo, " ",
        P1.Presentacion, " ",
        P1.Concentracion, " (", P1.Nombre_Comercial,") ",
        P1.Cantidad," ",
        P1.Unidad_Medida, " LAB-", P1.Laboratorio_Comercial ) IS NULL, CONCAT(P1.Nombre_Comercial," LAB-", P1.Laboratorio_Comercial), CONCAT( P1.Principio_Activo, " ",
        P1.Presentacion, " ",
        P1.Concentracion, " (", P1.Nombre_Comercial,") ",
        P1.Cantidad," ",
        P1.Unidad_Medida, " LAB-", P1.Laboratorio_Comercial )) as Nombre, P1.Nombre_Comercial, P1.Laboratorio_Comercial, P1.Id_Producto, P1.Embalaje FROM Inventario_Inicial I1
        INNER JOIN Producto P1
        ON I1.Id_Producto=P1.Id_Producto
        LEFT JOIN Reporte_Inventario R1
        On I1.Id_Inventario_Inicial=R1.Id_Inventario
        WHERE I1.Id_Bodega='.$id.' AND I1.Estiba IS NULL
        ORDER BY Cantidad_Final ASC, Nombre ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$i=-1;
foreach($productos as $producto){$i++;
    if($producto['Tipo']=='' && $producto['Cantidad_Reconteo']==''){
        $productos[$i]['Cantidad_Reconteo']=0;
    }elseif($producto['Tipo']=='faltante'){
        $vari=$producto['Cantidad']-$producto['Cantidad_Reconteo'];
        $productos[$i]['Cantidad_Reconteo']=$vari;
    }elseif($producto['Tipo']=='sobrante'){
        $vari=$producto['Cantidad']+$producto['Cantidad_Reconteo'];
        $productos[$i]['Cantidad_Reconteo']=$vari;
    }
    $productos[$i]['Cantidad_Ingresada']='';
}
echo json_encode($productos);

?>