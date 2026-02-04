<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condiciones=[];
if (isset($_REQUEST['iden']) && $_REQUEST['iden'] != "") {
    array_push($condiciones, "p.Id_Paciente LIKE '$_REQUEST[iden]%'");
}
if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    array_push($condiciones, "CONCAT(p.Primer_Nombre,' ', p.Segundo_Nombre,' ',p.Primer_Apellido, ' ' ,p.Segundo_Apellido) LIKE '%$_REQUEST[nom]%'");
}
if (isset($_REQUEST['eps']) && $_REQUEST['eps'] != "") {
    array_push($condiciones, "p.EPS LIKE '%$_REQUEST[eps]%'");
}
if (isset($_REQUEST['nivel']) && $_REQUEST['nivel'] != "") {
    array_push($condiciones, "n.Id_Nivel=$_REQUEST[nivel]");
}
if (isset($_REQUEST['reg']) && $_REQUEST['reg'] != "") {
    array_push($condiciones, "r.Id_Regimen = $_REQUEST[reg]");
}
if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
    array_push($condiciones, "p.Estado = '$_REQUEST[est]'");
}

// $query = 'SELECT COUNT(*) AS Total
//           FROM Paciente p , Regimen r, Nivel n 
//           WHERE   
//           p.Id_Regimen=r.Id_Regimen
//           AND
//           p.Id_Nivel = n.Id_Nivel ' . $condicion ;
$condicion=count($condiciones)>0?" WHERE ".  implode(" AND ", $condiciones):"";

$query = 'SELECT COUNT(*) AS Total
          FROM Paciente p 
          LEFT JOIN Regimen r ON r.Id_Regimen = p.Id_Regimen
          LEFT JOIN Nivel n ON n.Id_Nivel = p.Id_Nivel
           ' . $condicion ;
$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
} 

// $query = 'SELECT r.Nombre as NombreRegimen, 
//                  n.Nombre as NombreNivel,
//                  n.Id_Nivel, 
//                  CONCAT_WS(" ", p.Primer_Nombre, p.Segundo_Nombre,p.Primer_Apellido ,p.Segundo_Apellido) as NombrePaciente,
//                  p.EPS as EPS,
//                  p.Id_Paciente as Id_Paciente,
//                  p.Correo as Correo,
//                  p.Telefono as Telefono,
//                  p.Id_Paciente,Estado
//           FROM Paciente p , Regimen r, Nivel n 
//           WHERE   
//           p.Id_Regimen=r.Id_Regimen
//           AND
//           p.Id_Nivel = n.Id_Nivel '.$condicion.' LIMIT '.$limit.','.$tamPag ;


$query = 'SELECT r.Nombre as NombreRegimen, 
                 n.Nombre as NombreNivel,
                 n.Id_Nivel, 
                 CONCAT_WS(" ", p.Primer_Nombre, p.Segundo_Nombre,p.Primer_Apellido ,p.Segundo_Apellido) as NombrePaciente,
                 p.EPS as EPS,
                 p.Id_Paciente as Id_Paciente,
                 p.Correo as Correo,
                 p.Telefono as Telefono,
                 p.Id_Paciente,Estado
          FROM Paciente p 
          LEFT JOIN Regimen r ON r.Id_Regimen = p.Id_Regimen
          LEFT JOIN Nivel n ON n.Id_Nivel = p.Id_Nivel
          '.$condicion.' LIMIT '.$limit.','.$tamPag ;

    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$pacientes['pacientes'] = $oCon->getData();
unset($oCon);

$pacientes['numReg'] = $numReg;

echo json_encode($pacientes);
?>