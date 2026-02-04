<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');


$query ="SELECT I.Id_Doc_Inventario_Fisico_Punto, I.Funcionario_Digita AS 'Id_Funcionario_Digita',I.Funcionario_Cuenta 
        AS 'Id_Funcionario_Cuenta', I.Fecha_Inicio, I.Estado, I.Id_Estiba,
        E.Nombre AS Estiba, P.Nombre AS Punto_Dispensacion ,
        FD.Nombres AS Funcionario_Digita_Nombres, FD.Apellidos AS Funcionario_Digita_Apellidos,
        FC.Nombres AS Funcionario_Cuenta_Nombres, FC.Apellidos AS Funcionario_Cuenta_Apellidos
        FROM Doc_Inventario_Fisico_Punto I 
        INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
        INNER JOIN Punto_Dispensacion P ON P.Id_Punto_Dispensacion=E.Id_Punto_Dispensacion
        INNER JOIN Funcionario FD ON FD.Identificacion_Funcionario=I.Funcionario_Digita
        INNER JOIN Funcionario FC ON FC.Identificacion_Funcionario=I.Funcionario_Cuenta
        WHERE I.Estado NOT IN ('Terminado')";


    $oCon= new consulta();
    //$oCon->setTipo('Multiple');
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $Inv = $oCon->getData();
    unset($oCon);

if($Inv){
    $oItem = new complex("Funcionario","Identificacion_Funcionario",$inv['Funcionario_Cuenta']);
    $func_contador = $oItem->getData();
    unset($oItem);
    
    $oItem = new complex("Funcionario","Identificacion_Funcionario",$inv['Funcionario_Digita']);
    $func_digitador = $oItem->getData();
    unset($oItem);

    $resultado['tipo'] = "success";
    $resultado['documentos'] = $Inv;

}else{
    $resultado['tipo'] = "error";
    
}

echo json_encode($resultado);

?>