<?php
require_once('/home/software/sevicol.programing.com.co/config/start.inc.php');
require_once('/home/software/sevicol.programing.com.co/config/config.db.php');
require_once('/home/software/sevicol.programing.com.co/config/config.inc.php');
include_once('/home/software/sevicol.programing.com.co/class/class.lista.php');
include_once('/home/software/sevicol.programing.com.co/class/class.complex.php');
require_once '/home/software/sevicol.programing.com.co/php/funcionario/HTTP/Request2.php';
require('/home/software/sevicol.programing.com.co/php/funcionario/elibom/elibom.php');
											    
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

$dia_hoy=date("w");

if($dia_hoy!=0&&$dia_hoy!=6){
	$oLista= new lista('Funcionario');
	$oLista->setRestrict("Tipo_Turno","=","Fijo");
	$oLista->setRestrict("Imagen","!=","");
	$oLista->setRestrict("Identificacion_Funcionario","!=","1127943747");
	$oLista->setRestrict("Fecha_Ingreso","<=",date("Y-m-d"));
	$oLista->setRestrict("Fecha_Retiro",">=",date("Y-m-d"));
	$oLista->setOrder("Nombres","ASC");
	$funcionarios=$oLista->getList();
	
	$personas='<table style="width:400px;border:1px dotted #ccc;margin:0 auto;">';
	foreach($funcionarios as $func){
	    
	    $oLista= new lista('Diario_Fijo');
	    $oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	    $oLista->setRestrict("Fecha","=",date("Y-m-d"));
		$oLista->setRestrict("Hora_Entrada2","!=","00:00:00");
	    $diarios=$oLista->getList();
	    
	    $oLista= new lista('Novedad');
	    $oLista->setRestrict("Identificacion_Funcionario","=",$func["Identificacion_Funcionario"]);
	    $oLista->setRestrict("Tipo","!=","PermisoEspecial");
	    $oLista->setRestrict("Inicio","<=",date("Y-m-d")." 14:00:00");
	    $oLista->setRestrict("Fin",">=",date("Y-m-d")." 18:00:00");
	    $novedades=$oLista->getList();
	
	    if(count($diarios)<=0&&count($novedades)<=0){
	        
	        $per= "<tr style=''><td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'><img style='width:40px;border-radius:40px;' src='".$URL."IMAGENES/FUNCIONARIOS/".$func["Imagen"]."' /></td><td style='vertical-align:middle;border-bottom:1px dotted #ccc;padding-bottom:10px;'>".$func["Nombres"]." ".$func["Apellidos"]."</td></tr>\n";
	        $per2= $func["Nombres"]." ".$func["Apellidos"];
	        echo $per2."<br>";
	        $personas.=$per;
	        
	        /*
	        $elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
		    $tele = '57'.$func["Celular"];
		    $deliveryId = $elibom->sendMessage($tele,"Usted no ha reportado ingreso esta tarde en el Sistema de Control de Acceso Sevicol \n".date("Y-m-d H:i:s"));
		    $info= $elibom->getDelivery($deliveryId);
			
			$oItem = new complex('Funcionario','Identificacion_Funcionario',$func["Jefe"]);
			$jd=$oItem->getData();
			unset($oItem);
			$elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
		    $tele = '57'.$jd["Celular"];
		    $deliveryId = $elibom->sendMessage($tele,$per2." no ha reportado ingreso esta tarde en el Sistema de Control de Acceso Sevicol \n".date("Y-m-d H:i:s"));
		    $info= $elibom->getDelivery($deliveryId);
		    
		    $elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
	        $tele = '573156249459';
	        $deliveryId = $elibom->sendMessage($tele,$per2." no ha reportado ingreso esta tarde en el Sistema de Control de Acceso Sevicol \n".date("Y-m-d H:i:s"));
	        $info= $elibom->getDelivery($deliveryId);
			*/
	    } 
	}
	$personas.='</table>';
	
	echo $personas;
	
	$to = 'augustoacarrillo@hotmail.com';
	$subject = 'Funcionarios que no reportaron Ingreso esta Tarde (Sevicol)';
	
	$headers = "From: ruolino@sevicol.com.co\r\n";
	$headers .= "Reply-To: ruolino@sevicol.com.co\r\n";
	$headers .= "CCO: augustoacarrillo@gmail.com\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
	
	$message = '<html><body >';
	$message .= '<center><img src="'.$URL.'assets/img/logo-sevicol-vertical.png" style="width:200px;" /><h2>Funcionarios que no Reportaron Ingreso</h2></center>';	
	$message .= $personas;
	$message .= '</body></html>';

	mail($to, $subject, $message, $headers);
	
	/*
	    $elibom = new ElibomClient('social@prevencionlegal.net', 'Ac.19122222');
	    $tele = '573173824618';
	    $deliveryId = $elibom->sendMessage($tele,"Mensajes Enviados");
	    $info= $elibom->getDelivery($deliveryId);
		*/
}else{
	echo "Es domingo";
}
    
?>