<?php
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex('Alerta','Id_Alerta',$id);
$alerta = $oItem->getData();
unset($oItem);

$oLista= new lista('Llegada_Tarde');
$oLista->setRestrict("Identificacion_Funcionario","=",$alerta["Identificacion_Funcionario"]);
$oLista->SetRestrict("Fecha","=",date("Y-m-d",strtotime($alerta["Fecha"])));
$oLista->SetRestrict("Entrada_Real","=",date("H:i:s",strtotime($alerta["Fecha"])));
$tardes=$oLista->getList();
unset($oLista);

$oItem = new complex('Funcionario','Identificacion_Funcionario',$alerta["Identificacion_Funcionario"]);
$func = $oItem->getData();
unset($oItem);

echo '<h3>'.$func["Nombres"]." ".$func["Apellidos"].'</h3>
      <input type="hidden" name="datos[Id_Alerta]" value="'.$alerta["Id_Alerta"].'" />
      <input type="hidden" name="datos[Id_Llegada_Tarde]" value="'.$tardes[0]["Id_Llegada_Tarde"].'" />
      <label>Entrada Turno <b style="color:red;">'.$tardes[0]["Entrada_Turno"].'</b></label>
      <label style="margin-left:30px;">Entrada Real <b style="color:red;">'.$tardes[0]["Entrada_Real"].'</b></label>';
	  

?>