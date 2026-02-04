<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

//header('Accept: application/json');
include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
include_once('../class/class.mipres.php');
include_once('../class/class.php_mailer.php');

//include_once('../class/class.complex.php');

$queryObj = new QueryBaseDatos();

$mipres= new Mipres();

$ids = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : '';

$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '';


    
$query="
SELECT PDM.Id_Producto_Dispensacion_Mipres, PDM.NoPrescripcion, PDM.ID, PDM.Codigo_Cum, PDM.Cantidad, PDM.IdProgramacion, PDM.IdEntrega, PDM.IdReporteEntrega, PDM.Valor_Reportado,
#ERP.Id_Tipo, ERP.Fecha_Reporte, ERP.Cantidad_Reportada, ERP.Cum_Reportado, ERP.Valor_Reportado,
PDM.Anulado, PDM.Actualizado, PDM.Anulado, PDM.Actualizado2, PDM.Anulado3, PDM.Actualizado3
FROM Producto_Dispensacion_Mipres PDM 
INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
#INNER JOIN Z_Estado_Reporte_Mipres ERP ON ERP.ID = PDM.ID AND ERP.Tipo = 'ReporteEntrega' AND ERP.Estado_Reporte != 0

WHERE PDM.Id$tipo IN ($ids)
#AND PDM.Anulado3 IS NULL
#LIMIT 10
"; 

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$direccionamientos = $oCon->getData();
unset($oCon);


$i=0;    
$res='';
$res= "<table border='1' cellspacing='1' style='width-100%;' cellpadding='1'><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>Prod Dis Mipres</td><td>Valor Reportado</td><td>Id Entrega</td><td>Id Reporte Entrega</td><td>Anulado</td></tr>";
foreach($direccionamientos as $dir){ $i++;
$res.= "<tr>";
     $res.= "<td>$i</td>";
        
        //if($dir["Anulado3"]!="Si"){
           // echo "<td>Voy a aNular</td>";
           if($tipo=='ReporteEntrega'){
              
               $respuesta1= $mipres->AnularReporteEntrega($dir["IdReporteEntrega"]);
           }elseif ($tipo=='Entrega'){
             
               $respuesta1= $mipres->AnularEntrega($dir["IdEntrega"]);
           }
            $res.= "<td>$dir[NoPrescripcion]</td>";
            $res.= "<td>$dir[ID]</td>";
            $res.= "<td>$dir[Id_Producto_Dispensacion_Mipres]</td>";
           
                 
             $res.= "<td>$dir[Valor_Reportado]</td>";
             $res.= "<td>$dir[IdEntrega]</td>";
             $res.= "<td>$dir[IdReporteEntrega]</td>";
             
            if(isset($respuesta1["Errors"])){
               $res.= "<td>".$respuesta1["Errors"][0]."</td>";
               
               if($respuesta1["Errors"][0] == 'La reporte de entrega ya se encuentra anulado' && $tipo=='ReporteEntrega'){
                         
                   $query=' UPDATE Producto_Dispensacion_Mipres SET IdReporteEntrega = NULL, Fecha_Reporte_Entrega = NULL, Valor_Reportado = NULL
                            WHERE Id_Producto_Dispensacion_Mipres = '.$dir["Id_Producto_Dispensacion_Mipres"]; 
                   
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                   // $oCon->setTipo("Multiple");
                    $oCon->createData();
                    unset($oCon);
                    
               }
               
               
                if($respuesta1["Errors"][0] == 'La entrega ya se encuentra anulada' && $tipo=='Entrega'){
                         
                  $query=' UPDATE Producto_Dispensacion_Mipres SET IdEntrega = NULL, Fecha_Entrega=NULL, Cum_Reportado = NULL, Fecha_Entrega_Reportada = NULL
                            WHERE Id_Producto_Dispensacion_Mipres = '.$dir["Id_Producto_Dispensacion_Mipres"]; 
                   
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                   // $oCon->setTipo("Multiple");
                    $oCon->createData();
                    unset($oCon);
                    
               }
               
               
              
            }elseif ($respuesta1){
        
             //   $oItem = new complex("Producto_Dispensacion_Mipres","Id_Producto_Dispensacion_Mipres",$dir["Id_Producto_Dispensacion_Mipres"]);
               
               // $oItem->Anulado3 = "Si";
               $QUERY = '';
               if($tipo=='ReporteEntrega'){
                    
                    /*$oItem->IdReporteEntrega=NULL;
                    $oItem->Fecha_Reporte_Entrega=NULL;
                    $oItem->Valor_Reportado=NULL;*/
                    
                      
                   $query=' UPDATE Producto_Dispensacion_Mipres SET IdReporteEntrega = NULL, Fecha_Reporte_Entrega = NULL, Valor_Reportado = NULL
                            WHERE Id_Producto_Dispensacion_Mipres = '.$dir["Id_Producto_Dispensacion_Mipres"]; 
                    
                    
                    
                      $res.= "<td>ReporteEntrega Anulado".json_encode($respuesta1)."</td>";
               }elseif ($tipo=='Entrega'){
                  
                   /* $oItem->IdEntrega=NULL;
                    $oItem->Fecha_Entrega=NULL;
                    $oItem->Cum_Reportado=NULL;*/
                    
                      
                   $query=' UPDATE Producto_Dispensacion_Mipres SET IdEntrega = NULL, Fecha_Entrega=NULL, Cum_Reportado = NULL, Fecha_Entrega_Reportada = NULL
                            WHERE Id_Producto_Dispensacion_Mipres = '.$dir["Id_Producto_Dispensacion_Mipres"]; 
                    
                    
                    
                      $res.= "<td>Entrega Anulado".json_encode($respuesta1)."</td>";
               }
               
            //   var_dump($oItem);
            
              // $oItem->save();
               
               if($query){
                 
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                    $oCon->setTipo("Multiple");
                    $oCon->createData();
                    unset($oCon);
                }
                
                
                 
                unset($oItem);
            }else{
                //var_dump($respuesta1);
                $res.= "<td>SIN RESPUESTA MIPRES ".$respuesta1." </td>";
            }
       /* }else{
            echo "<td>YA HA SIDO ANULADO</td>";
        }
          */ 
   $res.="</tr>";
}
$res.= "</table>";  

echo json_encode($res);
//echo json_encode($direccionamientos,true);

?>