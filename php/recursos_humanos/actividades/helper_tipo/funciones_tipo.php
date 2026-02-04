<?php

function guardarProductoNoPos($NombreTip, $color, $Estado){      
 
    $query = 'INSERT INTO Tipo_Actividad_Recursos_Humanos
                          (Nombre, Color, Estado)
              VALUES("'.$NombreTip.'", "'.$color.'", "'.$Estado.'")';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
} 
function cambiarEstado($Id_Tipo, $Estado){
    $id = $Id_Tipo;
    $Estado = $Estado;
    $query = 'UPDATE Tipo_Actividad_Recursos_Humanos SET 
                     Estado = "'.$Estado.'"
                     WHERE Id_Tipo_Actividad_Recursos_Humanos = ' .$id;
    $oCon = new consulta();
    $oCon->setQuery($query); 
    $data = $oCon->createData();
    unset($oCon);  
}