<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );



$query = 'SELECT OCI.*,B.Nombre as Nombre_Bodega, P.Nombre as Nombre_Proveedor, CONCAT(F.Nombres," ",F.Apellidos) as Nombre_Funcionario
                
           FROM Orden_Compra_Internacional OCI 
           INNER JOIN Bodega B 
           ON  B.Id_Bodega = OCI.Id_Bodega 
           INNER JOIN Proveedor P
           ON P.Id_Proveedor = OCI.Id_Proveedor 
           INNER JOIN Funcionario F
           ON OCI.Identificacion_Funcionario=F.Identificacion_Funcionario
           WHERE OCI.Id_Orden_Compra_Internacional ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$ordencompra = $oCon->getData();
unset($oCon);


$query2 = 'SELECT P.Principio_Activo as producto,
                   POCI.Costo,
                   POCI.Cantidad,
                   POCI.Total,
                   POCI.Numero_Carton,
                   POCI.Cantidad_Carton,
                   POCI.Caja_Ancho,
                   POCI.Caja_Alto,
                   POCI.Caja_Largo,
                   POCI.Caja_Volumen
      
          FROM Producto_Orden_Compra_Internacional POCI
          INNER JOIN  Producto P
          ON  P.Id_Producto= POCI.Id_Producto
          WHERE POCI.Id_Orden_Compra_Internacional ='.$id;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productorden = $oCon->getData();
unset($oCon);
$i=-1;
foreach($productorden as $productord){$i++;
$total+=$productord["Total"];
}


$resultado["OrdenCompra"]=$ordencompra;
$resultado["Productos"]=$productorden;
$resultado["total"]=$total;

echo json_encode($resultado);
          
?>