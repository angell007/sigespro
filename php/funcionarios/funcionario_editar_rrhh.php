<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once 'HTTP/Request2.php';

$id_perfil = (isset($_REQUEST['id_perfil']) ? $_REQUEST['id_perfil'] : false);
$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false);
$contacto_emergencia = (isset($_REQUEST['contacto_emergencia']) ? $_REQUEST['contacto_emergencia'] : false);
$experiencia = (isset($_REQUEST['experiencia']) ? $_REQUEST['experiencia'] : false);
$referencias = (isset($_REQUEST['referencias']) ? $_REQUEST['referencias'] : false);
$auth = (isset($_REQUEST['autoriza']) ? $_REQUEST['autoriza'] : false);
$idenFuncionario = (isset($_REQUEST['Identificacion_Funcionario']) ? $_REQUEST['Identificacion_Funcionario'] : false);
$contrato = (isset($_REQUEST['contrato']) ? $_REQUEST['contrato'] : false);
$prorroga = (isset($_REQUEST['prorroga']) ? $_REQUEST['prorroga'] : false);

$cargo = (isset($_REQUEST['cargo']) ? $_REQUEST['cargo'] : false);
$grupo = (isset($_REQUEST['grupo']) ? $_REQUEST['grupo'] : false);
$dependencia = (isset($_REQUEST['dependencia']) ? $_REQUEST['dependencia'] : false);
$cargo=str_replace('"', '', $cargo);
$grupo=str_replace('"', '', $grupo);
$dependencia=str_replace('"', '', $dependencia);

$funcionario = (array) json_decode($funcionario, true);
$contrato = (array) json_decode($contrato, true);
$contacto_emergencia = (array) json_decode($contacto_emergencia, true);
$experiencia = (array) json_decode($experiencia, true);
$referencias = (array) json_decode($referencias, true);
$perfil_id = json_decode($perfil_id);

$funcionario['Autorizado'] = $auth;

unset($funcionario["Imagen"]);
/* GUARDA FUNCIONARIO */
if (!empty($_FILES['Foto']['name'])) {
    $posicion1 = strrpos($_FILES['Foto']['name'], '.') + 1;
    $extension1 = substr($_FILES['Foto']['name'], $posicion1);
    $extension1 = strtolower($extension1);
    $_filename1 = uniqid() . "." . $extension1;
    $_file1 = $MY_FILE . "IMAGENES/FUNCIONARIOS/" . $_filename1;

    $ancho = "800";
    $alto = "800";
    $subido1 = move_uploaded_file($_FILES['Foto']['tmp_name'], $_file1);
    if ($subido1) {
        list($width, $height, $type, $attr) = getimagesize($_file1);
        @chmod($_file1, 0777);
        $funcionario["Imagen"] = $_filename1;
    }
}

if ($funcionario && $funcionario["Identificacion_Funcionario"]) {
    $oItem = new complex('Funcionario', 'Identificacion_Funcionario', $funcionario["Identificacion_Funcionario"]);
    foreach ($funcionario as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->Salario = $contrato['Valor'];
    $oItem->Id_Cargo = (0)+ $cargo;
    $oItem->Id_Grupo = (int) $grupo;
    $oItem->Id_Dependencia = (int) $dependencia;
    if ($funcionario['Tipo'] == 'Externo') {
        $oItem->Tipo_Turno = 'Libre';
    }
    else{
        $oItem->Tipo_Turno = 'Fijo';
    }
    $oItem->save();
    unset($oItem);
}

/* GUARDA CONTACTO EMERGENCIA */
if (isset($contacto_emergencia["Identificacion_Funcionario_Contacto_Emergencia"])) {

    if ($contacto_emergencia["Identificacion_Funcionario_Contacto_Emergencia"] == '') {
        $contacto_emergencia["Identificacion_Funcionario_Contacto_Emergencia"] = 0;

        $oItem = new complex('Funcionario_Contacto_Emergencia', 'Identificacion_Funcionario_Contacto_Emergencia');
    } else {
        $oItem = new complex('Funcionario_Contacto_Emergencia', 'Identificacion_Funcionario_Contacto_Emergencia', $contacto_emergencia["Identificacion_Funcionario_Contacto_Emergencia"]);
    }

    foreach ($contacto_emergencia as $index => $value) {
        $oItem->$index = $value;
    }

    $oItem->save();
    unset($oItem);
}

if ($experiencia) {
    /* GUARDA EXPERIENCIA LABORAL */
    if (is_array($experiencia)) {
        foreach ($experiencia as $exp) {
            if ($exp["Nombre_Empresa"] != "") {
                //    $exp["Identificacion_Funcionario"]=$idenFuncionario;
                if ($exp["id_Funcionario_Experiencia_Laboral"] != "") {
                    $oItem = new complex('Funcionario_Experiencia_Laboral', 'id_Funcionario_Experiencia_Laboral', $exp["id_Funcionario_Experiencia_Laboral"]);
                } else {
                    $oItem = new complex('Funcionario_Experiencia_Laboral', 'id_Funcionario_Experiencia_Laboral');
                    unset($exp["id_Funcionario_Experiencia_Laboral"]);
                }

                foreach ($exp as $index => $value) {
                    $oItem->$index = $value;
                }
                $oItem->save();
                unset($oItem);
            }
        }
    }
}

/* GUARDA REFERENCIAS PERSONALES */
if (is_array($referencias)) {
    if (count($referencias) > 0) {
        foreach ($referencias as $ref) {
            if ($ref["Nombres"] != "") {
                $ref["Identificacion_Funcionario"] = $idenFuncionario;
                if ($ref["id_Funcionario_Referencias"] != "") {
                    $oItem = new complex('Funcionario_Referencia_Personal', 'id_Funcionario_Referencias', $ref["id_Funcionario_Referencias"]);
                } else {
                    $oItem = new complex('Funcionario_Referencia_Personal', 'id_Funcionario_Referencias');
                    unset($ref["id_Funcionario_Referencias"]);
                }

                foreach ($ref as $index => $value) {
                    $oItem->$index = $value;
                }
                $oItem->save();
                unset($oItem);
            }
        }
    }

}

if ($funcionario) {
//    ACTUALIZA FOTO DE PERSONA FOTO DE PERSONA
    if ($funcionario["Imagen"] != "") {
        $oItem = new complex('Funcionario', 'Identificacion_Funcionario', $funcionario["Identificacion_Funcionario"]);
        $func = $oItem->getData();
        unset($oItem);
        if ($func["personId"] == "") {
            $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/' . $AZURE_GRUPO . '/persons');
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
            $body = array(
                "name" => $func["Nombres"] . " " . $func["Apellidos"],
                "userData" => $func["Identificacion_Funcionario"],
            );
            $url->setQueryVariables($parameters);
            $request->setMethod(HTTP_Request2::METHOD_POST);
            $request->setBody(json_encode($body));
            try {
                $response = $request->send();
                $resp = $response->getBody();
                $resp = json_decode($resp);
                $person_id = $resp->personId;

                $oItem = new complex('Funcionario', 'Identificacion_Funcionario', $func["Identificacion_Funcionario"]);
                $oItem->personId = $person_id;
                $func = $oItem->getData();
                $oItem->save();
                unset($oItem);

            } catch (\Throwable $ex) {
                echo "error: " . $ex;
            }
        }
        if ($func["persistedFaceId"] != "") {
            $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/' . $AZURE_GRUPO . '/persons/' . $func["personId"] . '/persistedFaces/' . $func["persistedFaceId"]);
            $url = $request->getUrl();
            $request->setConfig(array(
                'ssl_verify_peer' => false,
                'ssl_verify_host' => false,
            ));
            $headers = array(
                'Ocp-Apim-Subscription-Key' => $AZURE_ID,
            );
            $request->setHeader($headers);
            $parameters = array(
            );
            $url->setQueryVariables($parameters);
            $request->setMethod(HTTP_Request2::METHOD_DELETE);
            $request->setBody("");
            try {
                $response = $request->send();
                echo $response->getBody();
            } catch (\Throwable $ex) {
                echo $ex;
            }
        }
        $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/' . $AZURE_GRUPO . '/persons/' . $func["personId"] . '/persistedFaces');
        $url = $request->getUrl();
        $request->setConfig(array(
            'ssl_verify_peer' => false,
            'ssl_verify_host' => false,
        ));
        $headers = array(
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $AZURE_ID,
        );

        $request->setHeader($headers);
        $parameters = array(
        );
        $body = array(
            "url" => $URL . "IMAGENES/FUNCIONARIOS/" . $_filename1,
        );
        $url->setQueryVariables($parameters);
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setBody(json_encode($body));
        try {
            $response = $request->send();
            $resp = $response->getBody();
            $resp = json_decode($resp);
            $persistedFaceId = $resp->persistedFaceId;
            $oItem = new complex('Funcionario', 'Identificacion_Funcionario', $funcionario["Identificacion_Funcionario"]);
            $oItem->persistedFaceId = $persistedFaceId;
            $oItem->save();
        } catch (\Throwable $ex) {
            echo $ex;
        }
        $request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/' . $AZURE_GRUPO . '/train');
        $url = $request->getUrl();
        $request->setConfig(array(
            'ssl_verify_peer' => false,
            'ssl_verify_host' => false,
        ));
        $headers = array(
            'Ocp-Apim-Subscription-Key' => $AZURE_ID,
        );
        $request->setHeader($headers);
        $parameters = array(

        );
        $url->setQueryVariables($parameters);
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setBody("");
        try {
            $response = $request->send();
            echo $response->getBody();
        } catch (\Throwable $ex) {
            echo $ex;
        }
    }

}
if ($contrato['Id_Contrato_Funcionario'] != '' && (isset($contrato['Id_Contrato_Funcionario']))) {
    $oItem = new complex('Contrato_Funcionario', 'Id_Contrato_Funcionario', $contrato['Id_Contrato_Funcionario']);
    $oItem->Auxilio_No_Prestacional = $contrato['Auxilio_No_Prestacional'];
    //$contrato['Valor']=number_format($funcionario["Salario"],2,".","");
    $contrato['Auxilio_No_Prestacional'] = number_format($contrato["Auxilio_No_Prestacional"], 2, ".", "");
    unset($contrato["Id_Contrato_Funcionario"]);
    foreach ($contrato as $index => $value) {
        $oItem->$index = $value;
        $oItem->Auxilio_No_Prestacional = number_format($contrato["Auxilio_No_Prestacional"], 2, ".", "");
    }
    $oItem->save();
    unset($oItem);
} else {
    if ($contrato['Id_Salario'] != '' && isset($contrato['Id_Salario'])) {
        $oItem = new complex('Contrato_Funcionario', 'Id_Contrato_Funcionario');

        $contrato['Estado'] = "Activo";
        $contrato['Identificacion_Funcionario'] = $funcionario["Identificacion_Funcionario"];
        // $contrato['Valor']=$funcionario["Salario"];
        unset($contrato["Id_Contrato_Funcionario"]);
        foreach ($contrato as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->save();
        unset($oItem);
    }

}

if ($prorroga) {
    $prorroga = (array) json_decode($prorroga, true);

    $oItem = new complex('Actividad_Contrato_Funcionario', 'Id_Actividad_Contrato_Funcionario');
    foreach ($prorroga as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    unset($oItem);
}

echo json_encode("¡Funcionario Actualizado Exitosamente!");
