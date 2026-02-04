<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');
include_once('..//class/NumeroALetra.php');


include_once('../class/class.querybasedatos.php');
include_once('..//class/class.http_response.php');
require_once('../class/class.configuracion.php');
include_once('../class/class.mipres.php');

$mipres= new Mipres();
$queryObj = new QueryBaseDatos();

$query = 'SELECT *
          FROM Z_Mipres_Reportar
          WHERE IdProgramacion = 0';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$todos = $oCon->getData();
unset($oCon);

$codigo_sede=GetCodigoSede();
$nit=GetNitProh();
 
$i=0;
$j=0;
foreach($todos as $t){ $i++;

    $productos=$mipres->GetDireccionamientoPorPrescripcion($t["Prescripcion"]);
    
    foreach($productos as $pm){

        if($pm["ID"]==$t["ID"]){
            $data['ID']=(INT)$pm['ID'];
            $data['FecMaxEnt']=$pm['FecMaxEnt'];
            $data['TipoIDSedeProv']='NI';
            $data['NoIDSedeProv']=$nit;
            $data['CodSedeProv']=$codigo_sede;
            $data['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
            $data['CantTotAEntregar']=$pm['CantTotAEntregar'];
            $respuesta=$mipres->Programacion($data);  
            
            
            if($respuesta[0]['Id']){ $j++;
        	$oItem=new complex('Z_Mipres_Reportar','Id_Mipres_Reportar',$t['Id_Mipres_Reportar']);
        	$oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
        	$oItem->save();
        	unset($oItem);
        	
        	echo $i." - ".$j." - Prescripcion:".$pm["NoPrescripcion"]." - Id Programado:".(INT)$pm['ID']."<br>";
            }else{
                echo $i." - Prescripcion:".$pm["NoPrescripcion"];
                var_dump($respuesta);
                $error= $respuesta["Errors"][0];
                if(strpos($error,"ya fue programado")!==false){
                   	echo $i." - ".$j." - Programado Anterior:".(INT)$pm['ID']."<br>"; 
                   	
                   	$res2 = $mipres->ConsultaProgramacion($t["Prescripcion"]);
                   	foreach($res2 as $prog){ $j++;
                        echo $i.") ---- ".$j.")  ".$prog["ID"]."<br>";
                        var_dump($prog);
                        echo "<br>";
                        if($prog["ID"]!="E"){
                            $query='UPDATE Z_Mipres_Reportar SET IdProgramacion ='.$prog["IDProgramacion"].'
                                WHERE ID = '.$prog["ID"].'';
                                
                            $oCon= new consulta();
                            $oCon->setQuery($query);     
                            $oCon->createData();     
                            unset($oCon);
                            
                        }
                         
                    }
                   	echo "<br><br>";
                }elseif(strpos($error,"anulado")!==false){
                    //echo $i." - ".$j." - Anulado:".(INT)$pm['ID']."<br>";
                }
                echo "<br>";
            }
            
        }
    
    }
}
                 


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