<?php

function guardarActividadProducto($id,$funcionario,$precio_acual,$nuevo_precio,$observacion){
    
    $precio_acual = number_format($precio_acual,2,'.','');
    $nuevo_precio = number_format($nuevo_precio,2,'.','');


    $query = 'INSERT INTO Actividad_Producto_Lista_Ganancia 
    (Id_Producto_Lista_Ganancia, Identificacion_Funcionario, Precio_Actual, Precio_Nuevo, Fecha, Detalle)
    VALUES('.$id.','.$funcionario.','.$precio_acual.','.$nuevo_precio.',NOW(), "'.$observacion.'")';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}

function actualizarPrecioProducto($id,$nuevo_precio){
    $nuevo_precio = number_format($nuevo_precio,2,'.','');

    $query = 'UPDATE Producto_Lista_Ganancia SET 
    Precio_Anterior = Precio,
    Precio = '.$nuevo_precio.',
    Ultima_Actualizacion = NOW()
    WHERE Id_Producto_Lista_Ganancia = '.$id;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->createData();
    unset($oCon);
}

function cambiarEstado($id,$estado,$funcionario){
    $query = 'UPDATE  Producto_Lista_Ganancia SET Estado ="'.$estado.'"
                WHERE Id_Producto_Lista_Ganancia  ='.$id;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
    guardarActividadProducto($id,$funcionario,0,0,'Cambio de estado: '.$estado);
}


function ListaProductosDescargar($id_lista){

    $query = 'SELECT * FROM Producto_Lista_Ganancia P
            WHERE P.Id_Lista_Ganancia ='.$id_lista;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos= $oCon->getData();
    return $productos;

}

function getProductoByCum($cum,$id_lista){
    $query = 'SELECT Id_Producto_Lista_Ganancia , Precio FROM Producto_Lista_Ganancia
            WHERE Cum ="'.$cum.'" AND Id_Lista_Ganancia='.$id_lista;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $prod= $oCon->getData();
    return $prod;
}

function insertProducto($cum,$precio,$id_lista){
    if(validarCumReal($cum)){
        $query = 'INSERT INTO Producto_Lista_Ganancia 
                    (Id_Lista_Ganancia,Cum,Precio,Ultima_Actualizacion,Estado)
                    VALUES('.$id_lista.',"'.$cum.'",'.$precio.',NOW(),"Activo")';
               echo $query;
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


