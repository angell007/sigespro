<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_perfil = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

// $query = 'SELECT PF.*
//             FROM Perfil_Permiso PF
//             WHERE PF.Id_Perfil='.$id_perfil;
//             $oCon= new consulta();
//             $oCon->setTipo('Multiple');
//             $oCon->setQuery($query);
//             $productos = $oCon->getData();
//             unset($oCon);


$query = 'SELECT PF.*  
          FROM Perfil_Permiso PF
          LEFT JOIN Perfil_Funcionario PP ON PP.Id_Perfil = PF.Id_Perfil
          WHERE PF.Id_Perfil = '.$id_perfil.' GROUP BY PF.Titulo_Modulo';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);


echo json_encode($productos);

?>