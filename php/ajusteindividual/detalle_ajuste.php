<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';



$query = "SELECT AI.*,
         (SELECT CONCAT(F.Nombres,' ', F.Apellidos) FROM
                        Funcionario F WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) AS Funcionario, 
         (SELECT C.Nombre FROM Funcionario F INNER JOIN Cargo C ON F.Id_Cargo=C.Id_Cargo 
                        WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) AS Cargo_Funcionario
  FROM `Ajuste_Individual` AI
  
   WHERE AI.Id_Ajuste_Individual=$id
   
   ";
    
$oCon= new consulta();
$oCon->setQuery($query);
$ajuste = $oCon->getData();
unset($oCon); 

$query = "SELECT P.Nombre_Comercial, 
CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida, ' ') as Nombre_Producto,
 PAI.Lote, P.Laboratorio_Comercial, PAI.Fecha_Vencimiento, PAI.Cantidad, PAI.Observaciones, PAI.Costo
 FROM Producto_Ajuste_Individual PAI 
 INNER JOIN Producto P ON PAI.Id_Producto=P.Id_Producto 
 WHERE PAI.Id_Ajuste_Individual=$id

 ";
         
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$res = $oCon->getData();
unset($oCon);
$total=0;
foreach ($res as $value) {
   $total+=$value['Costo'];
}

$resultado['encabezado'] = $ajuste;
$resultado['productos'] = $res;
$resultado['Total'] = $total;

echo json_encode($resultado); 


?>