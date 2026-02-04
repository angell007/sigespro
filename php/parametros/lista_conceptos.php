<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT * FROM Parametro_Nomina";


$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$parametros = $oCon->getData();
unset($oCon);

foreach ($parametros as $i => $cliente) {
    
    $query = "SELECT * FROM Concepto_Parametro_Nomina WHERE Id_Parametro_Nomina=".$cliente['Id_Parametro_Nomina'];

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $conceptos = $oCon->getData();
    unset($oCon);

    foreach ($conceptos as $j =>  $value) {
        if($value['Id_Cuenta_Contable']!=''){
            $query = 'SELECT PC.Id_Plan_Cuentas, CONCAT(PC.Codigo," - ",PC.Nombre) as Codigo FROM Plan_Cuentas PC WHERE PC.Id_Plan_Cuentas='.$value['Id_Cuenta_Contable'];

            $oCon = new consulta();
            $oCon->setQuery($query);      
            $cuenta_contable = $oCon->getData();
            unset($oCon);
            $conceptos[$j]['Cuenta']=$cuenta_contable;
        }
      
        if($value['Id_Contrapartida']!=''){
            $query = 'SELECT PC.Id_Plan_Cuentas, CONCAT(PC.Codigo," - ",PC.Nombre) as Codigo FROM Plan_Cuentas PC WHERE PC.Id_Plan_Cuentas='.$value['Id_Contrapartida'];

            $oCon = new consulta();
            $oCon->setQuery($query);      
            $contrapartida = $oCon->getData();
            unset($oCon);
            $conceptos[$j]['Contrapartida']=$contrapartida;
        }
       

      
       
    }

    $parametros[$i]['Conceptos'] = $conceptos;
}

echo json_encode($parametros);

