<?php
//header('Content-Type: application/json');

ini_set('max_execution_time', 3600);
ini_set('memory_limit','256M');

require_once("/home/sigespro/public_html/config/start.inc_cron.php");
include_once("/home/sigespro/public_html/class/class.lista.php");
include_once("/home/sigespro/public_html/class/class.complex.php");
include_once("/home/sigespro/public_html/class/class.consulta.php");
require_once('/home/sigespro/public_html/class/class.configuracion.php');
date_default_timezone_set('America/Bogota'); 

/* 
    ***ADVERTENCIA***
    ESTE SCRIPT SE DEBE EJCUTAR UNICAMENTE CUANDO EL CRON JOB FALLA,
    DEBEN ESTAR ACTULIZADAS LAS TABLAS TEMPORALES CON LA INFORMACIÓN DEL MES A SUBIR
    **Inventario_Valorizado_Temp
    **Costo_Promedio_Temp
    
*/
#Fecha del primer dia del mes a actualizar
$date = '2021-09-01 00:00:02';

$query = 'INSERT INTO Inventario_Valorizado (Fecha_Documento,Estado)
            VALUES ( "'.$date.'"  , "Activo")';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->createData();
$id_valorizado = $oCon->getID();
unset($oCon);

$query = 'SELECT
    if( E.Id_Bodega_Nuevo != 0 , "Bodega_Nuevo" , "Punto_Dispensacion" ) as Tipo_Origen,
    if( E.Id_Bodega_Nuevo != 0 , E.Id_Bodega_Nuevo , E.Id_Punto_Dispensacion )  AS Id_Origen ,
    I.Id_Inventario_Nuevo,
    IFNULL(C.Costo_Promedio,0) AS Costo,
    I.Id_Producto,
    I.Cantidad
    #COALESCE( (SELECT CT.Nombre FROM Categoria_Nueva CT WHERE CT.Id_Categoria_Nueva= SUB.Id_Categoria_Nueva ) , " ") AS Categoria_Nueva,
    #COALESCE( SUB.Nombre, " ") AS Subcategoria
    
    FROM Inventario_Temp I
    INNER JOIN Producto PRD ON PRD.Id_Producto = I.Id_Producto
    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba  
    LEFT JOIN Costo_Promedio_Temp C ON C.Id_Producto = I.Id_Producto
   
    #LEFT JOIN Subcategoria SUB ON SUB.Id_Subcategoria = PRD.Id_Subcategoria  
    
';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$inventario = $oCon->getData();
unset($oCon);

foreach($inventario as $inv){

    $oItem  = new complex('Descripcion_Inventario_Valorizado','Id_Inventario_Valorizado');
    $oItem->Id_Inventario_Valorizado = $id_valorizado;
    $oItem->Id_Origen = $inv['Id_Origen'];
    $oItem->Tipo_Origen = $inv['Tipo_Origen'];
    $oItem->Costo_Promedio = $inv['Costo'];
    $oItem->Id_Producto = $inv['Id_Producto'];
    $oItem->Cantidad = $inv['Cantidad'];
    $oItem->Id_Inventario_Nuevo = $inv['Id_Inventario_Nuevo'];
    $oItem->save();
    unset($oItem);
}

echo 'finalizó';