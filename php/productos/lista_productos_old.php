<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT 
            CONCAT( P.Principio_Activo, " ",
            P.Presentacion, " ",
            P.Concentracion, " (",
            P.Nombre_Comercial,") ",
            P.Cantidad," ",
            P.Unidad_Medida
            ) as Nombre, P.Codigo_Cum as Cum, 
            P.Laboratorio_Generico as Generico, 
            P.Laboratorio_Comercial as Comercial, 
            P.Invima as Invima, 
            P.Imagen as Foto, 
            P.Nombre_Comercial as Nombre_Comercial, 
            P.Id_Producto,
            P.Tipo as Tipo
          FROM Producto P
          Order by P.Codigo_Cum ASC' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
$i=-1;
foreach($resultado as $resultados){$i++;
    //echo $inventarios["Nombre_Comercial"];
    if($resultados["Tipo"]=="Material"){
        
        $resultado[$i]["Nombre"]=$resultados["Nombre_Comercial"];
    }
}
echo json_encode($resultado);

?>