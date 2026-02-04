<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$condicion = '';
$data = isset($_REQUEST['data']) && $_REQUEST['data'] != '' ? $_REQUEST['data'] : false;

$data = json_decode($data,true);

$cabecera = $data['cabecera'];
$productos = $data['productos'];

$porductosTemp = $productos;

//almacena relacion proveedor productos
$proveedorProductos = [];

//seleccionar proveedores de los productos
$proveedoresTemp = unique_multidim_array($porductosTemp,'Proveedor');


//Buscar los productos de cada PROVEEDOR
foreach ($proveedoresTemp as $key => $proveedor ) {
    # code...
    $productosProv = array_filter($porductosTemp, function($prod) use($proveedor) {
        return $prod['Proveedor'] == $proveedor;
    });
    $productosProv = array_values($productosProv);
    $provTemp['Proveedor'] = $proveedor;
    $provTemp['Productos'] = $productosProv;

    array_push($proveedorProductos,$provTemp);
}


foreach ($proveedorProductos as $key => $proveedor) {

    foreach ($proveedor['Productos'] as $key => $prod) {
        # code...
        $query = ' SELECT SUM( I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) AS Cantidad_Disponible 
                    FROM  Inventario_Nuevo I
                    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba  
                    WHERE ( E.Id_Bodega_Nuevo IS NOT NULL AND E.Id_Bodega_Nuevo) AND I.Id_Producto = '.$prod['Id_Producto'].'
                    GROUP BY I.Id_Producto
                    ';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);
        print_r($inventario);exit;

    }
}


print_r($proveedorProductos);

exit;

$idOrden = guardarCabecera($cabecera);

//$oItem->save();
unset($oItem);


/* 
function proveedorseach($val){
    return v
}
 */



function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();
   
    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val[$key];
        }
        $i++;
    }
    return $temp_array;
}

function guardarCabecera($cabecera){
    $oItem = new complex('Orden_Pedido','Id_Orden_Pedido');
    $oItem->Id_Cliente = $cabecera['cliente']['Id_Cliente'];
    $oItem->Fecha_Probable_Entrega= $cabecera['fecha_probable_entrega'];
    $oItem->Identificacion_Funcionario= $cabecera['Identificacion_Funcionario'];
    $oItem->Observaciones= $cabecera['observaciones'];
    $oItem->save();
    return $oItem->getId();
}


/*
<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$condicion = '';
$data = isset($_REQUEST['data']) && $_REQUEST['data'] != '' ? $_REQUEST['data'] : false;

$data = json_decode($data,true);

$cabecera = $data['cabecera'];
$productos = $data['productos'];

$productosTemp = $productos;

//almacena relacion proveedor productos
$proveedorProductos = [];

//seleccionar proveedores de los productos
$proveedoresTemp = unique_multidim_array($productosTemp,'Proveedor');

$compras = [];
foreach ($productosTemp as $key => $prod) {

        $query = ' SELECT SUM( I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) AS Cantidad_Disponible 
                    FROM  Inventario_Nuevo I
                    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba  
                    WHERE ( E.Id_Bodega_Nuevo IS NOT NULL AND E.Id_Bodega_Nuevo) AND I.Id_Producto = '.$prod['Id_Producto'].'
                    GROUP BY I.Id_Producto
                    ';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);
        print_r($inventario);
        
        if (condition) {
            # code...
        }
        $compras[] = ''
        
        exit;


}


print_r($proveedorProductos);

exit;

//$idOrden = guardarCabecera($cabecera);



function in_array_r($needle, $arraySearch, $strict = false) {
    foreach ($arraySearch as $item) {
        if () {
            return true;
        }
    }

    return false;
}

function guardarCabecera($cabecera){
    $oItem = new complex('Orden_Pedido','Id_Orden_Pedido');
    $oItem->Id_Cliente = $cabecera['cliente']['Id_Cliente'];
    $oItem->Fecha_Probable_Entrega= $cabecera['fecha_probable_entrega'];
    $oItem->Identificacion_Funcionario= $cabecera['Identificacion_Funcionario'];
    $oItem->Observaciones= $cabecera['observaciones'];
    $oItem->save();
    return $oItem->getId();
}

*/