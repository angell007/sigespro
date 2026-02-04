<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');

new  GetDomiciliarios();

class GetDomiciliarios
{
    public function __construct()
    {
        $query="SELECT  F.Identificacion_Funcionario As value, Concat(F.Nombres, F.Apellidos) As label  FROM Funcionario As F
        INNER JOIN  Perfil_Funcionario AS PF  ON PF.Identificacion_Funcionario = F.Identificacion_Funcionario 
        INNER JOIN  Perfil AS P  ON P.Id_Perfil = PF.Id_Perfil
        WHERE  P.Id_Perfil = (SELECT Id_Perfil FROM Perfil WHERE Nombre = 'Domiciliario')
        GROUP BY  F.Identificacion_Funcionario ";
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		show($oCon->getData());     
		unset($oCon);
    }
}
