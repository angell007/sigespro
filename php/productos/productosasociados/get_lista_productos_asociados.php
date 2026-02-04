<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta_paginada.php');


$pag = (isset($_REQUEST['pag']) ? $_REQUEST['pag'] : '1');
$tam = (isset($_REQUEST['tam']) ? $_REQUEST['tam'] : '10');
$condicionesProducto=[];
$condicion = SetCondiciones();
$condicionesProducto = implode(' AND ', $condicionesProducto);
$condicionesProducto = $condicionesProducto? "WHERE $condicionesProducto":'';
$fecha = date('Y-m-d');


$query = "SELECT 
            SQL_CALC_FOUND_ROWS
            PA.*
            
        FROM Producto_Asociado PA 
        ".($condicion);

// echo $query; exit;

$limit = ($pag - 1) * $tam;
$oCon = new consulta();
$oCon->setQuery($query . " LIMIT $limit, $tam");
$oCon->setTipo('Multiple');
$productos_asociados = $oCon->getData();
unset($oCon);
$productos_asociados['query_result']=[];


if (count($productos_asociados['data']) > 0) {
    $productos_asociados['codigo'] = "success";
    $productos_asociados['mensaje'] = "Se han encontrado registros!";

    $i = 0;
    foreach ($productos_asociados['data'] as $p) {
        $asociados = ObtenerInformacionProductosAsociados($p['Producto_Asociado']);
        $p['Productos_Asociados'] = $asociados;
        $productos_asociados['query_result'][$i] = $p;
        $i++;
    }
} else {
    $productos_asociados['codigo'] = "error";
    $productos_asociados['mensaje'] = "Consulta Vacía!";
}
unset($productos_asociados['data']);
$productos_asociados['numReg'] = $productos_asociados['total'];


echo json_encode($productos_asociados);

function SetCondiciones()
{
    global $condicionesProducto ;
    $req = $_REQUEST;

    if (isset($req['productos_excluir'])) {
        $req['productos_excluir'] = (array) json_decode($req['productos_excluir'], true);
    }
    $condiciones = [];
    $condicion = '';

    if (isset($req['id_producto_asociado']) && $req['id_producto_asociado']) {
        $cond = "PA.Id_Producto_Asociado in ( $req[id_producto_asociado])";
        array_push($condiciones, $cond);
    }
    if (isset($req['id_producto']) && $req['id_producto']) {
        $cond = "PA.Asociados2 LIKE '%-$req[id_producto]-%'";
        array_push($condiciones, $cond);
    }

    if (isset($req['cum']) && $req['cum']) {
        $cond = "P.Codigo_Cum LIKE '%$req[cum]%'";
        array_push($condicionesProducto, $cond);

    }
    if (isset($req['nombre']) && $req['nombre']) {
        $cond = "P.Nombre_Comercial LIKE '%" . $req['nombre'] . "%'";
        array_push($condicionesProducto, $cond);
    }

    if (isset($req['invima']) && $req['invima']) {
        $cond = "P.Invima LIKE '%" . $req['invima'] . "%'";
        array_push($condicionesProducto, $cond);
    }
    
    if(count($condicionesProducto)){
        // array_push($condicionesProducto, $cond);
        $condicionesProducto = implode(" AND ", $condicionesProducto);
        
        $query = "SELECT GROUP_CONCAT(P.Id_Producto) as ID
                from Producto P 
                WHERE  $condicionesProducto ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $ids = $oCon->getData()['ID'];
        
        $ids = array_map(function($p){return json_encode("%-$p-%");}, explode(",", $ids));

        $ids = implode(" OR PA.Asociados2 like ", $ids);

        
        $cond = " ( PA.Asociados2  like $ids )";
        array_push($condiciones, $cond);
        // echo $cond; exit;
    }




    $condicion = count($condiciones) > 0 ? "WHERE " . implode(' AND ', $condiciones) : '';



    return $condicion;
}

function ObtenerInformacionProductosAsociados($productosAsociados)
{
    $query = "SELECT
                    Nombre_Comercial,
                    Codigo_Cum
                FROM Producto
                WHERE
                    Id_Producto in ( $productosAsociados)";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $info_asociados = $oCon->getData()['data'];
    unset($oCon);

    return $info_asociados;
}
