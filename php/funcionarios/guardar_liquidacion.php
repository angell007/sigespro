<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.awsS3.php');
require('../comprobantes/funciones.php');
include_once('../../class/class.contabilizar.php');


$datos        = (isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '');
$datos        = (array) json_decode($datos , true);
$contabilizar = new Contabilizar();

$files = $_FILES;

if (empty($datos['Identificacion_Funcionario'])) {
    $resultado['mensaje'] = 'Identificacion_Funcionario es requerida para guardar la liquidacion.';
    $resultado['tipo'] = 'error';
    echo json_encode($resultado);
    exit;
}

function normalizarNumero($valor) {
    if ($valor === null) {
        return 0.0;
    }
    if (is_int($valor) || is_float($valor)) {
        return (float) $valor;
    }
    if (!is_string($valor)) {
        return (float) $valor;
    }
    $valor = trim($valor);
    if ($valor === '') {
        return 0.0;
    }
    $valor = str_replace(['$', ' '], '', $valor);
    $tiene_coma = strpos($valor, ',') !== false;
    $tiene_punto = strpos($valor, '.') !== false;
    if ($tiene_coma && $tiene_punto) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } elseif ($tiene_coma) {
        $valor = str_replace(',', '.', $valor);
    }
    return (float) $valor;
}

 
$cod             = generarConsecutivo(ucwords('Liquidacion'));
$datos['Codigo'] = $cod;
$datos['Fecha']  = date("Y-m-d H:i:s");
$oItem           = new complex('Liquidacion_Funcionario','Id_Liquidacion_Funcionario');

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_liquidacion  = $oItem->getId();
unset($oItem);

foreach ($datos['Conceptos'] as  $value) {
    $oItem           = new complex('Concepto_Liquidacion_Funcionario','Id_Concepto_Liquidacion_Funcionario');
    $oItem->Concepto = $value['Concepto'];
    $valor_normalizado = normalizarNumero($value['Valor']);
    $oItem->Valor    = bcdiv((string) $valor_normalizado, '1', 2);
    $oItem->Id_Liquidacion_Funcionario = $id_liquidacion;
    $oItem->save();    
    unset($oItem);
}

$cesantias = normalizarNumero($datos['Cesantias'] ?? 0);
$interes_cesantias = normalizarNumero($datos['Interes_Cesantia'] ?? 0);
$prima = normalizarNumero($datos['Prima'] ?? 0);
$vacaciones = normalizarNumero($datos['Vacaciones'] ?? 0);
$ultima_quincena = normalizarNumero($datos['Total_Quincena'] ?? 0);
$datos['Contabilizacion_Liquidacion'] = [
    'Cesantias' => $cesantias,
    'Intereses a las Cesantias' => $interes_cesantias,
    'Prima' => $prima,
    'Vacaciones' => $vacaciones,
    'Bancos' => $cesantias + $interes_cesantias + $prima + $vacaciones + $ultima_quincena,
];

$movimiento['Id_Registro']                 = $id_liquidacion;
$movimiento['Nit']                         = $datos['Identificacion_Funcionario'];
$movimiento['Contabilizacion_Liquidacion'] = $datos['Contabilizacion_Liquidacion'];
$movimiento['Contabilizacion_Quincena']    = $datos['Contabilizacion_Quincena'];
$movimiento['Documento']                   = $cod;

$contabilizar->CrearMovimientoContable('Liquidacion Funcionario', $movimiento);

$oItem         = new complex('Contrato_Funcionario','Id_Contrato_Funcionario',$datos['Id_Contrato_Funcionario'] );
$oItem->Estado = "Liquidado";
$oItem->save();
unset($oItem);

$oItem            = new complex('Funcionario','Identificacion_Funcionario',$datos['Identificacion_Funcionario'] );
$oItem->Liquidado = "SI";
$oItem->save();
unset($oItem);

$query = 'UPDATE Provision_Funcionario PF SET PF.Estado="Pagadas" 
          WHERE PF.Estado="Pendiente"
            AND PF.Identificacion_Funcionario = '.$datos['Identificacion_Funcionario'];
            
$oCon  = new consulta();
$oCon->setQuery($query);     
$oCon->createData();     
unset($oCon);


if ($files) {

    foreach ($files as $key=>$file) {

        $s3 = new AwsS3();

        try {
             $oItem =  new complex('Pazysalvo', 'Id_Pazysalvo');
             $ruta = 'liquidacion/'.$id_liquidacion.'/'.$key;     		
             $uri = $s3->putObject( $ruta, $files[$key]);
   
             $oItem->Id_Dependencia = $key;
             $oItem->Id_Liquidacion = $id_liquidacion ;
             $oItem->Archivo = $uri;
             $oItem->save();
             unset($oItem);
               
        } catch (Aws\S3\Exception\S3Exception $e) {
   
            echo json_encode('asdasd'.$e->getMessage());
         }
    }
  }

$resultado['mensaje'] = "Se ha liquidado correctamente al funcionario ".strtoupper($datos['Nombre_Funcionario']);
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>
