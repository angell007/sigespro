<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "SELECT P.* FROM Proveedor P WHERE P.Id_Proveedor = $id";
$oCon= new consulta();
$oCon->setQuery($query);
$detalle = $oCon->getData();
unset($oCon);

$reteica = getDatosRetenciones($detalle['Id_Plan_Cuenta_Reteica']);
$retefuente = getDatosRetenciones($detalle['Id_Plan_Cuenta_Retefuente']);
$reteiva = getDatosRetenciones($detalle['Id_Plan_Cuenta_Reteiva']);

$resultado['encabezado'] = $detalle;
$resultado['Retenciones'] = [
    "Reteica" => $reteica,
    "Retefuente" => $retefuente,
    "Reteiva" => $reteiva
];

echo json_encode($resultado);

function getDatosRetenciones($id_plan_cuenta) {

    if ($id_plan_cuenta != '') {
        $query = 'SELECT PC.Id_Plan_Cuentas, CONCAT(PC.Codigo," - ",PC.Nombre) as Codigo, PC.Centro_Costo, PC.Porcentaje
        FROM Plan_Cuentas PC WHERE CHAR_LENGTH(PC.Codigo)>5 AND PC.Id_Plan_Cuentas = '. $id_plan_cuenta;
    
        $oCon= new consulta();
        $oCon->setQuery($query);
        $res = $oCon->getData();
        unset($oCon);
    
        return $res;
    }

    return [];
    
}
?>