<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : false);

$resultado = [];
if ($id) {
    $oItem = new complex('Dotacion','Id_Dotacion',$id);
    $oItem->Estado = 'Anulada';
    $oItem->save();
    unset($oItem);

    $resultado["titulo"]="Exito!";
    $resultado["mensaje"]="Se ha anulado correctamente la dotación.";
    $resultado["tipo"]="success";

    $query = 'SELECT PD.Id_Inventario_Dotacion, PD.Cantidad CantidadaSumar, ID.Cantidad Cantidad
              FROM Producto_Dotacion PD
              INNER JOIN Inventario_Dotacion ID ON PD.Id_Inventario_Dotacion = ID.Id_Inventario_Dotacion
              WHERE id_Dotacion = '.$id.'';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    foreach ($resultado as $res){
        $total = ($res['CantidadaSumar']  + $res['Cantidad']);
        $oItem = new complex("Inventario_Dotacion","Id_Inventario_Dotacion",$res["Id_Inventario_Dotacion"]);
        $oItem->Cantidad =  $total;
        $oItem->save();
        $oItem->getId();
        unset($oItem);
        
    }

} else {
    $resultado["titulo"]="Oops!";
    $resultado["mensaje"]="Ha ocurrido un error inesperado.";
    $resultado["tipo"]="error";
}


echo json_encode($resultado);
?>