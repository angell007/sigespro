<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$condicion = setCondiciones($_REQUEST);

$page = isset($_REQUEST['page'])?$_REQUEST['page']: '1';
$tam = isset($_REQUEST['tam'])?$_REQUEST['tam']: '20';
$pendientes = isset($_REQUEST['Pendientes'])?$_REQUEST['Pendientes']: '';

if($pendientes =='Si'){
    $pendientes = 'HAVING Pendiente > 0 ';
}else
if($pendientes =='No'){
    $pendientes = 'HAVING Pendiente = 0 ';
}
else{
    $pendientes = '';
}
// $pendientes = '';



$limit = ($page-1)*$tam;
$limit ="LIMIT $limit, $tam";

$query = "SELECT Concat(O.Prefijo, O.Id_Orden_Pedido) as Id_Orden_Pedido, O.Fecha_Probable_Entrega , O.Fecha, O.Estado,
            CONCAT(F.Nombres, ' ',F.Apellidos) as Nombre_Funcionario,
            -- SUM(IF(POP.Estado ='Activo',( POP.Solicitada - ifnull(PR.Cantidad, 0) ), 0) )as Pendiente,
            SUM(IF(POP.Estado ='Activo',  IF(IFNULL(PR.Solicitada -PR.Remisionada, POP.Cantidad)>0, IFNULL(PR.Solicitada -PR.Remisionada, POP.Cantidad), 0), 0 )) as Pendiente,
            C.Nombre AS Nombre_Cliente
            FROM Orden_Pedido O
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = O.Identificacion_Funcionario
            INNER JOIN Producto_Orden_Pedido POP ON POP.Id_Orden_Pedido = O.Id_Orden_Pedido
            INNER JOIN Cliente C ON C.Id_Cliente = O.Id_Cliente
            
            
            LEFT JOIN (
                          SELECT SUM(PR.Cantidad) AS Remisionada, 
                             POP.Cantidad AS Solicitada,
                             COUNT(POP.Id_Producto) AS PRS,
                            R.Id_Orden_Pedido, PR.Id_Producto, 
                            GROUP_CONCAT(distinct PR.Id_Remision) AS Remisiones
                         FROM 
                         Producto_Orden_Pedido POP 
                         INNER JOIN Remision R ON R.Id_Orden_Pedido = POP.Id_Orden_Pedido
                         INNER JOIN Producto_Remision PR ON R.Id_Remision = PR.Id_Remision AND POP.Id_Producto = PR.Id_Producto
                         WHERE R.Id_Orden_Pedido > 0
                         AND R.Estado != 'Anulada'
                         GROUP BY POP.Id_Producto_Orden_Pedido
                 ) PR ON PR.Id_Orden_Pedido = O.Id_Orden_Pedido AND PR.Id_Producto = POP.Id_Producto
           
                $condicion
                GROUP BY O.Id_Orden_Pedido
                $pendientes
                ORDER BY O.Id_Orden_Pedido DESC ";
                // echo $query; exit;
$oCon= new consulta();
$oCon->setQuery("$query $limit");
$oCon->setTipo('Multiple');
$resultados['data'] = $oCon->getData();
unset($oCon);


$query = "SELECT COUNT(O.Id_Orden_Pedido) as Total       
                FROM (
                    $query
                    )O";
$oCon= new consulta();
$oCon->setQuery($query);
$resultados['total'] = $oCon->getData()['Total'];
unset($oCon);

echo json_encode($resultados);


function setCondiciones($params)
{
    $condiciones=[];

    foreach ($params as $key => $value) {
        if($value!=''){
            switch ($key) {
                case 'Fecha_Creado':
                    $fecha= explode(" - ", $value);
                    array_push($condiciones, "Date(O.Fecha) BETWEEN '$fecha[0]' AND '$fecha[1]'");
                    break;
                case 'Fecha_Probable_Entrega':
                    $fecha= explode(" - ", $value);
                    array_push($condiciones, "Date(O.Fecha_Probable_Entrega) BETWEEN '$fecha[0]' AND '$fecha[1]'");
                    break;
                case 'Funcionario':
                    $value = str_replace(" ", "%", $value);
                    array_push($condiciones, "CONCAT(F.Nombres, ' ',F.Apellidos) LIKE '%$value%'");
                    break;
                case 'Cliente':
                    $value = str_replace(" ", "%", $value);
                    array_push($condiciones, "C.Nombre LIKE '%$value%'");
                    break;
                case 'Codigo':
                    $value = str_replace(" ", "%", $value);
                    array_push($condiciones, "Concat(O.Prefijo, O.Id_Orden_Pedido) LIKE '%$value%'");
                    break;
                case 'Estado':
                    array_push($condiciones, "O.Estado = '$value'");
                    break;
                default:
                    break;
                }
        }
    }
    return count($condiciones)? "WHERE ".implode(" AND ", $condiciones) : "";

}

?>