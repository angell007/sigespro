<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT PF.*
FROM Perfil_Funcionario PF
WHERE PF.Identificacion_Funcionario='.$id;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$permisos = $oCon->getData();
$res['permisos'] = $permisos ;
unset($oCon);

if( count($permisos) >1  && $permisos[0]['Id_Perfi'] = '46' ) {
   // var_dump('sdsd');exit;
    $query = 'select Id_Zona FROM Funcionario_Zona
         WHERE Identificacion_Funcionario = '.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$zona = $oCon->getData();

$res['Id_Zona'] = $zona['Id_Zona'];

unset($oCon);
  }

echo json_encode($res);

?>