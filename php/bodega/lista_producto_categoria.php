<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idCategoria = ( isset( $_REQUEST['idCategoria'] ) ? $_REQUEST['idCategoria'] : '' );
$idbodega = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT 
            Inventario.Id_Bodega,
            CONCAT( Producto.Principio_Activo, " ", Producto.Presentacion, " ", Producto.Concentracion, " (", Producto.Nombre_Comercial,") ", Producto.Cantidad," ", Producto.Unidad_Medida, " " ) as Nombre, 
            Producto.Id_Producto, 
            Producto.Codigo_Cum as Cum, 
            Inventario.Fecha_Vencimiento as Vencimiento, 
            Inventario.Lote as Lote, 
            Inventario.Id_Inventario as Id_Inventario, 
            Inventario.Cantidad, 
            Producto.Laboratorio_Comercial,
            Producto.Codigo_Barras as Codigo_Barras,
            "editable" as Validacion,
            "true" AS Desabilitado
          FROM Producto 
          inner join Inventario 
          on Producto.Id_Producto=Inventario.Id_Producto 
          WHERE Producto.Codigo_Barras 
          IS NOT NULL 
          AND Producto.Id_Categoria ='.$idCategoria.' AND Inventario.Id_Bodega='.$idbodega;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
