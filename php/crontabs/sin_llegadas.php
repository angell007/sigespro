<?php
//header('Content-Type: application/json');

require_once("/home/sigespro/public_html/config/start.inc_cron.php");
include_once("/home/sigespro/public_html/class/class.lista.php");
include_once("/home/sigespro/public_html/class/class.complex.php");
include_once("/home/sigespro/public_html/class/class.consulta.php");
include_once('/home/sigespro/public_html/class/class.mensajes.php');
include_once('/home/sigespro/public_html/class/class.php_mailer.php');

$mail= new EnviarCorreo();

$sms_sender = new Mensaje();
$oItem = new complex("Configuracion","Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);

$dias = array(
	0=> "Domingo",
	1=> "Lunes",
	2=> "Martes",
	3=> "Miercoles",
	4=> "Jueves",
	5=> "Viernes",
	6=> "Sabado"
); 

$query="SELECT Festivos FROM Configuracion WHERE Id_Configuracion=1";
$oCon= new consulta();
$oCon->setQuery($query);
$festivos = $oCon->getData();
unset($oCon);
$fecha_hoy= date("Y-m-d");
$fecha_hoy=explode("-",$fecha_hoy);
$fecha_hoy=$fecha_hoy[2]."/".$fecha_hoy[1]."/".$fecha_hoy[0];



$pos = strpos($festivos["Festivos"], $fecha);
$dia_hoy=date("w");
$hoy=date("Y-m-d");
$fecha = strtotime("-1 days", strtotime($hoy));
$fecha = date('Y-m-d', $fecha);
$borradores_fails = [];


if($dia_hoy!=0&&$pos===false){
    
    $query="SELECT F.Identificacion_Funcionario, CONCAT(F.Nombres,' ',F.Apellidos ) as Funcionario, F.Imagen, F.Nombres,Celular  
    FROM Funcionario F WHERE F.Autorizado='Si' AND EXISTS(SELECT Identificacion_Funcionario FROM Diario_Fijo WHERE Identificacion_Funcionario=F.Identificacion_Funcionario ) AND F.Identificacion_Funcionario NOT IN (12345,54321,13747525,14253)";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $funcionarios = $oCon->getData();
    unset($oCon);

    $personas='<table style="width:400px;border:1px dotted #ccc;margin:0 auto;">';

    foreach ($funcionarios as  $func) {
        $query="SELECT * FROM  Diario_Fijo WHERE Fecha=curdate() AND Identificacion_Funcionario=$func[Identificacion_Funcionario]";

        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $diarios = $oCon->getData();
        unset($oCon);

        $query='SELECT * FROM  Novedad WHERE Identificacion_Funcionario='.$func['Identificacion_Funcionario'].' AND Fecha_Inicio<="'.date("Y-m-d").' 09:00:00" AND Fecha_Fin>="'.date("Y-m-d").' 09:00:00" ';

        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $novedades = $oCon->getData();
        unset($oCon);
        if(count($diarios)<=0&&count($novedades)<=0){
            $per= "<tr style=''><td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'><img style='width:40px; height:40px;border-radius:40px;' src='https://sigesproph.com.co/IMAGENES/FUNCIONARIOS/".$func["Imagen"]."' /></td><td style='vertical-align:middle;border-bottom:1px dotted #ccc;padding-bottom:10px;'>".$func["Funcionario"]."</td></tr>\n";
	        $per2= $func["Funcionario"];
	        echo $per2."<br>";
            $personas.=$per;
            
            EnviarMensaje($func);
        }
    }

    $personas.='</table>';

    echo $personas;

    $message='<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<center> <img src="https://sigesproph.com.co/IMAGENES/LOGOS/LogoProh.jpg" style="width:80px;"> <br> <h4> Jornada De la Mañana  </h4></center><br>
'.$personas.'
<br>

</body>
</html>';


$ruta_completa='';

$mail->EnviarMail('','Personas que no marcaron llegada ',$message,'');


    
   

   

}



function EnviarMensaje($func){
    global $sms_sender;

    $mensaje = "Usted no ha reportado ingreso en la jornada de la mañana  en el Sistema de Control de Acceso Sigespro \n".date("Y-m-d H:i:s"); 
    $enviado = $sms_sender->Enviar($func['Celular'], $mensaje);

	
    $oItem = new complex('Mensaje',"Id_Mensaje");
    $oItem->Mensaje = $mensaje;
    $oItem->Identificacion_Funcionario = $func['Identificacion_Funcionario'];		
    $oItem->Fecha = date('Y-m-d H:i:s');
    $oItem->Numero_Telefono = $func['Celular'];
    $oItem->save();
    unset($oItem); 
}






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



?>