<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

/* $query = 'SELECT 
      F.Identificacion_Funcionario, F.Imagen, CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario, C.Nombre as Cargo, D.Nombre as Dependencia , (SELECT COUNT(*) FROM Dispensacion WHERE Facturador_Asignado = F.Identificacion_Funcionario) as asignados,
      (SELECT COUNT(*) FROM Dispensacion WHERE Facturador_Asignado = F.Identificacion_Funcionario AND Estado_Facturacion = "Facturada") as facturadas,
      (SELECT COUNT(*) FROM Dispensacion WHERE Facturador_Asignado = F.Identificacion_Funcionario AND Estado_Facturacion = "Sin Facturar" AND Pendientes = 0) as No_Facturadas
        FROM Funcionario F 
        INNER JOIN Cargo C 
            ON F.Id_Cargo =C.Id_Cargo 
        INNER JOIN Dependencia D 
            ON F.Id_Dependencia=D.Id_Dependencia 
        WHERE F.Id_Cargo = 17'; */


$query="SELECT F.Identificacion_Funcionario,F.Imagen, CONCAT(Nombres,' ',Apellidos) AS Funcionario, IFNULL(D.Asignadas,0) AS asignados, IFNULL(Fact.Facturadas, 0) AS facturadas, C.Nombre as Cargo, Dep.Nombre as Dependencia
FROM Funcionario F 
LEFT JOIN (SELECT D.Facturador_Asignado, COUNT(Facturador_Asignado) AS Asignadas, COUNT(IFNULL(Id_Factura,0)) AS Facturadas FROM Dispensacion D WHERE Estado_Dispensacion != 'Anulada' AND D.Id_Tipo_Servicio != 7 GROUP BY D.Facturador_Asignado) D ON D.Facturador_Asignado = F.Identificacion_Funcionario 
LEFT JOIN Cargo C on C.Id_Cargo = F.Id_Cargo
LEFT JOIN Dependencia Dep on Dep.Id_Dependencia=C.Id_Dependencia
LEFT JOIN (SELECT Id_Funcionario,  COUNT(Id_Factura)as Facturadas FROM Factura Where Estado_Factura != 'Anulada' GROUP BY Id_Funcionario)Fact On Fact.Id_Funcionario = F.Identificacion_Funcionario
WHERE F.Liquidado= 'NO' And
 F.Id_Cargo = 17";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$auditores = $oCon->getData();
unset($oCon);


//

$query1 = 'SELECT DISTINCT 
                ( SELECT count(*) FROM `Dispensacion` WHERE `Estado_Facturacion` = "Facturada" AND Tipo != "Capita") as facturadas , 
                ( SELECT count(*) FROM `Dispensacion` WHERE `Estado_Facturacion` = "Sin Facturar" AND Tipo != "Capita" AND Pendientes = 0) as no_facturadas';


$oCon= new consulta();
$oCon->setQuery($query1);
$lista = $oCon->getData();
unset($oCon);

$resultado['auditoria']=$auditores;
$resultado['indicadores']=$lista;

echo json_encode($resultado);
?>