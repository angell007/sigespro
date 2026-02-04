<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$id_Precio_Regulado = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
if($id_Precio_Regulado){
    $query = 'SELECT APR.*, F.Imagen FROM Actividad_Precio_Regulado APR
                   INNER JOIN Funcionario F ON F.Identificacion_Funcionario = APR.Identificacion_Funcionario
                   WHERE Id_Precio_Regulado = '.$id_Precio_Regulado .' ORDER BY APR.Fecha DESC';
       $oCon = new consulta();
       $oCon->setQuery($query);
       $oCon->setTipo('Multiple');
       $actividades= $oCon->getData();   
   }

   echo json_encode($actividades);