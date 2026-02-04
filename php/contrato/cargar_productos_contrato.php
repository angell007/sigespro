<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../config/start.inc.php');
include_once('../../class/PHPExcel/IOFactory.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_contrato = isset($_REQUEST['id_contrato']) ? $_REQUEST['id_contrato'] : false;
$Tipo_Contrato = isset($_REQUEST['Tipo_Contrato']) ? $_REQUEST['Tipo_Contrato'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;

if(isset($_FILES['excelFile'])){ 
    try {   
        $excelObject = PHPExcel_IOFactory::load($_FILES['excelFile']['tmp_name']);
        $getShet = $excelObject->getActiveSheet()->toArray(null);
        $productos = filtrarFilasVacias($getShet);

        validarProductos($productos, $Tipo_Contrato);
        validarCums($productos);
        foreach ($productos as $key => $value) {

            $producto = getProductoByCum($value[0],$id_contrato);

            //evaluo que tipo de contrato es
            $valor2 = $Tipo_Contrato == 'General' ?  (isset($value[2]) ? $value[2] : 0) : 0 ;

            if ($producto) {
                actualizarPrecioProducto($producto['Id_Producto_Contrato'],$value[1],$valor2);
            }else{
                $id_producto_lista= insertProducto($value[0],$value[1],$valor2,$id_contrato);           
            }
        }    
        $respues['message'] = 'Actualizacion exitosa';
         echo json_encode($respues);
        
    } catch (Exception $th) {
         header("HTTP/1.0 400 ".$th->getMessage());
          $respues['message'] =$th->getMessage(); 
         echo json_encode($respues);
    }
}else{
      header("HTTP/1.0 400 No se envio el archivo");

}

function validarProductos($productos, $tipoContrato){
    if(empty($productos)){
        throw new Exception('El archivo no contiene filas con datos');
    }
    foreach ($productos as $key => $value) {
        $cum = isset($value[0]) ? trim($value[0]) : '';
        $precio = isset($value[1]) ? $value[1] : '';
        $cantidad = isset($value[2]) ? $value[2] : '';
        if($cum === '' || $precio === '' || $precio < 0){
            throw new Exception('Error de los datos en la fila '.($key+1));
        }
        if($tipoContrato === 'General' && ($cantidad === '' || $cantidad < 0)){
            throw new Exception('Error de los datos en la fila '.($key+1));
        }
    }
}
function getProductoByCum($cum,$id_contrato){
    $query = 'SELECT Id_Producto_Contrato , Precio FROM Producto_Contrato
            WHERE Cum ="'.$cum.'" AND Id_Contrato='.$id_contrato;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $prod= $oCon->getData();
    return $prod;
}
function actualizarPrecioProducto($id,$nuevo_precio,$cantidad){
    $nuevo_precio = number_format($nuevo_precio,2,'.','');

    $query = 'UPDATE Producto_Contrato SET Cantidad ='.$cantidad.', Precio = '.$nuevo_precio.'
    WHERE Id_Producto_Contrato = '.$id;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->createData();
    unset($oCon);
}
function insertProducto($cum,$precio,$cantidad,$id_contrato){
    $precio = number_format($precio,2,'.','');

    $validar = validarCumReal($cum);
    if($validar){
        $query = 'INSERT INTO Producto_Contrato
                    (Id_Contrato,Id_Producto,Cum,Precio,Cantidad)
                    VALUES('.$id_contrato.','.$validar['Id_Producto'].',"'.$cum.'",'.$precio.','.$cantidad.')';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        $id = $oCon->getID();
        unset($oCon);

        return $id;
    }

}
function validarCumReal($cum){
    $query = 'SELECT Id_Producto
                FROM Producto
                WHERE Codigo_Cum ="'.$cum.'"';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $producto= $oCon->getData();

    if(!$producto){
        throw new Exception('El cum ingresado no coincide en la base de datos: '.$cum);
    }
    return $producto;
}

function validarCums($productos){
    $cums = implode(",", 
        array_map(function($p){return ("'$p[0]'");},  $productos)
    ); 
    $cums2 = implode(",", 
        array_map(function($p){return ($p[0]);},  $productos)
    ); 
    $query = "SELECT Codigo_Cum
                FROM Producto
                WHERE Codigo_Cum in($cums)";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $reales = $oCon->getData();
    $reales= array_column($reales, 'Codigo_Cum');
    $cums = explode(",", $cums2);
    $cum_no_existe =[];
    foreach ($cums as $key => $value) {
        $buscar= array_search($value, $reales);
        if(($buscar===false )){
            array_push($cum_no_existe, "'$value'");
        }
    }

    $cum_no_existe = implode(",",$cum_no_existe);
    if($cum_no_existe){
        throw new Exception('El cum ingresado no coincide en la base de datos: '.$cum_no_existe);
    }
    return $cum_no_existe; 
}

function filtrarFilasVacias($productos){
    return array_values(array_filter($productos, function($fila){
        $cum = isset($fila[0]) ? trim($fila[0]) : '';
        $precio = isset($fila[1]) ? $fila[1] : '';
        $cantidad = isset($fila[2]) ? $fila[2] : '';
        return $cum !== '' || $precio !== '' || $cantidad !== '';
    }));
}

