<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT D.Nombre as NombreDepartamento
            FROM Punto_Dispensacion PD
            INNER JOIN Departamento D
            ON D.Id_Departamento = PD.Departamento
            GROUP BY D.Nombre' ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado1 = $oCon->getData();
unset($oCon);

$query1 = 'SELECT Tipo
            FROM Punto_Dispensacion 
            GROUP BY Tipo' ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query1);
$resultado2 = $oCon->getData();
unset($oCon);

$query2 = 'SELECT No_Pos
            FROM Punto_Dispensacion 
            GROUP BY No_Pos' ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$resultado3 = $oCon->getData();
unset($oCon);

$query3 = 'SELECT Turnero
            FROM Punto_Dispensacion 
            GROUP BY Turnero' ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query3);
$resultado4 = $oCon->getData();
unset($oCon);

$resultado['Departamento'] = array($resultado1);
$resultado['Tipo'] = array($resultado2);
$resultado['No_Pos'] = array($resultado3);
$resultado['Turnero'] = array($resultado4) ;

echo json_encode($resultado);
?>