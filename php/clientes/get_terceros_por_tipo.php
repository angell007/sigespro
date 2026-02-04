<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');

$tipo = isset($_REQUEST['Tipo'])? $_REQUEST['Tipo'] : false;

if ($tipo) { 
    $query = query($tipo);
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $clientes = $oCon->getData();

    $res = $clientes;
    echo json_encode($res);

}

function query($tipo){
    if ($tipo == 'Funcionario') {
        $select = "SELECT Identificacion_Funcionario AS Id_Cliente,
                     concat(Identificacion_Funcionario, ' - ', CONCAT_WS(' ',Nombres,Apellidos)) AS Nombre 
                     FROM Funcionario";
    }else if ($tipo == 'Cliente') {
        $select = 'SELECT Id_Cliente , concat(Id_Cliente, " - ",  (CASE
        WHEN Tipo = "Juridico" THEN Razon_Social
        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

        END)) AS Nombre FROM Cliente';
    }else if ($tipo == 'Proveedor') {
        $select = 'SELECT Id_Proveedor AS Id_Cliente , 
                    concat(Id_Proveedor, " - ", (CASE
              WHEN Tipo = "Juridico" THEN Razon_Social
              ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

              END)) AS Nombre FROM Proveedor';
    }
    return $select;
}