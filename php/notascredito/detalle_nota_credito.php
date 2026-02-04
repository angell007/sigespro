<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT NC.*, FV.Codigo as Factura, FV.Fecha_Documento as Fecha_Factura, C.Nombre as Cliente
FROM Nota_Credito NC
INNER JOIN  Factura_Venta FV 
ON NC.Id_Factura=FV.Id_Factura_Venta
INNER JOIN Cliente C
ON NC.Id_Cliente=C.Id_Cliente
WHERE NC.Id_Nota_Credito ='.$id ;
            
$oCon= new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);

$query2 = 'SELECT PFV.*, IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
                                CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,
                                PRD.Nombre_Comercial, PRD.Embalaje, PRD.Invima,
                                CONCAT_WS(" // ",PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico  ) as Laboratorios
FROM Producto_Nota_Credito PFV
INNER JOIN Producto PRD
ON PFV.Id_Producto=PRD.Id_Producto
WHERE PFV.Id_Nota_Credito = '.$id;




$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);
$iva=0;
$subtotal=0;
$total=0;
foreach ($productos as $producto) {
    $iva+=($producto['Precio_Venta']*$producto['Cantidad'])*($producto['Impuesto']/100);
    $subtotal+=($producto['Precio_Venta']*$producto['Cantidad']);
    
}
$total=$iva+$subtotal;
$dis['Subtotal']=$subtotal;
$dis['Impuesto']=$iva;
$dis['Total']=$total;

$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;

echo json_encode($resultado);


?>