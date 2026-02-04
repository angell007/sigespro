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
    $condicion1 = " AND DATE(D.Fecha_Asignado_Facturador) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $condicion2 = " AND DATE(Fecha_Facturado) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

/* $query = 'SELECT 
      F.Identificacion_Funcionario, F.Imagen, CONCAT_WS(" ", F.Nombres, F.Apellidos) as Nombre, C.Nombre as Cargo, D.Nombre as Dependencia,
      (SELECT Count(*) FROM Dispensacion WHERE Facturador_Asignado = F.Identificacion_Funcionario '.$condicion1.') as Asignadas,
      (SELECT Count(*) FROM Dispensacion WHERE Facturador_Asignado = F.Identificacion_Funcionario AND Estado_Facturacion = "Facturada" '.$condicion2.') as Facturadas
        FROM Funcionario F 
        LEFT JOIN Cargo C 
            ON F.Id_Cargo =C.Id_Cargo 
        LEFT JOIN Dependencia D 
            ON F.Id_Dependencia=D.Id_Dependencia 
        WHERE F.Id_Cargo = 17';
 */
        
$query="SELECT Identificacion_Funcionario,F.Imagen, CONCAT(Nombres,' ',Apellidos) AS Nombre, IFNULL(D.Asignadas,0) AS Asignadas, IFNULL(D.Facturadas, 0) AS Facturadas, 'AUX. DE FACTURACION' as Cargo, 'Dispensacion' as Dependencia
FROM Funcionario F LEFT JOIN (SELECT D.Facturador_Asignado, COUNT(Facturador_Asignado) AS Asignadas, COUNT(Id_Factura) AS Facturadas FROM Dispensacion D WHERE D.Id_Tipo_Servicio!=7 AND Estado_Dispensacion != 'Anulada' $condicion1  GROUP BY D.Facturador_Asignado) D ON D.Facturador_Asignado = F.Identificacion_Funcionario WHERE F.Id_Cargo = 17";
        

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