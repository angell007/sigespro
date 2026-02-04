<?php
     ini_set('max_execution_time', 3600);
     ini_set('memory_limit','256M');
     header('Access-Control-Allow-Origin: *');
     header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
     header('Content-Type: application/json');

     require_once('../../../config/start.inc.php');
     include_once('../../../class/class.lista.php');
     include_once('../../../class/class.complex.php');
     include_once('../../../class/class.consulta.php');

     //EL TIPO PUEDE SER DIARIO O MENSUAL - EL TIPO DEFINE LA CANTIDAD DE REGISTROS OBTENIDOS
     $tipo = (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != '') ? $_REQUEST['tipo'] : '';
     $limite_variable = (isset($_REQUEST['limite_variable']) && $_REQUEST['limite_variable'] != '') ? $_REQUEST['limite_variable'] : '';
     $limite_inicial = (isset($_REQUEST['limite_inicial']) && $_REQUEST['limite_inicial'] != '') ? $_REQUEST['limite_inicial'] : '';

     $fecha = '';
     $query = '';
     $dispensaciones_totales = [];

     if (strtolower($tipo) == 'diarias') {
          $fecha = date('Y-m-d');

          $query = "
               SELECT 
                    D.Id_Dispensacion,
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
                    IFNULL(PC.Nit, 0) AS Cliente,
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
                         DATE(D.Fecha_Actual) = '$fecha' 
                         AND D.Estado <> 'Anulada'
                         LIMIT $limite_variable, $limite_inicial";
                    
          $oCon= new consulta();

          $oCon->setQuery($query);
          $oCon->setTipo('Multiple');
          $dispensaciones_totales = $oCon->getData();
          unset($oCon);

     }else{

          $hoy = date('Y-m-d');
          $fecha = date('Y-m-d', strtotime('-30 days',strtotime($hoy)));

          $query = "
               SELECT 
                    D.Id_Dispensacion,
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
                    IFNULL(PC.Nit, 0) AS Cliente,
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
                    MONTH(D.Fecha_Actual) = MONTH(CURDATE()) 
                    AND YEAR(D.Fecha_Actual) = YEAR(CURDATE()) 
                    AND D.Estado <> 'Anulada'
                    LIMIT $limite_variable, $limite_inicial";
                    
          $oCon= new consulta();

          $oCon->setQuery($query);
          $oCon->setTipo('Multiple');
          $dispensaciones_totales = $oCon->getData();
          unset($oCon);  
     }

     echo json_encode($dispensaciones_totales);
?>