<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

function redondeo($n,$x=5) {
    return (ceil($n)%$x === 0) ? ceil($n) : round(($n+$x/2)/$x)*$x;
}
$condicion1='';
$condicion2='';

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion1 = " AND Fecha_Preauditoria BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";   
}

$query = 'SELECT 
F.Identificacion_Funcionario, F.Imagen, SUBSTRING(F.Nombres,1, LOCATE(" ", F.Nombres)-1) as Nombre, SUBSTRING(F.Apellidos,1, LOCATE(" ", F.Apellidos)) AS Apellido,  C.Nombre as Cargo, D.Nombre as Dependencia,
(SELECT Count(*) FROM Auditoria WHERE Funcionario_Preauditoria = F.Identificacion_Funcionario' .$condicion1.' ) as Realizadas,
"0" as Rechazadas, IFNULL((SELECT PD.Nombre FROM  Funcionario_Punto_Activo FPA
INNER JOIN Punto_Dispensacion PD 
ON FPA.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
WHERE FPA.Identificacion_Funcionario=F.Identificacion_Funcionario), "Punto No Asignado" ) as Punto
FROM Funcionario F 
LEFT JOIN Cargo C 
    ON F.Id_Cargo =C.Id_Cargo 
LEFT JOIN Dependencia D 
    ON F.Id_Dependencia=D.Id_Dependencia             
WHERE F.Id_Cargo = 48 ';
        

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=-1;
foreach($resultado as $res){ $i++;
    $total=100;
    if($res["Asignadas"]>0){
        $total=(INT)($res["Facturadas"]*100)/$res["Asignadas"];
    }
    $resultado[$i]["Porcentaje"] = redondeo($total);
}

echo json_encode($resultado);
?>