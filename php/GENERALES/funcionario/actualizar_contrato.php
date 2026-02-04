<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	// require_once('../../config/start.inc.php');
	// include_once('../../class/class.complex.php');
	// include_once('../../class/class.consulta.php');
	include_once('../../../class/class.http_response.php');
	include_once('../../../class/class.utility.php');
	include_once('../../../class/class.querybasedatos.php');


	$queryObj = new QueryBaseDatos();

    $query = 'SELECT * FROM Contrato_Temporal ';

    $queryObj->SetQuery($query);
    $result = $queryObj->ExecuteQuery('Multiple');

  foreach ($result as  $value) {
     $id_contrato=GetContratoFuncionario($value['Identificacion_Funcionario']);
     if($id_contrato!=null){
         $oItem=new complex('Contrato_Funcionario', 'Id_Contrato_Funcionario',$id_contrato);
         $oItem->Fecha_Inicio_Contrato=$value['Fecha_Inicio'];
         $oItem->Fecha_Fin_Contrato=$value['Fecha_Fin'];
         $oItem->save();
         unset($oItem);
     }else{
        $oItem=new complex('Contrato_Funcionario', 'Id_Contrato_Funcionario');
        $oItem->Identificacion_Funcionario=$value['Identificacion_Funcionario'];
        $oItem->Id_Tipo_Contrato=2;
        $oItem->Id_Salario=1;
        $oItem->Fecha_Inicio_Contrato=$value['Fecha_Inicio'];
        $oItem->Fecha_Fin_Contrato=$value['Fecha_Fin'];
        $oItem->Valor=GetValorSalario($value['Identificacion_Funcionario']);
        $oItem->Id_Riesgo=1;
        $oItem->Aporte_Pension='Si';
        $oItem->save();
         unset($oItem);
     }
     
  }

  echo "Finalizó";


  function GetContratoFuncionario($id){
      global $queryObj;

      $query="SELECT * FROM Contrato_Funcionario WHERE Identificacion_Funcionario=$id AND Estado='Activo'";
      
      $queryObj->SetQuery($query);
      $contrato = $queryObj->ExecuteQuery('simple');

      return $contrato['Id_Contrato_Funcionario'];
  }

  function GetValorSalario($id){
    global $queryObj;

    $query="SELECT Salario FROM Funcionario WHERE Identificacion_Funcionario=$id";
    $queryObj->SetQuery($query);
    $func = $queryObj->ExecuteQuery('simple');

    if($func['Salario']){
        $valor=$func['Salario'];
    }else{
        $valor='0';
    }

    return $valor;
  }
		
	
?>