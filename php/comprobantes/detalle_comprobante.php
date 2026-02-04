<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT C.*, 
				IFNULL(CL.Nombre, P.Nombre) AS Nombre, 
				(SELECT PC.Codigo FROM Plan_Cuentas PC WHERE PC.Id_Plan_Cuentas=C.Id_Cuenta_Debita  ) as Codigo_Debito, 
				(SELECT PC.Nombre FROM Plan_Cuentas PC WHERE PC.Id_Plan_Cuentas=C.Id_Cuenta_Debita ) as Cuenta_Debito, 
				(SELECT PC.Codigo FROM Plan_Cuentas PC WHERE PC.Id_Plan_Cuentas=C.Id_Cuenta_Acredita ) as Codigo_Credito, 
				(SELECT PC.Nombre FROM Plan_Cuentas PC WHERE PC.Id_Plan_Cuentas=C.Id_Cuenta_Acredita ) as Cuenta_Credito, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario
FROM Comprobante C 
LEFT JOIN Cliente CL 
ON C.Id_Cliente=CL.Id_Cliente
LEFT JOIN Proveedor P
ON C.Id_Proveedor=P.Id_Proveedor
INNER JOIN Funcionario F
ON F.Identificacion_Funcionario=C.Id_Funcionario
WHERE C.Id_Comprobante ='.$id; 
$oCon= new consulta();
$oCon->setQuery($query);
$tipo= $oCon->getData();
unset($oCon);


echo json_encode($tipo);

?>