<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$dias="87";
$hoy=date("Y-m-t");
$fecha=strtotime ( '-'.$dias.' days' , strtotime ( $hoy) ) ;
$nuevafecha= date('Y-m-01', $fecha);

$fase1="91529913,91532652,1098671376,1098776668";
$fase2="5437434,91506302,91509413,91516155";


$query2='SELECT F.Imagen, CONCAT_WS(" ",F.Nombres, F.Apellidos) AS Funcionario, F.Identificacion_Funcionario, (SELECT  IFNULL(SUM(TIMESTAMPDIFF(MINUTE,Inicio_Fase1,Fin_Fase1)),0 ) FROM Remision WHERE Fase_1=F.Identificacion_Funcionario AND  (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as Fase1,

(SELECT  IFNULL(SUM(TIMESTAMPDIFF(MINUTE,Inicio_Fase2,Fin_Fase2)),0 ) FROM Remision WHERE Fase_2=F.Identificacion_Funcionario AND (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as Fase2,

(SELECT COUNT(*) FROM Remision WHERE Fase_2=F.Identificacion_Funcionario AND (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as TotalFase2, 

 (SELECT COUNT(*) FROM Remision WHERE Fase_1=F.Identificacion_Funcionario AND (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as TotalFase1,
 
  (SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE (Fase_1=F.Identificacion_Funcionario OR  Fase_2=F.Identificacion_Funcionario) AND  (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as Total_Productos, 
  
  
  (SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE Fase_1=F.Identificacion_Funcionario AND  (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as Total_Fase1, (SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE Fase_2=F.Identificacion_Funcionario AND  (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as Total_Fase2,


 IFNULL((SELECT COUNT(*) FROM Producto_No_Conforme_Remision PCN
INNER JOIN No_Conforme NC ON PCN.Id_No_Conforme=NC.Id_No_Conforme
INNER JOIN Remision R 
ON NC.Id_Remision=R.Id_Remision
WHERE NC.Tipo="Remision" AND (DATE(R.Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) AND (Fase_1=F.Identificacion_Funcionario OR  Fase_2=F.Identificacion_Funcionario) ),0) as Novedades,


(SELECT COUNT(*) FROM Remision WHERE (Fase_1=F.Identificacion_Funcionario OR Fase_2=F.Identificacion_Funcionario) AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Rem,  (SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE Fase_1=F.Identificacion_Funcionario  AND (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as Total_Productos_1, (SELECT COUNT(*) FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE Fase_2=F.Identificacion_Funcionario  AND  (DATE(Fecha) BETWEEN "'.$nuevafecha.'" AND "'.$hoy.'" ) ) as Total_Productos_2

FROM Funcionario F
INNER JOIN Cargo C
ON F.Id_Cargo=C.Id_Cargo
WHERE F.Id_Cargo=11';
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$tiempos = $oCon->getData();
unset($oCon);

$menor_fase1_tiempo=100000000;
$mayor_fase1_cantidad=0;
$menor_fase2_tiempo=100000000;
$mayor_fase2_cantidad=0;
foreach ($tiempos as  $value) {
    if(strpos($fase1,$value['Identificacion_Funcionario'])!==false){
        if($value['Total_Productos_1']!=0){
            $promedio=$value['Fase1']/$value['Total_Productos_1'];
        }else{
            $promedio=0;
        }
        if($promedio<$menor_fase1_tiempo){
            $menor_fase1_tiempo=$promedio;
        }
        if($value['Total_Productos_1']>$mayor_fase1_cantidad){
            $mayor_fase1_cantidad=$value['Total_Productos_1'];
        }
    }else{
        if($value['Total_Productos_2']!=0){
            $promedio=$value['Fase2']/$value['Total_Productos_2'];
        }else{
            $promedio=0;
        }
        if($promedio<$menor_fase2_tiempo){
            $menor_fase2_tiempo=$promedio;
        }
        if($value['Total_Productos_2']>$mayor_fase2_cantidad){
            $mayor_fase2_cantidad=$value['Total_Productos_2'];
        }
    }
}
$mayor_fase1_cantidad=$mayor_fase1_cantidad/3;
$mayor_fase2_cantidad=$mayor_fase2_cantidad/3;



$query_fase_1 = '
    SELECT 
        F.Imagen, 
        CONCAT_WS(" ",F.Nombres, F.Apellidos) AS Funcionario, 
        F.Identificacion_Funcionario, 
        (SELECT  
            IFNULL(SUM(TIMESTAMPDIFF(MINUTE,Inicio_Fase1,Fin_Fase1)),0 ) 
        FROM Remision 
        WHERE 
            Fase_1=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Fase1,
        (SELECT 
            COUNT(*) 
        FROM Remision 
        WHERE 
            Fase_1=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as TotalFase1,
        (SELECT 
            COUNT(*) 
        FROM Producto_Remision PR 
        INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
        WHERE 
            Fase_1=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Productos, 
        (SELECT 
            COUNT(*) 
        FROM Producto_Remision PR 
        INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
        WHERE 
            Fase_1=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Fase1,
        IFNULL((SELECT 
                    COUNT(*) 
                FROM Producto_No_Conforme_Remision PCN
                INNER JOIN No_Conforme NC ON PCN.Id_No_Conforme=NC.Id_No_Conforme
                INNER JOIN Remision R ON NC.Id_Remision=R.Id_Remision
                WHERE 
                    NC.Tipo="Remision" 
                    AND MONTH(R.Fecha) =MONTH(NOW()) 
                    AND Fase_1=F.Identificacion_Funcionario ),0) as Novedades,
        (SELECT 
            COUNT(*) 
        FROM Remision 
        WHERE 
            Fase_1=F.Identificacion_Funcionario
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Rem,  
        (SELECT 
            COUNT(*) 
        FROM Producto_Remision PR 
        INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
        WHERE 
            Fase_1=F.Identificacion_Funcionario  
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Productos_1

    FROM Funcionario F
    WHERE F.Identificacion_Funcionario IN ('.$fase1.')' ;

$oCon= new consulta();
$oCon->setQuery($query_fase_1);
$oCon->setTipo('Multiple');
$funcionarios_fase1 = $oCon->getData();
unset($oCon);


$query_fase_2 = '
    SELECT 
        F.Imagen, 
        CONCAT_WS(" ",F.Nombres, F.Apellidos) AS Funcionario, 
        F.Identificacion_Funcionario,
        (SELECT  
            IFNULL(SUM(TIMESTAMPDIFF(MINUTE,Inicio_Fase2,Fin_Fase2)),0 ) 
        FROM Remision 
        WHERE 
            Fase_2=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Fase2,
        (SELECT 
            COUNT(*) 
        FROM Remision 
        WHERE 
            Fase_2=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as TotalFase2, 
        (SELECT 
            COUNT(*) 
        FROM Producto_Remision PR 
        INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
        WHERE 
            Fase_2=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Productos,
        (SELECT 
            COUNT(*) 
        FROM Producto_Remision PR 
        INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
        WHERE 
            Fase_2=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Fase2,
        IFNULL((SELECT 
                    COUNT(*) 
                FROM Producto_No_Conforme_Remision PCN
                INNER JOIN No_Conforme NC ON PCN.Id_No_Conforme=NC.Id_No_Conforme
                INNER JOIN Remision R ON NC.Id_Remision=R.Id_Remision
                WHERE 
                    NC.Tipo="Remision" 
                    AND MONTH(R.Fecha) =MONTH(NOW()) 
                    AND Fase_2=F.Identificacion_Funcionario ),0) as Novedades,
        (SELECT 
            COUNT(*) 
        FROM Remision 
        WHERE 
            Fase_2=F.Identificacion_Funcionario 
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Rem, 
        (SELECT 
            COUNT(*) 
        FROM Producto_Remision PR 
        INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
        WHERE 
            Fase_2=F.Identificacion_Funcionario  
            AND  MONTH(Fecha) = MONTH(NOW()) ) as Total_Productos_2

    FROM Funcionario F
    INNER JOIN Cargo C
    ON F.Id_Cargo=C.Id_Cargo
    WHERE F.Id_Cargo=11 AND F.Identificacion_Funcionario IN ('.$fase2.')' ;

$oCon= new consulta();
$oCon->setQuery($query_fase_2);
$oCon->setTipo('Multiple');
$funcionarios_fase2 = $oCon->getData();
unset($oCon);

// var_dump($funcionarios_fase1);
// var_dump($funcionarios_fase2);
// exit;


$i=-1;


$pesonal_fase1=[];
$pesonal_fase2=[];
foreach ($funcionarios_fase1 as $item) {$i++;

    if($mayor_fase1_cantidad!=0 && $item['Total_Productos_1']>0){
        $promedio=$item['Fase1']/$item['Total_Productos_1'];
        $funcionarios_fase1[$i]['Promedio']=number_format($promedio,2,".","");
        $porcentaje=($item['Novedades']*100)/$item['Total_Productos_1'];
        $rendimiento1=(1-(($promedio-$menor_fase1_tiempo)/$menor_fase1_tiempo))*100;
        $rendimiento2=($item['Total_Productos_1']*100)/$mayor_fase1_cantidad;
        $productividad=($rendimiento1+$rendimiento2)/2;
    }else{
        $funcionarios_fase1[$i]['Promedio']=number_format(0,0,".","");
        $productividad=0;
        $porcentaje=0;
    }
    $funcionarios_fase1[$i]['Productividad']=number_format($productividad,0,"","");
    $funcionarios_fase1[$i]['Porcentaje']=number_format($porcentaje,2,".","");
    $pesonal_fase1[]=$funcionarios_fase1[$i];
}

$j=-1;
foreach ($funcionarios_fase2 as $item) {$j++;

    if($mayor_fase2_cantidad!=0 && $item['Total_Productos_2']>0){
        $promedio=$item['Fase2']/$item['Total_Productos_2'];
        $funcionarios_fase2[$j]['Promedio']=number_format($promedio,2,".","");
        $porcentaje=($item['Novedades']*100)/$item['Total_Productos_2'];
        if($menor_fase2_tiempo>0){
            $rendimiento1=(1-(($promedio-$menor_fase2_tiempo)/$menor_fase2_tiempo))*100;
        }else{
            $rendimiento1=0;
        }
       
        $rendimiento2=($item['Total_Productos_2']*100)/$mayor_fase2_cantidad;
        $productividad=($rendimiento1+$rendimiento2)/2;

    }else{
        $funcionarios_fase2[$j]['Promedio']=number_format(0,0,".","");
        $porcentaje=0;
        $productividad=0;
    }
    $funcionarios_fase2[$j]['Porcentaje']=number_format($porcentaje,2,".","");
    $funcionarios_fase2[$j]['Productividad']=number_format($productividad,0,"","");

    $pesonal_fase2[]=$funcionarios_fase2[$j];
}

$meta1="Tiempo x Items: ".number_format($menor_fase1_tiempo,2,",","")." - Items ".number_format($mayor_fase1_cantidad,0,"",".");
$meta2="Tiempo x Items: ".number_format($menor_fase2_tiempo,2,",","")." - Items ".number_format($mayor_fase2_cantidad,0,"",".");


$resultado['Fase1']=$pesonal_fase1;
$resultado['Fase2']=$pesonal_fase2;
$resultado['Meta1']=$meta1;
$resultado['Meta2']=$meta2;
echo json_encode($resultado);
?>