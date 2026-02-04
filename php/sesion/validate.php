<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/javascript');
header('Content-Type: application/json');

// Inicio llamado funciones basicas sistema
include_once __DIR__ . "/../../config/start.inc.php";
include_once __DIR__ . '/../../class/class.complex.php';
include_once __DIR__ . '/../../class/class.lista.php';

// include_once("../../config/start.inc.php");
// include_once('../../class/class.complex.php');
// include_once('../../class/class.lista.php');

require_once "./jwt.php"; //autenticación mediante token

include_once __DIR__ . "/../../class/class.lista.php";
include_once __DIR__ . "/../../class/class.complex.php";
// Fin llamado funciones basicas sistema

// Inicio captura variables
$username = (isset($_POST['username']) ? $_POST['username'] : "");
$password = (isset($_POST['password']) ? $_POST['password'] : "");
// Fin captura variables

	$lista = [];
	// Inicio busqueda empleados usuario existe
	$oItem = new complex('Funcionario', 'Username', $username);
	$funcionario = $oItem->getData();
	// Fin busqueda empleados usuario existe
	if ($funcionario) {$lista[0] = $funcionario;}
	// Inicio validar contraseña usuario
	if ((count($lista) == 0)) {
	$error = 'Funcionario No Existe';
	} elseif ($lista[0]["Password"] != ($password)) {
	$error = 'Contraseña Incorrecta';
	} elseif ($lista[0]["Autorizado"] != "Si") {
	$error = 'Funcionario No Autorizado';
	} else {
	$error = false;
	}

if ($error === false) {
    $nom = explode(" ", $lista[0]["Nombres"])[0] . " " . explode(" ", $lista[0]["Apellidos"])[0];
    if ($lista[0]["Imagen"] != "") {
        $img = "https://sigesproph.com.co/IMAGENES/FUNCIONARIOS/" . $lista[0]["Imagen"];
    } else {
        $img = "https://sigesproph.com.co/assets/images/user.jpg";
    }
    $oItem = new complex('Cargo', "Id_Cargo", $lista[0]["Id_Cargo"]);
    $cargo = $oItem->getData();
    unset($oItem);

    if (!isset($_SESSION)) {
        session_start();

        $_SESSION['temporizador_sesion'] = time();

    }

    $lista[0]["Nombre_Func"] = $nom;
    $lista[0]["Imagen_Func"] = $img;
    $lista[0]["Nombre_Cargo"] = $cargo["Nombre"];
    $lista[0]["session_id"] = session_id();

    /*GENERAR TOKEN JWT */

    $Auth = new Auth_jwt();
    $token = $Auth->encode($lista[0]);
    //guardar datos  de ingreso en la BD
    $oItem = new complex("Z_Log_Login", "Id_Log_Login");
    $oItem->Identificacion_Funcionario = $lista[0]['Identificacion_Funcionario'];
    $oItem->Tipo = "Inicio";
    $oItem->save();
    unset($oItem);

    $_SESSION["login"] = "Si";
    $_SESSION["user"] = $lista[0]["Identificacion_Funcionario"];
    $_SESSION["hora"] = date("Y-m-d H:i:s");
    $_SESSION["Token"] = $token;

    $respuesta["Error"] = "No";
    $respuesta["Mensaje"] = $error;
    $respuesta["hora"] = date("Y-m-d H:i:s");
    $respuesta["Token"] = $token;
    $respuesta["Funcionario"] = $lista[0];

} else {
    $respuesta["Error"] = "Si";
    $respuesta["Mensaje"] = $error;
}
// fin validar contraseña usuario

echo json_encode($respuesta);
