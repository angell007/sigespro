<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_acta = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$tipo = (isset($_REQUEST['Tipo'])) ? $_REQUEST['Tipo'] : false;


$query = 'SELECT AR.*, (SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ") FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Factura, B.Nombre as Nombre_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor, (SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra
FROM Acta_Recepcion AR
INNER JOIN Bodega B
ON AR.Id_Bodega=B.Id_Bodega
WHERE AR.Id_Acta_Recepcion='.$id_acta;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

$query = 'SELECT Factura, Fecha_Factura, Archivo_Factura FROM Factura_Acta_Recepcion
WHERE Id_Acta_Recepcion='.$id_acta;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$facturas = $oCon->getData();
unset($oCon);

if (!$tipo) {
    $query3 = 'SELECT AR.*, 
B.Nombre as Nombre_Bodega, 
P.Nombre as NombreProveedor, P.Direccion as DireccionProveedor, P.Telefono as TelefonoProveedor,
(SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra
FROM Acta_Recepcion AR
INNER JOIN Bodega B
ON AR.Id_Bodega=B.Id_Bodega
INNER JOIN Proveedor P
On P.Id_Proveedor = AR.Id_Proveedor
WHERE AR.Id_Acta_Recepcion='.$id_acta;
} else {
    $query3 = 'SELECT AR.*, 
PD.Nombre as Nombre_Bodega, 
P.Nombre as NombreProveedor, P.Direccion as DireccionProveedor, P.Telefono as TelefonoProveedor,
(SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra
FROM Acta_Recepcion AR
INNER JOIN Punto_Dispensacion PD
ON AR.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
INNER JOIN Proveedor P
On P.Id_Proveedor = AR.Id_Proveedor
WHERE AR.Id_Acta_Recepcion='.$id_acta;
}



$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query3);
$datos2 = $oCon->getData();
unset($oCon);


$query2 = 'SELECT P.*, PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,
IFNULL(POC.Cantidad,0) as Cantidad_Solicitada
FROM Producto_Acta_Recepcion P
INNER JOIN Producto PRD
ON P.Id_Producto=PRD.Id_Producto
LEFT JOIN Producto_Orden_Compra_Nacional POC
ON POC.Id_Producto_Orden_Compra_Nacional = P.Id_Producto_Orden_compra
WHERE P.Id_Acta_Recepcion='.$id_acta;
      
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$productos_acta = $oCon->getData();
unset($oCon);


$id="";
$lotes=[];
$i=-1;
$j=-1;
$k=-1;
$subtotal=0;
$impuesto=0;
$total=0;
foreach($productos_acta as $prod){  $j++;
    if($id!=$prod["Id_Producto"]){
        $id=$prod["Id_Producto"];
        if($j>0){ $k++;
            $productos_final[$k]=$productos_acta[$j-1];
            $productos_final[$k]["Lotes"]=$lotes;
        }
        $lotes=[];
        $i=0;
        $lotes[$i]["Lote"]=$prod["Lote"];
        $lotes[$i]["Fecha_Vencimiento"]=$prod["Fecha_Vencimiento"];
        $lotes[$i]["Cantidad"]=(INT)$prod["Cantidad"];
        $lotes[$i]["Precio"]=$prod["Precio"];
        $lotes[$i]["Impuesto"]=(INT)$prod["Impuesto"];
        $lotes[$i]["Subtotal"]=$prod["Subtotal"];
    }else{ $i++;
        $lotes[$i]["Lote"]=$prod["Lote"];
        $lotes[$i]["Fecha_Vencimiento"]=$prod["Fecha_Vencimiento"];
        $lotes[$i]["Cantidad"]=(INT)$prod["Cantidad"];
        $lotes[$i]["Precio"]=$prod["Precio"];
        $lotes[$i]["Impuesto"]=(INT)$prod["Impuesto"];
        $lotes[$i]["Subtotal"]=$prod["Subtotal"];
    }
    $subtotal+=(INT)$prod["Cantidad"]*$prod["Precio"];
    $impuesto+=((INT)$prod["Cantidad"]*$prod["Precio"]*((INT)$prod["Impuesto"]/100));
    
}
$total+=$subtotal+$impuesto;
$productos_final[$k+1]=$prod;
$productos_final[$k+1]["Lotes"]=$lotes;
$resultado=[];

$resultado["Datos"]=$datos;
$resultado["Datos2"]=$datos2;
$resultado["Datos2"]["ConteoProductos"]=count($productos_final);
$resultado["Datos2"]["Subtotal"]=$subtotal;
$resultado["Datos2"]["Impuesto"]=$impuesto;
$resultado["Datos2"]["Total"]=$total;

$resultado["Productos"]=$productos_acta;  
$resultado["ProductosNuevo"]=$productos_final; 
$resultado["Facturas"]=$facturas; 


echo json_encode($resultado);

?>