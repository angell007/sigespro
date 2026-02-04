<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id_Contrato'] ) ? $_REQUEST['Id_Contrato'] : '' );

$condicion = '';

if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
    $condicion .= " AND PC.Cum LIKE '%$_REQUEST[cum]%'";
}

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
      $condicion .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[nom]%'";
  }
  


$query = 'SELECT COUNT(*) AS Total
            FROM Producto_Contrato PC
            INNER JOIN Contrato C ON PC.Id_Contrato = C.Id_Contrato
            INNER JOIN Producto P ON PC.Cum = P.Codigo_Cum
            WHERE PC.Id_Contrato = '.$id.$condicion;
            
$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}



$query = 'SELECT PC.Ultima_Actualizacion,
                  PC.Precio_Anterior, C.Tipo_Contrato, 
                  C.Nombre_Contrato, 
                  P.Id_Producto, 
                  PC.Cum, 
                  PC.Precio, 
                  PC.Id_Producto_Contrato, 
                  PC.Cantidad, 
                  P.Nombre_Comercial,
            CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial) as Nombre_Producto,
            (
                  SELECT 
                         SUM(IC.Cantidad - (IFNULL(IC.Cantidad_Apartada,0)+IFNULL(IC.Cantidad_Seleccionada,0)) )
                        FROM Inventario_Contrato IC
                        WHERE IC.Id_Contrato =  '.$id.' AND IC.Id_Producto_Contrato = PC.Id_Producto_Contrato
                        GROUP BY PC.Id_Producto_Contrato

            ) as Cantidad_Inventario, 
            (       
                  SELECT PR.Cantidad
                        FROM Producto_Remision PR
                        INNER JOIN Remision R ON PR.Id_Remision = R.Id_Remision
                        WHERE R.Id_Contrato = '.$id.' AND PR.Id_Producto_Remision = PC.Id_Producto_Contrato                    
            ) as Cantidad_Entregada
            
            FROM Producto_Contrato PC
            INNER JOIN Contrato C ON PC.Id_Contrato = C.Id_Contrato
            INNER JOIN Producto P ON PC.Cum = P.Codigo_Cum
            WHERE PC.Id_Contrato = '.$id.$condicion.' LIMIT ' . $limit . ',' . $tamPag;
            
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Productos'] = $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;


echo json_encode($resultado);


//primera consulta 
// $query = 'SELECT PC.Id_Producto, PC.Cum, PC.Precio, PC.Id_Producto_Contrato, PC.Cantidad, P.Nombre_Comercial, 
//             CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial) as Nombre_Producto,
//             (
//                   SELECT 
//                         SUM(IC.Cantidad - (IC.Cantidad_Apartada+IC.Cantidad_Seleccionada) ) 
//                         FROM Inventario_Contrato IC
//                         WHERE IC.id_Contrato =  '.$id.' AND IC.Id_Producto_Contrato = PC.Id_Producto_Contrato
//                         GROUP BY PC.Id_Producto_Contrato

//             ) as Cantidad_Inventario
            
//             FROM Producto_Contrato PC
//             INNER JOIN Producto P ON PC.Cum = P.Codigo_Cum
//             WHERE PC.Id_Contrato = "'.$id.'"';

