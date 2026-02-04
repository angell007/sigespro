<?php
     header('Access-Control-Allow-Origin: *');
     header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
     header('Content-Type: application/json');

     require_once('../../../config/start.inc.php');
     include_once('../../../class/class.lista.php');
     include_once('../../../class/class.complex.php');
     include_once('../../../class/class.consulta.php');

     $fecha_consulta = '2019-10-21';

     $query = "
          SELECT 
               D.Codigo,
               D.Fecha_Actual,
               D.Id_Punto_Dispensacion,
               D.Id_Servicio,
               D.Id_Tipo_Servicio,
               D.Numero_Documento,
               CONCAT_WS(' ',
                         PC.Primer_Nombre,
                         PC.Segundo_Nombre,
                         PC.Primer_Apellido,
                         PC.Segundo_Apellido) AS Paciente,
               IFNULL((SELECT Numero_Telefono FROM Paciente_Telefono WHERE Id_Paciente = D.Numero_Documento LIMIT 1), 'NULL') AS Numero_Telefono,
               PC.Direccion,
               (SELECT Nombre FROM Regimen WHERE Id_Regimen = PC.Id_Regimen) AS Regimen,
               IFNULL(D.CIE, 'NULL') AS CIE,
               PC.Nit AS Cliente,
			   PC.EPS AS Nombre_Cliente,
               D.Estado_Facturacion,
               D.Estado_Dispensacion,
               D.Estado_Auditoria,
               IFNULL(D.Id_Factura, 0) AS Id_Factura,
               D.Pendientes,
               (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = D.Identificacion_Funcionario) AS Funcionario,
               (SELECT Nombre FROM Departamento WHERE Id_Departamento = PC.Id_Departamento) AS Departamento
                FROM Dispensacion D
                STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente
                STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
                STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
               WHERE
                    DATE(D.Fecha_Actual) >= '$fecha_consulta' 
                    AND D.Estado <> 'Anulada'";
               
     $oCon= new consulta();

     $oCon->setQuery($query);
     $oCon->setTipo('Multiple');
      $dispensaciones = $oCon->getData();
     //$dispensaciones["dispensaciones"] = [];
     unset($oCon);


     echo json_encode($dispensaciones);
?>