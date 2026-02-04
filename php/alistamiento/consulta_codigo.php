<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : false );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );
$codigo1=substr($codigo,0,12);

$query = 'SELECT  P.Codigo_Barras, PR.Id_Producto
        FROM Producto_Remision PR
        INNER JOIN Producto P
        ON PR.Id_Producto=P.Id_Producto
        INNER JOIN Inventario I 
        ON PR.Id_Inventario=I.Id_Inventario 
        WHERE PR.Id_Producto='.$id.' AND (I.Codigo="'.$codigo1.'" OR P.Codigo_Barras="'.$codigo.'" OR I.Alternativo LIKE "%'.$codigo1.'%")';

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>
