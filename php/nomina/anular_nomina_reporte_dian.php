<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('./nomina_reporte_dian.php'); 

use Carbon\Carbon as Carbon;

$datosA  = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$func = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
$anulacion = (isset($_REQUEST['anulacion']) ? $_REQUEST['anulacion'] : '');

$datosA = (array) json_decode($datosA, true);
$anulacion = (array) json_decode($anulacion, true);

try{
    deleteElectroincPayroll($anulacion);
    
} catch (\Throwable $th) {
    //throw $th;
}


function deleteElectroincPayroll($anulacion)
{
  $query = 'SELECT FN.Fecha, N.Tipo_Nomina, FN.Cune 
              FROM Nomina_Funcionario FN 
              INNER JOIN Nomina N on N.Id_Nomina = FN.Id_Nomina
              WHERE FN.Id_Nomina_Funcionario = ' . $anulacion["Id_Nomina_Funcionario"];

    $oCon= new consulta();
    // $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $funcionario = $oCon->getData();
    unset($oCon);     
  
    
    $code = getConfig();
  
    $data = [];

    $data['note_type'] = '2';
    $data['code_payroll'] = $code[1].$code[0];
    $data['cune_payroll'] = $funcionario["Cune"];
    $data['date_payroll'] = date('Y-m-d', strtotime($funcionario["Fecha"]));
    $data['type_document_id'] = 8;
    $data['resolution_number'] = 2;
    $data['resolution_id'] = 7;
    $data['payroll_period'] = $funcionario["Tipo_Nomina"];
    
    $data['date'] =  Carbon::now()->format('Y-m-d');
    $data['date_pay'] = Carbon::now()->format('Y-m-d');
    $data['hour'] =  Carbon::now()->format("H:i:s") . '-05:00';
    $data['observation'] = 'Se elimina la nota';
    
    $data['prefix'] = $code[1];
    $data['number'] = $code[0];
    $data['code'] = $code[1].$code[0];
    $data['file'] = $code[1].$code[0];
    $data['person']['identifier'] = '0';
    $data['cune_propio'] = cuneGenerate($data, '804016084', '80401', '103', 1, 2);
    
    //**************************************************************** 
     $respuesta_dian = GetApi($data, $funcionario, true);
    //****************************************************************
    
    nElectronicaFuncionario($respuesta_dian,$data);
    
    $estado = '';
        
    if(strpos($respuesta_dian["Respuesta"], "procesado anteriormente") !== false) {
      
        $estado = "true";
        
    } else {
        
        $estado = $respuesta_dian["Procesada"];
    }
    if($estado == "true"){
      updateNominaFuncionario($data);
      actualizarConfiguracion(); 
    }
    
    // if( $respuesta_dian['status'] == 'succeded'){
    //     updateNominaFuncionario($data,$nominaElectronica);
    //     actualizarConfig();
    // }
}  

function cuneGenerate($data, $company, $softwarePin, $xmlType, $ambient, $typeNote)
{
    $ValDev = $typeNote == 1 ? $data['totals']['accrued'] : '0.00';
    $ValDed = $typeNote == 1 ? $data['totals']['deductions'] : '0.00';
    $ValTolNE = $typeNote == 1 ? $data['totals']['voucher'] : '0.00';
    $DocEmp = $typeNote == 1 ? $data['person']['identifier'] : '0';

    $cune = $data['code'] . $data['date'] . $data['hour'] . $ValDev . $ValDed .  $ValTolNE . $company . $DocEmp .  $xmlType .  $softwarePin . $ambient;
    hash('sha384', $cune);
    return hash('sha384', $cune);
}

function nElectronicaFuncionario($respuesta_dian,$data)
{
    global $anulacion,$func,$datos;
    
    $oItem = new complex("Nomina_Electronica_Funcionario ","Id");
    $oItem->Identificacion_Funcionario =$anulacion["Identificacion_Funcionario"];
    $oItem->Id_Nomina_Funcionario =$anulacion["Id_Nomina_Funcionario"];
    $oItem->Cune = $respuesta_dian['Estado'] == 'exito' ?  $data['cune_propio'] : '';
    $oItem->Fecha_Reporte =date('Y-m-d');
    $oItem->Estado = 'Anulado';
    $oItem->Respuesta_Dian =$respuesta_dian["Respuesta"];
    $oItem->Codigo_Nomina=  $respuesta_dian['Estado'] == 'exito' ?  $data['code'] : '';
    $oItem->Observacion= $datos["Motivo_Anulacion"];
    $oItem->Funcionario= $func;
    $oItem->save();
   
}

function updateNominaFuncionario($data)
{
    global $anulacion,$func;
    
    $oItem = new complex("Nomina_Funcionario ","Id_Nomina_Funcionario", $anulacion["Id_Nomina_Funcionario"]);
    $oItem->Procesado= 'true';
    $oItem->Estado_Nomina = 'Eliminado' ;
    $oItem->Cune=$data["cune_propio"];
    $oItem->Fecha=$data["date"];
    $oItem->Funcionario_Digita= $func;
    $oItem->Codigo_Nomina=$data['code'];
    $oItem->save();
    
    unset($oItem);
}

function actualizarConfiguracion(){
    $oItem = new complex('Configuracion','Id_Configuracion',1);
    $oItem->Nomina_Electronica=$oItem->Nomina_Electronica+1;
    $oItem->save();

    unset($oItem);
     
}



















