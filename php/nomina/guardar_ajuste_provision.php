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

$ajustes = $_REQUEST['datos']; 

$ajustes = json_decode($ajustes, true);

$quincena = $ajustes['Quincena'];
$funcionario = $ajustes['Identificacion_Funcionario'];

foreach ($ajustes['Ajustes'] as $key => $value) {
      if($value['Id_Provision_Funcionario_Ajuste']){
            $oItem = new complex('Provision_Funcionario_Ajuste', 'Id_Provision_Funcionario_Ajuste', $value['Id_Provision_Funcionario_Ajuste']);
      }
      else{
            $oItem = new complex('Provision_Funcionario_Ajuste', 'Id_Provision_Funcionario_Ajuste');
      }
      $oItem->Concepto = $key;
      $oItem->Identificacion_Funcionario = $funcionario;
      $oItem->Quincena = $quincena;
      foreach ($value as $k => $val) {
            $oItem->$k=$val;
      }
      $oItem->save();
      unset($oItem);
}



// echo json_encode($ajustes); exit;

$resultado["Mensaje"]="Guardado correctamente los Ajustes del Funcionario ";      
$resultado["Titulo"]="Operacion Exitosa";      
$resultado["Tipo"]="success";      

echo json_encode($resultado);

?>