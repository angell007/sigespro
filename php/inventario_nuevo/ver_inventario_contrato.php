<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');


$Id_Inventario_Nuevo = isset($_REQUEST['Id_Inventario_Nuevo']) ? $_REQUEST['Id_Inventario_Nuevo'] : '';

$query = 'SELECT C.Nombre_Contrato, C.Tipo_Contrato,
            IC.Cantidad as Cantidad,
            IC.Cantidad_Apartada,
            IC.Cantidad_Seleccionada,
            SUM(IC.Cantidad - (IC.Cantidad_Apartada + IC.Cantidad_Seleccionada)) AS cantidadContrato 
            FROM Inventario_Contrato IC
            INNER JOIN Contrato C ON IC.Id_Contrato = C.Id_Contrato
            WHERE IC.Id_Inventario_Nuevo IN  ('.$Id_Inventario_Nuevo.')
            GROUP BY IC.Id_Contrato';
            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
$contra = $oCon->getData();

unset($oCon);

echo json_encode($contra);


