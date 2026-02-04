<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query='SELECT PNA.Id, 
                PNA.Codigo, 
                PNA.Documento_Paciente, 
                PNA.Estado, 
                DATE(Fecha) AS Fecha, 
                PD.Nombre AS Punto_Dispensacion,
                D.Nombre AS Departamento,
                M.Nombre AS Municipio
        FROM Positiva_No_Autorizados_App PNA
        INNER JOIN Punto_Dispensacion PD ON PNA.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
        INNER JOIN Departamento D ON PD.Departamento = D.Id_Departamento
        INNER JOIN Municipio M ON D.Id_Departamento = M.Id_Departamento
        WHERE PNA.Estado = "Pendiente"';


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$positiva['Pendientes']= $oCon->getData();
unset($oCon);

$query='SELECT PNA.Id, 
                PNA.Codigo, 
                PNA.Documento_Paciente, 
                PNA.Estado, 
                DATE(Fecha) AS Fecha, 
                PD.Nombre AS Punto_Dispensacion,
                D.Nombre AS Departamento,
                M.Nombre AS Municipio
        FROM Positiva_No_Autorizados_App PNA
        INNER JOIN Punto_Dispensacion PD ON PNA.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
        INNER JOIN Departamento D ON PD.Departamento = D.Id_Departamento
        INNER JOIN Municipio M ON D.Id_Departamento = M.Id_Departamento
        WHERE PNA.Estado = "Radicado"';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$positiva['Radicado']= $oCon->getData();
unset($oCon);

echo json_encode($positiva);
