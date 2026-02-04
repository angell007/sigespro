<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$funcionario = ( isset( $_REQUEST['Funcionario'] ) ? $_REQUEST['Funcionario'] : '' );
$productos = ( isset( $_REQUEST['Productos'] ) ? $_REQUEST['Productos'] : '' );

$datos = ( isset( $_REQUEST['Datos'] ) ? $_REQUEST['Datos'] : '' );
$clientes = ( isset( $_REQUEST['Clientes'] ) ? $_REQUEST['Clientes'] : '' );

$datos = (array) json_decode($datos, true);
$clientes = (array) json_decode($clientes, true);
$prod = (array) json_decode($productos, true);



$contrato = '';
if ($datos["Id_Contrato"] == '') {
  $contrato = 0; 
}else{
    $contrato =  $datos['Id_Contrato'];
}


foreach ($prod as $producto) {
    $oItem= new complex("Pre_Compra","Id_Pre_Compra");
    $oItem->Identificacion_Funcionario=$funcionario;
    $oItem->Id_Proveedor = $producto["Id_Proveedor"];
    $oItem->Tipo = $datos['Tipo'];
    $oItem->Id_Contrato = $contrato;
    $oItem->Tipo_Medicamento = $datos['Tipo_Medicamento'];
    $oItem->Fecha_Inicio = $datos['Fecha_Inicial'];
    $oItem->Fecha_Fin = $datos['Fecha_Final'];
    $oItem->Excluir_Vencimiento = $datos['ExcluirVencimientos'];
    $oItem->Meses = $datos['Meses'];
    
    $oItem->save();
    $id_pre_compra= $oItem->getId();
    unset($oItem);
    foreach ($producto["Productos"] as $item) {
        if( isset($item["Id_Producto"]) && $item["Id_Producto"]!='' ){
            $oItem= new complex("Producto_Pre_Compra","Id_Producto_Pre_Compra");
            $oItem->Id_Pre_Compra=$id_pre_compra;
            $oItem->Id_Producto = GetIdProducto($item["Id_Producto"]);
            if($item["Cantidad"]==''){
                $oItem->Cantidad = 0;
            }else{
                $oItem->Cantidad = $item["Cantidad"];
            }
            if($item["Costo"]==''){
                $oItem->Costo = 0;
            }else{
                $oItem->Costo = $item["Costo"];
            }
        
            $oItem->save();
            unset($oItem);
        }
        
    }
   
}

$resultado["Tipo"]="success";
$resultado["Titulo"]="Operacion Exitosa";
$resultado["Texto"]="Se ha Guardado Correctamenta la Preconpra";
            
echo json_encode($resultado);

function GetIdProducto($idproducto){
    $producto=explode(',',$idproducto);

    return $producto[0];
}
?>