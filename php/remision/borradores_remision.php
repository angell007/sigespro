<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = ' WHERE B.Estado = "Activo" ';

if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
    $condicion .= " AND B.Id_Funcionario ='$_REQUEST[func]'"; 
}

$query = 'SELECT F.Imagen, B.Id_Borrador,B.Tipo,B.Fecha,B.Id_Funcionario,B.Nombre_Destino,B.Codigo
FROM Borrador B
INNER JOIN Funcionario F 
ON B.Id_Funcionario=F.Identificacion_Funcionario
 '.$condicion.'
ORDER BY B.Fecha DESC' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
          
?>