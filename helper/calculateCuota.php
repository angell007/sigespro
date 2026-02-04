<?php

function CalculoMaxCuota($paciente)
{
    $salario_base = GetSalarioBase();
    $maximo_cobro = 0;
    $porcentaje = 0;
    $aplica_cuota_recuperacion = 'No';
    $paciente['regimen'] = getRegimen($paciente['Id_Regimen']);
    $paciente['nivel'] = getNivel($paciente['Id_Nivel']);

    if ($paciente['regimen']['Nombre'] == 'Subsidiado') {
        if ($paciente['nivel']['Numero'] == '2') {

            $maximo_cobro = $salario_base * 2;
            $aplica_cuota_recuperacion = 'Si';
            $porcentaje = '0.1';
        } elseif ($paciente['nivel']['Numero'] == '3') {
            $maximo_cobro = $salario_base * 3;
            $aplica_cuota_recuperacion = 'Si';
            $porcentaje = '0.3';
        }
    }

    $paciente['Porcentaje'] = $porcentaje;
    $paciente['Aplica_Cuota_Recuperacion'] = $aplica_cuota_recuperacion;
    $paciente['Maximo_Cobro'] = $maximo_cobro;
    $paciente['Total_Cuota'] = GetCoutas($paciente['Id_Paciente']);
    $paciente['Tope'] = $paciente['Maximo_Cobro'] - $paciente['Total_Cuota'];

    return $paciente;
}

function GetCoutas($id)
{

    $query = "SELECT IFNULL(SUM(Cuota),0) as Total_Cuota FROM Dispensacion WHERE Numero_Documento='$id' AND Estado_Dispensacion!='Anulada' AND YEAR(Fecha_Actual) = YEAR(CURRENT_DATE())  ";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cuota =$oCon->getData();
    return $cuota['Total_Cuota'];

}

function getRegimen($regimen){
    $oItemRegimen = new complex('Regimen','Id_Regimen', $regimen );
    $regimen = $oItemRegimen->getData();
    unset($oItemRegimen);
    return $regimen;
}

function getNivel($nivel){
    $oItem = new complex('Nivel','Id_Nivel', $nivel );
    $nivel = $oItem->getData();
    unset($oItem);
    return $nivel;
}

function cuotaRecuperacion($prodDisMip, $cobros)
{

    return array_reduce(
        $prodDisMip,
        function ($carry, $item) use ($cobros) {
            $suma = 0;
     
                $precio = !$item['Costo'] ? 0 :  $item['Costo'];
                $porc =  !$cobros['Porcentaje']  ? 0 : floatval(($cobros['Porcentaje']));

                $result = ($precio * $item['Cantidad_Formulada']) * $porc;
                $carry += $item['Cantidad_Formulada'];

                if ($cobros['Tope'] > 0) {
                    if ($carry > $cobros['Tope']) {
                        $suma = $cobros['Tope'];
                    } else {
                        $suma = $carry + $result;
                    }
                }
            
            return  $suma;
        },
        0
    );
}

function GetSalarioBase (){

	$query="SELECT Salario_Base FROM Configuracion WHERE Id_Configuracion=1 ";
	$oCon = new consulta();
    $oCon->setQuery($query);
    $salario =$oCon->getData();

	return $salario['Salario_Base'];
}
 