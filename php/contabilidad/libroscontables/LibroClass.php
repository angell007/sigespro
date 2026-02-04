<?php
include_once('../../../class/class.consulta.php');

class Libro  {
 
  function getLibroDiario($date, $typeReport,$totalByYear=false){
    
    
    $formatTypeReport = $typeReport == 'PCGA' ? '': '_Niif';

    $formatDate = explode('-',$date);

    $group = 'GROUP BY  M.Id_Plan_Cuenta '. (!$totalByYear ? ' , Date(M.Fecha_Movimiento)' : '');
    $query = 'SELECT Date(M.Fecha_Movimiento) as Fecha, P.Codigo'.$formatTypeReport.' as Codigo,
               P.Nombre as Cuenta ,SUM(M.Debe'.$formatTypeReport.') as Debito, SUM(M.Haber'.$formatTypeReport.') AS Credito 

              FROM Movimiento_Contable M
              INNER JOIN Plan_Cuentas P ON P.Id_Plan_Cuentas = M.Id_Plan_Cuenta

              WHERE YEAR(M.Fecha_Movimiento) = '.$formatDate[0].' and Month(M.Fecha_Movimiento) = '.$formatDate[1].' 
              AND M.Estado = "Activo" and P.Movimiento = "S" and LEFT(P.Codigo'.$formatTypeReport.',1) BETWEEN 1 and 6 

              '.$group.'
              Order by Date(M.Fecha_Movimiento) , P.Codigo'.$formatTypeReport;
    $consulta = new consulta();
    $consulta->setQuery($query);
    $consulta->setTipo('Multiple');

    return $consulta->getData();
   
  }

  function getEncabezado(){

    $consulta = new consulta();
    $query2 = 'SELECT * From Configuracion limit 1 ';
    $consulta->setQuery($query2);
    return $consulta->getData();

  }
}


?>