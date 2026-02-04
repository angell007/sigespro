<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');

new  GetPuntos();

class GetPuntos
{
    public function __construct()
    {
        $query="SELECT Id_Punto_Dispensacion As value, Nombre As label  FROM Punto_Dispensacion";
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		show($oCon->getData());     
		unset($oCon);
    }
}
