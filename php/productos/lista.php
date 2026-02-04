<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT Codigo_Cum
          FROM Producto P WHERE Id_Producto='.$id ;

$oCon= new consulta();

$oCon->setQuery($query);
$producto = $oCon->getData();
unset($oCon);



$query = 'SELECT PL.*, L.Nombre, L.Porcentaje, L.Id_Lista_Ganancia
          FROM  Lista_Ganancia L 
          Left JOIN  Producto_Lista_Ganancia PL ON PL.Id_Lista_Ganancia=L.Id_Lista_Ganancia AND PL.Cum="'.$producto['Codigo_Cum'].'"' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$lista = $oCon->getData();
unset($oCon);

if(count($lista)==0){
    $query = 'SELECT L.Id_Lista_Ganancia, L.Nombre, "" as Precio 
          FROM  Lista_Ganancia L  ' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$lista = $oCon->getData();
unset($oCon);
}


echo json_encode($lista);

?>