<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require('../comprobantes/funciones.php');
date_default_timezone_set('America/Bogota');
include_once('../../class/class.http_response.php');
$http_response = new HttpResponse();

$funcionarios = ( isset( $_REQUEST['funcionarios'] ) ? $_REQUEST['funcionarios'] : '' );
$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

$Periodo = ( isset( $_REQUEST['Anio'] ) ? $_REQUEST['Anio'] : '' );
$mes = ( isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '' );

$funcionarios = (array) json_decode($funcionarios,true); 
$modelo = (array) json_decode($modelo,true); 

$query='SELECT *, Periodo FROM Prima WHERE Periodo LIKE "'.$Periodo.'-'.$mes.'"' ;

$oCon= new consulta();
$oCon->setQuery($query);
$prima = $oCon->getData();
unset($oCon);


if($prima){
  $http_response->SetRespuesta(1, 'Error', 'Ya existe un registro para esta prima,  se realizo el dia !'.$prima['Periodo']);

  $response = $http_response->GetRespuesta();
  
  echo json_encode($response);   
}else{
  // $mes=date('m');
  if($mes=='12'){
    // $prima='Prima de Diciembre de '.date('Y');
    $prima='Prima de Diciembre de '.$Periodo;
  }else{
    // $prima='Prima de Junio de '.date('Y');
    $prima='Prima de Junio de '.$Periodo;   
  }
  $oItem = new complex("Prima","Id_Prima");
  $oItem->Identificacion_Funcionario=$modelo['Identificacion_Funcionario'];
  $oItem->Fecha=date("Y-m-d"); // fecha actual
  $oItem->Prima=$prima;
  $oItem->Periodo=$Periodo.'-'.$mes;
  $oItem->Total_Prima=$modelo['Total_Prima'];
  $oItem->Total_Empleados=$modelo['Total_Funcionarios'];
  
  $oItem->save();
  $id_prima= $oItem->getId();
  unset($oItem);

  $contabilizar = new Contabilizar();
  $documento = 'PRI'.$Periodo.$mes;

  foreach ($funcionarios as $f) {
    $oItem = new complex("Prima_Funcionario","Id_Prima_Funcionario");
    $oItem->Id_Prima=$id_prima;
    $oItem->Identificacion_Funcionario=$f['Identificacion_Funcionario'];
    $oItem->Funcionario_Digita=$modelo['Identificacion_Funcionario'];
    $oItem->Fecha=date("Y-m-d"); // fecha actual
    $oItem->Detalles=$prima;
    $oItem->Dias_Trabajados=$f['Dias_Trabajados'];
    $oItem->Salario=$f['Salario'];
    $oItem->Total_Prima=$f['Valor_Prima'];
    $oItem->save();   
    unset($oItem);

    if (!empty($f['Valor_Prima']) && $f['Valor_Prima'] > 0) {
      $movimiento = [];
      $movimiento['Id_Registro'] = $id_prima;
      $movimiento['Nit'] = $f['Identificacion_Funcionario'];
      $movimiento['Conceptos'] = [
        'Prima de Servicios' => $f['Valor_Prima'],
        'Salarios por pagar' => $f['Valor_Prima'],
      ];
      $movimiento['Parafiscales'] = [];
      $movimiento['Provision'] = [];
      $movimiento['Documento'] = $documento;
      $contabilizar->CrearMovimientoContable('Nomina', $movimiento);
    }
}

$http_response->SetRespuesta(0, 'Prima guardada', 'Se ha guardado correctamente la informacion de la prima!');

$response = $http_response->GetRespuesta();

echo json_encode($response); 

}






   


?>
