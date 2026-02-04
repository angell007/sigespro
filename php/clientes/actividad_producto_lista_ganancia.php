<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    include_once '../../class/class.consulta.php';
    
    $id  = isset($_REQUEST['Id_Producto_Lista_Ganancia']) ? $_REQUEST['Id_Producto_Lista_Ganancia'] : false;

    $query = 'SELECT A.*, F.Imagen FROM Actividad_Producto_Lista_Ganancia A
                INNER JOIN Funcionario F ON F.Identificacion_Funcionario = A.Identificacion_Funcionario
                WHERE Id_Producto_Lista_Ganancia = '.$id .' ORDER BY A.Fecha DESC';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $actividades= $oCon->getData();

    echo json_encode($actividades);
