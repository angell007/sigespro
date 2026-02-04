<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_vacante = $_REQUEST['id'];

$query = 'SELECT v.Titulo_Vacante, 
            CONCAT("De ", DATE_FORMAT(v.Fecha_Inicio,"%d/%m/%Y")," hasta ",DATE_FORMAT(v.Fecha_Fin,"%d/%m/%Y")) AS Fechas, 
            CONCAT("$ ",FORMAT(v.Salario_Inferior,0), " - $", FORMAT(v.Salario_Superior,0)) AS Salarios, 
            CONCAT(DATE_FORMAT(v.Horario_Inferior,"%r")," hasta ",DATE_FORMAT(v.Horario_Superior,"%r")) AS Horarios, v.Descripcion, v.Educacion, v.Edad, v.Viajar, v.Cambio_Residencia, 
            CONCAT(dp.Nombre,", ",m.Nombre) AS Ubicacion, 
            CONCAT(d.Nombre," - ",c.Nombre) AS Cargo,
            v.Edad_Max
            FROM Vacante v
            LEFT JOIN Grupo g ON g.Id_Grupo=v.Grupo 
            LEFT JOIN Dependencia d ON d.Id_Dependencia=v.Dependencia
            LEFT JOIN Cargo c ON c.Id_Cargo=v.Cargo 
            LEFT JOIN Departamento dp ON dp.Id_Departamento=v.Departamento 
            LEFT JOIN Municipio m ON m.Id_Municipio=v.Municipio  WHERE v.Id_Vacante='.$id_vacante ;

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>