<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$dias = array(
	0=> "dom",
	1=> "lun",
	2=> "mar",
	3=> "mie",
	4=> "jue",
	5=> "vie",
	6=> "sab"
);


$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$datos["Fechas"]="";

$oItem = new complex('Funcionario','Identificacion_Funcionario',$datos["Identificacion_Funcionario"]);
$func=$oItem->getData();
unset($oItem);


if($datos["Tipo"]!="Permiso"&&$datos["Tipo"]!="PermisoEspecial"){
	$datos["Inicio"]=$datos["Inicio"]." 00:00:00";
	$datos["Fin"]=$datos["Fin"]." 23:59:59";
}elseif($datos["Tipo"]=="PermisoEspecial"){
	$startTime = strtotime( $datos["Inicio"]." 00:00:00" );
	$endTime = strtotime($datos["Fin"]." 23:59:59");
	
	$startTime2 = strtotime( $datos["Inicio_Repo"]." 00:00:00" );
	$endTime2 = strtotime($datos["Fin_Repo"]." 23:59:59");
	
	$d0=0;
	$d1=0;
	$d2=0;
	$d3=0;
	$d4=0;
	$d5=0;
	$d6=0;
	
	$obs1='<strong>Fechas de Permiso:</strong><br><span class="row" style="font-size:9px;">';
	$obs2='<strong>Fechas de Compensatorio:</strong><br><span class="row" style="font-size:9px;">';
	
	$dias1=implode(",",$datos["Dias"]);
	for($h=$startTime;$h<=$endTime; $h=strtotime("+1 Day",$h)){
		if(strpos($dias1,date("w",$h))!==false){
			$num="d".date("w",$h);
			$$num++;
			if($datos["Aplicacion_Permiso"]=="Quincenal"){
				if($$num%2!=0){
					$oItem = new complex('Novedad','Id_Novedad');
					$oItem->Funcionario_Reporta=$datos["Funcionario_Reporta"];
					$oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
					$oItem->Tipo="PermisoEspecialDetalle";
					$oItem->Inicio=date("Y-m-d",$h)." ".$datos[$dias[date("w",$h)]."ini"];
					$oItem->Fin=date("Y-m-d",$h)." ".$datos[$dias[date("w",$h)]."fin"];
					$oItem->Observaciones="Permiso del dia";
					$oItem->Compensatorio="Si";
					$oItem->Id_Grupo=$func["Id_Grupo"];
					$oItem->Id_Dependencia=$func["Id_Dependencia"];
					$oItem->Fechas="";
					$oItem->save();
					unset($oItem);
					
					$obs1.='<span class="col-md-3"><b>'.date("d/m/Y",$h)."</b> - ".$datos[$dias[date("w",$h)]."ini"]." a ".$datos[$dias[date("w",$h)]."fin"]."</span>";
				}
			}else{
				$oItem = new complex('Novedad','Id_Novedad');
				$oItem->Funcionario_Reporta=$datos["Funcionario_Reporta"];
				$oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
				$oItem->Tipo="PermisoEspecialDetalle";
				$oItem->Inicio=date("Y-m-d",$h)." ".$datos[$dias[date("w",$h)]."ini"];
				$oItem->Fin=date("Y-m-d",$h)." ".$datos[$dias[date("w",$h)]."fin"];
				$oItem->Observaciones="Permiso del dia";
				$oItem->Compensatorio="Si";
				$oItem->Id_Grupo=$func["Id_Grupo"];
				$oItem->Id_Dependencia=$func["Id_Dependencia"];
				$oItem->Fechas="";
				$oItem->save();
				unset($oItem);
				$obs1.='<span class="col-md-3"><b>'.date("d/m/Y",$h)."</b> - ".$datos[$dias[date("w",$h)]."ini"]." a ".$datos[$dias[date("w",$h)]."fin"]."</span>";
			}
		}
	}
	
	$d0=0;
	$d1=0;
	$d2=0;
	$d3=0;
	$d4=0;
	$d5=0;
	$d6=0;
	
	$dias2=implode(",",$datos["Dias2"]);
	for($h=$startTime2;$h<=$endTime2; $h=strtotime("+1 Day",$h)){
		if(strpos($dias2,date("w",$h))!==false){
			$num="d".date("w",$h);
			$$num++;
			if($datos["Aplicacion_Repo"]=="Quincenal"){
				if($$num%2!=0){
					$oItem = new complex('Compensatorio','Id_Compensatorio');
					$oItem->Fecha=date("Y-m-d",$h);
					$oItem->Hora_Inicio=$datos[$dias[date("w",$h)]."2ini"];
					$oItem->Hora_Fin=$datos[$dias[date("w",$h)]."2fin"];
					$oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
					$oItem->save();
					unset($oItem);
					$obs2.='<span class="col-md-3"><b>'.date("d/m/Y",$h)."</b> - ".$datos[$dias[date("w",$h)]."2ini"]." a ".$datos[$dias[date("w",$h)]."2fin"]."</span>";
				}
			}else{
				$oItem = new complex('Compensatorio','Id_Compensatorio');
				$oItem->Fecha=date("Y-m-d",$h);
				$oItem->Hora_Inicio=$datos[$dias[date("w",$h)]."2ini"];
				$oItem->Hora_Fin=$datos[$dias[date("w",$h)]."2fin"];
				$oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
				$oItem->save();
				unset($oItem);
				$obs2.='<span class="col-md-3"><b>'.date("d/m/Y",$h)."</b> - ".$datos[$dias[date("w",$h)]."2ini"]." a ".$datos[$dias[date("w",$h)]."2fin"]."</span>";
			}
		}
	}
	$obs1.='</span>';
	$obs2.='</span>';
	$datos["Inicio"]=$datos["Inicio"]." 00:00:00";
	$datos["Fin"]=$datos["Fin"]." 23:59:59";
	$datos["Fechas"]=$obs1."<br>".$obs2;
}



$datos["Id_Grupo"]=$func["Id_Grupo"];
$datos["Id_Dependencia"]=$func["Id_Dependencia"];
//var_dump($datos);

$oItem = new complex('Novedad','Id_Novedad',$id);
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);

echo "Novedad Guardada Exitosamente";

?>