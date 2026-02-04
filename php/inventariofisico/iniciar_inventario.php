<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idCategoria = ( isset( $_REQUEST['idCategoria'] ) ? $_REQUEST['idCategoria'] : '' );
$idBodega = ( isset( $_REQUEST['idBodega'] ) ? $_REQUEST['idBodega'] : '' );
$Letras = ( isset( $_REQUEST['Letras'] ) ? $_REQUEST['Letras'] : '' );
$Contador = ( isset( $_REQUEST['Contador'] ) ? $_REQUEST['Contador'] : '' );
$Digitador = ( isset( $_REQUEST['Digitador'] ) ? $_REQUEST['Digitador'] : '' );

$cond='';
$cond2='';
if($idCategoria!="Todas" && $idCategoria!=0){
    $cond=' AND PRD.Id_Categoria ='.$idCategoria;
    $cond2=' AND I.Categoria ='.$idCategoria;
}

$query = 'SELECT I.Id_Inventario_Fisico FROM Inventario_Fisico I WHERE
          I.Bodega='.$idBodega.$cond2.' AND I.Estado="Abierto" AND I.Letras="'.$Letras.'"';

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$Inv = $oCon->getData();
unset($oCon);

$let = explode("-",$Letras);

$order = 'PRD.Nombre_Comercial';

if($idCategoria==12){
    $order = 'PRD.Principio_Activo';
}
        
$fin = '';
foreach($let as $l){
    $fin.=$order.' LIKE "'.$l.'%" OR ';
}
$fin = trim($fin," OR ");

if($fin!=''){
    $cond.=' AND ('.$fin.') AND I.Cantidad>0 GROUP BY I.Id_Producto'; 
 
}

$inicio = date("Y-m-d H:i:s");

if(!isset($Inv["Id_Inventario_Fisico"])){
    $oItem = new complex("Funcionario","Identificacion_Funcionario",$Contador);
    $func_contador = $oItem->getData();
    unset($oItem);
    
    $oItem = new complex("Funcionario","Identificacion_Funcionario",$Digitador);
    $func_digitador = $oItem->getData();
    unset($oItem);
    
    if(isset($func_contador["Identificacion_Funcionario"])&&isset($func_digitador["Identificacion_Funcionario"])){
        
        $query = 'SELECT COUNT(*) as Total_Productos FROM Inventario I
          INNER JOIN Producto PRD
          ON I.Id_Producto = PRD.Id_Producto
          WHERE I.Id_Bodega='.$idBodega.$cond;

    
          
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $total = $oCon->getData();
        unset($oCon);

        $total["Total_Productos"] = count($total);
        
        $oItem = new complex("Bodega","Id_Bodega",$idBodega);
        $bodega = $oItem->getData();
        unset($oItem);
        if($idCategoria!="Todas" && $idCategoria!=0){
            $oItem = new complex("Categoria","Id_Categoria",$idCategoria);
            $categoria = $oItem->getData();
            unset($oItem);
        }else{
            $categoria["Nombre"]="Todas";
            $categoria["Id_Categoria"]="0";
        }
        
        $oItem = new complex("Inventario_Fisico","Id_Inventario_Fisico");
        $oItem->Fecha_Inicio = $inicio;
        $oItem->Bodega = $idBodega;
        $oItem->Categoria = $idCategoria;
        $oItem->Letras = $Letras;
        $oItem->Conteo_Productos = (INT)$total["Total_Productos"];
        $oItem->Funcionario_Digita = $Digitador;
        $oItem->Funcionario_Cuenta = $Contador;
        $oItem->save();
        $id_inv= $oItem->getId();
        unset($oItem);
        
        $resultado["Id_Inventario_Fisico"]=$id_inv;
        $resultado["Funcionario_Digita"]=$func_digitador;
        $resultado["Funcionario_Cuenta"]=$func_contador;
        $resultado["Bodega"] = $bodega;
        $resultado["Categoria"]=$categoria;
        $resultado["Letras"]=$Letras;
        $resultado["Inicio"] = $inicio;
        $resultado["Productos_Conteo"] = $total["Total_Productos"];
        $resultado["Tipo"] = "success";
        $resultado["Title"] = "Inventario Iniciado Correctamente";
        $resultado["Text"] = "Vamos a dar Inicio al Inventario Físico. Hay ".$total["Total_Productos"]." Productos por Inventariar.<br>¡Muchos Exitos!";
        
    }else{
        $resultado["Tipo"] = "error";
        $resultado["Title"] = "Error de Funcionario";
        $resultado["Text"] = "Alguna de las Cédulas de los Funcionarios, no coincide con Funcionarios Registrados en el sistema"; 
        
    }
    
    
    
}else{
    $resultado["Tipo"] = "error";
    $resultado["Title"] = "Inventario No Iniciado";
    $resultado["Text"] = "Ya hay otro Grupo de Personas Trabajando en un Inventario para la misma Bodega, Categoría y Letras";
}

echo json_encode($resultado);
?>