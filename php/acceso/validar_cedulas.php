<?php
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once 'HTTP/Request2.php';

date_default_timezone_set('America/Bogota');

function RestarHoras($horaini,$horafin)
{
	$horai=substr($horaini,0,2);
	$mini=substr($horaini,3,2);
	$segi=substr($horaini,6,2);
 
	$horaf=substr($horafin,0,2);
	$minf=substr($horafin,3,2);
	$segf=substr($horafin,6,2);
 
	$ini=((($horai*60)*60)+($mini*60)+$segi);
	$fin=((($horaf*60)*60)+($minf*60)+$segf);
 
	$dif=$fin-$ini;
	$band=0;
	if($dif<0){
		$dif=$dif*(-1);
		$band=1;
	}
 
	$difh=floor($dif/3600);
	$difm=floor(($dif-($difh*3600))/60);
	$difs=$dif-($difm*60)-($difh*3600);
	if($band==0){
		return "-".date("H:i:s",mktime($difh,$difm,$difs));
	}else{
		return date("H:i:s",mktime($difh,$difm,$difs));
	}
	
}

$dias = array(
	0=> "Domingo",
	1=> "Lunes",
	2=> "Martes",
	3=> "Miercoles",
	4=> "Jueves",
	5=> "Viernes",
	6=> "Sabado"
);

$datos = (isset($_REQUEST['datos'] ) ? $_REQUEST['datos'] : '');


foreach($datos as $persona){
	$oItem = new complex('Funcionario','Identificacion_Funcionario',$persona["Identificacion_Funcionario"]);
	$funcionario=$oItem->getData();
	unset($oItem);
	$hactual=$persona["Hora"];
	$hoy=$persona["Fecha"];
	$ayer=date("Y-m-d", strtotime($hoy.' - 1 day'));
	
	
	//$hactual="02:00:50";
	//$hoy="2017-06-22";
	//$ayer="2017-06-21";
	if($funcionario["Tipo_Turno"]=="Rotativo"){
		$oLista= new lista('Horario');
		$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
		$oLista->setRestrict("Fecha","=",$ayer);
		$horario_ayer=$oLista->getList();
		
		$oLista= new lista('Horario');
		$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
		$oLista->setRestrict("Fecha","=",$hoy);
		$horario_hoy=$oLista->getList();
		
		$salida_ayer=1;
		if(isset($horario_ayer[0]["Id_Horario"])){
			if($horario_ayer[0]["Id_Turno"]!=0){
				$oItem = new complex('Turno','Id_Turno',$horario_ayer[0]["Id_Turno"]);
				$turno=$oItem->getData();
				unset($oItem);
				
				if(strtotime($turno["Hora_Inicio1"])>=strtotime("18:00:00")){
					$oLista= new lista('Diario');
					$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
					$oLista->setRestrict("Fecha","=",$ayer);
					$diario=$oLista->getList();
					
					if(isset($diario[0]["Id_Diario"])){
						if($diario[0]["Hora_Salida"]=="00:00:00"){
							$respuesta["Icono"]="success";
							$respuesta["Titulo"]="Gracias por Trabajar con Nosotros";
							$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos Vemos Mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
								
							$oItem = new complex('Diario','Id_Diario',$diario[0]["Id_Diario"]);
							$oItem->Fecha_Salida=$hoy;
							$oItem->Hora_Salida=$hactual;
							$oItem->Img2=$fot;
							$oItem->save();
							unset($oItem);
							$salida_ayer=0; 
						}
					}
				}
			}
		}
		if($salida_ayer==1&&isset($horario_hoy[0]["Id_Horario"])){
			if($horario_hoy[0]["Id_Turno"]!=0){
				$oItem = new complex('Turno','Id_Turno',$horario_hoy[0]["Id_Turno"]);
				$turno=$oItem->getData();
				unset($oItem);
				
				$oItem = new complex('Proceso','Id_Proceso',$horario_hoy[0]["Id_Proceso"]);
				$proceso=$oItem->getData();
				unset($oItem);
					
				$oLista= new lista('Diario');
				$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
				$oLista->setRestrict("Fecha","=",$hoy);
				$diario=$oLista->getList();
						
				if(!isset($diario[0]["Id_Diario"])){
							$diferencia=RestarHoras($hactual,$turno["Hora_Inicio1"]);
							$diferencia=explode(":",$diferencia);
							
							if($diferencia[0]<0){
								$respuesta["Icono"]="success";
								$respuesta["Titulo"]="Acceso Autorizado";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido, Hoy ha llegado temprano</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));
								
								$oItem = new complex('Diario','Id_Diario');
								$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
								$oItem->Fecha=$hoy;
								$oItem->Id_Turno=$turno["Id_Turno"];
								$oItem->Proceso=$proceso["Codigo"];
								$oItem->Hora_Entrada=$hactual;
								$oItem->Img1=$fot;
								$oItem->save(); 
								unset($oItem);
							
							}elseif($diferencia[0]>0){
								$respuesta["Icono"]="success";
								$respuesta["Titulo"]="Acceso Autorizado";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido, Hoy ha llegado tarde</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));;
							
								$oItem = new complex('Diario','Id_Diario');
								$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
								$oItem->Fecha=$hoy;
								$oItem->Id_Turno=$turno["Id_Turno"];
								$oItem->Proceso=$proceso["Codigo"];
								$oItem->Hora_Entrada=$hactual;
								$oItem->Img1=$fot;
								$oItem->save();
								unset($oItem);
								
								$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
								$tol_ent=($turno["Tolerancia_Entrada"]*60);
								
								if($diff>$tol_ent){
									$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
									$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
									$oItem->Fecha=$hoy;
									$oItem->Tiempo=$diff;
									$oItem->Entrada_Turno=$turno["Hora_Inicio1"];
									$oItem->Entrada_Real=$hactual;
									$oItem->save();
									unset($oItem);
									
									$oItem = new complex('Alerta','Id_Alerta');
									$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
									$oItem->Fecha=$hoy." ".$hactual;
									$oItem->Tiempo=$diff;
									$oItem->Tipo="Llegada Tarde";
									$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
									$oItem->save();
									unset($oItem);
									
									
								}
							}else{
								$respuesta["Icono"]="success";
								$respuesta["Titulo"]="Acceso Autorizado";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
								
								$oItem = new complex('Diario','Id_Diario');
								$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
								$oItem->Fecha=$hoy;
								$oItem->Id_Turno=$turno["Id_Turno"];
								$oItem->Proceso=$proceso["Codigo"];
								$oItem->Hora_Entrada=$hactual;
								$oItem->Img1=$fot;
								$oItem->save();
								unset($oItem);
								
								/* if(strpos($diferencia[0],"-")===false){
									$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
									$tol_ent=($turno["Tolerancia_Entrada"]*60);
									
									if($diff>$tol_ent){
										$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Tiempo=$diff;
										$oItem->save();
										unset($oItem);
										
										$oItem = new complex('Alerta','Id_Alerta');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy." ".$hactual;
										$oItem->Tiempo=$diff;
										$oItem->Tipo="Llegada Tarde";
										$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
										$oItem->save();
										unset($oItem);
										
									}
								} */
							}
						}else{
							$dif_entrada=RestarHoras($hactual,$diario[0]["Hora_Entrada"]);
							$dif_entrada=explode(":",$dif_entrada);
							
							$dif_entrada=($dif_entrada[0]*3600)+($dif_entrada[1]*60)+$dif_entrada[2];
							
							if($dif_entrada>600){ 
								if($diario[0]["Hora_Salida"]=="00:00:00"){
									$respuesta["Icono"]="success";
									$respuesta["Titulo"]="Hasta Mañana";
									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos Vemos Mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
									$oItem = new complex('Diario','Id_Diario',$diario[0]["Id_Diario"]);
									$oItem->Hora_Salida=$hactual;
									$oItem->Fecha_Salida=$hoy;
									$oItem->Img2=$fot;
									$oItem->save();
									unset($oItem);
								}else{
									$respuesta["Icono"]="warning"; 
									$respuesta["Titulo"]="Ya ha reportado turno hoy";  
									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Ya reportaste entrada y salida de turno hoy <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong>";  
								}
							}else{
								$respuesta["Icono"]="warning";
								$respuesta["Titulo"]="Ya ha ingresado hoy";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Ya marcaste ingreso en un rango de 10 minutos<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong>";  
							}
							
								
						}
				}else{
						$respuesta["Icono"]="error";
						$respuesta["Titulo"]="Acceso Denegado";
						$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>De acuerdo a la programación, hoy es su día libre</strong>";					
				}
		}elseif($salida_ayer==1&&!isset($horario_hoy[0]["Id_Horario"])){
			$respuesta["Icono"]="error";
			$respuesta["Titulo"]="Acceso Denegado";
			$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>No tiene un turno asignado para este día, por favor comuníquese con su supervisor.</strong>";  
			
		}
		
	}elseif($funcionario["Tipo_Turno"]=="Fijo"){
		
		if($funcionario["Id_Turno"]!=0){
				$oItem = new complex('Turno','Id_Turno',$funcionario["Id_Turno"]);
				$turno=$oItem->getData();
				unset($oItem);
				
				$oItem = new complex('Proceso','Id_Proceso',$funcionario["Id_Proceso"]);
				$proceso=$oItem->getData();
				unset($oItem);
				
				
				$oLista= new lista('Hora_Turno');
				$oLista->setRestrict("Id_Turno","=",$funcionario["Id_Turno"]);
				$oLista->setRestrict("Dia","=",$dias[date("w",strtotime($hoy))]);
				$horas=$oLista->getList();
				
				$oLista= new lista('Diario_Fijo');
				$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
				$oLista->setRestrict("Fecha","=",$hoy);
				$diario=$oLista->getList();
						
				if(!isset($diario[0]["Id_Diario_Fijo"])){
							$diferencia=RestarHoras($hactual,$horas[0]["Hora_Inicio1"]);
							$dife=$diferencia;
							$diferencia=explode(":",$diferencia);
							
							if($diferencia[0]<0){
								$respuesta["Icono"]="success";
								$respuesta["Titulo"]="Acceso Autorizado";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido, Hoy ha llegado temprano</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));
								
								$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
								$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
								$oItem->Fecha=$hoy;
								$oItem->Id_Turno=$turno["Id_Turno"];
								$oItem->Proceso=$proceso["Codigo"];
								$oItem->Hora_Entrada1=$hactual;
								$oItem->Img1=$fot;
								$oItem->save();
								unset($oItem);
							
							}elseif($diferencia[0]>0){
								$respuesta["Icono"]="success";
								$respuesta["Titulo"]="Acceso Autorizado";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Bienvenido, Hoy ha llegado tarde</strong><br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));;
							
								$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
								$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
								$oItem->Fecha=$hoy;
								$oItem->Id_Turno=$turno["Id_Turno"];
								$oItem->Proceso=$proceso["Codigo"];
								$oItem->Hora_Entrada1=$hactual;
								$oItem->Img1=$fot;
								$oItem->save();
								unset($oItem);
								
								$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
								$tol_ent=($turno["Tolerancia_Entrada"]*60);
								
								if($diff>$tol_ent){
									$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
									$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
									$oItem->Fecha=$hoy;
									$oItem->Tiempo=$diff;
									$oItem->Entrada_Turno=$horas[0]["Hora_Inicio1"];
									$oItem->Entrada_Real=$hactual;
									$oItem->save();
									unset($oItem);
									
									$oItem = new complex('Alerta','Id_Alerta');
									$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
									$oItem->Fecha=$hoy." ".$hactual;
									$oItem->Tiempo=$diff;
									$oItem->Tipo="Llegada Tarde";
									$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
									$oItem->save();
									unset($oItem);
								}
							}else{
								$respuesta["Icono"]="success";
								$respuesta["Titulo"]="Acceso Autorizado";
								$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
								
								$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
								$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
								$oItem->Fecha=$hoy;
								$oItem->Id_Turno=$turno["Id_Turno"];
								$oItem->Proceso=$proceso["Codigo"];
								$oItem->Hora_Entrada1=$hactual;
								$oItem->Img1=$fot;
								$oItem->save();
								unset($oItem);
								
								/*if(strpos($diferencia[0],"-")===false){
									$diff=($diferencia[0]*60*60)+($diferencia[1]*60)+($diferencia[2]);
									$tol_ent=($turno["Tolerancia_Entrada"]*60);
									
									if($diff>$tol_ent){
										$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy;
										$oItem->Tiempo=$diff;
										$oItem->save();
										unset($oItem);
										
										$oItem = new complex('Alerta','Id_Alerta');
										$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
										$oItem->Fecha=$hoy." ".$hactual;
										$oItem->Tiempo=$diff;
										$oItem->Tipo="Llegada Tarde";
										$oItem->Detalles=$funcionario["Nombres"]." ".$funcionario["Apellidos"]." ha llegado Tarde";
										$oItem->save();
										unset($oItem);
									}
								}*/
							}
						}else{
								if($diario[0]["Hora_Salida1"]=="00:00:00"){
									$respuesta["Icono"]="success";
									$respuesta["Titulo"]="Hasta Luego";
									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Hasta Luego, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
									$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
									$oItem->Hora_Salida1=$hactual;
									$oItem->Img2=$fot;
									$oItem->save();
									unset($oItem);
								}elseif($diario[0]["Hora_Entrada2"]=="00:00:00"){
									$respuesta["Icono"]="success";
									$respuesta["Titulo"]="Bienvenido de Nuevo";
									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
									$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
									$oItem->Hora_Entrada2=$hactual;
									$oItem->Img3=$fot;
									$oItem->save();
									unset($oItem);
								}elseif($diario[0]["Hora_Salida2"]=="00:00:00"){
									$respuesta["Icono"]="success";
									$respuesta["Titulo"]="Hasta Mañana";
									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos vemos mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
										
									$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
									$oItem->Hora_Salida2=$hactual;
									$oItem->Img4=$fot;
									$oItem->save();
									unset($oItem);
								}else{
									$respuesta["Icono"]="warning";
									$respuesta["Titulo"]="Ya ha reportado turno hoy";
									$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Ya reportaste entrada y salida de turno hoy <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong>";  
								}
						}
		}else{
				$respuesta["Icono"]="error";
				$respuesta["Titulo"]="Acceso Denegado";
				$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br><strong>Hoy no Tiene un Turno Asignado</strong>";					
		}
	}elseif($funcionario["Tipo_Turno"]=="Libre"){
		$oLista= new lista('Diario_Fijo');
		$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario["Identificacion_Funcionario"]);
		$oLista->setRestrict("Fecha","=",$hoy);
		$diario=$oLista->getList();
		
		$oItem = new complex('Proceso','Id_Proceso',$funcionario["Id_Proceso"]);
		$proceso=$oItem->getData();
		unset($oItem);
		
		if(!isset($diario[0]["Id_Diario_Fijo"])){
		
			$respuesta["Icono"]="success";
			$respuesta["Titulo"]="Acceso Autorizado";
			$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido<br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
								
								
			$oItem = new complex('Diario_Fijo','Id_Diario_Fijo');
			$oItem->Identificacion_Funcionario=$funcionario["Identificacion_Funcionario"];
			$oItem->Fecha=$hoy;
			$oItem->Id_Turno=0;
			$oItem->Hora_Entrada1=$hactual;
			$oItem->Proceso=$proceso["Codigo"];
			$oItem->Img1=$fot;
			$oItem->save();
			unset($oItem);
		
		}else{
			if($diario[0]["Hora_Salida1"]=="00:00:00"){
				$respuesta["Icono"]="success";
				$respuesta["Titulo"]="Hasta Luego";
				$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Hasta Luego, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
					
				$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
				$oItem->Hora_Salida1=$hactual;
				$oItem->Img2=$fot;
				$oItem->save();
				unset($oItem);
			}elseif($diario[0]["Hora_Entrada2"]=="00:00:00"){
				$respuesta["Icono"]="success";
				$respuesta["Titulo"]="Bienvenido de Nuevo";
				$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Bienvenido, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
					
				$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
				$oItem->Hora_Entrada2=$hactual;
				$oItem->Img3=$fot;
				$oItem->save();
				unset($oItem);
			}elseif($diario[0]["Hora_Salida2"]=="00:00:00"){
				$respuesta["Icono"]="success";
				$respuesta["Titulo"]="Hasta Mañana";
				$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Nos vemos mañana, <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong><br>".date("d/m/Y H:i:s",strtotime($hoy." ".$hactual));  
					
				$oItem = new complex('Diario_Fijo','Id_Diario_Fijo',$diario[0]["Id_Diario_Fijo"]);
				$oItem->Hora_Salida2=$hactual;
				$oItem->Img4=$fot;
				$oItem->save();
				unset($oItem);
			}else{
				$respuesta["Icono"]="warning";
				$respuesta["Titulo"]="Ya ha reportado turno hoy";
				$respuesta["Mensaje"]="<img src='".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"]."' class='img-thumbnail img-circle img-responsive' style='max-width:200px;'  /><br>Ya reportaste entrada y salida de turno hoy <br><strong>".$funcionario["Nombres"]." ".$funcionario["Apellidos"]."</strong>";  
			}
		}
	}

}
			
echo "Registros Asincrónicos Realizados Correctaente";
//echo json_encode($respuesta);
?>