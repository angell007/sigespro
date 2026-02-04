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

     $hoy = date('Y-m-d');
     $ultimos_30_dias = date('Y-m-d', strtotime('-30 days',strtotime($hoy)));
     // $ultimos_dos_meses = date('Y-m-01', $ultimos_dos_meses);

     $query_count = "
          SELECT 
               COUNT(Id_Dispensacion) AS Total
          FROM Dispensacion D
          STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente
          STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
          WHERE
               DATE(D.Fecha_Actual) >= '$ultimos_30_dias' 
               AND D.Estado <> 'Anulada'";
               
     $oCon= new consulta();

     $oCon->setQuery($query_count);
     $oCon->setTipo('simple');
     $records = $oCon->getData();
     //$dispensaciones["dispensaciones"] = [];
     unset($oCon);

     $lim_inicial = 3000;
     $limit = ceil($records['Total'] / $lim_inicial);
     $lim_variable = 0;


     $dispensaciones = [];
     $dispensaciones_total = [];

     for ($i=0; $i <= 1; $i++) { 
          $lim_variable = $lim_inical * $i;

          // echo "Ciclo Actual: ".$i."\n";
          // echo "Limite: ".$lim_variable."\n\n";

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
                    (SELECT Numero_Telefono FROM Paciente_Telefono WHERE Id_Paciente = D.Numero_Documento LIMIT 1) AS Numero_Telefono,
                    PC.Direccion,
                    (SELECT Nombre FROM Regimen WHERE Id_Regimen = PC.Id_Regimen) AS Regimen,
                    D.CIE,
                    PC.Nit AS Cliente,
                    PC.EPS AS Nombre_Cliente,
                    D.Estado_Facturacion,
                    D.Estado_Dispensacion,
                    D.Estado_Auditoria,
                    D.Id_Factura,
                    D.Pendientes,
                    (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = D.Identificacion_Funcionario) AS Funcionario,
                    (SELECT Nombre FROM Departamento WHERE Id_Departamento = PC.Id_Departamento) AS Departamento
               FROM Dispensacion D
               STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente
               STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
               STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
               WHERE
                    DATE(D.Fecha_Actual) >= '$ultimos_30_dias' 
                    AND D.Estado <> 'Anulada'
                    LIMIT $lim_variable, $lim_inicial";
                    
          $oCon= new consulta();

          $oCon->setQuery($query);
          $oCon->setTipo('Multiple');
          $dispensaciones = $oCon->getData();
          $dispensaciones_total = array_merge($dispensaciones_total, $dispensaciones);
          // array_push($dispensaciones, $oCon->getData());
          //$dispensaciones["dispensaciones"] = [];
          unset($oCon);
     }

     echo json_encode($dispensaciones_total);
?>