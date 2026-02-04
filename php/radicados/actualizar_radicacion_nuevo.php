<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

include_once '../../class/class.http_response.php';
include_once '../../class/class.utility.php';
include_once '../../class/class.querybasedatos.php';
require '../../class/class.awsS3.php';

$util = new Utility();
$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$response = array();

$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');
$facturas = (isset($_REQUEST['facturas']) ? $_REQUEST['facturas'] : '');
$id_facturas = (isset($_REQUEST['id_facturas']) ? $_REQUEST['id_facturas'] : '');
$cerrar_radicacion = (isset($_REQUEST['cerrar']) ? $_REQUEST['cerrar'] : '');

$modelo = json_decode($modelo, true);
$facturas = json_decode($facturas, true);
$id_facturas = json_decode($id_facturas, true);
$fecha = date('Y-m-d');

if (count($facturas) == 0) {

    $http_response->SetRespuesta(2, 'Alerta', 'No hay facturas para actualizar el registro, verifique o contacte al administrador del sistema!');
    $response = $http_response->GetRespuesta();
    echo json_encode($response);
    return;
}

/**
 * SE ACTUALIZAN LAS FACTURAS
 * */
foreach ($facturas as $factura) {
    GuardarGlosasFactura($factura['Glosas_Factura'], $factura['Id_Factura']);

    $oItem = new complex("Radicado_Factura", "Id_Radicado_Factura", $factura['Id_Radicado_Factura']);
    $oItem->Estado_Radicado_Factura = SetEstadoRadicadoFactura($factura['Id_Radicado_Factura'], true);
    $oItem->Total_Glosado = SetEstadoRadicadoFactura($factura['Id_Radicado_Factura']);
    $oItem->save();
    unset($oItem);

}

/**
 * SE CREA LA ACTIVIDAD DE ACTUALIZACION DE LAS FACTURAS
 * */
$cadena_facturas = $util->ArrayToCommaSeparatedString($id_facturas);
$detalle_actividad = 'Se editaron las facturas ' . $cadena_facturas;

GuardarActividadRadicado($modelo['Id_Radicado'], $modelo['Id_Funcionario'], $modelo['Codigo'], $detalle_actividad);
$http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado la radicacion exitosamente!');

/**
 * CERRAR RADICACION
 * */
if ($cerrar_radicacion == 'si') {

    $oItem = new complex("Radicado", "Id_Radicado", $modelo['Id_Radicado']);
    $oItem->Estado = "Cerrada";
    $oItem->Fecha_Cierre = $fecha;
    $oItem->save();
    unset($oItem);

    GuardarActividadCierre($modelo['Id_Radicado'], $modelo['Id_Funcionario'], $modelo['Codigo']);
    $http_response->SetRespuesta(0, 'Cierre Exitoso', 'Se ha cerrado la radicacion exitosamente!');
}

$response = $http_response->GetRespuesta();
unset($http_response);

echo json_encode($response);

function GuardarGlosasFactura($glosas, $idFactura)
{
    $id_radicado_factura = $glosas[0]['Id_Radicado_Factura'];

    $idGlosasActualizar = ArmarCadena($glosas);
    InhabilitarGlosasFactura($idGlosasActualizar, $id_radicado_factura);

    /**
     * ASIGNAR DATOS DE GLOSAS Y ACTUALIZARLAS
     */
    if (count($glosas) > 0) {

        foreach ($glosas as $g) {

            $factura = new complex('Factura', 'Id_Factura', $idFactura);
            $factura = $factura->getData();

            $archivo = "$factura[Codigo]_$g[Codigo_Glosa]";

            $s3 = new AwsS3();

            $ruta = "glosas/$factura[Codigo]";

            $idGlosaFactura = ExisteRegistroGlosaFactura($g['Codigo_Glosa'], $id_radicado_factura, $idFactura);

            if ($idGlosaFactura == '0') {
                $oItem = new complex("Glosa_Factura", "Id_Glosa_Factura");

                $oItem->Codigo_Glosa = $g['Codigo_Glosa'];
                $oItem->Id_Codigo_Especifico_Glosa = $g['Id_Codigo_Especifico_Glosa'];
                $oItem->Id_Codigo_General_Glosa = $g['Id_Codigo_General_Glosa'];
                $oItem->Id_Radicado_Factura = $g['Id_Radicado_Factura'];
                $oItem->Valor_Glosado = floatval(number_format($g['Valor_Glosado'], 2, ",", ""));
                $oItem->Observacion_Glosa = $g['Observacion_Glosa'];
                $oItem->save();
                unset($oItem);
            } else {

                $uri = "";
                if (!empty($_FILES[$archivo]['name'])) {
                    /**
                     * GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
                     * */
                    try {
                        $uri = $s3->putObject($ruta, $_FILES[$archivo]);
                    } catch (\Throwable $th) {
                    }
                }

                $oItem = new complex("Glosa_Factura", "Id_Glosa_Factura", $idGlosaFactura);
                $oItem->Codigo_Glosa = $g['Codigo_Glosa'];
                $oItem->Id_Codigo_Especifico_Glosa = $g['Id_Codigo_Especifico_Glosa'];
                $oItem->Id_Codigo_General_Glosa = $g['Id_Codigo_General_Glosa'];
                $oItem->Radicado_Glosa = $g['Radicado_Glosa'];
                $oItem->Valor_Glosado = floatval(number_format($g['Valor_Glosado'], 2, ",", ""));
                $oItem->Observacion_Glosa = $g['Observacion_Glosa'];
                $oItem->Fecha_Registro = date('Y-m-d H:i:s');
                $oItem->Estado = 'Activa';
                $oItem->Archivo_Glosa = $uri;
                $oItem->Id_Radicado_Factura = $g['Id_Radicado_Factura'];
                $oItem->save();
                unset($oItem);
            }
        }
    }
}

function GuardarActividadRadicado($idRadicado, $idFuncionario, $codigo, $detalle)
{

    $oItem = new complex("Actividad_Radicado", "Id_Actividad_Radicado");
    $oItem->Id_Funcionario = $idFuncionario;
    $oItem->Id_Radicado = $idRadicado;
    $oItem->Detalle = 'Se edito la radicacion con codigo ' . $codigo;
    $oItem->Estado = 'Edicion';
    $oItem->save();
    unset($oItem);
}

function GuardarActividadCierre($idRadicado, $idFuncionario, $codigo)
{

    $oItem = new complex("Actividad_Radicado", "Id_Actividad_Radicado");
    $oItem->Id_Funcionario = $idFuncionario;
    $oItem->Id_Radicado = $idRadicado;
    $oItem->Detalle = 'Se ha cerrado la radicacion codigo ' . $codigo;
    $oItem->Estado = 'Cerrado';
    $oItem->save();
    unset($oItem);
}

function ArmarCadena($glosas)
{
    $ids = '';

    if (count($glosas) > 0) {
        foreach ($glosas as $g) {

            $ids .= $g['Codigo_Glosa'] . ', ';
        }
    } else {

        $ids = '0';
    }

    return trim($ids, ", ");
}

function InhabilitarGlosasFactura($idGlosas, $idRadicadoFactura)
{
    global $queryObj;

    if ($idGlosas != '0') {

        $query = "SELECT
					IFNULL(GROUP_CONCAT(Id_Glosa_Factura), '0') AS Id_Glosas_Inactivar
				FROM Glosa_Factura
				WHERE
					Id_Radicado_Factura =  $idRadicadoFactura
             AND Codigo_Glosa NOT IN ( $idGlosas )";

        $queryObj->SetQuery($query);
        $result = $queryObj->ExecuteQuery('simple');

        if ($result !== false) {

            $query_update = "UPDATE Glosa_Factura SET Estado = 'Inactiva' WHERE Id_Glosa_Factura IN ($result[Id_Glosas_Inactivar] ) AND Id_Radicado_Factura = $idRadicadoFactura";

            $queryObj->SetQuery($query_update);
            $queryObj->QueryUpdate();
        }
    }
}

function ExisteRegistroGlosaFactura($idTipoGlosa, $idRadicadoFactura, $idFactura)
{
    global $queryObj;

    $query = "SELECT
				GF.Id_Glosa_Factura
			FROM Glosa_Factura GF
			INNER JOIN Radicado_Factura RF ON GF.Id_Radicado_Factura = RF.Id_Radicado_Factura
			WHERE
				GF.Id_Radicado_Factura =  $idRadicadoFactura
        		AND GF.Codigo_Glosa = '$idTipoGlosa' AND RF.Id_Factura =  $idFactura";

    $queryObj->SetQuery($query);
    $result = $queryObj->ExecuteQuery('simple');

    return $result !== false ? $result['Id_Glosa_Factura'] : '0';
}

function SetEstadoRadicadoFactura($idRadicadoFactura, $estado = false)
{
    global $queryObj;

    $query = "SELECT
				IFNULL(SUM(Valor_Glosado), 0) AS Total_Glosado
			FROM Glosa_Factura
			WHERE
				Id_Radicado_Factura = $idRadicadoFactura AND Estado = 'Activa' ";

    $queryObj->SetQuery($query);
    $result = $queryObj->ExecuteQuery('simple');

    if ($estado) {
        if ($result !== false && floatval($result['Total_Glosado']) > 0) {
            return "Glosada";
        } else if ($result !== false && floatval($result['Total_Glosado']) == 0) {
            return "Radicada";
        } else if ($result === false) {
            return "Radicada";
        }

    } else {
        if ($result !== false && floatval($result['Total_Glosado']) > 0) {
            return $result['Total_Glosado'];
        } else if ($result !== false && floatval($result['Total_Glosado']) == 0) {
            return $result['Total_Glosado'];
        } else if ($result === false) {
            return "0";
        }
    }

}
