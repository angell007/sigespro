<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-type:application/json');
error_reporting(0);

include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
require_once 'HTTP/Request2.php';
require 'elibom/elibom.php';
require_once '../../config/start.inc.php';
// $_REQUEST

date_default_timezone_set('America/Bogota');

$dias = array(
    0 => "Domingo",
    1 => "Lunes",
    2 => "Martes",
    3 => "Miercoles",
    4 => "Jueves",
    5 => "Viernes",
    6 => "Sabado",
);
$error = null;

$foto = guardarImagen();
// $foto="foto5eccf73cdfc75.jpg";
$face_id = detectarRostro($foto);

if ($face_id != "") {
    $resp = identificarPersona($face_id);
    try {
        if (is_array($resp)) {
            $candidatos = $resp[0]->candidates[0];

            if (!is_null($candidatos)) {
                $candidato = $candidatos->personId;
                if ($candidato != "") {
                    $oItem = new complex('Funcionario', 'personId', $candidato);
                    $funcionario = $oItem->getData();
                    unset($oItem);
                    $hactual = date("H:i:s");
                    $hoy = date('Y-m-d');
                    $ayer = date("Y-m-d", strtotime(date("Y-m-d") . ' - 1 day'));
                    if ($funcionario["Autorizado"] !== 'Si') {

                        $respuesta = armarRespuesta("error", "No permitido", "Funcionario no Autorizado");
                    } elseif ($funcionario["Tipo_Turno"] == "Rotativo") {
                        $respuesta = armarRespuesta("error", "No permitido", "Su turno asignado no está disponible");
                    } elseif ($funcionario["Tipo_Turno"] == "Fijo") {
                        $respuesta = turnoFijo($funcionario, $foto);
                    } elseif ($funcionario["Tipo_Turno"] == "Libre") {
                        $respuesta = turnoLibre($funcionario, $foto);
                    }

                } else {
                    $respuesta = armarRespuesta("error", "Acceso No autorizado", "Persona sin informacion");
                }
            } else {
                $respuesta = armarRespuesta("error", "Acceso Denegado", "Su Cara no es conocida, comuniquese con un administrador");
            }

        } else {
            $respuesta = armarRespuesta("error", "Acceso No Autorizado", "Su rostro no se encuentra en nuestros registros");
        }

    } catch (\Throwable $ex) {
        $respuesta = armarRespuesta("error", "Error del sistema", $ex->getMessage());
    }
} else {
    $respuesta = armarRespuesta("error", "Acceso Denegado", "Error inesperado en la identificación - $error");
}

echo json_encode($respuesta);

function RestarHoras($horaini, $horafin)
{
    $fecha1 = new DateTime($horaini); //fecha inicial
    $fecha2 = new DateTime($horafin); //fecha de cierre

    $intervalo = $fecha1->diff($fecha2);
    $negativo = $horaini < $horafin;
    $dif = $negativo ? '-' : '';
    $dif .= date("H:i:s", mktime($intervalo->h, $intervalo->i, $intervalo->s));
    return $dif;
}

function identificarPersona($face_id)
{
    global $AZURE_ID, $AZURE_GRUPO;
    $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/identify');
    $url = $request->getUrl();
    $headers = array(
        'Content-Type' => 'application/json',
        'Ocp-Apim-Subscription-Key' => $AZURE_ID,
    );
    $request->setConfig(array(
        'ssl_verify_peer' => false,
        'ssl_verify_host' => false,
    ));
    $request->setHeader($headers);
    $parameters = array(
    );
    $url->setQueryVariables($parameters);
    $request->setMethod(HTTP_Request2::METHOD_POST);
    $body = array(
        'personGroupId' => $AZURE_GRUPO,
        'faceIds' => [
            $face_id,
        ],
        "confidenceThreshold" => 0.6,
        "maxNumOfCandidatesReturned" => 1,
    );
    $request->setBody(json_encode($body));

    // var_dump($request); exit;

    $response = $request->send();
    $resp = $response->getBody();
    $resp = json_decode($resp);
    return $resp;
}
function detectarRostro($fot)
{
    global $AZURE_ID, $error;
    /* INICIO DETECCION DE CARA*/
    $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/detect');
    $url = $request->getUrl();
    $headers = array(
        'Content-Type' => 'application/json',
        'Ocp-Apim-Subscription-Key' => $AZURE_ID,
    );
    $request->setConfig(array(
        'ssl_verify_peer' => false,
        'ssl_verify_host' => false,
    ));
    $request->setHeader($headers);
    $parameters = array(
        'returnFaceId' => 'true',
        'returnFaceLandmarks' => 'false',
        'recognitionModel' => 'recognition_01',
        'returnRecognitionModel' => 'false',
        'detectionModel' => 'detection_01',
    );
    $url->setQueryVariables($parameters);
    // echo json_encode($url); exit;
    $request->setMethod(HTTP_Request2::METHOD_POST);
    $URL = "https://sigesproph.com.co/";
    $body = array(
        'url' => $URL . 'IMAGENES/TEMPORALES/' . $fot,
    );
    $request->setBody(json_encode($body));

    try {
        $response = $request->send();
        $resp = $response->getBody();
        $code = $response->getStatus();
        
        $resp = json_decode($resp, true);
        if (is_array($resp) && $code =='200') {
            $face_id = $resp[0]->faceId;
        }else{

            $error = $resp['error']['innererror']['message'];
        }
    } catch (\Throwable $ex) {
        echo $ex->getMessage();
    }
    return $face_id;
    /* FIN DETECCION DE CARA */
}
function guardarImagen()
{

    global $MY_FILE;
    $imagen = (isset($_REQUEST['imagen']) ? $_REQUEST['imagen'] : '');

    list($type, $imagen) = explode(';', $imagen);
    list(, $imagen) = explode(',', $imagen);
    $imagen = base64_decode($imagen);
    $fot = "foto" . uniqid() . ".jpg";
    $archi = $MY_FILE . "IMAGENES/TEMPORALES/" . $fot;
    file_put_contents($archi, $imagen);
    chmod($archi, 0644);

    return $fot;
    // http_response_code(404);
}

function turnoFijo($funcionario, $fot)
{
    global $dias, $hoy, $hactual, $URL, $latitud, $longitud;

    if ($funcionario["Id_Turno"] != 0) {

        //Validacion de dia laboral
        $tipo_dia = date("w", strtotime($hoy));
        $oLista = new lista('Hora_Turno');
        $oLista->setRestrict("Id_Turno", "=", $funcionario["Id_Turno"]);
        $oLista->setRestrict("Dia", "=", $dias[$tipo_dia]);
        $horas = $oLista->getList();
        $horas = $horas[0];

        if (isset($horas)) { //validacion de dia de turno
            $oItem = new complex('Turno', 'Id_Turno', $funcionario["Id_Turno"]);
            $turno = $oItem->getData();
            unset($oItem);

            $oLista = new lista('Diario_Fijo');
            $oLista->setRestrict("Identificacion_Funcionario", "=", $funcionario["Identificacion_Funcionario"]);
            $oLista->setRestrict("Fecha", "=", $hoy);
            $diario = $oLista->getList();
            $diario = $diario[0];

            // Si no tiene registros del dia, se registra la hora de entrada
            if (!isset($diario["Id_Diario_Fijo"]) && $horas['Hora_Inicio1'] !== "" && $horas['Hora_Inicio1'] !== "00:00:00") {

                $oItem = new complex('Diario_Fijo', 'Id_Diario_Fijo');
                $oItem->Identificacion_Funcionario = $funcionario["Identificacion_Funcionario"];
                $oItem->Fecha = $hoy;
                $oItem->Id_Turno = $turno["Id_Turno"];
                $oItem->Hora_Entrada1 = $hactual;
                $oItem->Img1 = $fot;
                if ($latitud != "") {
                    $oItem->Latitud = $latitud;
                }
                if ($longitud != "") {
                    $oItem->Longitud = $longitud;
                }
                $oItem->save();
                unset($oItem);

                $diferencia = RestarHoras($hactual, $horas["Hora_Inicio1"]);
                $h_inicio = $horas["Hora_Inicio1"];

                $diff = $diferencia;
                $tol_ent = date("H:i:s", mktime(0, $turno["Tolerancia_Entrada"], 0));

                // Validacion de llegada tarde
                if ($diff > $tol_ent) {
                    /**
                     * Se Guarda el registro de la llegada tarde en la base de datos
                     */
                    $lleg = new complex('Llegada_Tarde', 'Id_Llegada_Tarde');
                    $lleg->Identificacion_Funcionario = $funcionario["Identificacion_Funcionario"];
                    $lleg->Fecha = $hoy;
                    $lleg->Tiempo = strtotime($hactual) - strtotime($horas['Hora_Inicio1']);
                    $lleg->Id_Dependencia = $funcionario["Id_Dependencia"];
                    $lleg->Id_Grupo = $funcionario["Id_Grupo"];
                    $lleg->Entrada_Turno = $h_inicio;
                    $lleg->Entrada_Real = $hactual;
                    $lleg->save();
                    unset($lleg);

                    $oLista = new lista("Llegada_Tarde");
                    $oLista->setRestrict("Identificacion_Funcionario", "=", $funcionario["Identificacion_Funcionario"]);
                    $oLista->setRestrict("Fecha", "LIKE", date("Y-m"));
                    $oLista->setRestrict("Estado", "=", "Nueva");
                    $oLista->setRestrict("Cuenta", "=", "Si");
                    $tardias = $oLista->getList();
                    $num_tarde = count($tardias);

                    if ($num_tarde <= 1) {
                        $lleg = "Hoy ha llegado tarde.";
                    } elseif ($num_tarde == 2) {
                        $lleg = "Es su segunda llegada tarde en este mes.";
                    } elseif ($num_tarde == 3) {
                        $lleg = "Es su tercera llegada tarde en este mes, se le ha generado una observacion.";
                    } elseif ($num_tarde >= 4) {
                        $lleg = "Ha llegado tarde por " . $num_tarde . " vez en este mes, por favor diríjase a la Oficina de Talento Humano.";
                    }

                    /**
                     * Se genera un alerta al funcionario, informando la llegada tarde en el mes.
                     */
                    $alert = new complex('Alerta', 'Id_Alerta');
                    $alert->Identificacion_Funcionario = $funcionario["Identificacion_Funcionario"];
                    $alert->Fecha = $hoy . " " . $hactual;
                    $alert->Modulo = $diff;
                    $alert->Tipo = "Llegada Tarde";
                    $alert->Detalles = $funcionario["Nombres"] . " " . $funcionario["Apellidos"] . " ha llegado Tarde";
                    $alert->save();
                    unset($alert);
                    return armarRespuesta("success", "Acceso Autorizado", "Bienvenid(@) Hora actual: $hoy $hactual", " Retardo: $diff \n $lleg");
                    // return armarRespuesta("success", "Acceso Autorizado", "Bienvenid(@) <br>Retardo:$diff  <br> $lleg ", "$hoy $hactual");
                }

                return armarRespuesta("success", "Acceso Autorizado", "Bienvenid(@), Hoy ha llegado temprano  $hoy $hactual ");

            } else {
                $oItem = new complex('Diario_Fijo', 'Id_Diario_Fijo', $diario["Id_Diario_Fijo"]);
                $registros_hoy = $oItem->getData();
                /**
                 * La validacion se hace secuencial deacuerdo a las horas del turno;
                 */
                if ($registros_hoy['Hora_Salida1'] == "00:00:00" && $horas['Hora_Fin1'] !== "" && $horas['Hora_Fin1'] !== "00:00:00") {
                    $oItem->Hora_Salida1 = $hactual;
                    $oItem->Img2 = $fot;
                    $oItem->Latitud2 = $latitud;
                    $oItem->Longitud2 = $longitud;
                    $oItem->save();
                    return armarRespuesta("success", "Hasta Luego", "$hoy $hactual");

                } else if ($registros_hoy['Hora_Entrada2'] == "00:00:00" && $horas['Hora_Inicio2'] !== "" && $horas['Hora_Inicio2'] !== "00:00:00") {

                    $oItem->Hora_Entrada2 = $hactual;
                    $oItem->Img3 = $fot;
                    $oItem->Latitud3 = $latitud;
                    $oItem->Longitud3 = $longitud;
                    $oItem->save(); // Guardar el registro de entrada

                    // $diferencia = RestarHoras($hactual, $registros_hoy['Hora_Salida1']); //Tiempo de almuerzo utilizado por el funcionario
                    $tiempo_reglamentario = strtotime($horas['Hora_Inicio2']) - strtotime($horas['Hora_Fin1']);
                    $tiempo_con_tolerancia = date("H:i:s", mktime(0, $turno["Tolerancia_Entrada"], $tiempo_reglamentario)); // Tiempo de almuerzo asignado del turno mas el tiempo de tolerancia de entrada

                    $diff = strtotime($registros_hoy['Hora_Salida1']) + (strtotime($tiempo_con_tolerancia) - strtotime("00:00:00"));
                    $entrada = date("H:i:s", $diff); //Se calcula la hora permisiva para entrar
                    $tiempo_retardo = RestarHoras($hactual, $entrada);

                    //  Registro de llegada Tarde
                    if ($hactual > $entrada) {
                        /**
                         * Se Guarda el registro de la llegada tarde en la base de datos
                         */
                        $retardo = new complex('Llegada_Tarde', 'Id_Llegada_Tarde');
                        $retardo->Identificacion_Funcionario = $funcionario["Identificacion_Funcionario"];
                        $retardo->Fecha = $hoy;
                        $retardo->Tiempo = strtotime($hactual) - strtotime($entrada);
                        $retardo->Id_Dependencia = $funcionario["Id_Dependencia"];
                        $retardo->Id_Grupo = $funcionario["Id_Grupo"];
                        $retardo->Entrada_Turno = $entrada;
                        $retardo->Entrada_Real = $hactual;
                        $retardo->save();
                        unset($retardo);

                        $oLista = new lista("Llegada_Tarde");
                        $oLista->setRestrict("Identificacion_Funcionario", "=", $funcionario["Identificacion_Funcionario"]);
                        $oLista->setRestrict("Fecha", "LIKE", date("Y-m"));
                        $oLista->setRestrict("Estado", "=", "Nueva");
                        $oLista->setRestrict("Cuenta", "=", "Si");
                        $tardias = $oLista->getList();
                        $num_tarde = count($tardias);

                        if ($num_tarde <= 1) {
                            $lleg = "Hoy ha llegado tarde.";
                        } elseif ($num_tarde == 2) {
                            $lleg = "Es su segunda llegada tarde en este mes.";
                        } elseif ($num_tarde == 3) {
                            $lleg = "Es su tercera llegada tarde en este mes, se le ha generado una observacion";
                        } elseif ($num_tarde >= 4) {
                            $lleg = "Ha llegado tarde por " . $num_tarde . " vez en este mes, por favor diríjase a la Oficina de Talento Humano.";
                        }

                        /**
                         * Se genera un alerta al funcionario, informando la llegada tarde en el mes.
                         */
                        $alert = new complex('Alerta', 'Id_Alerta');
                        $alert->Identificacion_Funcionario = $funcionario["Identificacion_Funcionario"];
                        $alert->Fecha = $hoy . " " . $hactual;
                        $alert->Modulo = $tiempo_retardo;
                        $alert->Tipo = "Llegada Tarde";
                        $alert->Detalles = $funcionario["Nombres"] . " " . $funcionario["Apellidos"] . " ha llegado Tarde";
                        $alert->save();
                        unset($alert);
                        return armarRespuesta("success", "Acceso Autorizado", "Bienvenid(@) $hactual", " Retardo: $tiempo_retardo  $lleg");
                    }

                    return armarRespuesta("success", "Acceso Autorizado", "Bienvenido nuevamente $hactual");
                } else if ($registros_hoy['Hora_Salida2'] == "00:00:00" && $horas['Hora_Fin2'] !== "" && $horas['Hora_Fin2'] !== "00:00:00") {
                    $oItem->Hora_Salida2 = $hactual;
                    $oItem->Img4 = $fot;
                    $oItem->Latitud4 = $latitud;
                    $oItem->Longitud4 = $longitud;
                    $oItem->save();
                    return armarRespuesta("success", "Hasta Luego", "Gracias por trabajar con nosotros $hactual ");
                }
                return armarRespuesta("warning", "No Disponible", "Hoy ya ha registrado entradas y salidas satisfactoriamente");
            }

        } else {
            return armarRespuesta("error", "Dia no laboral", "No hay turnos para hoy");
        }
    } else {
        return armarRespuesta("error", "Turno Vacío", "No Tiene un Turno de acceso Asignado");
    }
}
function turnoLibre($funcionario, $fot)
{
    global $dias, $hoy, $hactual, $URL, $latitud, $longitud;

    $oLista = new lista('Diario_Fijo');
    $oLista->setRestrict("Identificacion_Funcionario", "=", $funcionario["Identificacion_Funcionario"]);
    $oLista->setRestrict("Fecha", "=", $hoy);
    $diario = $oLista->getList();
    $diario = $diario[0];
    if (!isset($diario["Id_Diario_Fijo"])) {
        $oItem = new complex('Diario_Fijo', 'Id_Diario_Fijo');
        $oItem->Identificacion_Funcionario = $funcionario["Identificacion_Funcionario"];
        $oItem->Fecha = $hoy;
        $oItem->Id_Turno = 0;
        $oItem->Hora_Entrada1 = $hactual;
        $oItem->Img1 = $fot;
        if ($latitud != "") {
            $oItem->Latitud = $latitud;
        }
        if ($longitud != "") {
            $oItem->Longitud = $longitud;
        }
        $oItem->save();
        unset($oItem);
        return armarRespuesta("success", "Acceso Autorizado", "Bienvenid(@) Hora actual: $hoy $hactual");
    } else {
        $oItem = new complex('Diario_Fijo', 'Id_Diario_Fijo', $diario["Id_Diario_Fijo"]);
        $registros_hoy = $oItem->getData();
        if ($registros_hoy['Hora_Salida1'] == "00:00:00") {
            $oItem->Hora_Salida1 = $hactual;
            $oItem->Img2 = $fot;
            $oItem->Latitud2 = $latitud;
            $oItem->Longitud2 = $longitud;
            $oItem->save();
            return armarRespuesta("success", "Hasta Luego", "$hoy $hactual");
        } else if ($registros_hoy['Hora_Entrada2'] == "00:00:00") {
            $oItem->Hora_Entrada2 = $hactual;
            $oItem->Img3 = $fot;
            $oItem->Latitud3 = $latitud;
            $oItem->Longitud3 = $longitud;
            $oItem->save(); // Guardar el registro de entrada
            return armarRespuesta("success", "Acceso Autorizado", "Bienvenido nuevamente $hactual");
        } else if ($registros_hoy['Hora_Salida2'] == "00:00:00") {
            $oItem->Hora_Salida2 = $hactual;
            $oItem->Img4 = $fot;
            $oItem->Latitud4 = $latitud;
            $oItem->Longitud4 = $longitud;
            $oItem->save();
            return armarRespuesta("success", "Hasta Luego", "Gracias por trabajar con nosotros $hactual ");
        }
        return armarRespuesta("warning", "No Disponible", "Hoy ya ha registrado entradas y salidas satisfactoriamente");
    }
}

function armarRespuesta($icono, $titulo, $mensaje, $tarde = false)
{
    global $URL, $funcionario, $MY_FILE,$foto;
    $mensaje = str_replace('-', '/', $mensaje);
    $respuesta["Icono"] = "$icono";
    $respuesta["Titulo"] = "$titulo";
    $respuesta["Imagen"] = "$URL/IMAGENES/FUNCIONARIOS/$funcionario[Imagen]";
    $respuesta["Comunicado"] = $mensaje;
    $respuesta["Tarde"] = $tarde;
    $respuesta["Mensaje"] = $tarde
    ? "<img src='$URL/IMAGENES/FUNCIONARIOS/$funcionario[Imagen]' class='img-thumbnail img-circle img-responsive' style='max-width:140px;'  /><br><h1><strong style='color:red;'>$mensaje</strong><br> $tarde </h1>"
    : "<img src='$URL/IMAGENES/FUNCIONARIOS/$funcionario[Imagen]' class='img-thumbnail img-circle img-responsive' style='max-width:140px;'  /><br><h1><strong>$mensaje</strong></h1>";
    
    if($icono=='error' || $icono=="warning"){
        unlink($MY_FILE."IMAGENES/TEMPORALES/$foto");
    }
    
    return $respuesta;
}

function compensatorios()
{
    global $funcionario, $hoy;
    $oLista = new lista('Compensatorio');
    $oLista->setRestrict("Identificacion_Funcionario", "=", $funcionario['Identificacion_Funcionario']);
    $oLista->setRestrict("Hora_Inicio", "!=", null);
    $oLista->setRestrict("Hora_Fin", "!=", null);
    $compensatorio = $oLista->getList();

}
