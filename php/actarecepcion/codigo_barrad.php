<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : false;
$orden = isset($_REQUEST['orden']) ? $_REQUEST['orden'] : false;
        
$query = "SELECT Id_Producto, Codigo_Cum FROM Producto WHERE Codigo_Barras LIKE '$codigo%' AND Estado ='Activo'";
  
$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);


$query = 'SELECT Id_Orden_Compra_Nacional, Codigo FROM Orden_Compra_Nacional WHERE Codigo="'.$orden.'"';
          
$oCon= new consulta();
$oCon->setQuery($query);
$orden = $oCon->getData();
unset($oCon);

$cum=explode("-",$resultado['Codigo_Cum']);

$condicion='';
if($orden['Id_Orden_Compra_Nacional']){
    $condicion.= "  AND POC.Id_Orden_Compra_Nacional=".$orden['Id_Orden_Compra_Nacional'];
}


if($resultado['Id_Producto'] && $orden['Id_Orden_Compra_Nacional'] ){
    $query=' SELECT IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto, P.Codigo_Cum as Codigo_CUM,  P.Imagen AS Foto,"No" as Eliminado,
    P.Id_Categoria,  P.Id_Subcategoria, P.Peso_Presentacion_Regular AS Peso,P.Id_Producto,P.Nombre_Comercial, P.Laboratorio_Comercial,

    IFNULL((SELECT POC.Id_Producto_Orden_Compra_Nacional FROM Producto_Orden_Compra_Nacional POC
    INNER JOIN Producto P ON POC.Id_Producto=P.Id_Producto
     WHERE P.Codigo_Cum LIKE "%'.$cum[0].'%" '.$condicion.' LIMIT 1 ),0) as Id_Producto_Orden_Compra, IFNULL(P.Invima,"No Tiene") as Invima,

    IFNULL((SELECT SUM(POC.Cantidad) FROM Producto_Orden_Compra_Nacional POC WHERE POC.Id_Producto='.$resultado['Id_Producto'].$condicion.' GROUP BY POC.Id_Producto ),0) as CantidadProducto,
    IFNULL((SELECT POC.Costo FROM Producto_Orden_Compra_Nacional POC WHERE POC.Id_Producto='.$resultado['Id_Producto'].$condicion.' GROUP BY POC.Id_Producto ),0) as CostoProducto, P.Embalaje, IF(P.Gravado="Si",19,0) AS Impuesto
    FROM Producto P WHERE P.Id_Producto='.$resultado['Id_Producto'];
}

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

if($resultado['Id_Producto']!=$resultado['Id_Producto_Orden_Compra'] && $resultado['Id_Producto_Orden_Compra']!=0){
    $resultado['Diferente']=true;
}
$prod[0]['Precio']='0';
$prod[0]['Cantidad']='0';
$prod[0]['Fecha_Vencimiento']='';
$prod[0]['Impuesto']=$resultado['Impuesto'];
$prod[0]['Subtotal']=0;
$prod[0]['Lote']='';
$prod[0]['Codigo_CUM']=$resultado['Codigo_CUM'];
$prod[0]['Factura']='';
$prod[0]['Id_Producto']=$resultado['Id_Producto'];
$prod[0]['Id_Producto_Orden_Compra']=$resultado['Id_Producto_Orden_Compra'];
$prod[0]['Codigo_Compra']=$orden['Codigo'];
$prod[0]['Required']=true;
$prod[0]['No_Conforme']=0;
$resultado['producto']=$prod;



          
echo json_encode($resultado);


?>