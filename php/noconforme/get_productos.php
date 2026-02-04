<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');
require('../../class/class.guardar_archivos.php');


$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

date_default_timezone_set('America/Bogota');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
 
$productos=GetProductosNoConforme($id);
$encabezado=GetDatos($id);

$respuesta['Productos']=$productos;
$respuesta['Datos']=$encabezado;


echo json_encode($respuesta);

function GetProductosNoConforme($id){
    global $queryObj;
    $query="SELECT 
    PRN.Id_Producto_Remision,
    (PRN.Cantidad-PRN.Cantidad_Reenviada) AS Cantidad_No_Enviada,
    PR.Fecha_Vencimiento,
    PR.Lote,
    PR.Precio,
    PR.Id_Inventario_Nuevo,
    PR.Nombre_Producto,
    PR.Id_Producto, P.Nombre_Comercial, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre, P.Codigo_Cum,P.Laboratorio_Comercial,P.Embalaje, '' as Cantidad, 0 as Seleccionado, PRN.Id_No_Conforme,PRN.Id_Producto_No_Conforme_Remision
    FROM Producto_No_Conforme_Remision PRN
    INNER JOIN Producto_Remision PR ON PRN.Id_Producto_Remision = PR.Id_Producto_Remision
    INNER JOIN Producto P On PRN.Id_Producto=P.Id_Producto
    WHERE PRN.Id_No_Conforme=$id  AND PR.Id_Inventario_Nuevo IS NOT NULL HAVING Cantidad_No_Enviada>0 ";
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');

    return $productos;
}

function GetDatos($id){
    global $queryObj;

    $query="SELECT R.Tipo,R.Tipo_Origen,R.Id_Origen, R.Nombre_Origen,R.Tipo_Destino, R.Id_Destino,R.Nombre_Destino,R.Tipo_Bodega, NC.Codigo FROM No_Conforme NC INNER JOIN Remision R ON NC.Id_Remision=R.Id_Remision WHERE NC.Id_No_Conforme=$id";

    $queryObj->SetQuery($query);
    $datos = $queryObj->ExecuteQuery('simple');

    return $datos;
}


?>





