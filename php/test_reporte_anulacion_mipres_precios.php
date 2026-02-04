<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
include_once('../class/class.mipres.php');
include_once('../class/class.php_mailer.php');

$queryObj = new QueryBaseDatos();

$mipres= new Mipres();
    
    
    
$query="
SELECT PDM.*
FROM Producto_Dispensacion_Mipres PDM
INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
WHERE PDM.Anulado = 'Si' 
AND PDM.Actualizado = 'Si'  
AND PDM.Actualizado2 IS NULL
AND PDM.Anulado2 IS NULL
ORDER BY PDM.Fecha_Reporte_Entrega DESC

#LIMIT 1
"; 
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$direccionamientos = $oCon->getData();
unset($oCon);
    


$i=0;    
echo "<table border='1' cellspacing='1' cellpadding='1'><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>Valor Reportado</td><td>Valor Real</td><td>Anulado</td><td>Actualizado</td></tr>";
foreach($direccionamientos as $dir){ $i++;
   $query="SELECT D.Id_Dispensacion, SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada, SUM(PF.Cantidad) AS Cantidad_Factura, PF.Precio, PF.Impuesto
           FROM Producto_Dispensacion PD
           INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
           INNER JOIN Producto_Factura PF ON PF.Id_Producto_Dispensacion = PD.Id_Producto_Dispensacion
           INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
           WHERE F.Tipo='Factura' AND F.Estado_Factura != 'Anulada' AND PD.Id_Producto_Dispensacion_Mipres =".$dir["Id_Producto_Dispensacion_Mipres"]."
    "; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $dis = $oCon->getData();
    unset($oCon);
    
    $valor=number_format(($dis["Precio"]*$dis["Cantidad_Factura"])+($dis["Precio"]*($dis["Cantidad_Factura"]*$dis["Impuesto"]/100)),0,"","");
    
    echo "<tr><td>".$i."</td><td>".$dir["NoPrescripcion"]."</td><td>".$dir["ID"]."</td><td>".$dir["Valor_Reportado"]."</td><td>".$valor."</td>";
    if(isset($dis["Id_Dispensacion"])&&$dis["Id_Dispensacion"]!=''){ 
        
        if($dir["Valor_Reportado"]!=$valor){ 
            
            //echo "<td colspan='2'>VALORES DIFERENTES</td>";
            
            if($dir["Anulado2"]!="Si"){
               $respuesta1= $mipres->AnularReporteEntrega($dir["IdReporteEntrega"]);
                if(isset($respuesta1["Errors"])){
                    echo "<td>".$respuesta1["Errors"][0]."</td>";
                }else{
                    echo "<td>ANULADO EXITOSAMENTE</td>";
                    $oItem = new complex("Producto_Dispensacion_Mipres","Id_Producto_Dispensacion_Mipres",$dir["Id_Producto_Dispensacion_Mipres"]);
                    $oItem->Anulado2 = "Si";
                    $oItem->save();
                    unset($oItem);
                }
            }else{
                echo "<td>YA HA SIDO ANULADO</td>";
            }
            
            if($dir["Actualizado2"]!="Si"){
                $data['ID']=(INT)$dir['ID'];
                $data['EstadoEntrega']=1;
                $data['CausaNoEntrega']=0;
                $data['ValorEntregado']=$valor;
                $respuesta2=$mipres->ReportarEntregaEfectiva($data);
               
                if($respuesta2[0]['Id']){
                    $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$dir['Id_Producto_Dispensacion_Mipres']);
                    $oItem->IdReporteEntrega=$respuesta2[0]['IdReporteEntrega'];
                    $oItem->Fecha_Reporte_Entrega=date("Y-m-d H:i:s");
                    $oItem->Valor_Reportado=$valor;
                    $oItem->Actualizado2 = "Si";
                    $oItem->save();
                    unset($oItem);
                    echo "<td>SE ACTUALIZO EL REPORTE ENTREGA</td>";
                }else{
                    
                    echo "<td>".$respuesta2["Errors"][0]."</td>";
                    //var_dump($respuesta2);
                    //$respuesta3=$mipres->ConsultaEntregaEfectiva($dir["NoPrescripcion"]);
                    //var_dump($respuesta3);
                    //exit;
                }
                
            }else{
                echo "<td>YA HA SIDO ACTUALIZADO</td>";
            }
            
            echo "</tr>";
            
        }else{
            echo "<td colspan='2'>SON IGUALES</td>";
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$dir['Id_Producto_Dispensacion_Mipres']);
            //$oItem->Fecha_Reporte_Entrega=$dir["Fecha_Reporte"];
            //$oItem->Valor_Reportado=$valor;
            $oItem->Actualizado2 = "Si";
            $oItem->save();
            unset($oItem);
        }
        //var_dump($dis);
    }else{
        echo "<td colspan='2'>NO HAY REGISTROS</td>";
    }
    
    //echo "<br><br><br>";
   
}
 echo "</table>";  

//echo json_encode($direccionamientos,true);

?>