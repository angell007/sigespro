<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    include_once('../../class/class.utility.php');

    $id_funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
    $match = ( isset( $_REQUEST['match'] ) ? $_REQUEST['match'] : '' );

    var_dump($_REQUEST);
    exit;

    $query = '
        SELECT 
            C.Nombre,
            MC.Id_Cliente
        FROM Meta M 
        INNER JOIN Meta_Cliente MC ON M.Id_Meta = MC.Id_Meta
        INNER JOIN Cliente C ON MC.Id_Cliente=C.Id_Cliente
        WHERE
            M.Identificacion_Funcionario = '.$id_funcionario.' AND (MC.Id_Cliente LIKE "%'.$match.'%" OR C.Nombre LIKE "%'.$match.'%")
        GROUP BY MC.Id_Cliente';


    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $results = $oCon->getData();
    unset($oCon);

    echo json_encode($results);

?>