<?php

function getCampo() {
    $campo['codigo'] = $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
    $campo['nombre'] = $_REQUEST['Tipo'] == 'Pcga' ? 'Nombre' : 'Nombre_Niif';
    $campo['debe'] = $_REQUEST['Tipo'] == 'Pcga' ? 'Debe' : 'Debe_NIIF';
    $campo['haber'] = $_REQUEST['Tipo'] == 'Pcga' ? 'Haber' : 'Haber_NIIF';
  
    return $campo;
  }


function calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito)
{
    $nuevo_saldo = 0;
    
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $nuevo_saldo = ($saldo_anterior + $debito) - $credito;
    } else {
        $nuevo_saldo = ($saldo_anterior + $credito) - $debito;
    }

    return $nuevo_saldo;
}


function fecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}