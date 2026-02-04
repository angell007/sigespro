<?php

use Carbon\Carbon;

require_once('../../vendor/autoload.php');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');

header("Content-type: application/json");

$respuesta_dian = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$identificacion_funcionario = (isset($_REQUEST['documento']) ? $_REQUEST['documento'] : '');
$code = (isset($_REQUEST['code']) ? $_REQUEST['code'] : '');
$idnomina =  (isset($_REQUEST['nomina']) ? $_REQUEST['nomina'] : 8);
// $identificacion_funcionario = (int)$identificacion_funcionario;
$condicion = 'FN.Identificacion_Funcionario = ' . $identificacion_funcionario . ' AND';

// echo ($respuesta_dian); exit;
$respuesta_dian = json_decode($respuesta_dian, true);
$respuesta_dian= (isset($respuesta_dian['Json'])?$respuesta_dian['Json'] : $respuesta_dian);
$cune = $respuesta_dian["cune"];

// echo $cune; exit;
$func = getData();
// echo json_encode($idnomina); exit;
$estado = '';
try {


    $errores= $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendNominaSyncResponse"]["SendNominaSyncResult"]["ErrorMessage"];
    $errores_dian=implode(",", $errores);
    $hour = new Carbon($func["Fecha"]);
    //code...
     $estado = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendNominaSyncResponse"]["SendNominaSyncResult"]["IsValid"];
    if(strpos($errores_dian, "procesado anteriormente")!==false){
        $estado="true";
    }
    // else
    // {   
    // }
} catch (\Throwable $th) {
    echo $th->getMessage();
}

if ($estado == "true" ||  count($errores)==0) {
    $estado="true";
    // $respuesta['config'] = actualizarConfig();
    // exit;
    // $totalDevengados = $funcionario["datos_dian"]["totalDian"]["Valor"] + $funcionario["Deducciones"];

    // $configPrefixCune = getConfigPrefix($func["Fecha"], $totalDevengados, $funcionario["Deducciones"], $funcionario["datos_dian"]["totalDian"]["Valor"], $func['Identificacion_Funcionario'], $hour);
    // $funcionarios[$i]['code'] = $configPrefixCune[1] . $configPrefixCune[0];

    $respuesta['nomina'] = actualizarNominaFuncionario($cune, $estado, $func["Id_Nomina_Funcionario"], $code);


    $respuesta['nomina-electronica'] = nominaElectronicaFuncionario($func["Identificacion_Funcionario"], $func["Id_Nomina_Funcionario"], $respuesta_dian, $cune, $code);
    echo json_encode($respuesta);
    exit;
}
http_response_code(416);
// sleep("3");


function getData()
{

    global $idnomina, $condicion;

    $query = 'SELECT F.Identificacion_Funcionario,
                     ifnull(F.Nombres, concat(F.Primer_Nombre, F.Segundo_Nombre)) as Nombres,
                     ifnull(F.Apellidos, concat(F.Primer_Apellido, F.Segundo_Apellido)) as Apellidos,
                     FN.Id_Nomina_Funcionario,
                     N.Fecha_Inicio, 
                     N.Fecha_Fin, 
                     N.Tipo_Nomina, 
                     N.Fecha,
                     N.Codigo as codigoNomina,
                     M.Codigo_Dane,
                     TP.Cod_Dian,
                     D.Codigo
              FROM Nomina_Funcionario FN 
              INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FN.Identificacion_Funcionario
              INNER JOIN Tipo_Documento TP ON TP.Id_Tipo_Documento = F.Id_Tipo_Documento
              INNER JOIN Contrato_Funcionario CF ON CF.Identificacion_Funcionario = F.Identificacion_Funcionario
              LEFT JOIN Municipio M ON M.Id_Municipio = F.Id_Municipio
              LEFT JOIN Departamento D ON D.Id_Departamento = F.Id_Departamento
              INNER JOIN Nomina N on N.Id_Nomina = FN.Id_Nomina
              WHERE ' . $condicion . ' F.Tipo != "Externo" and N.Id_Nomina = ' . $idnomina . ' limit 1';

            

    // echo $query; exit;
    $oCon = new consulta();
    $oCon->setTipo('simple');
    $oCon->setQuery($query);
    $funcionarios = $oCon->getData();
    unset($oCon);

    return $funcionarios;
}

function actualizarNominaFuncionario($cune, $estado, $idnominafuncionario, $codigoNomina)
{

    try {
        //code...
        $oItem = new complex("Nomina_Funcionario ", "Id_Nomina_Funcionario", $idnominafuncionario);
        $oItem->Procesado = $estado;
        $oItem->Estado_Nomina = $estado == 'true' ? 'Exito' : 'Error';
        $oItem->Cune = $estado == "false" ? '' : $cune;
        $oItem->Codigo_Nomina = $estado == "false" ? '' : $codigoNomina;
        $oItem->save();

        unset($oItem);
        return ("Nomina actualizada");
    } catch (\Throwable $th) {
        return ($th);
    }
}

function actualizarConfig()
{
    try {
        $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
        $oItem->Nomina_Electronica = $oItem->Nomina_Electronica + 1;
        $oItem->save();

        unset($oItem);
        return ("Actualizada configuracion");
    } catch (\Throwable $th) {
        return ($th->getMessage());
    }
}

function nominaElectronicaFuncionario($identificacion_Funcionario, $idnominafuncionario, $respuesta_dian, $cune, $codigoNomina)
{

    $estado = "true";

    try {
        $oItem = new complex("Nomina_Electronica_Funcionario ", "Id");
        $oItem->Identificacion_Funcionario = (int)$identificacion_Funcionario;
        $oItem->Id_Nomina_Funcionario = $idnominafuncionario;
        $oItem->Cune = $estado == "false" ? '' : $cune;
        $oItem->Fecha_Reporte = date('Y-m-d');
        $oItem->Estado  = $estado == "false" ? 'Error' : 'Exito';
        $oItem->Respuesta_Dian = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendNominaSyncResponse"]["SendNominaSyncResult"]["StatusMessage"];
        $oItem->Codigo_Nomina = $estado == "false" ? '' : $codigoNomina;
        $oItem->save();
        // echo json_encode($identificacion_Funcionario);

        unset($oItem);

        return ("Nomina electronica actualizada");
    } catch (\Throwable $th) {
        return ($th);
    }
}
