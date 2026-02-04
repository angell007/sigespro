<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../vendor/autoload.php';
require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.nomina.php';
include_once '../../class/class.parafiscales.php';
include_once '../../class/class.provisiones.php';

use Carbon\Carbon as Carbon;

$datos = (isset($_REQUEST['reporte']) ? $_REQUEST['modelo'] : '');
$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');
$modelo = (array) json_decode($modelo, true);
$condicion = '';

$idnomina = (isset($_REQUEST['datos']) ? ($_REQUEST['datos']) : $modelo['Id_Nomina']);
$condicion = isset($_REQUEST['reporte']) ? " FN.Identificacion_Funcionario = $modelo[Identificacion_Funcionario] AND " : '';

if (isset($_REQUEST['datos']) || isset($_REQUEST['reporte'])) {

    $funcionarios = getData();
}

function getData()
{

    global $idnomina, $condicion;

    $query = "SELECT F.Identificacion_Funcionario,
    IFNULL(F.Nombres, CONCAT(F.Primer_Nombre, F.Segundo_Nombre)) as Nombres,
    IFNULL(F.Apellidos, CONCAT(F.Primer_Apellido, F.Segundo_Apellido)) as Apellidos,
    FN.Id_Nomina_Funcionario,
    N.Fecha_Inicio,
    N.Fecha_Fin,
    N.Tipo_Nomina,
    N.Fecha,
    N.Codigo as codigoNomina,
    M.Codigo_Dane,
    TP.Cod_Dian,
    D.Codigo,
    CF.Fecha_Inicio_Contrato,
    CF.Fecha_Fin_Contrato, 
    FN.Codigo_Nomina,
    PF.PROVISIONES
    FROM Nomina_Funcionario FN

      INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FN.Identificacion_Funcionario
      INNER JOIN Nomina N on N.Id_Nomina = FN.Id_Nomina
      INNER JOIN Tipo_Documento TP ON TP.Id_Tipo_Documento = F.Id_Tipo_Documento
      INNER JOIN Contrato_Funcionario CF ON CF.Identificacion_Funcionario = F.Identificacion_Funcionario AND CF.Fecha_Inicio_Contrato <= N.Fecha_Fin AND CF.Fecha_Fin_Contrato>=N.Fecha_Inicio
      LEFT JOIN Municipio M ON M.Id_Municipio = F.Id_Municipio
      LEFT JOIN Departamento D ON D.Id_Departamento = F.Id_Departamento
      -- Se arma el array de las provisiones del funcionario
      LEFT JOIN (SELECT CONCAT('[',GROUP_CONCAT(CONCAT('{\"Tipo\":\"',PF.Tipo, '\",\"Valor\":\"',PF.Valor,'\",\"Porcentaje\":\"',P.Porcentaje*100,'\",\"Cantidad\":\"',IFNULL(PF.Cantidad, 0), '\"}')), ']') AS PROVISIONES, PF.Identificacion_Funcionario, PF.Id_Nomina 
                        FROM Provision_Funcionario PF 
                        LEFT JOIN Provision P ON P.Prefijo = PF.Tipo
                        GROUP BY PF.Identificacion_Funcionario, PF.Id_Nomina
                        ) PF ON PF.Identificacion_Funcionario= F.Identificacion_Funcionario AND PF.Id_Nomina = N.Id_Nomina

      WHERE 
         $condicion
            (FN.Procesado = 'false' OR FN.Estado_Nomina = 'Eliminado'  ) AND F.Tipo != 'Externo' and N.Id_Nomina =  $idnomina Limit 1";

    $oCon = new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $funcionarios = $oCon->getData();
    unset($oCon);

// echo json_encode($funcionarios);

// exit;
    return $funcionarios;
}


$con = new complex("Configuracion", "Id", 1);
$configuracion = $con->getData();
unset($con);

try {

    $i = -1;
    foreach ($funcionarios as $func) {
        $i++;

        $mes_actual = date('m', strtotime($func['Fecha_Inicio']));
        $anio_actual = date('Y', strtotime($func['Fecha_Inicio']));
        $mensualidad = "'$anio_actual-$mes_actual%'";
        $quincena = "%";

        $fini = $func['Fecha_Inicio'];
        $ffin = $func['Fecha_Fin'];

        $ini = $fini;
        if ($func['Fecha_Inicio_Contrato'] > $fini) {
            $ini = $func['Fecha_Inicio_Contrato'];
        }
        $fin = $ffin;
        if ($func['Fecha_Fin_Contrato'] >= $fini && $func['Fecha_Fin_Contrato'] < $ffin) {

            $fin = $func['Fecha_Fin_Contrato'];
        }

        // echo $func['Identificacion_Funcionario']; exit;
        $funcionario = new CalculoNomina($func['Identificacion_Funcionario'], $quincena, $ini, $fin, 'Nomina',  $mensualidad, "Activo");

        $funcionario = $funcionario->CalculosNomina(false);

        $base_aux = $funcionario['Salario_Quincena'] + $funcionario['Auxilio'] + $funcionario['Total_Incapacidades'];

        $provisiones=new CalculosProvisiones($base_aux,$func['Identificacion_Funcionario'],$fini,$ffin, $funcionario['Salario_Quincena']);  
        $provisiones=$provisiones->CalcularProvisiones();

        // echo json_encode($provisiones);
        // exit;
        unset($oItem);


        //  echo json_encode($funcionario["Contrato"]);exit;

        $funcionarios[$i]['type_document_id'] = 7;
        $funcionarios[$i]['resolution_number'] = 2;
        $funcionarios[$i]['resolution_id'] = 7;
        $funcionarios[$i]['date_pay'] = date("Y-m-d", strtotime($func["Fecha"]));
        $funcionarios[$i]['date'] = date("Y-m-d", strtotime($func["Fecha"]));

        // echo $func["Fecha"];
        $hour = new Carbon($func["Fecha"]);

        $funcionarios[$i]['hour'] = $hour->format('H:i:sP');
        $funcionarios[$i]['payroll_period'] = $func["Tipo_Nomina"];

        $totalDevengados = $funcionario["datos_dian"]["totalDian"]["Valor"] + $funcionario["Deducciones"];
        $configPrefixCune = getConfigPrefix($func["Fecha"], $totalDevengados, $funcionario["Deducciones"], $funcionario["datos_dian"]["totalDian"]["Valor"], $func['Identificacion_Funcionario'], $hour, $func['Codigo_Nomina']);

        $funcionarios[$i]['cune_propio'] = hash('sha384', $configPrefixCune[2]);

        $funcionarios[$i]['observation'] = "Nomina Electronica";
        $funcionarios[$i]['prefix'] = $configPrefixCune[1];
        $funcionarios[$i]['number'] = $configPrefixCune[0];
        $funcionarios[$i]['code'] = $configPrefixCune[1] . $configPrefixCune[0];
        $funcionarios[$i]['file'] = $configPrefixCune[1] . $configPrefixCune[0];

        $funcionarios[$i]['date_start_period'] = $func["Fecha_Inicio"];
        $funcionarios[$i]['date_end_period'] = $func["Fecha_Fin"];

        $funcionarios[$i]["integration_date"] = date('Y-m-d', strtotime($funcionario["Contrato"]["Fecha_Inicio_Contrato"]));
        $funcionarios[$i]['person']["historic_worked_time"] = $funcionario["tiempo_laborado"];
        $funcionarios[$i]['person']["salary"] = $funcionario["Contrato"]["Valor"]; //valor contrato
        $funcionarios[$i]['person']["code"] = $funcionario["Contrato"]["CodigoFun"];
        $funcionarios[$i]['person']["work_contract_type"]["code"] = $funcionario["Contrato"]["Cod_Dian"];
        $funcionarios[$i]['person']["salary_integral"] = "true";
        $funcionarios[$i]['person']["worker_type"]["code"] = $funcionario["Contrato"]["Codigo_Tipo"];
        $funcionarios[$i]['person']["worker_subtype"]["code"] = "00";
        $funcionarios[$i]['person']["work_contract_type"]["code"] = 1;
        $funcionarios[$i]['person']['high_risk_pension'] = "false";
        // echo $func["Identificacion_Funcionario"];exit;
        $funcionarios[$i]['person']['identifier'] = $func["Identificacion_Funcionario"];
        
        $apellidos=explode("-", $func["Apellidos"]);
        $apellidos = explode(" ", $apellidos[0]);
    
        if(count($apellidos)>2){
            $seg_apellido=implode(" ", array_splice($apellidos, 1, count($apellidos)));
            $apellidos[1]= trim($seg_apellido);
        }
        if(!isset($apellidos[1]) || $apellidos[1]=="")
            $apellidos[1]=".";
        
        $funcionarios[$i]['person']['first_name'] = $func['Nombres'];
        $funcionarios[$i]['person']['last_name'] = $apellidos[0];
        $funcionarios[$i]['person']['last_names'] = $apellidos[1];

        $funcionarios[$i]['person']['type_document_identification']['code'] = $func["Cod_Dian"];
        $funcionarios[$i]['person']['work_place']['country']['id'] = 46;
        $funcionarios[$i]['person']['work_place']['country']['code'] = "CO";
        $funcionarios[$i]['person']['work_place']['municipality']['id'] = 46;
        $funcionarios[$i]['person']['work_place']['municipality']['code'] = "68001";
        $funcionarios[$i]['person']['work_place']['municipality']['department']['code'] = "68";
        $funcionarios[$i]['person']['work_place']['addres'] = "Prueba";

        //  $funcionarios[$i]['person']["integration_date"]=$funcionario["Contrato"]["Fecha_Inicio_Contrato"];
        $funcionarios[$i]['pay']["payroll_pay_formate"] = [];
        $funcionarios[$i]['pay']["payroll_pay_formate"]["code"] = 1;

        $funcionarios[$i]['pay']["payroll_pay_method"]["code"] = "1";
        //   echo json_encode($funcionarios[$i]);exit;
        $funcionarios[$i]['accrued']["basic"]["worked_days"] = $funcionario["Dias_Laborados"];
        $funcionarios[$i]['accrued']["basic"]["salary_payroll"] = $funcionario["Total_Quincena"]; //pagado

        $provision = (array)json_decode($func['PROVISIONES'], true);
        
        
        
        // echo json_encode();
        // exit;
        if(isset($provisiones['Ajuste_Provisiones']['Ajustes'])){
            // $ajustes=$provisiones['Ajuste_Provisiones']['Ajustes'];
            foreach($provisiones['Ajuste_Provisiones']['Ajustes'] as $key=>$prov){
                // echo $key; exit;
                 if ($key == "Cesantias") {
                $funcionarios[$i]['accrued']["severance"]["value"] = number_format($prov['Valor'], 2, ".", ""); //pagado
                $funcionarios[$i]['accrued']["severance"]["percentage"] = number_format($prov['Porcentaje'], 2, ".", ""); //porcentaje
            }
            if ($key == "Interes_Cesantias") {
                $funcionarios[$i]['accrued']["severance"]["rate_pay"] = $prov['Valor']; //pago intereses
            }
            //Incluir para el reporte de Primas
            if ($key == "Prima") {
                $funcionarios[$i]['accrued']["prima"]["days"] = $prov['Cantidad']; //dias de prima
                $funcionarios[$i]['accrued']["prima"]["value"] = number_format($prov['Valor'], 2, ".", ""); //pagado Prima
            }

            if ($key == "Vacaciones") {
                $novedad_vacaciones = [
                    "days" => $prov["Cantidad"],
                    "value" => number_format($prov['Valor'], 2, ".", ""),
                ];
                $funcionarios[$i]['accrued']["vacations"]["cumn"] =$novedad_vacaciones ;
            }
            }
        // echo json_encode($funcionarios[$i]['accrued']); exit;
        }
        else{
        
        //  echo ($func['PROVISIONES']); exit;
        foreach ($provision as $prov) {
            //Incluir para el reporte de Cesantias
            if ($prov['Tipo'] == "Cesantias") {
                $funcionarios[$i]['accrued']["severance"]["value"] = number_format($prov['Valor'], 2, ".", ""); //pagado
                $funcionarios[$i]['accrued']["severance"]["percentage"] = number_format($prov['Porcentaje'], 2, ".", ""); //porcentaje
            }
            if ($prov['Tipo'] == "Interes_Cesantias") {
                $funcionarios[$i]['accrued']["severance"]["rate_pay"] = $prov['Valor']; //pago intereses
            }
            //Incluir para el reporte de Primas
            if ($prov['Tipo'] == "Prima") {
                $funcionarios[$i]['accrued']["prima"]["days"] = $prov['Cantidad']; //dias de prima
                $funcionarios[$i]['accrued']["prima"]["value"] = number_format($prov['Valor'], 2, ".", ""); //pagado Prima
            }

            if ($prov['Tipo'] == "Vacaciones") {
                $novedad_vacaciones = [
                    "days" => $prov["Cantidad"],
                    "value" => number_format($prov['Valor'], 2, ".", ""),
                ];
                $funcionarios[$i]['accrued']["vacations"]["cumn"] =$novedad_vacaciones ;
            }
        }}

        // echo json_encode($funcionarios[$i]['accrued']);exit;
        
        if ($funcionario["datos_dian"]["AuxilioTransporte"]["Valor"] != 0) {
            $funcionarios[$i]['accrued']["transport_subsidy"]["salarial"] = round($funcionario["datos_dian"]["AuxilioTransporte"]["Valor"]);
        }

        foreach ($funcionario["Lista_Novedades"] as $ln) {

            if ($ln["Id_Tipo_Novedad"] == 2 || $ln["Id_Tipo_Novedad"] == 3) {

                $inabi = [];
                $inabi = ["date_start" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "date_end" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "days" => $ln["Dias"], "type" => $ln["Codigo_Dian"], "value" => round(($funcionario["Sueldo"] * $ln["Dias"]) / 30)];
                $funcionarios[$i]['accrued']["inability"][] = $inabi;

                continue;
            }

            if ($ln["Id_Tipo_Novedad"] == 4 || $ln["Id_Tipo_Novedad"] == 5) {

                $novedadmp = [];
                $novedadmp = ["date_start" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "date_end" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "days" => $ln["Dias"], "type" => $ln["Tipo_Novedad"], "value" => 0];
                $funcionarios[$i]['accrued']["licences"]["mp"][] = $novedadmp;

                continue;
            }

            if ($ln["Id_Tipo_Novedad"] == 6) {

                $novedadr = [];
                $novedadr = ["date_start" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "date_end" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "days" => $ln["Dias"], "type" => $ln["Tipo_Novedad"], "value" => 0];
                $funcionarios[$i]['accrued']["licences"]["r"][] = $novedadr;

                continue;
            }

            if ($ln["Id_Tipo_Novedad"] == 7) {

                $novedadnr = [];
                $novedadnr = ["date_start" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "date_end" => date('Y-m-d', strtotime($ln["Fecha_Inicio"])), "days" => $ln["Dias"], "type" => $ln["Tipo_Novedad"], "value" => 0];
                $funcionarios[$i]['accrued']["licences"]["nr"][] = $novedadnr;

                continue;
            }
            
        }

        foreach ($funcionario["Bono_Funcionario"] as $bn) {;

            if ($bn['Valor'] == 0) {
                $bn['Valor'] = null;
            }

            if ($bn['Tipo_Bono'] == 2) {
                $valorb = [];
                $valorb = ["salarial" => $bn["Valor"]];
                $funcionarios[$i]['accrued']["bonus"][] = $valorb;
            } else {
                $b = [];
                $valorb = ["no_salarial" => $bn["Valor"]];
                $funcionarios[$i]['accrued']["bonus"][] = $valorb;
            }
        }

        foreach ($funcionario["Lista_Ingresos_No_Salariales"] as $nsa) {

            // echo json_encode($nsa);
            if ($nsa['Valor'] > 0) {
                if ($nsa["Id_Concepto_Parametro_Nomina"] == 17 || $nsa["Id_Concepto_Parametro_Nomina"] == 20 || $nsa["Id_Concepto_Parametro_Nomina"] == 25 || $nsa["Id_Concepto_Parametro_Nomina"] == 26) {
                    $valor = [];
                    $valor = ["no_salarial" => $nsa["Valor"]];
                    $funcionarios[$i]['accrued']["assistances"][] = $valor;

                    continue;
                }

                if ($nsa["Id_Concepto_Parametro_Nomina"] == 18 || $nsa["Id_Concepto_Parametro_Nomina"] == 19 | $nsa["Id_Concepto_Parametro_Nomina"] == 21 || $nsa["Id_Concepto_Parametro_Nomina"] == 22 || $nsa["Id_Concepto_Parametro_Nomina"] == 23 || $nsa["Id_Concepto_Parametro_Nomina"] == 24) {
                    $valor1 = [];
                    $valor1 = ["description" => $nsa["Nombre"], "no_salarial" => $nsa["Valor"], "salarial" => 1];
                    $funcionarios[$i]['accrued']["others"][] = $valor1;
                }
                // }

                //  if($nsa["Id_Concepto_Parametro_Nomina"] == 21 || $nsa["Id_Concepto_Parametro_Nomina"] == 22 || $nsa["Id_Concepto_Parametro_Nomina"] == 23 || $nsa["Id_Concepto_Parametro_Nomina"] == 24){
                //     $b= [];
                //     $valorb = ["no_salarial"=>$nsa["Valor"], "salarial"=>0];
                //     $funcionarios[$i]['accrued']["compensations"][]=$valorb;
                //  }
                $vCompensations = [];
                $vCompensations = ["value_ordanary" => 1, "value_extra_ordanary" => "0"];
                $funcionarios[$i]['accrued']["compensations"][] = $vCompensations;

                // $funcionarios[$i]['accrued']["compensations"]["value_ordanary"]=1;
                // $funcionarios[$i]['accrued']["compensations"]["value_extra_ordanary"]="0";

                $vcommissions = [];
                $vcommissions = ["value" => 1];
                $funcionarios[$i]['accrued']["commissions"][] = $vcommissions;

                $vthird = [];
                $vthird = ["value" => 1];
                $funcionarios[$i]['accrued']["third_payments"][] = $vthird;

                $vAdvance = [];
                $vAdvance = ["value" => 1];
                $funcionarios[$i]['accrued']["advances"][] = $vAdvance;
                // echo json_encode($funcionarios[$i]['accrued']);exit;
            }
        }

        // $funcionarios[$i]['accrued']["assitence_practical"]=0;
        // $funcionarios[$i]['accrued']["remote_work"]=0;

        $s = -1;
        foreach ($funcionario["Lista_Ingresos_Salariales"] as $sa) {
            $s++;
            // $funcionarios[$i]['accrued']["assistances"][$s]["no_salarial"]=0;
            if ($sa["Valor"] > 0) {
                $funcionarios[$i]['accrued']["assistances"][$s]["salarial"] = $sa["Valor"];
            }

        }

        $funcionarios[$i]["deductions"]["healt"] = $funcionario["datos_dian"]["Salud"];
        $funcionarios[$i]["deductions"]["pension_funds"] = $funcionario["datos_dian"]["Pension"];

        if ($funcionario["Prestamos_Funcionario"]['Valor_Cuota'] != 0) {
            foreach ($funcionario["Prestamos_Funcionario"] as $pr) {;
                $bn = [];
                $bn = ["description" => $pr['Observaciones'], "value" => $pr['Valor_Cuota']];
                $funcionarios[$i]["deductions"]["loans"][] = $bn;
            }
        }

        // $funcionarios[$i]["deductions"]["third_payments"]["value"]=0;
        // $funcionarios[$i]["deductions"]["advances"]["value"]=0;
        //  echo json_encode($funcionario["datos_dian"]["OtrasDeducciones"]);exit;
        if ($funcionario["datos_dian"]["OtrasDeducciones"] != 0) {
            $funcionarios[$i]["deductions"]["other_deductions"][0]["value"] = $funcionario["datos_dian"]["OtrasDeducciones"];
        }

        if ($funcionario["datos_dian"]["PensionV"]["Valor"] != 0) {
            $funcionarios[$i]["deductions"]["voluntary_pension"] = $funcionario["datos_dian"]["PensionV"]["Valor"];
            // $funcionarios[$i]["deductions"]["voluntary_pension"]="1414000.00";
        }
        //   echo json_encode($funcionarios[$i]["deductions"]);exit;

        if ($funcionario["datos_dian"]["Retencion"]["Valor"] > 0) {
            $funcionarios[$i]["deductions"]["source_retention"] = $funcionario["datos_dian"]["Retencion"]["Valor"];
        }

        // $funcionarios[$i]["deductions"]["education"]="0";
        // $funcionarios[$i]["deductions"]["refund"]="0";
        // $funcionarios[$i]["deductions"]["debt"]="0";

        $funcionarios[$i]["totals"]["rounded"] = "0";
        $funcionarios[$i]["totals"]["accrued"] = number_format($funcionario["datos_dian"]["totalDian"]["Valor"] + $funcionario["Deducciones"], 2, '.', '');
        $funcionarios[$i]["totals"]["deductions"] = number_format($funcionario["Deducciones"], 2, '.', '');
        $funcionarios[$i]["totals"]["voucher"] = number_format($funcionario["datos_dian"]["totalDian"]["Valor"], 2, '.', '');

        // echo json_encode($funcionario["datos_dian"]);
        // echo json_encode($funcionario["Deducciones"]);

        // echo json_encode($funcionarios[$i]);

        // $respuesta_dian =  GetApi($funcionarios[$i],$funcionario=false, $note= false);

        //   echo "123456";exit;
        // echo json_encode($respuesta_dian);

    }

    echo json_encode($funcionarios[$i]);
    http_response_code(503);
    exit;
} catch (\Throwable $th) {
    http_response_code(400);
    echo $th->getMessage();
}

function getConfigPrefix($fecha, $devengados, $deducciones, $total, $identificacion, $hora, $codigo_nomina = null)
{
    $v = number_format($devengados, 2, '.', '');

    $con = getConfig();
    $consecutivo = (isset($codigo_nomina) && $codigo_nomina !== '') ? str_replace($con[1], '', $codigo_nomina): $con[0];
    $cune_propio = $con[1] . $consecutivo . str_replace(" ", "", $fecha) . "-05:00" . number_format($devengados, 2, '.', '') . number_format($deducciones, 2, '.', '') . number_format($total, 2, '.', '') . "804016084" . $identificacion . "102" . "80401" . "1";

    return [$consecutivo, $con[1], $cune_propio];
}


function getConfig()
{

    $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
    $ne = $oItem->getData();
    $oItem->Nomina_Electronica = $oItem->Nomina_Electronica;
    $oItem->save();
    $con = $ne["Nomina_Electronica"];
    unset($oItem);

    return [$ne["Nomina_Electronica"], $ne["Prefijo_Nomina_Electronica"]];
}

function GetApi($datos, $funcionario, $note = false)
{
    // var_dump($funcionario);exit;
    $login = 'facturacion@prohsa.com';
    $password = '804016084';
    $host = 'https://api-dian.sigesproph.com.co';
    $api = '/api';
    $version = '/ubl2.1';
    // $modulo = '/payroll';
    $modulo = $note ? '/payroll-note' : '/payroll';
    $url = $host . $api . $version . $modulo;

    $data = json_encode($datos);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    // var_dump(base64_encode($login . ':' . $password));exit;
    $headers = array(
        "Content-type: application/json",
        "Accept: application/json",
        "Cache-Control: no-cache",
        "Authorization: Basic " . base64_encode($login . ':' . $password),
        "Pragma: no-cache",
        "SOAPAction:\"" . $url . "\"",
        "Content-length: " . strlen($data),
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    //var_dump(curl_error($ch));
    if (curl_errno($ch)) {
        $respuesta["Estado"] = "error";
        $respuesta["Error"] = '# Error : ' . curl_error($ch);
        return $respuesta;
    } elseif ($result) {
        $resp = json_encode($result);
        $json_output = json_decode($resp, true);
        $json_output = (array) json_decode($json_output, true);

        //var_dump($json_output);
        $mensaje = $json_output["message"];
        $respuesta["Cune"] = $json_output["cune"];
        $respuesta["Json"] = $json_output;
        // $respuesta["Enviado"] = $datos;
        if (strpos($mensaje, "invalid") !== false) {
            $respuesta["Estado"] = "error";
            $respuesta["Respuesta"] = $json_output["errors"];
        } else {
            $r = $json_output["ResponseDian"]["Envelope"]["Body"]["SendNominaSyncResponse"]["SendNominaSyncResult"];
            $estado = $r["IsValid"];

            $respuesta["Procesada"] = $estado;
            if ($estado == "true") {
                $respuesta["Estado"] = "exito";
                $respuesta["Respuesta"] = $r["StatusDescription"] . " - " . $r["StatusMessage"];
            } else {
                $respuesta["Estado"] = "error";
                $respuesta["Respuesta"] = '';
                foreach ($r["ErrorMessage"] as $e) {
                    echo json_encode($e);
                    $respuesta["Respuesta"] .= $e . " - ";
                }
                $respuesta["Respuesta"] .= $r["StatusMessage"];
                $respuesta["Respuesta"] = trim($respuesta["Respuesta"], " - ");
            }
        }

        return $respuesta;
        //   echo json_encode($respuesta);
    }
    function getNombre()
    {
        global $codigo;
        $nit = getNit();
        $codigo = (int) str_replace("NE", "", $codigo);
        $nombre = "nie" . str_pad($nit, 10, "0", STR_PAD_LEFT) . "" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
        return $nombre;
    }

    function getNit()
    {
        $con = new complex("Configuracion", "Id_Configuracion", 1);
        $configuracion = $con->getData();
        $nit = explode("-", $configuracion['NIT']);
        $nit = str_replace(".", "", $nit[0]);
        return $nit;
    }
}
