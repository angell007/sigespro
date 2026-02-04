<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.consulta.php';


$facts = (isset($_REQUEST['facturas']) ? $_REQUEST['facturas'] : '');
$facts = (array) json_decode($facts, true);
if ($facts[count($facts) - 1]["Factura"] == "") {
      unset($facts[count($facts) - 1]);
}
$resultado['tipo']='';
$i=-1;
$encontradas='';
foreach ($facts as $fact) {$i++;
      $existe=ValidarFactura($fact['Factura']);
      $encontradas.=$existe? "$existe,":"";
}
if($encontradas!=''){
      $resultado['mensaje'] = "$encontradas Ya se encuentra en el sistema para otra acta de recepcion";
      $resultado['tipo'] = "error";

}
echo json_encode($resultado);
exit;

function ValidarFactura($factura)
{
    $factura = str_replace(['-', ' '], '', strtoupper($factura));
    $query = "SELECT UPPER(Replace(REPLACE(F.Factura, '-', ''), ' ', ''))AS 'Factura', A.Codigo, A.Estado FROM Factura_Acta_Recepcion F
    INNER JOIN Acta_Recepcion A ON A.Id_Acta_Recepcion= F.Id_Acta_Recepcion
    WHERE A.Estado!='Anulada' AND F.Factura = '$factura'";
    //HAVING Factura LIKE '$factura'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    return $oCon->getData()['Factura'];
}
