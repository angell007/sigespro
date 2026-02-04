<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$Id_Doc_Inventario_Fisico =  ( isset( $_REQUEST['Id_Doc_Inventario_Fisico'] ) ? $_REQUEST['Id_Doc_Inventario_Fisico'] : false );

if ($Id_Doc_Inventario_Fisico) {


    // $oItem = new complex("Doc_Inventario_Fisico","Id_Doc_Inventario_Fisico",$Id_Doc_Inventario_Fisico);
    // $inv= $oItem->getData();
    // unset($oItem);

    $query ="SELECT I.Id_Doc_Inventario_Fisico, I.Estado, I.Funcionario_Digita,I.Funcionario_Cuenta ,
     I.Fecha_Inicio, I.Lista_Productos, 
    E.Nombre AS Nombre_Estiba, E.Id_Estiba,
    B.Nombre AS  Nombre_Bodega, B.Id_Bodega_Nuevo 
    FROM Doc_Inventario_Fisico I 
    INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
    LEFT JOIN Bodega_Nuevo B ON B.Id_Bodega_Nuevo=E.Id_Bodega_Nuevo 
    WHERE I.Id_Doc_Inventario_Fisico = $Id_Doc_Inventario_Fisico";
 
    $oCon= new consulta();
    //$oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $inv = $oCon->getData();
    unset($oCon);
   
    


            

    if ($inv) {
        $oItem = new complex("Funcionario","Identificacion_Funcionario",$inv['Funcionario_Cuenta']);
        $func_contador = $oItem->getData();
        unset($oItem);
        
        $oItem = new complex("Funcionario","Identificacion_Funcionario",$inv['Funcionario_Digita']);
        $func_digitador = $oItem->getData();
        unset($oItem);

        
        $resultado['Data']["Id_Doc_Inventario_Fisico"]=$inv['Id_Doc_Inventario_Fisico'];
        $resultado['Data']["Funcionario_Digita"]= $func_digitador;
        $resultado['Data']["Funcionario_Cuenta"]= $func_contador;

        $resultado['Data']["Productos"]=$inv['Lista_Productos'];
        $resultado['Data']["Estiba"]['Nombre']=$inv['Nombre_Estiba'];
        $resultado['Data']['Estiba']["Id_Estiba"]=$inv['Id_Estiba'];

        $resultado['Data']["Bodega"]['Nombre']=$inv['Nombre_Bodega'];
        $resultado['Data']['Bodega']["Id_Bodega_Nuevo"]=$inv['Id_Bodega_Nuevo'];
       
        $resultado['Data']["Inicio"] =$inv['Fecha_Inicio'];
        
        $resultado['Data']["Estado"] =$inv['Estado'];
        

        $resultado["Tipo"] = "success";
        $resultado["Title"] = "Inventario Iniciado Correctamente";
    

    }else{
        $resultado["Tipo"] = "error";
        $resultado["Title"] = "No se encontraron Inventarios";
   
    }


}else{
        $resultado["Tipo"] = "error";
    $resultado["Title"] = "Debe ingresar un Inventario";
}

echo json_encode($resultado);