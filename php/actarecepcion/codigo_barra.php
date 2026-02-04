<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : false;
        
$query = "SELECT Id_Producto, Nombre_Comercial, Laboratorio_Comercial, Embalaje, Imagen FROM Producto WHERE Codigo_Barras LIKE '$codigo%'";
          
$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
          
echo json_encode($resultado);


?>