<?php

function guardarActividadRegulado($id,$funcionario,$precio_actual,$nuevo_precio,$observacion){
    if(!$precio_actual){
        $precio_actual = 0;
    }
    $precio_actual = number_format($precio_actual,2,'.','');
    $nuevo_precio = number_format($nuevo_precio,2,'.','');

    $query = 'INSERT INTO Actividad_Precio_Regulado 
    (Id_Precio_Regulado, Identificacion_Funcionario, Precio_Actual, Precio_Nuevo, Fecha, Detalle)
    VALUES('.$id.','.$funcionario.','.$precio_actual.','.$nuevo_precio.',NOW(), "'.$observacion.'")';

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

    $query = 'UPDATE  Precio_Regulado SET Estado ="'.$estado.'"
                WHERE Id_Precio_Regulado  ='.$id;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
    guardarActividadRegulado($id,$funcionario,0,0,'Cambio de estado: '.$estado);
}

