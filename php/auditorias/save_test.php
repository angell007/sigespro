<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.http_response.php';
include_once '../../helper/calculateCuota.php';

require '../../class/class.awsS3.php';

$http_response = new HttpResponse();

$files = (isset($_FILES) ? $_FILES : '');
$soportes = (isset($_REQUEST['Soportes']) ? $_REQUEST['Soportes'] : '');
$soportes = json_decode($soportes, true);

$paciente = isset($_REQUEST['Paciente']) ? $_REQUEST['Paciente'] : '';
$paciente = json_decode($paciente, true);

$errores = (isset($_REQUEST['Errores']) ? $_REQUEST['Errores'] : []);
$errores = json_decode($errores, true);

$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');
$modelo = json_decode($modelo, true);

$dispensaciones = (isset($_REQUEST['Dispensaciones']) ? $_REQUEST['Dispensaciones'] : '');
$dispensaciones = (array) json_decode($dispensaciones, true);

$camposDinamicosProd = (isset($_REQUEST['CamposDinamicos']) ? $_REQUEST['CamposDinamicos'] : '');
$camposDinamicosProd = json_decode($camposDinamicosProd, true);

$observacion = isset($_REQUEST['Observacion']) ? $_REQUEST['Observacion'] : '';

$columnPac = ['Nit', 'Id_Nivel', 'Id_Regimen', 'EPS'];
$columnDis = ['CIE', 'IPS', 'Fecha_Formula', 'EPS', 'Doctor'];
$funcionarioAudita = $modelo['Identificacion_Funcionario'];

if (array_key_exists('Id_Eps', $paciente) && $paciente['Id_Eps']) {
    # code...
    $query = 'SELECT Nombre, Nit FROM Eps WHERE Id_Eps = ' . $paciente['Id_Eps'];
    $oCon = new consulta();
    $oCon->setQuery($query);
    $eps = $oCon->getData();

    $paciente['EPS'] = $eps['Nombre'];
    $paciente['Nit'] = $eps['Nit'];
}

$oItem = new complex("Paciente", "Id_Paciente", $modelo['Id_Paciente'], 'Varchar');

$save = false;
$set = '';
foreach ($columnPac as $key => $value) {
    # code...
    if (array_key_exists($value, $paciente) && $paciente[$value]) {
        $save = true;
        $set .= ' ' . $value . ' = ' . $paciente[$value];
        $oItem->$value = $paciente[$value];
    }
}

$save == true ? $oItem->save() : '';
$save = false;
$pacienteDB = $oItem->getData();
unset($oItem);
$auditorias = [];
foreach ($dispensaciones as $dispensacion) {
    array_push($auditorias, actualizarDatos($dispensacion, $modelo['Estado']));
}
guardarSoportes($auditorias);

$auditorias = implode(',', $auditorias);
$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos de la Auditoria! ' . $auditorias);
$response = $http_response->GetRespuesta();
echo json_encode($response);

function actualizarDatos($dispensacion, $estado)
{
    global $camposDinamicosProd, $columnDis, $paciente, $pacienteDB, $soportes, $files, $funcionarioAudita, $observacion;

    $itemAud = new complex('Auditoria', 'Id_Dispensacion', $dispensacion['Id_Dispensacion']);
    $auditoria = $itemAud->getData();
    unset($itemAud);

    setProductos($camposDinamicosProd, $dispensacion['Productos']);

    $oItem = new complex("Dispensacion", "Id_Dispensacion", $dispensacion['Id_Dispensacion']);

    foreach ($columnDis as $key => $value) {
        # code...
        if (array_key_exists($value, $paciente) && $paciente[$value]) {
            $save = true;
            $oItem->$value = $paciente[$value];
        }
    }

    $save == true ? $oItem->save() : '';

    $dis = $oItem->getData();

    $oItem = new complex("Dispensacion", "Id_Dispensacion", $dispensacion['Id_Dispensacion']);
    $oItem->Estado_Auditoria = 'Auditada';
    $oItem->save();

    if ($dis['EPS'] != 'Positiva') {
        if (!$dis['Cuota'] || $dis['Cuota'] == 0) {

            $pacienteDB = CalculoMaxCuota($pacienteDB);
            $prod = [];
            $pacienteDB['Cuota'] = cuotaRecuperacion($dispensacion['Productos'], $pacienteDB);
            if ($pacienteDB['Cuota']) {
                $oItem = new complex("Dispensacion", "Id_Dispensacion", $dispensacion['Id_Dispensacion']);
                $oItem->Cuota = $pacienteDB['Cuota'];
                $oItem->save();
            }
        }

    }

    $oItem = new complex("Auditoria", "Id_Auditoria", $auditoria['Id_Auditoria']);
    $oItem->Estado = $estado =='Rechazar'? 'Con Observacion': $estado;
    $oItem->save();
    unset($oItem);

    $estado = trim($estado, ', ');

    if ($estado == '') {
        $estado = 'Ninguno';
    }
    $datalle = ObtenerTexto($estado, $observacion);

    GuardarActividadAuditoria($auditoria['Id_Auditoria'], $funcionarioAudita, $datalle, $observacion);
    GuardarActividadDispensacion($funcionarioAudita, $dispensacion['Id_Dispensacion'], $datalle);

    if ($estado == 'Rechazar') {

        GuardarAlerta($auditoria['Id_Auditoria']);
    }
    # code...
    return $auditoria['Id_Auditoria'];
}

function guardarSoportes(array $auditorias)
{

    global $soportes, $funcionarioAudita, $files;
    $estado = '';

    #actualiza soportes
    foreach ($soportes as $key => $value) {
        # code...
        $oItem = new complex("Soporte_Auditoria", "Id_Soporte_Auditoria", $value["Id_Soporte_Auditoria"]);
        if (!$value['Cumple'] || $value['Cumple'] == "No") {
            $oItem->Cumple = "0";
            $estado .= $value['Tipo_Soporte'] . ", ";
        } else {

            $oItem->Cumple = "1";
        }

        //$oItem->save();

        if (array_key_exists('newFile', $value)) {
            try {
                $s3 = new AwsS3();

                $ruta = 'dispensacion/auditoria/soportes/' . $value['Id_Auditoria'] . '/' . $value['Id_Tipo_Soporte'];
                $uri = $s3->putObject($ruta, $files[$value['Id_Tipo_Soporte']]);
                //$oItem = new complex('Soporte_Auditoria', 'Id_Soporte_Auditoria');
                GuardarActividadAuditoria($auditorias[0]['Id_Auditoria'], $funcionarioAudita, 'Cambio de soporte' . $value['Tipo_Soporte'], 'Se ha modificado  el archvio de ' . $value['Tipo_Soporte']);

                
                /* $oItem->Id_Tipo_Soporte = $value['Id_Tipo_Soporte'];
                $oItem->Tipo_Soporte = $value['Tipo_Soporte'];
                $oItem->Id_Auditoria = $auditoria; */
                
                /* if (!$value['Cumple'] || $value['Cumple'] == "No") {
                    $oItem->Cumple = "0";
                } else {
                    
                    $oItem->Cumple = "1";
                } */
                
                $oItem->Archivo = $uri;
                
                /* foreach ($auditorias as $auditoria) {
                    
                } */
            } catch (Aws\S3\Exception\S3Exception $e) {
                
            }
            
        } else {
            
        }
        $oItem->save();
        unset($oItem);
    }

}
function GuardarAlerta($idAuditoria)
{
    $idFun = ObtenerFuncionario($idAuditoria);
    if ($idFun) {
        $oItem = new complex("Alerta", "Id_Alerta");

        $oItem->Identificacion_Funcionario = $idFun;
        $oItem->Tipo = "Auditoria";
        $oItem->Detalles = "La Auditoria con codigo: AUD00" . $idAuditoria . " presenta algunos errores con los documentos, por favor revise";
        $oItem->Modulo = "/corregirdocumentos";
        $oItem->Id = $idAuditoria;
        $oItem->save();

        unset($oItem);
    }

    $func = GetFuncionarioDispensacion($idAuditoria);
    if ($func['Identificacion_Funcionario']) {

        if ($func['Identificacion_Funcionario'] != ObtenerFuncionario($idAuditoria)) {
            $oItem = new complex("Alerta", "Id_Alerta");
            $oItem->Identificacion_Funcionario = $func['Identificacion_Funcionario'];
            $oItem->Tipo = "Auditoria";
            $oItem->Detalles = "La Auditoria con codigo: AUD00" . $idAuditoria . " presenta algunos errores con los documentos, por favor revise";
            $oItem->Modulo = "";
            $oItem->Id = $idAuditoria;
            $oItem->save();
            unset($oItem);
        }
    }
}
function ObtenerFuncionario($idAuditoria)
{
    $query = 'SELECT Funcionario_Preauditoria as Identificacion_Funcionario FROM Auditoria WHERE Id_Auditoria=' . $idAuditoria;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $funcionario = $oCon->getData();
    unset($oCon);
    return $funcionario['Identificacion_Funcionario'];
}
function GetFuncionarioDispensacion($idAuditoria)
{
    $query = 'SELECT D.Identificacion_Funcionario  FROM Auditoria A INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion WHERE A.Id_Auditoria=' . $idAuditoria;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $auditoria = $oCon->getData();
    unset($oCon);
    return $auditoria;
}

function GuardarActividadAuditoria($idAuditoria, $idFuncionario, $tipo, $observacion)
{

    if ($idFuncionario) {
        $oItem = new complex("Actividad_Auditoria", "Id_Actividad_Auditoria");
        $oItem->Identificacion_Funcionario = $idFuncionario;
        $oItem->Id_Auditoria = $idAuditoria;
        $oItem->Detalle = $tipo;
        $oItem->Estado = 'Validacion';
        $oItem->Fecha = date("Y-m-d H:i:s");
        $oItem->Observacion = $observacion != '' ? $observacion : "Sin Observacion";
        $oItem->save();
        unset($oItem);
    }
}

function GuardarActividadDispensacion($idFuncionario, $Id_Dispensacion, $datalle)
{
    if ($idFuncionario) {
        $oItem = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
        $oItem->Identificacion_Funcionario = $idFuncionario;
        $oItem->Id_Dispensacion = $Id_Dispensacion;
        $oItem->Fecha = date("Y-m-d H:i:s");
        $oItem->Detalle = $datalle;
        $oItem->Estado = 'Auditada';
        $oItem->save();
        unset($oItem);
    }
}
function ObtenerTexto($tipo, $observacion)
{
    $texto = '';
    if ($tipo == 'Aceptar') {
        $texto = 'Se verifica que toda la informacion de la auditoria es correcta, Nota: ' . $observacion;
    } elseif ($tipo == 'Rechazar') {
        $texto = 'Se evidencia que hay algunos errores, se anexa la siguiente observacion : ' . $observacion;
    }
    return $texto;
}
function setProductos($campos, $productos)
{

    foreach ($productos as $key => $producto) {
        # code...
        $oItem = new complex("Producto_Dispensacion", "Id_Producto_Dispensacion", $producto["Id_Producto_Dispensacion"]);
        $oItem->Generico = $producto['Generico'];
        foreach ($campos as $campo) {
            # code...
            if (array_key_exists($campo['Field_Name'], $producto) && $producto[$campo['Field_Name']]) {

                $pos = $campo['Field_Name'];
                $oItem->$pos = $producto[$campo['Field_Name']];
            }
        }
        $oItem->save();
        unset($oItem);

    }
}
