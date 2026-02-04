<?php

function pruebaVence($value)
{  
    $fecha = date("Y-m-d");
    $Fecha_Inicio_Contrato=date('Y-m-d', strtotime($value['Fecha_Inicio_Contrato']));
    $datetime1 = new DateTime($fecha);
    $datetime2 = new DateTime($Fecha_Inicio_Contrato); 

    $interval = $datetime1->diff($datetime2);
    echo $interval->format('%R%a días');


}
