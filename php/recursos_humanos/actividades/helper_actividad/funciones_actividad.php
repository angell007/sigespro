<?php

function insertAlert($funcionario,$fecha_incio,$detalles, $data){
   
    $pru = new complex('Alerta','Id_Alerta');
    $pru->Identificacion_Funcionario   =$funcionario;
    $pru->Tipo                         ="Actividad";
    $pru->Fecha                        = $fecha_incio;
    $pru->Detalles                     = $detalles;
    $pru->Id                           = $data;
    $pru->save();
    unset($pru);
}
function insertFuncionActividad($idActividad, $funcionario){
  
    $pru = new complex('Funcionario_Actividad','Id_Funcionario_Actividad');
    $pru->Id_Actividad_Recursos = $idActividad;
    $pru->Id_Funcionario_Asignado =$funcionario;
    $pru->save();
    $datos = $pru->getId();
    unset($pru);
    
     return $datos;
    
}
function actuFuncionActividad($tot, $funcionario){
    $pru = new complex('Funcionario_Actividad','Id_Funcionario_Actividad');
    $pru->Id_Actividad_Recursos = $tot;
    $pru->Id_Funcionario_Asignado =$funcionario;
    $pru->save();
    unset($pru);
}


