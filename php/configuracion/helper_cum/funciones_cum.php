<?php

function validarProductos($productos){
    foreach ($productos as $key => $value) {
        if(!$value[0] || $value[0]==='' || !$value[1] || $value[1]==='' || !$value[2] || $value[2]==='' || !$value[3] || $value[3]===''){
            throw new Exception('Error de los datos en la fila '.$key+=1);
        }
        if (!$value[4]) {
            break;
        } 
    }
}
function getProductonoposByCum($Id_Lista_Producto_Nopos, $cum){
    $query = 'SELECT Id_Producto_NoPos , Cum FROM producto_nopos
              WHERE Cum ="'.$cum.'" AND Id_Lista_Producto_Nopos='.$Id_Lista_Producto_Nopos;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $prod= $oCon->getData();
    return $prod;
}
function actualizarProductoNoPos($Id_Producto_NoPos,$value){
    $nuevo_precio_cum = number_format($value[1],2,'.','');
    $nuevo_precio_homologo_cum = number_format($value[3],2,'.','');
    $cum_homologo = $value[2];
    $query = 'UPDATE producto_nopos SET 
                     Precio = '.$nuevo_precio_cum.',
                     Cum_Homologo = '.$cum_homologo.',
                     Precio_Homologo = '.$nuevo_precio_homologo_cum.'
                     WHERE Id_Producto_NoPos = '.$Id_Producto_NoPos;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->createData();
    unset($oCon); 
}
function guardarProductoNoPos($Id_Lista_Producto_Nopos,$value){      
    $cum = $value[0];
    $precio_cum = $value[1];
    $cum_homologo = $value[2];
    $precio_homologo = $value[3];
    $query = 'INSERT INTO producto_nopos 
                          (Id_Lista_Producto_Nopos, 
                          Cum, Precio, 
                          Cum_Homologo, 
                          Precio_Homologo)
    VALUES('.$Id_Lista_Producto_Nopos.',"'.$cum.'",'.$precio_cum.',"'.$cum_homologo.'",'.$precio_homologo.')';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}   
function validarExistencia($value){
    $cum = $value[0];
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

