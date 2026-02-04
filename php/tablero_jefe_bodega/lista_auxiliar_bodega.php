<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT F.Imagen, CONCAT_WS(" ",F.Nombres, F.Apellidos) AS Funcionario, 
F.Identificacion_Funcionario, 
(SELECT  IFNULL(SUM(TIMESTAMPDIFF(MINUTE,Inicio_Fase1,Fin_Fase1)),0 ) FROM Remision WHERE Fase_1=F.Identificacion_Funcionario AND  MONTH(Fecha) = MONTH(NOW()) ) as Fase1,
(SELECT  IFNULL(SUM(TIMESTAMPDIFF(MINUTE,Inicio_Fase2,Fin_Fase2)),0 ) FROM Remision WHERE Fase_2=F.Identificacion_Funcionario AND  MONTH(Fecha) = MONTH(NOW()) ) as Fase2, 
(SELECT COUNT(*) FROM Remision WHERE Fase_2=F.Identificacion_Funcionario AND  MONTH(Fecha) = MONTH(NOW()) ) as TotalFase2, 
(SELECT COUNT(*) FROM Remision WHERE Fase_1=F.Identificacion_Funcionario AND  MONTH(Fecha) = MONTH(NOW()) ) as TotalFase1, 
(SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE (Fase_1=F.Identificacion_Funcionario OR  Fase_2=F.Identificacion_Funcionario) AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Productos, 
(SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE Fase_1=F.Identificacion_Funcionario AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Fase1, (SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE Fase_2=F.Identificacion_Funcionario AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Fase2
FROM Funcionario F
INNER JOIN Cargo C
ON F.Id_Cargo=C.Id_Cargo
WHERE F.Id_Cargo=11 AND F.Liquidado="No"' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$funcionarios = $oCon->getData();
unset($oCon);
$i=-1;
foreach ($funcionarios as $item) {$i++;
    if($item['TotalFase1']!=0  ){
        $promediofase1=$item['Fase1']/$item['TotalFase1'];
          }else if ($item['TotalFase1']==0 && $item['Fase1']==0){
            $promediofase1=0;
          }
    if( $item['TotalFase2'] !=0) {
        $promediofase2=$item['Fase2']/$item['TotalFase2'];
    }else if ($item['TotalFase2']==0 && $item['Fase2']==0){
        $promediofase2=0;
    }
     $promedioremision=($promediofase1+$promediofase2)/2;
     if ($item['Total_Productos'] != 0) {
        $promediototal=($promediofase1+$promediofase2)/$item['Total_Productos'];
     } else {
         $promediototal = 0;
     }
    
    $funcionarios[$i]['PromedioFase1']=number_format($promediofase1,1,".","");
    $funcionarios[$i]['PromedioFase2']=number_format($promediofase2,1,".","");
    $funcionarios[$i]['PromedioRemision']=number_format($promedioremision,0,".","");
    $funcionarios[$i]['PromedioTotal']=number_format($promediototal,1,".","");
   
}

echo json_encode($funcionarios);