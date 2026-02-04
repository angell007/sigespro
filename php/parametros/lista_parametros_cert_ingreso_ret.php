<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT * FROM Parametro_Certificado_Ingreso_Retencion_Renglon";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 4; 
$numReg = count($resultados); 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}

$query = "SELECT * FROM Parametro_Certificado_Ingreso_Retencion_Renglon LIMIT $limit,$tamPag";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

foreach ($resultados as $i => $value) {
    $resultados[$i]['Codigos_Cuentas'] = getCodigosCuentas($value['Cuentas']);
    $ids = explode(',',$value['Cuentas']);
    $resultados[$i]['Cuentas'] = [];
    foreach ($ids as $id) {
        $resultados[$i]['Cuentas'][] = [
            'Cuenta' => getDatosCuentaContable($id),
            'Id_Plan_Cuenta' => $id
        ];
    }
}

$response['lista'] = $resultados;
$response['numReg'] = $numReg;

echo json_encode($response);


function getCodigosCuentas($ids_cuentas) {
    $query = "SELECT GROUP_CONCAT(Codigo SEPARATOR ', ') AS Codigos FROM Plan_Cuentas WHERE Id_Plan_Cuentas IN ($ids_cuentas)";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado['Codigos'];
}

function getDatosCuentaContable($id_plan_cuenta) {

    $query = "SELECT PC.Id_Plan_Cuentas, PC.Id_Plan_Cuentas AS Id, PC.Codigo, PC.Codigo AS Codigo_Cuenta, CONCAT(PC.Nombre,' - ',PC.Codigo) as Codigo, CONCAT(PC.Codigo,' - ',PC.Nombre) as Nombre, PC.Centro_Costo
    FROM Plan_Cuentas PC WHERE PC.Id_Plan_Cuentas = $id_plan_cuenta";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}
?>