<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex("Orden_Compra_Internacional","Id_Orden_Compra_Internacional",$id);
$encabezado= $oItem->getData();
unset($oItem);

$query = 'SELECT 
                CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ) as Nombre, 
                POCN.*,
                "0" as Rotativo
           FROM Producto_Orden_Compra_Internacional POCN 
           INNER JOIN Producto P 
           ON P.Id_Producto = POCN.Id_Producto 
           WHERE POCN.Id_Orden_Compra_Internacional ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$i=-1;
foreach($productos as $lista){$i++;
$productos[$i]['producto'] = $productos[$i];
    
}

$resultado["Datos"]=$encabezado;
$resultado["Productos"]=$productos;

echo json_encode($resultado);
          
?>