<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$query = 'SELECT r.Nombre as NombreRegimen, 
                 n.Nombre as NombreNivel, 
                 CONCAT(p.Primer_Nombre," ", p.Segundo_Nombre," ",p.Primer_Apellido, " " ,p.Segundo_Apellido) as NombrePaciente,
                 p.EPS as EPS,
                 p.Id_Paciente as Id_Paciente,
                 p.Correo as Correo,
                 p.Telefono as Telefono
          FROM Paciente p , Regimen r, Nivel n 
          WHERE   
          p.Id_Regimen=r.Id_Regimen
          AND
          p.Id_Nivel = n.Id_Nivel' ;

    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$pacientes = $oCon->getData();
unset($oCon);

echo json_encode($pacientes);