<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$id_producto = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$id_origen = ( isset( $_REQUEST['id_origen'] ) ? $_REQUEST['id_origen'] : '' );
$grupo = ( isset( $_REQUEST['id_grupo_estiba'] ) ? $_REQUEST['id_grupo_estiba'] : '' );
switch($tipo){
    case "Bodega_Nuevo":{
            $query='SELECT I.Id_Inventario_Nuevo, I.Fecha_Vencimiento, I.Lote,  (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Cantidad,
                E.Nombre AS Nombre_Estiba
        FROM Inventario_Nuevo I
        INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
        INNER JOIN Bodega_Nuevo B ON B.Id_Bodega_Nuevo = E.Id_Bodega_Nuevo
        WHERE B.Id_Bodega_Nuevo='.$id_origen.'
         AND I.Id_Producto='.$id_producto.
         ' AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0 
           AND E.Id_Grupo_Estiba = '.$grupo.' 
           ORDER BY I.Fecha_Vencimiento ASC ';       
        
        break;
    }
    case "Punto_Dispensacion":{
        $query='SELECT I.Id_Inventario_Nuevo, I.Fecha_Vencimiento, I.Lote, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Cantidad
        FROM Inventario_Nuevo I
        WHERE I.Id_Punto_Dispensacion='.$id_origen.' AND I.Id_Producto='.$id_producto.' AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)>0 
        ORDER BY I.Fecha_Vencimiento ASC'; 
        break;
    }
}

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$lotes= $oCon->getData();
unset($oCon);


echo json_encode($lotes);

?>