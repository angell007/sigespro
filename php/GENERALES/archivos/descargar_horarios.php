<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$funcionario  = (isset($_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '');
$fini  = (isset($_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '');
$ffin  = (isset($_REQUEST['fin'] ) ? $_REQUEST['fin'] : '');
$grupo  = (isset($_REQUEST['grupo'] ) ? $_REQUEST['grupo'] : '');
$tipoturno = (isset($_REQUEST['tipoturno'] ) ? $_REQUEST['tipoturno'] : '');


require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte Horarios ('.$fini.' - '.$ffin.').xls"');
header('Cache-Control: max-age=0');

$objPHPExcel = new PHPExcel;

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objSheet = $objPHPExcel->getActiveSheet();
$objSheet->setTitle('Reporte de Horarios');

function diff($start, $end) {
	$start_ts = strtotime($start);
	$end_ts = strtotime($end);
	$diff = $end_ts - $start_ts;
	return $diff / 3600;
}

function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}

$meses = array(
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre",
);
$dias = array(
	0=> "Domingo",
	1=> "Lunes",
	2=> "Martes",
	3=> "Miercoles",
	4=> "Jueves",
	5=> "Viernes",
	6=> "Sabado"
);

$oLista= new lista('Funcionario');
if($funcionario!="Todos"){
	$oLista->setRestrict("Identificacion_Funcionario","=",$funcionario);
}
if($grupo!="Todos"){
	$oLista->setRestrict("Id_Grupo","=",$grupo);
}
if($tipoturno!="Todos"){
	$oLista->setRestrict("Tipo_Turno","=",$tipoturno);
}
$oLista->setOrder("Nombres","ASC");
$funcionarios=$oLista->getList();
unset($oLista);

$oLista= new lista('Grupo');
$grupos=$oLista->getList();
unset($oLista);
  
$oItem = new complex("Configuracion","id",1);
$config= $oItem->getData();
unset($oItem);

$fi=explode("-",$fini);
$ff=explode("-",$ffin);


$ps=date("W",mktime(0,0,0,$fi[1],$fi[2],$fi[0]));
$us=date("W",mktime(0,0,0,$ff[1],$ff[2],$ff[0]));

$startTime = strtotime( $fini.' 00:00:00' );
$endTime = strtotime(date($ffin.' 23:59:59'));

$objSheet->getCell('A1')->setValue('Reporte de Horas | Fecha: '.$fini.' - '.$ffin);
$objSheet->mergeCells('A1:P1');
$objSheet->getStyle('A1:P1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getCell('A2')->setValue('Código '.$ps." - ".$us);
$objSheet->getCell('B2')->setValue('Empleado');
$objSheet->getCell('C2')->setValue('Fecha');
$objSheet->getCell('D2')->setValue('Entrada 1');
$objSheet->getCell('E2')->setValue('Salida 1');
$objSheet->getCell('F2')->setValue('Entrada 2');
$objSheet->getCell('G2')->setValue('Salida 2');
$objSheet->getCell('H2')->setValue('Laborado');
$objSheet->getCell('I2')->setValue('');
$objSheet->getCell('J2')->setValue('HED');
$objSheet->getCell('K2')->setValue('HEN');
$objSheet->getCell('L2')->setValue('HEDFD');
$objSheet->getCell('M2')->setValue('HEDFN');
$objSheet->getCell('N2')->setValue('RN');
$objSheet->getCell('O2')->setValue('RDF');
$objSheet->getCell('P2')->setValue('RDFD');

$objSheet->getStyle('A2:P2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objSheet->getStyle('A2:P2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00000000');
$objSheet->getStyle('A2:P2')->getFont()->setBold(true);
$objSheet->getStyle('A2:P2')->getFont()->getColor()->setARGB('FFFFFFFF');

$fila=2;
foreach($funcionarios as $func){ $fila++; 

	$objSheet->getCell('A'.$fila)->setValue($func["Codigo"]);
	$objSheet->getCell('B'.$fila)->setValue($func["Nombres"]." ".$func["Apellidos"]);
	$objSheet->getCell('J'.$fila)->setValue('HED');
	$objSheet->getCell('K'.$fila)->setValue('HEN');
	$objSheet->getCell('L'.$fila)->setValue('HEDFD');
	$objSheet->getCell('M'.$fila)->setValue('HEDFN');
	$objSheet->getCell('N'.$fila)->setValue('RN');
	$objSheet->getCell('O'.$fila)->setValue('RDF');
	$objSheet->getCell('P'.$fila)->setValue('RDFD');
	$objSheet->getStyle('A'.$fila.':P'.$fila)->getFont()->setBold(true);
	$objSheet->getStyle('A'.$fila.':P'.$fila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('b0b0b0b0');
	
	
		
	$tot_tiempo_laborado=0;
	$tot_hed=0; /* Hora Extra Diurna */
	$tot_hen=0; /* Hora Extra Nocturna */
	$tot_hedd=0; /* Hora Extra Dom Diurna */
	$tot_hedn=0; /* Hora Extra Dom Nocturna*/
	$tot_rdd=0; /* Recargo Domingo Diurno */
	$tot_rdn=0; /* Recargo Domingo Nocturno */
	$tot_rn=0; /* Recargo Nocturno */
	
	$posiciones='';
	
	$oLista= new lista('Horario');
	$oLista->setRestrict("Fecha","<=",$ffin);
	$oLista->setRestrict("Fecha",">=",$fini);
	$oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	$horarios_des=$oLista->getList();
	unset($oLista);
	$descanso_semana="No";
	
	foreach($horarios_des as $hora){
		$id_turno=$hora["Id_Turno"];
		if($id_turno==0){
			$descanso_semana="Si";
		}
	}
	$compensatorio="No";
	
	for ( $i = $startTime; $i <= $endTime; $i=strtotime("+1 day",$i) ) { $fila++;
	
	$descanso="No";
			
	$fecha=date("Y-m-d",$i);
	$pos_array++;
	
	$festivo=strpos($config["Festivos"], fecha($fecha));
	$dia=date("w",strtotime($fecha));
	
	$hora_turno="Sin Turno Asignado";
	$hora_real="No Reportó Horas";
	
	$tiempo_asignado=0;
	$tiempo_laborado=0;
	
	$diferencia=0;
	$diferencia1=0;
	$diferencia2=0;
	$fecha_salida='0000-00-00';
	
	$reporta_extras="Si";
	if($func["Tipo_Turno"]=="Fijo"){ 
		$oItem = new complex("Turno","Id_Turno",$func["Id_Turno"]); 
		$turn=$oItem->getData(); 
		
		$id_turno=$turn["Id_Turno"];
		$oLista= new lista('Hora_Turno');
		$oLista->setRestrict("Id_Turno","=",$func["Id_Turno"]);
		$oLista->setRestrict("Dia","=",$dias[date("w",strtotime($fecha))]);
		
		$horas=$oLista->getList();
		
		$hora_turno=$horas[0]["Hora_Inicio1"]." - ".$horas[0]["Hora_Fin1"]; 
		if($horas[0]["Hora_Inicio2"]!="00:00:00"){
			$hora_turno.=" y ".$horas[0]["Hora_Inicio2"]." - ".$horas[0]["Hora_Fin2"];
		}
	}
	if($reporta_extras=="Si"){ $cant_func++;

	$oLista= new lista('Horario');
	$oLista->setRestrict("Fecha","=",$fecha);
	$oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	$horarios=$oLista->getList();
	unset($oLista);
	
	foreach($horarios as $hora){
		
		$oItem = new complex("Turno","Id_Turno",$hora["Id_Turno"]);
		$turno = $oItem->getData();
		$id_turno=$turno["Id_Turno"];
		$hora_turno=$turno["Hora_Inicio1"]." - ".$turno["Hora_Fin1"];
		if($turno["Hora_Inicio2"]!="00:00:00"){
			$hora_turno.=" y ".$turno["Hora_Inicio2"]." - ".$turno["Hora_Fin2"];
		} 
		
		if($id_turno==0){
			$descanso="Si";
		}
	}	
	$oLista= new lista('Diario');
	$oLista->setRestrict("Fecha","=",$fecha);
	$oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	$diarios=$oLista->getList();
	unset($oLista);
	
	
	
	$hed=0; /* Hora Extra Diurna */
	$hen=0; /* Hora Extra Nocturna */
	$hedd=0; /* Hora Extra Dom Diurna */
	$hedn=0; /* Hora Extra Dom Nocturna*/
	$rdd=0; /* Recargo Domingo Diurno */
	$rdn=0; /* Recargo Domingo Nocturno */
	$rn=0; /* Recargo Nocturno */
	
	$entrada="";
	$salida="";
	$entrada2="";
	$salida2="";
	
	$id_diario=0;
	
	foreach($diarios as $diario){
		$fecha_salida=$diario["Fecha_Salida"];
		$id_diario=$diario["Id_Diario"];
		
		/*$oItem = new complex("Turno","Id_Turno",$diario["Id_Turno"]);
		$turno = $oItem->getData();
		unset($oItem);*/
		$tolerancia=$turno["Tolerancia_Salida"];
		 
		$hora_real=$diario["Hora_Entrada"]." - ".$diario["Hora_Salida"];
		$entrada=$diario["Hora_Entrada"];
		$salida=$diario["Hora_Salida"];
		
		$hora_real2=$diario["Hora_Entrada"];
		if($diario["Img1"]!=""){
			$hora_real2.='&nbsp;&nbsp;<a href="javascript:foto(\''.$diario["Img1"].'\');"><i class="fa fa-smile-o"></i></a>';
		}
		$hora_real2.=" - ".$diario["Hora_Salida"];
		if($diario["Img2"]!=""){
			$hora_real2.='&nbsp;&nbsp;<a href="javascript:foto(\''.$diario["Img2"].'\');"><i class="fa fa-smile-o"></i></a>';
		}
		
		if($turno["Extras"]=="Si"){
		if($turno["Hora_Inicio1"]>="18:00:00"){
			$tiempo_asignado=diff(date("Y-m-d")." ".$turno["Hora_Inicio1"],date("Y-m-d",strtotime('+1 day',strtotime(date("Y-m-d"))))." ".$turno["Hora_Fin1"]);
		}else{
			$tiempo_asignado=diff(date("Y-m-d")." ".$turno["Hora_Inicio1"],date("Y-m-d")." ".$turno["Hora_Fin1"]);
			if($turno["Hora_Inicio2"]!="00:00:00"){
				$tiempo_asignado+=diff(date("Y-m-d")." ".$turno["Hora_Inicio2"],date("Y-m-d")." ".$turno["Hora_Fin2"]);
			}
		}	
		
		$tiempo_laborado=diff($diario["Fecha"]." ".$diario["Hora_Entrada"],$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
		
		
		if($diario["Hora_Salida"]>"07:00:00"){
			$tipo_turno="Diurno";
		}else{
			$tipo_turno="Nocturno";
		}
			if($tiempo_asignado<=9){
				
				if($tipo_turno=="Diurno"){
					if($tiempo_laborado>(8+($tolerancia/60))){
						$diferencia=$tiempo_laborado-8;
						$nuevafecha = strtotime ( '+'.$tolerancia.' minutes' , strtotime ( $diario["Fecha"]." ".$config["Hora_Inicio_Noche"] ) ) ;
						$nuevafecha=date("H:i:s",$nuevafecha);
						if($diario["Hora_Salida"]>$nuevafecha){
							if($diferencia>($tolerancia/60)){
								if($dia==0||$festivo!==false){
									$hedn+=$diferencia;	
								}else{
									$hen+=$diferencia;
								}
							}
						}else{
							if($dia==0||$festivo!==false){
								$hedd+=$diferencia;	
							}else{
								$hed+=$diferencia;
							}
						}
					}
					if($diario["Hora_Salida"]>$config["Hora_Fin_Dia"]){
						$diferencia=diff($diario["Fecha"]." ".$config["Hora_Fin_Dia"],$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
						$rn+=number_format($diferencia);
						
					}
					if($festivo!==false||$dia==0){
						if($tiempo_laborado>=8){
							if($descanso_semana=="Si"&&$compensatorio=="No"){
								$rdn+=8;
								$compensatorio="Si";
							}else{
								$rdd+=8;
							}
						}else{
							if($descanso_semana=="Si"&&$compensatorio=="No"){
								$rdn+=$tiempo_laborado;
								$compensatorio="Si";
							}else{
								$rdd+=$tiempo_laborado;
							}
							
						}
					}
					
				}elseif($tipo_turno=="Nocturno"){
					if($tiempo_laborado>(8+($tolerancia/60))){
						$diferencia=$tiempo_laborado-8;
						$festivo_sig=strpos($config["Festivos"], fecha($diario["Fecha_Salida"]));
						if($dia==6||$festivo_sig!==false){
							$hedn+=$diferencia;
						}else{
							$hen+=$diferencia;
						}	
					}
					$nuevafecha = strtotime ( '-'.$tolerancia.' minutes' , strtotime ( $diario["Fecha"]." ".$config["Hora_Inicio_Noche"] ) ) ;
					$nuevafecha=date("H:i:s",$nuevafecha);

					if($diario["Hora_Entrada"]>=$nuevafecha){
						$diferencia=diff($diario["Fecha"]." ".$diario["Hora_Entrada"],$diario["Fecha_Salida"]." ".$config["Hora_Fin_Noche"]);
						$rn+=number_format($diferencia);
					}else{
						$rn+=9;
					}
					
					if($festivo!==false||$dia==0){
						$diferencia_fest=diff($diario["Fecha"]." ".$diario["Hora_Entrada"],$diario["Fecha"]." 23:59:59");
						if($descanso_semana=="Si"&&$compensatorio=="No"){
							$rdn+=$diferencia_fest;
							$compensatorio="Si";
						}else{
							$rdd+=$diferencia_fest;
						}
						$festivo_lunes=strpos($config["Festivos"], fecha($diario["Fecha_Salida"]));
						if($festivo_lunes!==false){
							$diferencia_fest2=diff($diario["Fecha_Salida"]." 00:00:00",$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
							if($descanso_semana=="Si"){
								$rdn+=$diferencia_fest2;
							}else{
								$rdd+=$diferencia_fest2;
							}	
						}else{
							$diferencia_fest2=diff($diario["Fecha_Salida"]." 00:00:00",$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
							$hen+=$diferencia_fest2;
						}
					}
					/*if($dia==6){
						$diferencia_fest=diff($diario["Fecha_Salida"]." 00:00:00",$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
						if($descanso_semana=="Si"){
							$rdn+=$diferencia_fest;
						}else{
							$rdd+=$diferencia_fest;
						}
					}*/
					
					
					
				}
			}elseif($tiempo_asignado > 9){
				
				if($tiempo_asignado>=10){
					if($tipo_turno=="Diurno"&&$festivo===false){
						$tiempo_laborado-=1;
					}
				}	
				
				if($tipo_turno=="Diurno"){
					if($tiempo_laborado>(8+($tolerancia/60))){
						$diferencia=$tiempo_laborado-8;
						if($dia==0||$festivo!==false){
							$hedd+=$diferencia;
							if($tiempo_laborado>=8){
								if($descanso_semana=="Si"&&$compensatorio=="No"){
									$rdn+=8;
									$compensatorio="Si";
								}else{
									$rdd+=8;
								}
							}else{
								if($descanso_semana=="Si"&&$compensatorio=="No"){
									$rdn+=$tiempo_laborado;
									$compensatorio="Si";
								}else{
									$rdd+=$tiempo_laborado;
								}
							}
						}else{
							$hed+=$diferencia;
						}
					}else{
						$diferencia=$tiempo_laborado-8;
						$hed+=$diferencia;
					}
					if($diario["Hora_Salida"]>$config["Hora_Fin_Dia"]){
						$diferencia=diff($diario["Fecha"]." ".$config["Hora_Fin_Dia"],$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
						$rn+=number_format($diferencia);
					}
					if($festivo!==false||$dia==0){
						if($tiempo_laborado>=8){
							if($descanso_semana=="Si"&&$compensatorio=="No"){
								$rdn+=8;
								$compensatorio="Si";
							}else{
								$rdd+=8;
							}
						}else{
							if($descanso_semana=="Si"&&$compensatorio=="No"){
								$rdn+=$tiempo_laborado;
								$compensatorio="Si";
							}else{
								$rdd+=$tiempo_laborado;
							}
						}
					}
				}elseif($tipo_turno=="Nocturno"){
					if($tiempo_laborado>(8+($tolerancia/60))){
						$diferencia=$tiempo_laborado-8;
						if($dia==0||$festivo!==false){
							$hedn+=$diferencia;	
						}else{
							$hen+=$diferencia;
						}
					}else{
						$diferencia=$tiempo_laborado-8;
						$hen+=$diferencia;
					}
					/*
					if($dia==6){
						$diferencia_fest=diff($diario["Fecha_Salida"]." 00:00:00",$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
						if($descanso_semana=="Si"){
							$rdn+=$diferencia_fest;
						}else{
							$rdd+=$diferencia_fest;
						}
					}
					*/
					if($dia==0||$festivo!==false){
						$diferencia_fest=diff($diario["Fecha"]." ".$diario["Hora_Entrada"],$diario["Fecha"]." 23:59:59");
						if($descanso_semana=="Si"&&$compensatorio=="No"){
							$rdn+=$diferencia_fest;
							$compensatorio="Si";
						}else{
							$rdd+=$diferencia_fest;
						}
						$festivo_lunes=strpos($config["Festivos"], fecha($diario["Fecha_Salida"]));
						if($festivo_lunes!==false){
							$diferencia_fest2=diff($diario["Fecha_Salida"]." 00:00:00",$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
							if($descanso_semana=="Si"&&$compensatorio=="No"){
								$rdn+=$diferencia_fest2;
								$compensatorio="Si";
							}else{
								$rdd+=$diferencia_fest2;
							}	
						}else{
							$diferencia_fest2=diff($diario["Fecha_Salida"]." 00:00:00",$diario["Fecha_Salida"]." ".$diario["Hora_Salida"]);
							$hen=$diferencia_fest2;
						}
					}
					$rn+=9;
				}
			}
		
		}
	
	}
	
	$oLista= new lista('Diario_Fijo');
	$oLista->setRestrict("Fecha","=",$fecha);
	$oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	$diarios_fijos=$oLista->getList();
	unset($oLista);
	
	foreach($diarios_fijos as $diario){
		
		$oItem = new complex("Turno","Id_Turno",$diario["Id_Turno"]);
		$turno = $oItem->getData();

		unset($oItem);
		$id_diario=$diario["Id_Diario_Fijo"];
		
		$oLista= new lista('Hora_Turno');
		$oLista->setRestrict("Id_Turno","=",$func["Id_Turno"]);
		$oLista->setRestrict("Dia","=",$dias[date("w",strtotime($fecha))]);
		$horas=$oLista->getList();
		unset($oLista);
		
		$hora_real=$diario["Hora_Entrada1"]." - ".$diario["Hora_Salida1"];
		$entrada=$diario["Hora_Entrada1"];
		$salida=$diario["Hora_Salida1"];
		
		$entrada2=$diario["Hora_Entrada2"];
		$salida2=$diario["Hora_Salida2"];
		
		$hora_real2=$diario["Hora_Entrada1"];
		if($diario["Img1"]!=""){
			$hora_real2.='&nbsp;&nbsp;<a href="javascript:foto(\''.$diario["Img1"].'\');"><i class="fa fa-smile-o"></i></a>';
		}
		$hora_real2.=" - ".$diario["Hora_Salida1"];
		if($diario["Img2"]!=""){
			$hora_real2.='&nbsp;&nbsp;<a href="javascript:foto(\''.$diario["Img2"].'\');"><i class="fa fa-smile-o"></i></a>';
		}
		
		if($diario["Hora_Entrada2"]!="00:00:00"){
			$hora_real.=" y ".$diario["Hora_Entrada2"]." - ".$diario["Hora_Salida2"];
			
			$hora_real2.=" y ".$diario["Hora_Entrada2"];
			if($diario["Img3"]!=""){
				$hora_real2.='&nbsp;&nbsp;<a href="javascript:foto(\''.$diario["Img3"].'\');"><i class="fa fa-smile-o"></i></a>';
			}
			$hora_real2.=" - ".$diario["Hora_Salida2"];
			if($diario["Img4"]!=""){
				$hora_real2.='&nbsp;&nbsp;<a href="javascript:foto(\''.$diario["Img4"].'\');"><i class="fa fa-smile-o"></i></a>';
			}
		}
		
		$dif_asig1=diff($diario["Fecha"]." ".$horas[0]["Hora_Inicio1"],$diario["Fecha"]." ".$horas[0]["Hora_Fin1"]);
		$dif_asig2=diff($diario["Fecha"]." ".$horas[0]["Hora_Inicio2"],$diario["Fecha"]." ".$horas[0]["Hora_Fin2"]);
		$tiempo_asignado=$dif_asig1+$dif_asig2;
		
		$diferencia1=diff($diario["Fecha"]." ".$diario["Hora_Entrada1"],$diario["Fecha"]." ".$diario["Hora_Salida1"]);
		$diferencia2=diff($diario["Fecha"]." ".$diario["Hora_Entrada2"],$diario["Fecha"]." ".$diario["Hora_Salida2"]);
		
		$almuerzo=diff($diario["Fecha"]." ".$horas[0]["Hora_Fin1"],$diario["Fecha"]." ".$horas[0]["Hora_Inicio2"]);
		
		if($diferencia1<0){
			$diferencia1=0;
		}
		if($diferencia2<0){
			$diferencia2=0;
		}
		if($almuerzo<0){
			$almuerzo=0;
		}
		
		if($diario["Hora_Entrada2"]=="00:00:00"){
			$tiempo_laborado=$diferencia1+$diferencia2-$almuerzo;
		}else{
			$tiempo_laborado=$diferencia1+$diferencia2;
		}

		if($tiempo_laborado>8){
			if($tiempo_asignado==9.5){
				$hed+=0.75;
			}else{
				if($tiempo_laborado>($tiempo_asignado+($turno["Tolerancia_Salida"]/60))){
					$hed+=($tiempo_laborado-$tiempo_asignado);
				}
			}
		}
		if($festivo!==false||$dia==0){
			if($descanso_semana=="Si"){
				$rdn+=$tiempo_laborado;
			}else{
				$rdd+=$tiempo_laborado;
			}
		}				
	}
	

	$oLista= new lista('Reporte');
	$oLista->setRestrict("Fecha","=",$fecha);
	$oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	$reportes=$oLista->getList();
	unset($oLista);
	
	$oLista= new lista('Novedad');
	$oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	$oLista->setRestrict("Inicio","<=",$fecha.' 00:00:00');
	$oLista->setRestrict("Fin",">=",$fecha.' 23:59:59');
	$novedades=$oLista->getList();
	unset($oLista);
	
	if($tiempo_laborado<0){
		$tiempo_laborado=0;
	}
	if($hed<0){
		$hed=0;
	}
	if($hen<0){
		$hen=0;
	}
	if($hedd<0){
		$hedd=0;
	}
	if($hedn<0){
		$hedn=0;
	}
	if($rdd<0){
		$rdd=0;
	}
	if($rdn<0){
		$rdn=0;
	}
	if($rn<0){
		$rn=0;
	}
	if($rdd>8){
		$rdd=8;
	}
	if($rdn>8){
		$rdn=8;
	}
	
	$tot_tiempo_laborado+=$tiempo_laborado;
	if(isset($reportes[0]['Id_Reporte'])){ $tot_hed+=$reportes[0]['HED']; $hed=str_replace(",",".",$reportes[0]['HED']); }else{ $tot_hed+=$hed; }
	if(isset($reportes[0]['Id_Reporte'])){ $tot_hen+=$reportes[0]['HEN']; $hen=str_replace(",",".",$reportes[0]['HEN']);}else{ $tot_hen+=$hen; }
	if(isset($reportes[0]['Id_Reporte'])){ $tot_hedd+=$reportes[0]['HEDD']; $hedd=str_replace(",",".",$reportes[0]['HEDD']); }else{ $tot_hedd+=$hedd; }
	if(isset($reportes[0]['Id_Reporte'])){ $tot_hedn+=$reportes[0]['HEDN']; $hedn=str_replace(",",".",$reportes[0]['HEDN']); }else{ $tot_hedn+=$hedn; }
	if(isset($reportes[0]['Id_Reporte'])){ $tot_rdd+=$reportes[0]['RDD']; $rdd=str_replace(",",".",$reportes[0]['RDD']); }else{ $tot_rdd+=$rdd; }
	if(isset($reportes[0]['Id_Reporte'])){ $tot_rdn+=$reportes[0]['RDN']; $rdn=str_replace(",",".",$reportes[0]['RDN']); }else{ $tot_rdn+=$rdn; }
	if(isset($reportes[0]['Id_Reporte'])){ $tot_rn+=$reportes[0]['RN']; $rn=str_replace(",",".",$reportes[0]['RN']); }else{ $tot_rn+=$rn; }
	if($descanso=="Si"){
		$hora_turno="Día Descanso";
	}
	
	$objSheet->getCell('C'.$fila)->setValue(fecha($fecha));
	$objSheet->getCell('D'.$fila)->setValue($entrada);
	$objSheet->getCell('E'.$fila)->setValue($salida);
	if($entrada2!=''&&$entrada2!="00:00:00"){
		$objSheet->getCell('F'.$fila)->setValue($entrada2);
		$objSheet->getCell('G'.$fila)->setValue($salida2);
	}
	
	$objSheet->getCell('H'.$fila)->setValue(number_format($tiempo_laborado,2,",","."));
	
	$objSheet->getCell('J'.$fila)->setValue(number_format($hed,2,",","."));
	$objSheet->getCell('K'.$fila)->setValue(number_format($hen,2,",","."));
	$objSheet->getCell('L'.$fila)->setValue(number_format($hedd,2,",","."));
	$objSheet->getCell('M'.$fila)->setValue(number_format($hedn,2,",","."));
	$objSheet->getCell('N'.$fila)->setValue(number_format($rn,2,",","."));
	$objSheet->getCell('O'.$fila)->setValue(number_format($rdd,2,",","."));
	$objSheet->getCell('P'.$fila)->setValue(number_format($rdn,2,",","."));
	
	}	
	
	}
	$fila++;
	$objSheet->getCell('H'.$fila)->setValue(number_format($tot_tiempo_laborado,2,",","."));
	$objSheet->getCell('J'.$fila)->setValue(number_format($tot_hed,2,",","."));
	$objSheet->getCell('K'.$fila)->setValue(number_format($tot_hen,2,",","."));
	$objSheet->getCell('L'.$fila)->setValue(number_format($tot_hedd,2,",","."));
	$objSheet->getCell('M'.$fila)->setValue(number_format($tot_hedn,2,",","."));
	$objSheet->getCell('N'.$fila)->setValue(number_format($tot_rn,2,",","."));
	$objSheet->getCell('O'.$fila)->setValue(number_format($tot_rdd,2,",","."));
	$objSheet->getCell('P'.$fila)->setValue(number_format($tot_rdn,2,",","."));
	
	$objSheet->getStyle('A'.$fila.':P'.$fila)->getFont()->setBold(true);
	$objSheet->getStyle('A'.$fila.':P'.$fila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('EFEFEFEF');
	
	$fila++;
	$objSheet->getCell('A'.$fila)->setValue(" ");
	$objSheet->mergeCells('A'.$fila.':P'.$fila);
	$objSheet->getStyle('A'.$fila.':P'.$fila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);	
	

}	
	
	
	




$objSheet->getColumnDimension('A')->setWidth(25);
$objSheet->getColumnDimension('B')->setWidth(40);
$objSheet->getColumnDimension('C')->setWidth(15);
$objSheet->getColumnDimension('D')->setWidth(15);
$objSheet->getColumnDimension('E')->setWidth(15);
$objSheet->getColumnDimension('F')->setWidth(10);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

?>