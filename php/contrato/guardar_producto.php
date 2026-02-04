<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$Id_Producto = ( isset( $_REQUEST['Id_Producto'] ) ? $_REQUEST['Id_Producto'] : '' );
$Id_Contrato = ( isset( $_REQUEST['Id_Contrato'] ) ? $_REQUEST['Id_Contrato'] : '' );
$datos = (array) json_decode($datos);
$Id_Producto = (array) json_decode($Id_Producto);


$cantidad = $datos["Cantidad"] ? $cantidad = $datos["Cantidad"] : 0;

insertProducto($Id_Contrato,$Id_Producto[0],$datos["Cum"],$cantidad,$datos["Precio"]);

function insertProducto($Id_Contrato,$idproducto,$cum,$cantidad,$precio){
        $precio = number_format($precio,2,'.','');
  
        $query = 'INSERT INTO Producto_Contrato (Id_Contrato,Id_Producto,Cum,Cantidad,Precio)
                                VALUES('.$Id_Contrato.','.$idproducto.',"'.$cum.'",'.$cantidad.','.$precio.')';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        $id = $oCon->getID();
        unset($oCon);

        return $id;
}
