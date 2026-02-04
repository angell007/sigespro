<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$query = 'SELECT D.Fecha_Actual, D.Codigo, D.EPS, TS.Nombre, D.Estado_Dispensacion, PD.Nombre, D.Id_Dispensacion, (SELECT F.Codigo FROM Factura F WHERE F.Id_Dispensacion=D.Id_Dispensacion LIMIT 1) as Facturas, (SELECT FC.Codigo FROM Factura_Capita FC WHERE FC.Id_Factura_Capita=D.Id_Factura LIMIT 1) as Facturas_Capitas,  DIS.Formulada, DIS.Entregada, DIS.Pendientes, ROUND(DIS.Costo,0)

FROM Dispensacion D
INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento
INNER JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio
INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
LEFT JOIN (SELECT PDI.Id_Dispensacion, SUM(PDI.Cantidad_Formulada) as Formulada, SUM(PDI.Cantidad_Entregada) as Entregada, SUM(PDI.Cantidad_Formulada-PDI.Cantidad_Entregada) as Pendientes, SUM(PDI.Cantidad_Entregada*IFNULL(I.Costo,0)) as Costo FROM Producto_Dispensacion PDI LEFT JOIN Inventario I ON I.Id_Inventario = PDI.Id_Inventario GROUP BY Id_Dispensacion) DIS ON DIS.Id_Dispensacion = D.Id_Dispensacion
WHERE D.EPS LIKE "%SALUD VIDA%" AND D.Estado_Dispensacion !="Anulada" AND DIS.Pendientes != 0 AND DATE(D.Fecha_Actual) BETWEEN "2019-01-01" AND "2019-12-31" AND D.Id_Tipo_Servicio = 7
HAVING Facturas IS NULL AND Facturas_Capitas IS NULL';

$query='SELECT D.Fecha_Actual, D.Codigo, D.EPS, R.Nombre, TS.Nombre, D.Estado_Dispensacion, PD.Nombre, D.Id_Dispensacion, (SELECT F.Codigo FROM Factura F WHERE F.Id_Dispensacion=D.Id_Dispensacion LIMIT 1) as Facturas, (SELECT FC.Codigo FROM Factura_Capita FC WHERE FC.Id_Factura_Capita=D.Id_Factura LIMIT 1) as Facturas_Capitas, DIS.Productos, DIS.Formulada, DIS.Entregada, DIS.Pendientes, ROUND(DIS.Costo,0)

FROM Dispensacion D
INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento
INNER JOIN Regimen R ON R.Id_Regimen = P.Id_Regimen
INNER JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio
INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
LEFT JOIN (SELECT PDI.Id_Dispensacion, GROUP_CONCAT(PP.Nombre_Comercial) AS Productos, SUM(PDI.Cantidad_Formulada) as Formulada, SUM(PDI.Cantidad_Entregada) as Entregada, SUM(PDI.Cantidad_Formulada-PDI.Cantidad_Entregada) as Pendientes, SUM(PDI.Cantidad_Entregada*IFNULL(I.Costo,0)) as Costo FROM Producto_Dispensacion PDI LEFT JOIN Inventario I ON I.Id_Inventario = PDI.Id_Inventario INNER JOIN Producto PP ON PP.Id_Producto = PDI.Id_Producto GROUP BY Id_Dispensacion) DIS ON DIS.Id_Dispensacion = D.Id_Dispensacion
WHERE D.Estado_Dispensacion !="Anulada" AND DIS.Pendientes != 0 AND DATE(D.Fecha_Actual) BETWEEN "2018-01-01" AND "2018-12-31" AND D.Id_Tipo_Servicio = 7
HAVING Facturas IS NULL AND Facturas_Capitas IS NULL';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);

$i=0;
foreach($facturas as $factura){ $i++;
    echo $i." - ".$factura["Codigo"]."<br>";
    
    $oItem = new complex("Dispensacion","Id_Dispensacion",$factura["Id_Dispensacion"]);
    $oItem->Estado_Dispensacion = "Anulada";
    $oItem->save();
    unset($oItem);
    echo "<br>Anulada la Dis<br><br>";
    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Id_Dispensacion"] = $factura["Id_Dispensacion"];
    $ActividadDis["Identificacion_Funcionario"] = "13747525";
    $ActividadDis["Detalle"] = "Dispensacion Anulada por Autorización de Freddy Arciniegas, de manera automatica para limpiar el sistema";
    $ActividadDis["Estado"] = "Anulada";
    $oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
    foreach($ActividadDis as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
    echo "<br>Creada la Actividad<br>=========================<br>";
    
}

?>