<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('./../config/start.inc.php');
include_once('./../class/class.lista.php');
include_once('./../class/class.complex.php');
include_once('./../class/class.consulta.php');

$query = 'SELECT AR.*
FROM Actividad_Remision AR
WHERE AR.Estado="Fase 2" ORDER BY  AR.Id_Remision ASC';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$actividades = $oCon->getData();
unset($oCon);
//var_dump($productos);
//$guia=[];
$bandera='';
foreach ($actividades as $actividad) {
        if($bandera!=$actividad['Id_Remision']){
            $bandera=$actividad['Id_Remision'];
        }else{
            $oItem=new complex('Actividad_Remision', 'Id_Actividad_Remision',$actividad['Id_Actividad_Remision']);
           $oItem->delete();
            echo $actividad['Id_Remision'].'<br>';
            unset($oItem);
            $bandera=$actividad['Id_Remision'];
        }
       /* $oItem=new complex('Actividad_Remision', 'Id_Actividad_Remision');
        $oItem->Id_Remision=$actividad['Id_Remision'];
        $oItem->Identificacion_Funcionario="12345";
        $oItem->Fecha=$actividad['Fecha'];
        $oItem->Detalles="Se marca como Enviada de forma automatica por solicitud del personal de PROH la remision ".$actividad['Codigo']." no se anexa guia ni empresa de envio";
        $oItem->Estado="Enviada";

        $oItem->save();
        unset($oItem);*/

       /* echo $actividad['Id_Remision'].'<br>';
        echo $actividad['Codigo'].'<br><br>';
         /*$oItem=new complex('Remision', 'Id_Remision', $actividad['Id_Remision']);
        $oItem->Guia=trim($guia[0]);
        $oItem->Empresa_Envio=trim($guia[1]);
        $oItem->save();
        unset($oItem);*/
  
}

?>