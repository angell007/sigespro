<?php

function getProductoByCum($cum, $idproveedor){
    $query = 'SELECT Id_Lista_Precio, Precio 
                FROM Lista_Precio_Proveedor
                WHERE Cum ="'.$cum.'" AND Id_Lista_Precio='.$idproveedor;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $prod= $oCon->getData();
    return $prod;
}
function actualizarPrecioProducto($id,$nuevo_precio){
    $nuevo_precio = number_format($nuevo_precio,2,'.','');

    $query = 'UPDATE Lista_Precio_Proveedor SET 
    Precio_Anterior = Precio,
    Precio = '.$nuevo_precio.',
    Ultima_Actualizacion = NOW()
    WHERE Id_Lista_Precio = '.$id;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->createData();
    unset($oCon);
}
function guardarActividadProducto($id,$funcionario,$precio_acual,$nuevo_precio,$observacion){
    
    $precio_acual = number_format($precio_acual,2,'.','');
    $nuevo_precio = number_format($nuevo_precio,2,'.','');


    $query = 'INSERT INTO Actividad_Lista_Precio_Proveedor 
    (Id_Lista_Precio, Identificacion_Funcionario, Precio_Actual, Precio_Nuevo, Fecha, Detalle)
    VALUES('.$id.','.$funcionario.','.$precio_acual.','.$nuevo_precio.',NOW(), "'.$observacion.'")';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}
function insertProducto($id_lista,$cum,$precio){
    if(validarCumReal($cum)){
        $query = 'INSERT INTO Lista_Precio_Proveedor 
                    (Id_Proveedor,Cum,Precio,Ultima_Actualizacion)
                    VALUES('.$id_lista.',"'.$cum.'",'.$precio.',NOW())';
               
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