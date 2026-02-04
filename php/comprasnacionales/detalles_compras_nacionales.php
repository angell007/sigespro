<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

/*$oItem = new complex("Orden_Compra_Nacional","Id_Orden_Compra_Nacional",$id);
$encabezado= $oItem->getData();
unset($oItem);*/

$query = 'SELECT CN.*,
            PR.Nombre as NombreProveedor
            
           FROM Orden_Compra_Nacional CN 
           INNER JOIN Proveedor PR 
           ON PR.Id_Proveedor = CN.Id_Proveedor
           WHERE CN.Id_Orden_Compra_Nacional='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$encabezado = $oCon->getData();
unset($oCon);


$query2 = 'SELECT 
               POCN.*,
               CONCAT(IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) , "\nCUM:",PRD.Codigo_Cum) as Nombre_Producto, 
               
               PRD.Embalaje,PRD.Nombre_Comercial,
                POCN.Costo as Costo , 
                POCN.Cantidad as Cantidad, 
                POCN.Iva as Iva, 
                PRD.Codigo_Barras,
                PRD.Codigo_Cum,
                POCN.Total as Total,
                POCN.Id_Producto as Id_Producto,
                PRD.Cantidad_Presentacion AS Presentacion,
                "0" as Rotativo, 
                (Select Costo_Promedio from Costo_Promedio Where Id_Producto = PRD.Id_Producto) as Costo_Actual
                
           FROM Producto_Orden_Compra_Nacional POCN 
           INNER JOIN Producto PRD 
           ON PRD.Id_Producto = POCN.Id_Producto 
           WHERE POCN.Id_Orden_Compra_Nacional ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

// $i=-1;
// foreach($productos as $lista){$i++;
// $productos[$i]['producto'] = $productos[$i];
    
// }

$resultado["Datos"]=$encabezado;
$resultado["Productos"]=$productos;

echo json_encode($resultado);
          
?>