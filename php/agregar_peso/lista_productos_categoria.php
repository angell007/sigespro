<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_bodega = ( isset( $_REQUEST['id_bodega'] ) ? $_REQUEST['id_bodega'] : '' );
$id_categoria = ( isset( $_REQUEST['id_categoria'] ) ? $_REQUEST['id_categoria'] : '' );
$id_funcionario_cuenta = ( isset( $_REQUEST['funcionario_cuenta'] ) ? $_REQUEST['funcionario_cuenta'] : '' );
$id_funcionario_digita = ( isset( $_REQUEST['funcionario_digita'] ) ? $_REQUEST['funcionario_digita'] : '' );
$condicion = '';


$query = 'SELECT 
            COUNT(*) AS Total
          FROM Inventario I
          INNER JOIN Producto P
          ON I.Id_Producto=P.Id_Producto
          '.$condicion.'
          Order by P.Codigo_Cum ASC' ;

$oCon= new consulta();

$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);


####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $productos["Total"]; 
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

	$query = 'SELECT IFNULL(CONCAT(PRD.Nombre_Comercial, " (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, ") ", PRD.Cantidad," ", 			 
	PRD.Unidad_Medida, ". ","LAB - ",PRD.Laboratorio_Generico 
	), CONCAT(PRD.Nombre_Comercial, " LAB: ", PRD.Laboratorio_Comercial)) as Nombre, PRD.Peso_Presentacion_Minima, PRD.Peso_Presentacion_Regular,PRD.Peso_Presentacion_Maxima, PRD.Embalaje, PRD.Imagen as Foto, PRD.Id_Producto, PRD.Cantidad_Presentacion, PRD.Tolerancia AS Torerancia, PRD.Id_Categoria
    FROM Inventario I
    INNER JOIN Producto PRD
    ON I.Id_Producto=PRD.Id_Producto
    WHERE I.Id_Bodega='.$id_bodega.' AND PRD.Id_Categoria='.$id_categoria.' AND PRD.Actualizado="No"
    ORDER BY Nombre ASC LIMIT '.$limit.','.$tamPag;
   	 
   	 #echo $query;
   	 #exit;
   	
  
 //echo $query;     
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Productos'] = $oCon->getData();
unset($oCon);

$query1 = 'SELECT F.* FROM Funcionario F WHERE F.Identificacion_Funcionario= '.$id_funcionario_cuenta;    
$oCon= new consulta();
$oCon->setQuery($query1);
$funcionario_cuenta= $oCon->getData();
unset($oCon);
$query1 = 'SELECT F.* FROM Funcionario F WHERE F.Identificacion_Funcionario= '.$id_funcionario_digita;    
$oCon= new consulta();
$oCon->setQuery($query1);
$funcionario_digita= $oCon->getData();
unset($oCon);



//$resultado['Productos']=$productos;
$resultado['Funcionario_Cuenta']=$funcionario_cuenta;
$resultado['Funcionario_Digita']=$funcionario_digita;
$resultado['numReg'] = $numReg;
echo json_encode($resultado);


?>