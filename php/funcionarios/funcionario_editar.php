<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once './HTTP/Request2.php';
require '../../class/class.guardar_archivos.php';

//Objeto de la clase que almacena los archivos
$storer = new FileStorer();

$id_perfil = (isset($_REQUEST['id_perfil']) ? $_REQUEST['id_perfil'] : '');
$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
$modulos = (isset($_REQUEST['modulos']) ? $_REQUEST['modulos'] : '');
$bodegas = (isset($_REQUEST['bodegas']) ? $_REQUEST['bodegas'] : '');
$puntos = (isset($_REQUEST['puntos']) ? $_REQUEST['puntos'] : '');
$categoria = (isset($_REQUEST['categoria']) ? $_REQUEST['categoria'] : '');
$auth = (isset($_REQUEST['autoriza']) ? $_REQUEST['autoriza'] : '');
$firma = (isset($_REQUEST['firma']) ? $_REQUEST['firma'] : '');
$id_zona = (isset($_REQUEST['id_zona']) ? $_REQUEST['id_zona'] : '');

$funcionario = (array) json_decode($funcionario, true);
$modulos = (array) json_decode($modulos, true);
$bodegas = (array) json_decode($bodegas, true);
$puntos = (array) json_decode($puntos, true);
$categoria = (array) json_decode($categoria, true);
$perfil_id = json_decode($perfil_id);

$funcionario['Autorizado'] = $auth;

unset($funcionario["Imagen"]);

if ($id_zona) {
    $oItem = new complex('Funcionario_Zona', 'Identificacion_Funcionario', $funcionario["Identificacion_Funcionario"]);
    $oItem->Identificacion_Funcionario = $funcionario['Identificacion_Funcionario'];
    $oItem->Id_Zona = $id_zona;
    $oItem->save();
    unset($oItem);

}

/* GUARDA FUNCIONARIO */
if (!empty($_FILES['Foto']['name'])) {
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/FUNCIONARIOS/');
    $funcionario["Imagen"] = $nombre_archivo[0];

    // $posicion1 = strrpos($_FILES['Foto']['name'],'.')+1;
    // $extension1 =  substr($_FILES['Foto']['name'],$posicion1);
    // $extension1 =  strtolower($extension1);
    // $_filename1 = uniqid() . "." . $extension1;
    // $_file1 = $MY_FILE . "IMAGENES/FUNCIONARIOS/" . $_filename1;

    // $ancho="800";
    // $alto="800";
    // $subido1 = move_uploaded_file($_FILES['Foto']['tmp_name'], $_file1);
    //     if ($subido1){
    //         list($width, $height, $type, $attr) = getimagesize($_file1);
    //         @chmod ( $_file1, 0777 );
    //         $funcionario["Imagen"] = $_filename1;
    //     }
}

$oItem = new complex('Funcionario', 'Identificacion_Funcionario', $funcionario["Identificacion_Funcionario"]);
$oItem->Id_Cargo = $funcionario['Id_Cargo'];
$oItem->Id_Grupo = $funcionario['Id_Grupo'];
$oItem->Id_Dependencia = $funcionario['Id_Dependencia'];
$oItem->Ver_Costo = $funcionario['Ver_Costo'];
$oItem->Aprobar_Gastos_Puntos = $funcionario['Aprobar_Gastos_Puntos'];
$oItem->Autorizado = $auth;
$oItem->save();
unset($oItem);

$imagen = $firma;

$fot = '';
if ($imagen != "") {
    list($type, $imagen) = explode(';', $imagen);
    list(, $imagen) = explode(',', $imagen);
    $imagen = base64_decode($imagen);

    $fot = "firma" . uniqid() . ".jpg";
    $archi = $MY_FILE . "DOCUMENTOS/" . $funcionario["Identificacion_Funcionario"] . "/" . $fot;
    file_put_contents($archi, $imagen);
    chmod($archi, 0644);
    //$storer->UpdateFile("DOCUMENTOS/".$funcionario["Identificacion_Funcionario"]."/".$fot, $imagen);
}

$oItem = new complex('Funcionario', 'Identificacion_Funcionario', $funcionario["Identificacion_Funcionario"]);
$oItem->Firma = $fot;
$oItem->save();
unset($oItem);

/* ACTUALIZA FOTO DE PERSONA FOTO DE PERSONA */

if ($funcionario) {
    /* ACTUALIZA FOTO DE PERSONA FOTO DE PERSONA */
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

            } catch (HttpException $ex) {
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
            } catch (HttpException $ex) {
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
        } catch (HttpException $ex) {
            echo $ex;
        }
        /*PERMITE QUE SE PUEDAN REVISAR LAS FOTOS NUEVAS */
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
        } catch (HttpException $ex) {
            echo $ex;
        }
    }

}

// Guarda el perfil del Funcionario
$id_funcionario = $funcionario["Identificacion_Funcionario"];
//var_dump($id_perfil);

if (isset($modulos[0]["Id_Perfil_Funcionario"]) && $modulos[0]["Id_Perfil_Funcionario"] != "") {

} else {
    $query1 = 'DELETE  PF.*
        FROM Perfil_Funcionario PF
        WHERE PF.Identificacion_Funcionario=' . $id_funcionario;
    $oCon = new consulta();
    $oCon->setQuery($query1);
    $bod = $oCon->deleteData();
    unset($oCon);
}
foreach ($modulos as $modulo) {
    if (isset($modulo["Id_Perfil_Funcionario"]) && $modulo["Id_Perfil_Funcionario"] != "") {
        $oItem = new complex("Perfil_Funcionario", "Id_Perfil_Funcionario", $modulo["Id_Perfil_Funcionario"]);
    } else {
        $oItem = new complex("Perfil_Funcionario", "Id_Perfil_Funcionario");

    }
    $oItem->Id_Perfil = $id_perfil;
    $oItem->Identificacion_Funcionario = $id_funcionario;
    $oItem->Titulo_Modulo = $modulo["Titulo_Modulo"];
    $oItem->Modulo = $modulo["Modulo"];
    if ($modulo["Ver"] != "") {
        $oItem->Ver = $modulo["Ver"];
    } else {
        $oItem->Ver = "0";
    }
    if ($modulo["Crear"] != "") {
        $oItem->Crear = $modulo["Crear"];
    } else {
        $oItem->Crear = "0";
    }
    if ($modulo["Editar"] != "") {
        $oItem->Editar = $modulo["Editar"];
    } else {
        $oItem->Editar = "0";
    }
    if ($modulo["Eliminar"] != "") {
        $oItem->Eliminar = $modulo["Eliminar"];
    } else {
        $oItem->Eliminar = "0";
    }
    $oItem->save();
    unset($oItem);
}
//Guardar las bodegas asociadas al Funcionario
$query = 'DELETE  FB.*
FROM Funcionario_Bodega_Nuevo FB
WHERE FB.Identificacion_Funcionario=' . $id_funcionario;
$oCon = new consulta();
$oCon->setQuery($query);
$bod = $oCon->deleteData();
unset($oCon);

foreach ($bodegas as $bodega) {
    $oItem = new complex("Funcionario_Bodega_Nuevo", "Id_Funcionario_Bodega_Nuevo");
    $oItem->Identificacion_Funcionario = $id_funcionario;
    $oItem->Id_Bodega_Nuevo = $bodega;
    $oItem->save();
    unset($oItem);
}
//Guardar los puntos de Dispensacion asociados al Funcionario
$query = 'DELETE FP.*
FROM Funcionario_Punto FP
WHERE FP.Identificacion_Funcionario=' . $id_funcionario;
$oCon = new consulta();
$oCon->setQuery($query);
$punt = $oCon->deleteData();
unset($oCon);

foreach ($puntos as $punto) {
    $oItem = new complex("Funcionario_Punto", "Id_Funcionario_Punto");
    $oItem->Identificacion_Funcionario = $id_funcionario;
    $oItem->Id_Punto_Dispensacion = $punto;
    $oItem->save();
    unset($oItem);
}

$query = 'DELETE FP.*
FROM Funcionario_Categoria FP
WHERE FP.Identificacion_Funcionario=' . $id_funcionario;
$oCon = new consulta();
$oCon->setQuery($query);
$punt = $oCon->deleteData();
unset($oCon);

foreach ($categoria as $catego) {
    $oItem = new complex("Funcionario_Categoria", "Id_Funcionario_Categoria");
    $oItem->Identificacion_Funcionario = $id_funcionario;
    $oItem->Id_Categoria = $catego;
    $oItem->save();
    unset($oItem);
}

/*PERMITE QUE SE PUEDAN REVISAR LAS FOTOS NUEVAS */
/*$request = new Http_Request2('https://westcentralus.api.cognitive.microsoft.com/face/v1.0/persongroups/'.$AZURE_GRUPO.'/train');
$url = $request->getUrl();
$request->setConfig(array(
'ssl_verify_peer'   => FALSE,
'ssl_verify_host'   => FALSE
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
try{
$response = $request->send();
echo $response->getBody();
}catch (HttpException $ex){
echo $ex;
}*/

echo json_encode("¡Funcionario Actualizado Exitosamente!");
