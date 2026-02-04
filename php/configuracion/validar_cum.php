<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$cum  = ( isset( $_REQUEST['cum'] ) ? $_REQUEST['cum'] : '' );
$id   = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

$query = 'SELECT IFNULL(
         (SELECT Id_Producto_NoPos FROM Producto_NoPos PP WHERE Cum="'.$cum.'" AND Id_Lista_Producto_Nopos='.$id.' ),0)
          as Lista_No_Pos, IFNULL((SELECT Id_Producto FROM Producto WHERE Codigo_Cum="'.$cum.'"),0) as Producto';


$oCon= new consulta();

$oCon->setQuery($query);
$prod = $oCon->getData();
unset($oCon);
$resultado['tipo'] = "Exitoso";
$resultado['data'] = $prod;

if($tipo!="Homologo"){
    if($prod['Lista_No_Pos']!=0){
        $resultado['mensaje'] = "El cum ya se registrado en la lista, por favor revise ";
        $resultado['tipo']    = "error";
    }
}

if($prod['Producto']==0){
    $resultado['mensaje'] = "El cum no se encuentra registrado en la base datos de productos ";
    $resultado['tipo']    = "error";
}

echo json_encode($resultado);
?>
