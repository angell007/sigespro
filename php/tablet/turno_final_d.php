<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$persona = ( isset( $_REQUEST['Persona'] ) ? $_REQUEST['Persona'] : '' );
$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );
$servicio = ( isset( $_REQUEST['Servicio'] ) ? $_REQUEST['Servicio'] : '' );
$turno = ( isset( $_REQUEST['Turno'] ) ? $_REQUEST['Turno'] : '' );

$servicio = json_decode($servicio, true);
$turno = json_decode($turno, true);


if($turno['Nivel_Prioridad']!=4){
  $hora=getUltimosTurnos($turno['Numero_Turno'], $turno['Nivel_Prioridad']);
}else{
  $hora= date("H:i:s");
}



$oItem = new complex("Reclamante","Id_Reclamante",$persona);
$per = $oItem->getData();
unset($oItem);

$autorizacion=GetAutorizacion($punto);

$total_turnos= ValidarTurnos($punto);

if($autorizacion=='Si' && $servicio['Tipo']!='Pendientes'){
  $tipo='Auditoria'; 
  $tipo_turno='Auditoria';
}else{
  if($servicio['Autorizacion']=='No' ){
    $tipo='Espera';
    $tipo_turno='Dispensacion';
  }else{
    $tipo='Auditoria';
    $tipo_turno='Auditoria';
  }
}

if($servicio['Tipo']=='Pendientes'){
  $tipo_turno='Pendientes';
}

$numero_turnos=GetNumerosTurnos($punto);

if($total_turnos>$numero_turnos){
  $final["Error"]="Si";
  $final["Tipo"] = "error"; 
  $final["Solicitar"] = "No";
  $final["Persona"]='';
  $final["Cedula"] = '';
  $final["Mensaje"]="No se pueden solicitar mas turnos para auditoria, ya se han generado lo 100 turnos disponibles para este servicio";
}else{
  $oItem = new complex("Turnero","Id_Turnero");
  $oItem->Identificacion_Persona=$persona;
  $oItem->Persona = $per["Nombre"];  
  $oItem->Id_Turneros = $punto;
  $oItem->Fecha = date("Y-m-d");
  $oItem->Hora_Turno = $hora;
  $oItem->Estado = $tipo; 
  $oItem->Tipo = $servicio['Tipo'];
  $oItem->Prioridad = $turno['Nivel_Prioridad'];
  $oItem->Id_Prioridad_Turnero = $turno['Id_Prioridad_Turnero'];
  $oItem->Tipo_Turno = $tipo_turno;
  $oItem->save();
  unset($oItem);
  
  $final["Error"]="No";
  $final["Tipo"] = "success"; 
  $final["Solicitar"] = "Si";
  $final["Persona"]=$per["Nombre"];
  $final["Cedula"] = $persona;
  $final["Mensaje"]="Su turno se ha asignado Correctamente en la pantalla.";
}




echo json_encode($final);

function getUltimosTurnos($numero,$prioridad){
  
  global $punto;
  $query="SELECT * FROM Turnero WHERE Id_Turneros=$punto AND Fecha=curdate() AND Hora_Turno!='23:59:59' AND Estado!='Anulado' ORDER BY 	Id_Turnero DESC LIMIT $numero ";


  $oCon=new consulta();
  $oCon->setQuery($query);
  $oCon->setTipo('Multiple');
  $turnos=$oCon->getData();
  unset($oCon);

  $tiempo='';
  if(count($turnos)>0){
    $pos='';
    for ($i=(count($turnos)-1); $i >=0 ; $i--) { 
      if($turnos[$i]['Prioridad']!='4'){
       $pos=$i;
      }
    }
    if(strval($pos)!=''){
      $tiempo=RetornarHora($turnos[$pos]['Hora_Turno'],'Baja');
     
    }else{
     
     $tiempo=RetornarHora($turnos[count($turnos)-1]['Hora_Turno'],'Sube');
 
    }

 }else{
   $tiempo= date("H:i:s");
 }



 return $tiempo;

}
  function RetornarHora($hora,$tipo){
    $tiempo='';
    $hora=explode(':',$hora);
  
    if($tipo=='Sube'){
      if($hora[2]=='00' && $hora[1]!='00'){
        $tiempo.=$hora[0];
        $tiempo.=':';
        $tiempo.=strval(intval($hora[1])-1);
        $tiempo.=':59';
      }elseif($hora[2]=='00' && $hora[1]=='00'){
        $tiempo.=strval(intval($hora[0])-1);
        $tiempo.=':';
        $tiempo.=':59';
        $tiempo.=':59';
      }else{
   
       $tiempo.=$hora[0];
       $tiempo.=':'.$hora[1];
       $tiempo.=':'.strval(intval($hora[2])-1);
       
      }
  
    }elseif($tipo=='Baja'){

      if($hora[2]=='59' && $hora[1]!='59'){
        $tiempo.=$hora[0];
        $tiempo.=':';
        $tiempo.=strval(intval($hora[1])+1);
        $tiempo.=':00';
      }elseif($hora[2]=='59' && $hora[1]=='59'){
  
        $tiempo.=strval(intval($hora[0])+1);
        $tiempo.=':00:00';
      }else{
   
       $tiempo.=$hora[0];
       $tiempo.=':'.$hora[1];
       $tiempo.=':'.strval(intval($hora[2])+1);
       
      }
    }

    return $tiempo;
  }
  function GetAutorizacion($turnero){
    $query="SELECT * FROM Turneros WHERE Id_Turneros=$turnero  ";


    $oCon=new consulta();
    $oCon->setQuery($query);
    $data=$oCon->getData();
    unset($oCon);

    return $data['Autorizacion_Servicios'];
  }

  function ValidarTurnos($turnero){
    $query="SELECT COUNT(Id_Turnero) as Turnos FROM Turnero WHERE Id_Turneros=$turnero AND Fecha=CURDATE() AND Tipo_Turno='Auditoria' ";
    $oCon=new consulta();
    $oCon->setQuery($query);
    $turnos=$oCon->getData();
    unset($oCon);

    if($turnos['Turnos']){
      $total=$turnos['Turnos'];
    }else{
      $total=0;
    }

    return $total;
  }

  function GetNumerosTurnos($turnero){
    $query="SELECT Maximo_Turnos FROM Turneros WHERE Id_Turneros=$turnero  ";
    $oCon=new consulta();
    $oCon->setQuery($query);
    $turnos=$oCon->getData();
    unset($oCon);

    return $turnos['Maximo_Turnos'];
  }
?>