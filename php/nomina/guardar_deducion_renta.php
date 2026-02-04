<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

date_default_timezone_set('America/Bogota');


$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$modelo = (array) json_decode($modelo,true); 

foreach ($modelo['Conceptos_Renta'] as $value) {
  foreach ($value['Conceptos'] as $item) {
    if($item['Id_Deduccion_Renta_Funcionario']!=0){
      $oItem=new complex('Deduccion_Renta_Funcionario','Id_Deduccion_Renta_Funcionario',$oItem['Deduccion_Renta_Funcionario']);
    }else{
      $oItem=new complex('Deduccion_Renta_Funcionario','Id_Deduccion_Renta_Funcionario');
    }
    $oItem->Identificacion_Funcionario=$modelo['Identificacion_Funcionario'];
    $oItem->Valor=number_format($item['Valor'],2,".","");
    $oItem->Id_Concepto_Retencion_Fuente=$item['Id_Concepto_Retencion_Fuente'];
    $oItem->save();
    unset($oItem);
  }
}


$resultado["Mensaje"]="Guardado correctamente las Deducciones y Rentas exentas ";      
$resultado["Titulo"]="Operacion Exitosa";      
$resultado["Tipo"]="success";      

echo json_encode($resultado);

?>