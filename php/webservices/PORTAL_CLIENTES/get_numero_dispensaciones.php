<?php
     header('Access-Control-Allow-Origin: *');
     header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
     header('Content-Type: application/json');

     require_once('../../../config/start.inc.php');
     include_once('../../../class/class.lista.php');
     include_once('../../../class/class.complex.php');
     include_once('../../../class/class.consulta.php');

     //EL TIPO PUEDE SER DIARIO O MENSUAL - EL TIPO DEFINE LA CANTIDAD DE REGISTROS OBTENIDOS
     $tipo = (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != '') ? $_REQUEST['tipo'] : '';

     $fecha = '';

     if (strtolower($tipo) == 'diarias') {
          $fecha = date('Y-m-d');

          $query_count = "
               SELECT 
                    COUNT(Id_Dispensacion) AS Total
               FROM Dispensacion D
               STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente
               STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
               STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
               WHERE
                    DATE(D.Fecha_Actual) = '$fecha' 
                    AND D.Estado <> 'Anulada'";
     }else{
          $hoy = date('Y-m-d');
          $fecha = date('Y-m-d', strtotime('-30 days',strtotime($hoy)));  

          // $query_count = "
          //      SELECT 
          //           COUNT(Id_Dispensacion) AS Total
          //      FROM Dispensacion D
          //      STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente
          //      STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          //      STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
          //      WHERE
          //           DATE(D.Fecha_Actual) >= '$fecha' 
          //           AND D.Estado <> 'Anulada'";

          $query_count = "
               SELECT 
                    COUNT(Id_Dispensacion) AS Total
               FROM Dispensacion D
               STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente
               STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
               STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
               WHERE
                    MONTH(D.Fecha_Actual) = MONTH(CURDATE()) 
                    AND YEAR(D.Fecha_Actual) = YEAR(CURDATE()) 
                    AND D.Estado <> 'Anulada'";
     }
               
     $oCon= new consulta();

     $oCon->setQuery($query_count);
     $oCon->setTipo('simple');
     $records = $oCon->getData();
     unset($oCon);

     echo $records['Total'];
?>