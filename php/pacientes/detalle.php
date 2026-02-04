<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idPaciente = ( isset( $_REQUEST['IdPaciente'] ) ? $_REQUEST['IdPaciente'] : '' );

$query = 'SELECT r.Nombre as NombreRegimen, 
                 n.Nombre as NombreNivel, 
                 n.Valor as NivelValor,
                 n.Numero as NivelNumero,
                 CONCAT_WS(" ",p.Primer_Nombre, p.Segundo_Nombre,p.Primer_Apellido,p.Segundo_Apellido) as NombrePaciente,
                 p.EPS as EPS
          FROM Paciente p , Regimen r, Nivel n
          WHERE   
          p.Id_Regimen=r.Id_Regimen
          AND
          p.Id_Nivel = n.Id_Nivel
          AND
          p.Id_Paciente='.$idPaciente ;

    
$oCon= new consulta();
$oCon->setQuery($query);
$pacientes = $oCon->getData();
unset($oCon);


$query2 = 'SELECT D.Fecha_Actual, D.Cuota, C.Salario_Base
           FROM Dispensacion D, Configuracion C 
           WHERE 
           YEAR(D.Fecha_Actual) =  YEAR(NOW())
           AND
           D.Numero_Documento='.$idPaciente ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$dispensaciones = $oCon->getData();
unset($oCon);

$query3 = 'SELECT  CONCAT(P.Principio_Activo, " ",P.Presentacion, " ",P.Concentracion, " (",P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " ") as Nombre, 
            D.*, 
           (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
            PD.Id_Producto,
            PD.Cum as Cum,
            PD.Lote as Lote,
            PD.Id_Inventario as IdInventario,
            PD.Cantidad_Formulada,
            PD.Cantidad_Entregada,
            PD.Numero_Autorizacion,
            PD.Fecha_Autorizacion,
            I.Fecha_Vencimiento as Vencimiento,
            I.Cantidad,
            PD.Id_Producto_Dispensacion
FROM Dispensacion D
INNER JOIN Producto_Dispensacion PD
ON D.Id_Dispensacion=PD.Id_Dispensacion
INNER JOIN Producto P
ON PD.Id_Producto=P.Id_Producto
INNER JOIN Inventario I
ON PD.Id_Inventario=I.Id_Inventario
WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 AND D.Numero_Documento='.$idPaciente ;

$oCon= new consulta();
$oCon->setQuery($query3);
$oCon->setTipo('Multiple');
$listapendientes = $oCon->getData();
unset($oCon);



if($pacientes["NombreRegimen"]==="Subsidiado"){

    $totalsalario=0;
    $aplica='';
    $porcentaje='';
    if($pacientes["NivelNumero"]==="1"){
        
        $totalcuota=0;
        $aplica="No";
       
    }else if($pacientes["NivelNumero"]==="2" ){
        
        $totalsalario=$dispensaciones[0]["Salario_Base"]*2;
        $aplica="Si";
        $porcentaje="10";
         
    }else{
        
        $totalsalario=$dispensaciones[0]["Salario_Base"]*3;
        $aplica="Si";
        $porcentaje="30";
    }
    
}
 if($pacientes["NivelNumero"]!="1"){
    $totalcuota=0;
    foreach($dispensaciones as $cuota){
      
    $totalcuota=$totalcuota + $cuota["Cuota"];
    }  
 }
    

        $oLista = new lista("Dispensacion");
        $oLista->setRestrict("Numero_Documento","=",$idPaciente);
        $oLista->setRestrict("Fecha_Actual","LIKE",date("Y-m" ));
        $medicamentomes = $oLista->getList();
        unset($oLista);
       
$productos=[];     
     foreach($medicamentomes as $medicamento){
         
        $oLista = new lista("Producto_Dispensacion");
        $oLista->setRestrict("Id_Dispensacion","=",$medicamento["Id_Dispensacion"]);
        $inventario = $oLista->getList();
        unset($oLista);
        
        $productos[$inventario[0]["Id_Inventario"]] = $inventario[0]["Id_Inventario"]; 
     }

$resultado["pendientes"]=$listapendientes;
$resultado["aplica"]=$aplica;
$resultado["totalcuota"]=$totalcuota;
$resultado["porcentaje"]=$porcentaje;
$resultado["totalsalario"]=$totalsalario;
$resultado["inventario"]=$productos;        
$resultado["paciente"]=$pacientes; 


echo json_encode($resultado);

?>