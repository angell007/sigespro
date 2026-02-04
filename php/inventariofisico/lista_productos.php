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
            IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," (",PRD.Nombre_Comercial, ") ", PRD.Cantidad," ", PRD.Unidad_Medida, " "),CONCAT(PRD.Nombre_Comercial," LAB-",PRD.Laboratorio_Comercial)) as Nombre, 
            PRD.Id_Producto, 
            PRD.Codigo_Cum as Cum, 
            Inventario.Fecha_Vencimiento as Vencimiento, 
            Inventario.Lote as Lote, 
            Inventario.Id_Inventario as Id_Inventario, 
            Inventario.Cantidad, 
            PRD.Laboratorio_Comercial,
            PRD.Codigo_Barras as Codigo_Barras,
            "editable" as Validacion,
            "true" AS Desabilitado
          FROM Producto PRD
          inner join Inventario 
          on PRD.Id_Producto=Inventario.Id_Producto 
          WHERE PRD.Codigo_Barras 
          IS NOT NULL 
          AND PRD.Id_Categoria ='.$idCategoria.' AND Inventario.Id_Bodega='.$idbodega;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);


?>