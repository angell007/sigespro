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

$query = 'SELECT SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada, D.Numero_Documento, PDM.ID, PDM.NoPrescripcion, PDM.IdProgramacion, PDM.IdEntrega, PDM.IdReporteEntrega, PD.Id_Dispensacion, PD.Cum, PD.Lote, PDM.Id_Producto_Dispensacion_Mipres, PDM.Id_Dispensacion_Mipres, PDM.Tipo_Tecnologia, PDM.CodSerTecAEntregar, PDM.Estado_Direccionamiento, DATE(D.Fecha_Actual) AS Fecha_Actual,  DATE(D.Fecha_Reportar) AS Fecha_Reportar, A.Id_Auditoria
FROM Producto_Dispensacion PD 
INNER JOIN Producto_Dispensacion_Mipres PDM ON PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres
INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
INNER JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion
WHERE PDM.Estado_Direccionamiento IS NULL
AND (PDM.IdEntrega = 0 OR  PDM.IdEntrega IS NULL)
AND PDM.IdProgramacion != 0
AND D.Estado_Dispensacion != "Anulada"
GROUP BY PD.Id_Producto_Dispensacion_Mipres
HAVING Entregada = Formulada';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);


$codigo_sede=GetCodigoSede();
$nit=GetNitProh();
 
$i=0;
$j=0;
echo "<table border='1' cellspacing='1' cellpadding='1'><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>ID Programacion</td><td>Id Entrega</td><td>Estado</td></tr>";
foreach($productos as $pm){ $i++;

    $reclamante = getReclamante($pm);
    
    $fecha = $pm["Fecha_Reportar"] !='' ? $pm["Fecha_Reportar"] : $pm["Fecha_Actual"];
 
    $data['ID']=(INT)$pm['ID'];
    $data['CodSerTecEntregado']= ($pm["Tipo_Tecnologia"]=="M" ? $pm['Cum'] : $pm['CodSerTecAEntregar']);
    $data['CantTotEntregada']=$pm['Entregada'];
    $data['EntTotal']=0;
    $data['CausaNoEntrega']=0;
    $data['FecEntrega']= $fecha;
    $data['NoLote']=$pm["Lote"];
    $data['TipoIDRecibe']='CC';
    $data['NoIDRecibe']= $reclamante;
    

    echo "<tr><td>".$i."</td><td>".$pm["NoPrescripcion"]." - ".$reclamante."</td><td>".$pm["ID"]."</td><td>".$pm["IdProgramacion"]."</td>";;
    
    
    $entrega=$mipres->ReportarEntrega($data);

    if($entrega[0]['Id']){ $j++;
        
        echo "<td>".$entrega[0]['IdEntrega']."</td><td>ENTREGADA CORRECTAMENTE</td>";
        $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
        $oItem->IdEntrega=$entrega[0]['IdEntrega'];
        $oItem->Fecha_Entrega=date("Y-m-d H:i:s");
        $oItem->save();
        unset($oItem);

    }else{
        //echo "<br>".$i."<br><br>";
        //var_dump($entrega);
        
        $error= $entrega["Errors"][0];
        echo "<td>0</td><td>".$entrega["Errors"][0]."</td>";
         
        if(strpos($error,"ya fue entregada")!==false){
            
           	$res2 = $mipres->ConsultaEntrega($pm["NoPrescripcion"]);
           	
           	foreach($res2 as $prog){ 
                if($prog["ID"]!="E"){
                    $query='UPDATE Producto_Dispensacion_Mipres SET IdEntrega ='.$prog["IDEntrega"].', Fecha_Entrega="'.$prog["FecEntrega"].'"
                        WHERE ID = '.$prog["ID"].'';
                    $oCon= new consulta();
                    $oCon->setQuery($query);     
                    $oCon->createData();     
                    unset($oCon);
                }
            } 
        }
    }
    
    echo "<tr>";
   /* $data['ID']=(INT)$pm['ID'];
    $data['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
    $data['TipoIDSedeProv']='NI';
    $data['NoIDSedeProv']=$nit;
    $data['CodSedeProv']=$codigo_sede;
    $data['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
    $data['CantTotAEntregar']=$pm['Cantidad'];
    $respuesta=$mipres->Programacion($data);  
    
    
    if($respuesta[0]['Id']){ $j++;
    	$oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
    	$oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
    	$oItem->save();
    	unset($oItem);
    	
    	echo $i." - ".$j." - Id Programado:".(INT)$pm['ID']."<br>";
    }else{
        $error= $respuesta["Errors"][0];
        if(strpos($error,"ya fue programado")!==false){
           	echo $i." - ".$j." - Programado Anterior:".(INT)$pm['ID']."<br>"; 
           	
           	$res2 = $mipres->ConsultaProgramacion($pm["NoPrescripcion"]);
           	foreach($res2 as $prog){ $j++;
                echo $i.") ---- ".$j.")  ".$prog["ID"]."<br>";
                var_dump($prog);
                echo "<br>";
                if($prog["ID"]!="E"){
                    $query='UPDATE Producto_Dispensacion_Mipres SET IdProgramacion ='.$prog["IDProgramacion"].', Fecha_Programacion="'.$prog["FecProgramacion"].'"
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
    }
    */
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
function GetLoteEntregado($idProducto,$idDis){
    global $queryObj;
    $query = "SELECT Lote 
        From Producto_Dispensacion 
        WHERE Id_Producto_Mipres=$idProducto AND Id_Dispensacion=$idDis ";
    $queryObj->SetQuery($query);
    $lote = $queryObj->ExecuteQuery('simple');
    return $lote['Lote'];
}
function GetReclamante($pm){

    global $queryObj;

    $query = "SELECT Identificacion_Persona FROM Auditoria A INNER JOIN  Turnero T ON A.Id_Auditoria=T.Id_Auditoria
    WHERE A.Id_Auditoria=$pm[Id_Auditoria] ";
    $queryObj->SetQuery($query);
    $persona = $queryObj->ExecuteQuery('simple');

    if($persona){
        return $persona['Identificacion_Persona'];
    }else{
        return $pm['Numero_Documento'];
    }

}               
                
                
?>