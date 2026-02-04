<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

if($id)
{
    $query = 'SELECT A.Estado, TS.Auditoria, TS.Mipres, TS.Positiva
               FROM Auditoria A 
                INNER JOIN Dispensacion D on A.Id_Dispensacion = D.Id_Dispensacion OR D.Id_Auditoria = A.Id_Auditoria
                INNER JOIN Tipo_Servicio TS ON A.Id_Tipo_Servicio = TS.Id_Tipo_Servicio
                WHERE D.Id_Dispensacion = '.$id;
                // echo $query;
    $oCon= new consulta();
    $oCon->setTipo('simple');
    $oCon->setQuery($query);
    $dispensacion= $oCon->getData();
    unset($oCon);
}
echo json_encode($dispensacion);
