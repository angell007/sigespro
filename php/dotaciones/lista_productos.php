<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    
    require_once('../../config/start.inc.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    
    $Id_Inventario = (isset($_REQUEST['Id_Inventario']) ? $_REQUEST['Id_Inventario'] : false);

?>