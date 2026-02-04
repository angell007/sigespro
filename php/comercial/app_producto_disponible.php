<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.utility.php');

$util=new Utility();

$condicion = '';
$id_funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

if (isset($_REQUEST['Nit']) && $_REQUEST['Nit'] != "") {
    if($condicion==''){
        $condicion .= " WHERE C.Id_Cliente=$_REQUEST[Nit] ";
    }
    
}
if(isset($_REQUEST['nom']) && $_REQUEST['nom'] != ""){
    if($condicion==''){
        $condicion .= " WHERE P.Nombre_Comercial LIKE '%$_REQUEST[nom]%' ";
    }else{
        $condicion .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[nom]%' ";
    }
}

$clientes_funcionario = GetNitsFuncionario($id_funcionario);
$check = VerificarClienteFuncionario($clientes_funcionario, $_REQUEST['Nit']);

$check = true;

if ($check) {
    $query = '
        SELECT 
            P.Id_Producto, 
            PLG.Precio, 
            P.Nombre_Comercial, 
            CONCAT(SUBSTRING(P.Nombre_Comercial, 1, 20),"...") AS Nombre_Comercial_Corto, 
            P.Presentacion, 
            P.Laboratorio_Comercial, 
            P.Embalaje 
        FROM Producto_Lista_Ganancia PLG 
        INNER JOIN Producto P ON PLG.Cum=P.Codigo_Cum 
        INNER JOIN Cliente C ON PLG.Id_Lista_Ganancia=C.Id_Lista_Ganancia 
        INNER JOIN Inventario I ON PLG.Cum=I.Codigo_CUM '.$condicion.' AND I.Cantidad>0 AND I.Id_Bodega!=0 GROUP BY PLG.Cum';


    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos = $oCon->getData();
    unset($oCon);


    $i=-1;
    foreach ($productos as  $value) {$i++;
        $query = ' SELECT (SELECT Nombre FROM Bodega B WHERE B.Id_Bodega=I.Id_Bodega) as Bodega, SUM(I.Cantidad-(I.Cantidad_Apartada-I.Cantidad_Seleccionada)) as Cantidad, '.$value['Precio'].' as Precio  FROM Inventario I WHERE I.Id_Producto='.$value['Id_Producto'].' AND I.Id_Bodega!=0  GROUP BY I.Id_Bodega HAVING Cantidad>0';
     
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);

        $productos[$i]['Bodegas']=$inventario;        
    }

    echo json_encode($productos);
}else{

    echo json_encode('Intenta consultar un cliente que no tiene asignado, consulte uno de sus clientes asignados!');
}


function GetNitsFuncionario($id_funcionario){
    $query = '
        SELECT
            Id_Cliente
        FROM Meta_Cliente MC
        INNER JOIN Meta M ON MC.Id_Meta = M.Id_Meta
        WHERE
            M.Identificacion_Funcionario = '.$id_funcionario
        .' GROUP BY MC.Id_Cliente';

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $clientes_funcionario = $oCon->getData();
    unset($oCon);

    return $clientes_funcionario;
}

function VerificarClienteFuncionario($clientes, $nit_consultar){
    for ($i=0; $i <= count($clientes); $i++) { 
        if ($nit_consultar == $clientes[$i]['Id_Cliente']) {
            return true;
        }
    }

    return false;
}

?>