<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT  P.Nombre_Comercial,
          CONCAT( P.Principio_Activo, " ",
                  P.Presentacion, " ",
                  P.Concentracion, " (", P.Nombre_Comercial,") ",
                  P.Cantidad," ",
                  P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ) as Nombre, P.Nombre_Comercial, P.Laboratorio_Comercial, P.Id_Producto, P.Gravado
   
          FROM Producto P
          WHERE P.Mantis!=""' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

$i=-1;
foreach($resultados as $resultado){$i++;
	if ($resultado["Nombre"]==""){
		$resultados[$i]["Nombre"]=$resultado["Nombre_Comercial"]." LAB-".$resultado["Laboratorio_Comercial"];
		
	}else{
		$resultado["Nombre"]=$resultado["Nombre"];
	}


}

echo json_encode($resultados);

?>