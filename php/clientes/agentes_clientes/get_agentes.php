<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    include_once '../../../class/class.consulta.php';
    
    $id_cliente  = isset($_REQUEST['Id_Cliente']) ? $_REQUEST['Id_Cliente'] : false;
    $nombre_agente  = isset($_REQUEST['Nombre_Agente']) ? $_REQUEST['Nombre_Agente'] : false;
    
    $cond = '';

    if($id_cliente){
        $cond .= ' WHERE Id_Cliente = '.$id_cliente ;
    }
    
    if ( $nombre_agente ) {
        $cond .= $cond == '' ? ' WHERE ' : ' AND ' ;
        $cond .= ' (  CONCAT( Nombres , " " , Apellidos ) LIKE "%'.$nombre_agente.'%"  OR Identificacion LIKE "%'.$nombre_agente.'%"  )' ;
    }

    //echo $cond;exit;
    $query = 'SELECT * ,  CONCAT( Identificacion , " - ", Nombres , " " , Apellidos ) Nombre_Find FROM Agentes_Cliente '.$cond;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $agentes = $oCon->getData();

    echo json_encode($agentes);
