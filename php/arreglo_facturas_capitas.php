<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('./../config/start.inc.php');
include_once('./../class/class.lista.php');
include_once('./../class/class.complex.php');
include_once('./../class/class.consulta.php');

$query = "SELECT * FROM Factura_Capita";

$oCon = new Consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);

foreach ($facturas as $datos) {
    
    $query = "SELECT D.* FROM Dispensacion D INNER JOIN Paciente P ON D.Numero_Documento=P.Id_Paciente WHERE D.Tipo='Capita' AND D.Pendientes=0 AND D.Estado_Facturacion = 'Facturada' AND D.Estado_Dispensacion != 'Anulada' AND (D.Fecha_Actual LIKE '$datos[Mes]-%' OR D.Fecha_Actual < '$datos[Mes]-01 00:00:00') AND P.Nit=$datos[Id_Cliente] AND P.Id_Departamento=$datos[Id_Departamento] AND P.Id_Regimen = $datos[Id_Regimen]";

    $con = new consulta();
    $con->setQuery($query);
    $con->setTipo('Multiple');
    $dispensaciones = $con->getData();
    unset($con);

    foreach($dispensaciones as $dispensacion){
        $oItem = new complex('Dispensacion',"Id_Dispensacion",$dispensacion['Id_Dispensacion']);
        // $oItem->Estado_Facturacion='Facturada';
        // $oItem->Id_Factura= $datos['Id_Factura_Capita'];
        $oItem->Fecha_Facturado = $datos['Fecha_Documento'];
        // $oItem->Facturador_Asignado = $datos['Identificacion_Funcionario'];
        $oItem->save();
        unset($oItem);
    }
    
}

echo "LISTO, FACTURAS CAPITAS ARREGLADA LA FECHA DE FACTURACIÓN";
  

?>