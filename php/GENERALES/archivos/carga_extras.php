 <?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
//require_once 'HTTP/Request2.php';

function ceros($numero,$ceros) {
 $order_diez = explode(".",$numero); 
 $dif_diez = $ceros - strlen($order_diez[0]); 
 $insertar_ceros='';
 for($m=0;$m<$dif_diez;$m++){ 
  @$insertar_ceros .= 0;
 } 
 return $insertar_ceros .= $numero; 
}

function fecha($str)
{
    $date = explode("/",$str);
    return $date[2] . "-". $date[1] ."-". ceros($date[0],2);
}

$fecha= strtotime( '2017-05-01 00:00:00' );

$y=-1;
if (!empty($_FILES['Archivo']['name'])){

  $arrResult = array();
  
  $final="";
  $total=0;
  $handle = fopen($_FILES['Archivo']['tmp_name'], "r");
  if($handle){
  	
    while (($data = fgetcsv($handle, 2000, ";")) !== FALSE) {
      $y++;
      $arrResult[] = $data;
	  
	  if($arrResult[$y][1]=="Rotativo"){
	  	
		$oLista = new lista("Diario");
		$oLista->setRestrict("Identificacion_Funcionario","=",utf8_encode(ucwords(strtolower(trim($arrResult[$y][0])))));
		$oLista->setRestrict("Fecha","=",fecha(utf8_encode(ucwords(strtolower(trim($arrResult[$y][2]))))));
		$diarios=$oLista->getList();
		unset($oLista);
		
		/*foreach($diarios as $d){
			$oItem = new complex("Diario","Id_Diario",$d["Id_Diario"]);
			$oItem->delete();
			unset($oitem);
		}*/
		if(count($diarios)==0){
			$oItem = new complex('Diario','Id_Diario');
			$oItem->Identificacion_Funcionario=utf8_encode(ucwords(strtolower(trim($arrResult[$y][0]))));
			$oItem->Id_Turno=1;
			$oItem->Fecha=fecha(utf8_encode(ucwords(strtolower(trim($arrResult[$y][2])))));
			$oItem->Hora_Entrada=date("H:i:s",(utf8_encode(ucwords(strtolower(trim($arrResult[$y][3]))))*3600)+$fecha);
			$oItem->Hora_Salida=date("H:i:s",(utf8_encode(ucwords(strtolower(trim($arrResult[$y][4]))))*3600)+$fecha);
			$oItem->Fecha_Salida=fecha(utf8_encode(ucwords(strtolower(trim($arrResult[$y][7])))));;
			$oItem->Proceso=utf8_encode(strtoupper(trim($arrResult[$y][8])));;
			$oItem->save();
		}
	  	
	  }elseif($arrResult[$y][1]=="Fijo"){
	  		
	  	$oLista = new lista("Diario_Fijo");
		$oLista->setRestrict("Identificacion_Funcionario","=",utf8_encode(ucwords(strtolower(trim($arrResult[$y][0])))));
		$oLista->setRestrict("Fecha","=",fecha(utf8_encode(ucwords(strtolower(trim($arrResult[$y][2]))))));
		$diarios=$oLista->getList();
		unset($oLista);
		
		/*foreach($diarios as $d){
			$oItem = new complex("Diario","Id_Diario_Fijo",$d["Id_Diario_Fijo"]);
			$oItem->delete();
			unset($oitem);
		}*/
		
		if(count($diarios)==0){
			$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
			$oItem->Identificacion_Funcionario=utf8_encode(ucwords(strtolower(trim($arrResult[$y][0]))));
			$oItem->Id_Turno=1;
			$oItem->Fecha=fecha(utf8_encode(ucwords(strtolower(trim($arrResult[$y][2])))));
			$oItem->Hora_Entrada1=date("H:i:s",(utf8_encode(ucwords(strtolower(trim($arrResult[$y][3]))))*3600)+$fecha);
			$oItem->Hora_Salida1=date("H:i:s",(utf8_encode(ucwords(strtolower(trim($arrResult[$y][4]))))*3600)+$fecha);
			$oItem->Hora_Entrada2=date("H:i:s",(utf8_encode(ucwords(strtolower(trim($arrResult[$y][5]))))*3600)+$fecha);
			$oItem->Hora_Salida2=date("H:i:s",(utf8_encode(ucwords(strtolower(trim($arrResult[$y][6]))))*3600)+$fecha);
			$oItem->Proceso=utf8_encode(strtoupper(trim($arrResult[$y][8])));
			$oItem->save();
		}
	  }
    }
    fclose($handle);
  }
	
  $text="<i class='fa fa-check-circle fa-3x'></i><br>Completado";
}else{
  $text="Sin Archivo";
}
echo $text;

?>



