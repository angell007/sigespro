<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
    require_once('./jwt.php');

    $Auth=new Auth_Jwt();
    $token  = (isset($_REQUEST['token'] ) ? $_REQUEST['token'] : 'no token' );
  
    if ($token) {
        $response=$Auth->check($token);
        echo json_encode($response);
    }else{
        echo json_encode(false);
    }
    
