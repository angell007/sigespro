<?php 
require  '../../vendor/autoload.php';
 use \Firebase\JWT\JWT;
 class Auth_Jwt{
    private static $key="CorvusLab";
    private static $algorithms=['HS256'];

    public function encode($data){
        $time = time();
        $token = array(
            'iat' => $time, // Tiempo que inició el token
            'exp' => $time + (45*600), // Tiempo que expirará el token (+1 hora)
            'data' => [$data]// información del usuario        
        );
    
        $jwt = JWT::encode($token, self::$key);
        return $jwt;
     }

     function check($token){
        try {
           
            $data = JWT::decode($token, self::$key,  self::$algorithms);
            return true;
    
        } catch (\Exception $e) {
            return false;
        }
     }

     function decode($token){
        try {
           
            $data = JWT::decode($token, self::$key,  self::$algorithms);
            return $data;
    
        } catch (\Exception $e) {
            return FALSE;
        }
     }

 }







 



