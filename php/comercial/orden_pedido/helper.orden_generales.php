<?php 

function GetBodega(){
    $query = 'SELECT Nombre , Id_Bodega_Nuevo FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = 1';
    $oCon = new consulta();
    $oCon->setQuery($query);
 
    return  $oCon->getData();
}


  /*   function getCliente($id){
        $query = 'SELECT ';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        = $oCon->getData();
    } */