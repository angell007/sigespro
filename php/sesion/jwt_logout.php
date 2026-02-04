<?php
 header('Access-Control-Allow-Origin: *');
 header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
 header('Content-Type: application/json');

 require_once("../../config/start.inc.php");
 //include_once('../../class/class.complex.php');
  include_once($MY_CLASS . "class.complex.php");

     $session_id  = isset($_REQUEST['Session_Id']) ? $_REQUEST['Session_Id'] : false; 
    if ($session_id) {
        session_id($session_id);
        session_start();
        $oItem = new complex("Z_Log_Login", "Id_Log_Login");
        $oItem->Identificacion_Funcionario =   $_SESSION["user"];
        $oItem->Tipo = "Salida";
        $oItem->save();
        unset($oItem);
    }   
    session_destroy();
    
?>