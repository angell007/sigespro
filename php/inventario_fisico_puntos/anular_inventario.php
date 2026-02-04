<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
    
$contabilizar = new Contabilizar();

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$modelo = json_decode($modelo, true);



$oItem= new complex("Inventario_Fisico_Punto","Id_Inventario_Fisico_Punto",$modelo['Id_Inventario_Fisico_Punto']);
//var_dump($oItem);
$oItem->Estado = 'Anulado';
$oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
$oItem->Funcionario_Anula = $modelo['Identificacion_Funcionario'];
$oItem->Observaciones_Anulacion = $modelo['Observaciones'];
//var_dump($oItem);
$oItem->save();
unset($oItem);

$resultado["Tipo"]="success";
$resultado["Titulo"]="Operacion exitosa";
$resultado["Texto"]="Se ha anulado correctamente el inventario";
            
AnularMovimientoContable($modelo['Id_Inventario_Fisico_Punto']);            

echo json_encode($resultado);

function AnularMovimientoContable($idRegistroModulo){
    global $contabilizar;

    $contabilizar->AnularMovimientoContable($idRegistroModulo, 10);
}
?>