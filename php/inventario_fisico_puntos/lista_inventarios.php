<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query = 'SELECT  GROUP_CONCAT(FP.Id_Punto_Dispensacion) as punto
FROM Funcionario_Punto FP
WHERE FP.Identificacion_Funcionario='.$id;
$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$punto = $oCon->getData();
unset($oCon);
$query = 'SELECT I.Id_Inventario_Fisico_Punto, I.Id_Punto_Dispensacion, I.Fecha_Inicio, I.Fecha_Fin, I.Estado,
          CONCAT(FD.Nombres," ",FD.Apellidos) as Funcionario_Digita, CONCAT(FC.Nombres," ",FC.Apellidos) as Funcionario_Cuenta, I.Comparar,
          PD.Nombre as Punto, I.Conteo_Productos , I.Inventario
          FROM Inventario_Fisico_Punto I
          INNER JOIN Funcionario FD
          ON I.Funcionario_Digita = FD.Identificacion_Funcionario
          INNER JOIN Funcionario FC
          ON I.Funcionario_Cuenta = FC.Identificacion_Funcionario
          INNER JOIN Punto_Dispensacion PD
          On I.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
          WHERE I.Id_Punto_Dispensacion IN ('.$punto["punto"].')
          ORDER BY I.Id_Inventario_Fisico_Punto DESC ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);


?>