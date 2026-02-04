<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$oItem = new complex($mod,"Identificacion_".$mod,$id);
$detalle= $oItem->getData();
unset($oItem);

$oItem = new complex("Funcionario_Contacto_Emergencia","Identificacion_Funcionario",$id);
$detalle['Contacto_Emergencia']= $oItem->getData();
unset($oItem);

$oLista = new lista("Funcionario_Experiencia_Laboral");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$detalle['Experiencia_Laboral']= $oLista->getlist();
unset($oLista);

$oLista = new lista("Funcionario_Referencia_Personal");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$detalle['Referencia_Personal']= $oLista->getlist();
unset($oLista);

$query = 'SELECT * FROM Contrato_Funcionario WHERE Estado="Activo" AND Identificacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$detalle['Contrato_Funcionario'] = $oCon->getData();
unset($oCon);


if($detalle['Contrato_Funcionario']['Id_Contrato']){

      $query = "SELECT * FROM Otrosi_Contrato WHERE Estado='Activo' AND Id_Contrato = $detalle[Contrato_Funcionario][Id_Contrato]
            Order by Id_Otrosi_Contrato DESC Limit 1 ";
      $oCon= new consulta();
      $oCon->setQuery($query);
      $detalle['Contrato_Funcionario']['Otrosi_Contrato'] = $oCon->getData();
      unset($oCon);
}
$detalle['Salario']= $detalle['Contrato_Funcionario']['Otrosi_Contrato']?$detalle['Contrato_Funcionario']['Otrosi_Contrato']['Salario']:$detalle['Contrato_Funcionario']['Valor'] ;



echo json_encode($detalle);
?>