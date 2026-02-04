<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$idpunto = ( isset( $_REQUEST['idPunto'] ) ? $_REQUEST['idPunto'] : '' );
$Contador = ( isset( $_REQUEST['Contador'] ) ? $_REQUEST['Contador'] : '' );
$Digitador = ( isset( $_REQUEST['Digitador'] ) ? $_REQUEST['Digitador'] : '' );

$cond='';


$query = 'SELECT COUNT(*) as Total FROM Inventario_Nuevo I WHERE
          I.Id_Punto_Dispensacion='.$idpunto.' AND I.Id_Bodega=0';

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$Inv = $oCon->getData();
unset($oCon);


$inicio = date("Y-m-d H:i:s");

    $oItem = new complex("Funcionario","Identificacion_Funcionario",$Contador);
    $func_contador = $oItem->getData();
    unset($oItem);
    
    $oItem = new complex("Funcionario","Identificacion_Funcionario",$Digitador);
    $func_digitador = $oItem->getData();
    unset($oItem);
    
    if(isset($func_contador["Identificacion_Funcionario"])&&isset($func_digitador["Identificacion_Funcionario"])){
        
        /*$query = 'SELECT COUNT(*) as Total_Productos FROM Inventario I
          INNER JOIN Producto PRD
          ON I.Id_Producto = PRD.Id_Producto
          WHERE I.Id_Punto_Dispensacion='.$idpunto;
          
        $oCon= new consulta();
        //$oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $total = $oCon->getData();
        unset($oCon);*/
        
        $oItem = new complex("Punto_Dispensacion","Id_Punto_Dispensacion",$idpunto);
        $bodega = $oItem->getData();
        unset($oItem);
        $oItem = new complex("Inventario_Fisico_Punto","Id_Inventario_Fisico_Punto");
        $oItem->Fecha_Inicio = $inicio;
        $oItem->Id_Punto_Dispensacion = $idpunto;
        $oItem->Conteo_Productos = (INT)$total["Total_Productos"];
        if($Inv['Total']){
             $oItem->Inventario ="Si";
        }else{
             $oItem->Inventario ="No";
        }
        $oItem->Funcionario_Digita = $Digitador;
        $oItem->Funcionario_Cuenta = $Contador;
        $oItem->save();
        $id_inv= $oItem->getId();
        unset($oItem);
        
        $resultado["Id_Inventario_Fisico_Punto"]=$id_inv;
        $resultado["Funcionario_Digita"]=$func_digitador;
        $resultado["Funcionario_Cuenta"]=$func_contador;
        $resultado["Punto"] = $bodega;
        $resultado["Inicio"] = $inicio;
        //$resultado["Productos_Conteo"] = $total["Total_Productos"];
        $resultado["Tipo"] = "success";
        $resultado["Title"] = "Inventario Iniciado Correctamente";
        $resultado["Text"] = "Vamos a dar Inicio al Inventario Físico.<br>¡Muchos Exitos!";
        if($Inv['Total']){
            $resultado['Inventario']="Si";
        }else{
            $resultado['Inventario']="No";
        }
        
    }else{
        $resultado["Tipo"] = "error";
        $resultado["Title"] = "Error de Funcionario";
        $resultado["Text"] = "Alguna de las Cédulas de los Funcionarios, no coincide con Funcionarios Registrados en el sistema"; 
        
    }
    
    
    

echo json_encode($resultado);
?>