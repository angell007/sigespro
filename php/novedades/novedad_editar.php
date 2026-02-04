<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$valor = isset($_REQUEST['valor']) ? $_REQUEST['valor'] : false;
$id1 = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$Identificacion_Funcionario = isset($_REQUEST['Identificacion_Funcionario']) ? $_REQUEST['Identificacion_Funcionario'] : false;


$query = 'SELECT N.*, 
            DATE_FORMAT(N.Fecha_Inicio, "%Y-%m-%d") AS Fecha_Inicio_N,
            DATE_FORMAT(N.Fecha_Fin, "%Y-%m-%d") AS Fecha_Fin_N,
            DATE_FORMAT(N.Fecha_Inicio, "%Y-%m-%dT%H:%i:%s") AS Fecha_Inicio_No,
            DATE_FORMAT(N.Fecha_Fin, "%Y-%m-%dT%H:%i:%s") AS Fecha_Fin_No,
            CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario,F.Imagen,
            T.Tipo_Novedad,
            DATE_FORMAT(N.Fecha_Reporte, "%d/%m/%Y %H:%i:%s") as Fecha_Reporte,
            T.Novedad
FROM Novedad N 
INNER JOIN Funcionario F ON N.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Tipo_Novedad T ON N.Id_Tipo_Novedad=T.Id_Tipo_Novedad
WHERE N.Id_Novedad='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

// echo json_encode($resultado);

if($id1 != ''){
    $oItem = new complex("Novedad","Id_Novedad",$id1 );
            $oItem->Estado_Vacaciones = 'Pago';
            $oItem->Funcionario_Aprueba = $Identificacion_Funcionario;
            $oItem->Fecha_Aprobacion = date("Y-m-d");
            $oItem->save();
    // unset($oCon);
    
}

$oItem = new complex("Pago_Vacaciones","Id_Pago_Vacaciones");
            $oItem->Id_Novedad = $id;
            $oItem->Estado = 'Pago';
            $oItem->Valor = $valor;
            $oItem->save();

$resultado["Mensaje"]="Periodo de Vacaciones pagado exitosamente";      
$resultado["Titulo"]="Operacion Exitosa";      
$resultado["Tipo"]="success";      

echo json_encode($resultado);

/*$condicion = '';
if ($id) {
    $condicion .= 'WHERE n.Id_Novedad='.$id;
}

$query = 'SELECT n.*, IF(tn.Modalidad="Hora", DATE_FORMAT(n.Fecha_Inicio, "%Y-%m-%dT%H:%i"), DATE_FORMAT(n.Fecha_Inicio, "%Y-%m-%d")) AS F_Inicio, IF(tn.Modalidad="Hora", DATE_FORMAT(n.Fecha_Fin, "%Y-%m-%dT%H:%i"), DATE_FORMAT(n.Fecha_Fin, "%Y-%m-%d")) AS F_Fin, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=n.Funcionario_Reporta) AS Funcionario_Novedad, 
(SELECT Imagen FROM Funcionario WHERE Identificacion_Funcionario=n.Identificacion_Funcionario) AS Imagen_Funcionario, Texto 
FROM Novedad n 
LEFT JOIN Tipo_Novedad tn ON n.Id_Tipo_Novedad=tn.Id_Tipo_Novedad '.$condicion.' ORDER BY n.Fecha_Creacion DESC' ;

$oCon= new consulta();
$oCon->setQuery($query);
if (!$id) {
    $oCon->setTipo('Multiple');
}
$resultado = $oCon->getData();
unset($oCon);*/
?>