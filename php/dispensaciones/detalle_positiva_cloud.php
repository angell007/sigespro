<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$callCenter = ( isset( $_REQUEST['callCenter'] ) ? $_REQUEST['callCenter'] : '' );

$query0 = 'SELECT A.Id, A.Codigo, A.Documento_Paciente, A.Estado 
            FROM Positiva_No_Autorizados_App A 
            WHERE Id =  '.$id;
            $oCon= new consulta();
            $oCon->setQuery($query0);
            $auditoria = $oCon->getData();

unset($oCon);


$callCond = $callCenter ? ' AND ( S.Cumple =  0   OR S.Cumple IS NULL  ) ' : '';


$query4 = 'SELECT S.Id as Id_Soporte_Auditoria, S.Id_Positiva_No_Autorizado AS Id_Auditoria, S.Id_Tipo_Soporte, S.Url AS Archivo, T.Tipo_Soporte,   "No" AS Cumple
           FROM Soporte_Positiva_No_Autorizados_App S
           INNER JOIN Tipo_Soporte T ON T.Id_Tipo_Soporte =  S.Id_Tipo_Soporte
            WHERE S.Id_Positiva_No_Autorizado =  '.$id.'
        '.$callCond.'
        ORDER BY Id ASC' ;

$oCon= new consulta();
$oCon->setQuery($query4);
$oCon->setTipo('Multiple');
$soportes = $oCon->getData();

unset($oCon);

$resultado["Auditoria"]=$auditoria;
$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;
$resultado["Soportes"]=$soportes;
$resultado["AcDispensacion"]=$acti;

echo json_encode($resultado);

?>
