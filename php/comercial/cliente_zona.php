<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idZona = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$sem=( isset( $_REQUEST['sem'] ) ? $_REQUEST['sem'] : '' );
$anio_s=( isset( $_REQUEST['anio'] ) ? $_REQUEST['anio'] : '' );

if($sem=='0'){
    $meses=['01','02','03','04','05','06',];
    
}else{    
    $meses=['07','08','09','10','11','12'];
}

$query="SELECT GROUP_CONCAT(DISTINCT MC.Id_Cliente) as Id_Cliente, 
M.Id_Meta FROM Meta M INNER JOIN Meta_Cliente MC ON M.Id_Meta=MC.Id_Meta WHERE M.Id_Zona=".$idZona." AND M.Anio=".$anio_s;

$oCon= new consulta();
$oCon->setQuery($query);
$existe = $oCon->getData();
unset($oCon); 

if($existe['Id_Cliente']){
    $anio['Anio']=$anio_s-1;
    $id=$existe['Id_Meta'];

    $query="SELECT * FROM Meta M WHERE M.Id_Meta=".$id;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $meta= $oCon->getData();
    unset($oCon); 

    $query='SELECT UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) AS Nombre, F.Identificacion_Funcionario
    FROM Funcionario F WHERE F.Liquidado="NO" AND F.Suspendido="NO" AND F.Identificacion_Funcionario='.$meta['Identificacion_Funcionario'];
    $oCon= new consulta();
    $oCon->setQuery($query);
    $meta['Funcionario']= $oCon->getData();
    unset($oCon); 


    $query = "SELECT C.Id_Cliente  , C.Nombre  FROM Meta_Cliente MC INNER JOIN  Cliente C ON MC.Id_Cliente=C.Id_Cliente
    WHERE C.Estado = 'Activo' AND C.Id_Zona = ".$idZona.' GROUP BY C.Id_Cliente ';

}else{
    $query = "SELECT  (YEAR(NOW())-1) as Anio";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $anio = $oCon->getData();
    unset($oCon);
    $id='';

    $query = "SELECT C.Id_Cliente  , C.Nombre  FROM Cliente C 
    INNER JOIN Factura_Venta FV ON C.Id_Cliente=FV.Id_Cliente WHERE C.Estado = 'Activo' AND C.Id_Zona = ".$idZona.' GROUP BY C.Id_Cliente ';
}






$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$clientes = $oCon->getData();
unset($oCon);

$query = "SELECT C.Id_Cliente  , C.Nombre  FROM Cliente C 
INNER JOIN Factura_Venta FV ON C.Id_Cliente=FV.Id_Cliente WHERE C.Estado = 'Activo' AND C.Id_Zona = ".$idZona.' GROUP BY C.Id_Cliente ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$clientes_zona = $oCon->getData();
unset($oCon);

foreach ($clientes as $i=> $value) {
    $id_meta_cliente='';
   foreach ($meses as $item) {
    $query = "SELECT IFNULL((SELECT SUM(PFV.Subtotal+(PFV.Subtotal*(PFV.Impuesto/100)))  FROM Producto_Factura_Venta PFV
    INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta=FV.Id_Factura_Venta
    INNER JOIN Remision R ON PFV.Id_Remision=R.Id_Remision 
    WHERE FV.Fecha_Documento LIKE '$anio[Anio]-$item%' AND R.Id_Origen!=2 AND FV.Id_Cliente=".$value['Id_Cliente']."),0) as Medicamentos,
    IFNULL((SELECT MC.Valor_Medicamento FROM Meta_Cliente MC INNER JOIN Meta M on MC.Id_Meta=M.Id_Meta WHERE MC.Id_Cliente=".$value['Id_Cliente']." AND MC.Mes=".$item." AND M.Id_Zona=".$idZona." ),0) as Valor_Medicamento,
    IFNULL((SELECT MC.Valor_Material FROM Meta_Cliente MC INNER JOIN Meta M on MC.Id_Meta=M.Id_Meta WHERE MC.Id_Cliente=".$value['Id_Cliente']." AND MC.Mes=".$item." AND M.Id_Zona=".$idZona." ),0) as Valor_Material,
    IFNULL((SELECT SUM(PFV.Subtotal+(PFV.Subtotal*(PFV.Impuesto/100)))  FROM Producto_Factura_Venta PFV
    INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta=FV.Id_Factura_Venta
    INNER JOIN Remision R ON PFV.Id_Remision=R.Id_Remision 
    WHERE FV.Fecha_Documento LIKE '$anio[Anio]-$item%' AND R.Id_Origen=2 AND  FV.Id_Cliente=".$value['Id_Cliente']." ),0) as Materiales,
    IFNULL((SELECT MC.Id_Meta_Cliente FROM Meta_Cliente MC INNER JOIN Meta M on MC.Id_Meta=M.Id_Meta WHERE MC.Id_Cliente=".$value['Id_Cliente']." AND MC.Mes=".$item." AND M.Id_Zona=".$idZona." ),0) As Id_Meta_Cliente "; 
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $datos1 = $oCon->getData();
    unset($oCon);
    $datos1['Mes']=$item;
    $clientes[$i]['Meses'][]=$datos1;   
    $id_meta_cliente.= $datos1['Id_Meta_Cliente'].",";
   
   }

   $clientes[$i]['Id_Meta_Cliente']=trim($id_meta_cliente,",");
}

$datos['Clientes']=$clientes;
$datos['Clientes_Zona']=$clientes_zona;
$datos['Id']=$id;
$datos['Meta']=$meta;

echo json_encode($datos);