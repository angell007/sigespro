<?php 

 header('Access-Control-Allow-Origin: *');
 header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
 header('Content-Type: application/json');

 require_once("../../config/start.inc.php");
//include_once('../../class/class.complex.php');
 include_once(__DIR__. "../../../class/class.complex.php");
 $session_id  = isset($_REQUEST['Session_Id']) ? $_REQUEST['Session_Id'] : false; 
 $tipo_entrada  = isset($_REQUEST['Tipo_Entrada']) ? $_REQUEST['Tipo_Entrada'] : false;



 $estado=false;
 if ($session_id ) {
      session_id($session_id);
      session_start();
        $inactivo=12000000;
        if(isset($_SESSION['temporizador_sesion']) ) {
            $vida_session = time() - $_SESSION['temporizador_sesion'];
            if($vida_session > $inactivo)
            {
                $estado=false;
                
                 echo json_encode($estado);
                 //guardar datos  de ingreso en la BD
                    $oItem = new complex("Z_Log_Login", "Id_Log_Login");
                    $oItem->Identificacion_Funcionario =   $_SESSION["user"];
                    $oItem->Tipo = "Salida";
                    $oItem->save();
                    unset($oItem);
                 session_destroy();
                exit;
            }else{
                $estado=true;
                if ($tipo_entrada=='contador') {
                      $_SESSION['temporizador_sesion']=time(); 
                }        
                 
            }
        } 
        
        echo json_encode($estado);

}else{
    echo json_encode($estado);
}
    
?>