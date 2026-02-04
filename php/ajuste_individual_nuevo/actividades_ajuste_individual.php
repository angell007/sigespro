<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
//include_once('./helper.ajuste_individual.php');
$id_ajuste = isset($_REQUEST['Id_Ajuste']) ? $_REQUEST['Id_Ajuste'] : false;

if($id_ajuste){
 
    $query = "SELECT A.Fecha_Creacion AS Fecha, A.Estado, CONCAT_WS(' ',FC.Nombres,FC.Apellidos) AS Funcionario, 
                FC.Imagen, A.Detalle AS Detalles
                FROM Actividad_Ajuste_Individual A 
                INNER JOIN Funcionario FC ON FC.Identificacion_Funcionario = A.Identificacion_Funcionario 
                WHERE A.Id_Ajuste_Individual = $id_ajuste";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $res['data'] = $oCon->getData();
    $res['type'] = 'success';
    echo json_encode($res);
}


