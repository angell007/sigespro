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

$query = 'SELECT PC.*,
            PR.Nombre as NombreProveedor
            
           FROM Pre_Compra PC
           INNER JOIN Proveedor PR 
           ON PR.Id_Proveedor = PC.Id_Proveedor
           WHERE PC.Id_Pre_Compra='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$encabezado = $oCon->getData();
unset($oCon);

$query = 'SELECT PR.Id_Proveedor, CONCAT(PR.Nombre," - ",PR.Id_Proveedor) as NombreProveedor
            
           FROM Pre_Compra PC
           INNER JOIN Proveedor PR 
           ON PR.Id_Proveedor = PC.Id_Proveedor
           WHERE PC.Id_Pre_Compra='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$proveedor = $oCon->getData();
unset($oCon);


$query2 = 'SELECT 
                IFNULL(CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ),CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial)) as producto, 
                POCN.Costo as Costo , 
                POCN.Cantidad as Cantidad, 
                0 as Iva, 
                (POCN.Cantidad*POCN.Costo) as Total,
                0 AS Iva_Acu,
                POCN.Id_Producto as Id_Producto,
                P.Cantidad_Presentacion AS Presentacion,
                "0" as Rotativo,P.Embalaje
           FROM Producto_Pre_Compra POCN 
           INNER JOIN Producto P 
           ON P.Id_Producto = POCN.Id_Producto 
           WHERE POCN.Id_Pre_Compra ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

// $i=-1;
// foreach($productos as $lista){$i++;
// $productos[$i]['producto'] = $productos[$i];
    
// }
$i=-1;
foreach ($productos as $value) {$i++;
     $productos[$i]['Total']=number_format($value['Total'],2,".","");
}

$resultado["Datos"]=$encabezado;
$resultado["Productos"]=$productos;
$resultado['Proveedor']=$proveedor;

echo json_encode($resultado);
          
?>