<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');


include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.mipres.php');

$mipres= new Mipres();
$queryObj = new QueryBaseDatos();

$query = 'SELECT PDM.*, DM.*
          FROM Producto_Dispensacion_Mipres PDM
          INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
          WHERE PDM.IdProgramacion = 0 AND PDM.Estado_Direccionamiento IS NULL
          AND PDM.CodSerTecAEntregar!=""';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

$codigo_sede=GetCodigoSede();
$nit=GetNitProh();
 
$i=0;
$j=0;
echo "<table><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>Estado</td><td>ID Programacion</td></tr>";
foreach($productos as $pm){ $i++;
    $data['ID']=(INT)$pm['ID'];
    $data['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
    $data['TipoIDSedeProv']='NI';
    $data['NoIDSedeProv']=$nit;
    $data['CodSedeProv']=$codigo_sede;
    $data['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
    $data['CantTotAEntregar']=$pm['Cantidad'];
    $respuesta=$mipres->Programacion($data);  

    if($respuesta[0]['Id']){ 
    	$oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
    	$oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
    	$oItem->Fecha_Programacion = date("Y-m-d H:i:s");
    	$oItem->save();
    	unset($oItem);
    	
    	echo "<tr><td>".$i."</td><td>".$pm["NoPrescripcion"]."</td><td>".(INT)$pm['ID']."</td><td>Programado</td><td>".$respuesta[0]['IdProgramacion']."</td></tr>";
    }else{
        
        $error= $respuesta["Errors"][0];
        echo "<tr><td>".$i."</td><td>".$pm["NoPrescripcion"]."</td><td>".(INT)$pm['ID']."</td><td>ERROR</td><td>".$error."</td></tr>";
       
        
        if(strpos($error,"ya fue programado")!==false){
           	$res2 = $mipres->ConsultaProgramacion($pm["NoPrescripcion"]); 
           	if(is_array($res2)){
               	foreach($res2 as $prog){ 
                    if($prog["ID"]!="E"){
                        $query='UPDATE Producto_Dispensacion_Mipres SET IdProgramacion ='.$prog["IDProgramacion"].', Fecha_Programacion="'.$prog["FecProgramacion"].'"
                            WHERE ID = '.$prog["ID"].'';
                        $oCon= new consulta();
                        $oCon->setQuery($query);     
                        $oCon->createData();     
                        unset($oCon);
                    }
                }
           	}
        }elseif(strpos($error,"anulado")!==false){
           	$oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
        	$oItem->Estado_Direccionamiento="ANULADO";
        	$oItem->save();
        	unset($oItem);
        }
        
    }
    
}
echo "</table>";
                 


function GetCodigoSede(){
    global $queryObj;
    $query = 'SELECT Codigo_Sede				
        FROM Configuracion
        WHERE Id_Configuracion=1';
    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');
    return $dato['Codigo_Sede'];
}
function GetNitProh(){
    global $queryObj;
    $query = 'SELECT NIT				
            FROM Configuracion
            WHERE Id_Configuracion=1';
    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');

    $n=explode('-',$dato['NIT']);
    $nit=$n[0];
    $nit=str_replace('.','',$nit);
    return $nit;
}

                
                
                
?>