<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
$tipoCompra = ( isset( $_REQUEST['compra'] ) ? $_REQUEST['compra'] : '' );

switch($tipoCompra){
    
    case "Nacional":{
        $query = 'SELECT  COUNT(*) as Total_Items
           FROM Producto_Orden_Compra_Nacional POCN 
               INNER JOIN Producto P 
                ON P.Id_Producto = POCN.Id_Producto 
               INNER JOIN Orden_Compra_Nacional OCN
                ON OCN.Id_Orden_Compra_Nacional = POCN.Id_Orden_Compra_Nacional
           WHERE OCN.Codigo ="'.$codigo.'"' ;
           
           $query1 ="SELECT 'Nacional' AS Tipo, Id_Orden_Compra_Nacional AS Id_Orden_Compra, Codigo, Identificacion_Funcionario, Id_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=OCN.Id_Proveedor) AS Proveedor, OCN.Id_Proveedor FROM Orden_Compra_Nacional OCN WHERE Codigo = '".$codigo."'";


            $query2=' SELECT GROUP_CONCAT(POCN.Id_Producto) as Id_Producto,  GROUP_CONCAT(SUBSTRING_INDEX(P.Codigo_Cum,"-",1)) as Cum,POCN.Id_Orden_Compra_Nacional as Id_Orden_Nacional
            FROM Producto_Orden_Compra_Nacional POCN 
                INNER JOIN Producto P 
                 ON P.Id_Producto = POCN.Id_Producto 
                INNER JOIN Orden_Compra_Nacional OCN
                 ON OCN.Id_Orden_Compra_Nacional = POCN.Id_Orden_Compra_Nacional
            WHERE OCN.Codigo ="'.$codigo.'"';
           
        break;
    }
    case "Internacional":{
        $query = 'SELECT COUNT(*) as Total_Items
           FROM Producto_Orden_Compra_Internacional POCN 
               INNER JOIN Producto P 
                ON P.Id_Producto = POCN.Id_Producto 
               INNER JOIN Orden_Compra_Internacional OCN
                ON OCN.Id_Orden_Compra_Internacional = POCN.Id_Orden_Compra_Internacional
           WHERE OCN.Codigo ="'.$codigo.'" GROUP BY POCN.Id_Producto' ;
        
        $query1 ="SELECT 'Internacional' AS Tipo, Id_Orden_Compra_Internacional AS Id_Orden_Compra, Codigo, Identificacion_Funcionario, Id_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=OCI.Id_Proveedor) AS Proveedor, OCI.Id_Proveedor FROM  Orden_Compra_Internacional OCI WHERE Codigo = '".$codigo."'";

        $query2 = 'SELECT GROUP_CONCAT(POCN.Id_Producto) as Id_Producto, GROUP_CONCAT(P.Codigo_Cum) as Cum
        FROM Producto_Orden_Compra_Internacional POCN 
            INNER JOIN Producto P 
             ON P.Id_Producto = POCN.Id_Producto 
            INNER JOIN Orden_Compra_Internacional OCN
             ON OCN.Id_Orden_Compra_Internacional = POCN.Id_Orden_Compra_Internacional
        WHERE OCN.Codigo ="'.$codigo.'" GROUP BY POCN.Id_Producto' ;
        
        break;
    }
}

$oCon= new consulta();
$oCon->setQuery($query);
$res = $oCon->getData();
unset($oCon);


$oCon= new consulta();
$oCon->setQuery($query1);
$res1 = $oCon->getData();
unset($oCon);

$oCon= new consulta();
$oCon->setQuery($query2);
$id_productos = $oCon->getData();
unset($oCon);

$resultado['encabezado'] = $res1;
$resultado['Items'] = $res['Total_Items'];
$resultado['Productos']=$id_productos;



echo json_encode($resultado);
          
?>