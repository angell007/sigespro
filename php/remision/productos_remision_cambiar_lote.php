<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$idremision = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT PR.*,  P.Id_Categoria, IFNULL(CONCAT( P.Principio_Activo, " ",
P.Presentacion, " ",
P.Concentracion, " (", P.Nombre_Comercial,") ",
P.Cantidad," ",
P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial, "\nCodigoCum:", P.Codigo_Cum ), CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial, "\nCodigoCum:", P.Codigo_Cum)) AS Nombre_Producto, 
E.Id_Estiba,
G.Id_Grupo_Estiba,    
  E.Nombre AS Nombre_Estiba,
"false" as deshabilitado, "true" as habilitado, true as Lectura
FROM Producto_Remision PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
INNER JOIN Inventario_Nuevo I ON I.Id_Inventario_Nuevo = PR.Id_Inventario_Nuevo
INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
WHERE PR.Id_Remision='.$idremision.' ORDER BY Nombre_Producto';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon); 
$i=-1;
foreach ($productos as  $value) {$i++;
    $productos[$i]['Lotes']=[];
}


echo json_encode($productos);
?>