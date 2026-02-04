<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$query = 'SELECT m.codigo as CodigoMunicipio, m.Nombre as NombreMunicipio, d.Nombre as NombreDepartamento, m.Id_Municipio as IdMunicipio 
          FROM `Municipio` m , Departamento d 
          WHERE d.Id_Departamento = m.Id_Departamento' ;

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);