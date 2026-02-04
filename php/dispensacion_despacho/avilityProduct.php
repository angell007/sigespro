<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');


$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : false );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );
$codigo1=substr($codigo,0,12);

$query = 'SELECT P.Codigo_Barras, PR.Id_Producto
                FROM Producto_Dispensacion PR
                INNER JOIN Producto P
                ON PR.Id_Producto=P.Id_Producto
                WHERE PR.Id_Producto ='.$id.' AND (P.Codigo_Barras="'.$codigo.'")';
$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

show($resultado);
