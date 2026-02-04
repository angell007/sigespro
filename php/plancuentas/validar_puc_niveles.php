<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipo_plan = $_REQUEST['Tipo_Plan'];   // clase | grupo | cuenta | subcuenta | auxiliar
$codigo    = $_REQUEST['Codigo'];
$tipo_puc  = $_REQUEST['Tipo_Puc'];

$nombre_nivel_superior = null;

/**
 *  CASO CLAVE:
 * Si es CLASE (nivel raíz), no se valida contra BD
 */
if ($tipo_plan === 'clase') {
    echo json_encode([
        "validacion" => 1,
        "nivel_superior" => null
    ]);
    exit;
}

$codigo_validar = getCodigoValidar($tipo_plan, $codigo);
$campo_codigo  = $tipo_puc === 'pcga' ? 'Codigo' : 'Codigo_Niif';

$query = "
    SELECT Id_Plan_Cuentas 
    FROM Plan_Cuentas 
    WHERE $campo_codigo LIKE '$codigo_validar%'
";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$response = [
    "validacion" => $resultado ? 1 : 0,
    "nivel_superior" => $nombre_nivel_superior
];

echo json_encode($response);


/* =======================
   Helpers
======================= */

function getCodigoValidar($tipo_plan, $codigo) {
    $nivel_superior = getNivelSuperiorLength($tipo_plan);
    return substr($codigo, 0, $nivel_superior);
}

function getNivelSuperiorLength($tipo_plan) {

    global $nombre_nivel_superior;

    //  Correcto según  BD
    $nombres_niveles = [
        1 => "Clase",
        2 => "Grupo",
        4 => "Cuenta",
        6 => "Subcuenta",
        8 => "Auxiliar"
    ];

    //  Clase NO aparece: no tiene padre
    $niveles_superior = [
        "grupo"     => 1,
        "cuenta"    => 2,
        "subcuenta" => 4,
        "auxiliar"  => 6
    ];

    $len_superior = $niveles_superior[$tipo_plan];
    $nombre_nivel_superior = $nombres_niveles[$len_superior];

    return $len_superior;
}
