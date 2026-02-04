<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');
include_once('../class/NumeroALetra.php');


include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
require_once('../class/class.configuracion.php');
include_once('../class/class.mipres.php');

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);


$mipres= new Mipres();
$queryObj = new QueryBaseDatos();


$query = 'SELECT *  
FROM Z_Relacion_Mipres_2019 
WHERE Id_Producto_Dispensacion NOT LIKE "%,%" 
AND Procesado = 1 AND Relacionado = 0
ORDER BY Id_Producto_Dispensacion ASC
#LIMIT 1
';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

$z=0;
echo "<table><tr><td>ID General</td><td>ID Dispensacion</td><td>Id Producto dispensacion</td><td>Id producto dispensacion Mipres</td></tr>";
foreach($productos as $prod){ $z++;

    $query="SELECT *
    FROM Producto_Dispensacion_Mipres PDM 
    WHERE PDM.ID =".$prod["ID"]." LIMIT 1"; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $uno = $oCon->getData();
    unset($oCon);
    
    if($uno){
        
        $oItem=new complex('Producto_Dispensacion','Id_Producto_Dispensacion',$prod["Id_Producto_Dispensacion"]);
        $oItem->Id_Producto_Dispensacion_Mipres = $uno["Id_Producto_Dispensacion_Mipres"];
        $dis = $oItem->getData();
        $oItem->save();
        unset($oItem);
        
        
        $oItem=new complex('Dispensacion','Id_Dispensacion',$dis["Id_Dispensacion"]);
        $oItem->Id_Dispensacion_Mipres = $uno["Id_Dispensacion_Mipres"];
        $oItem->save();
        unset($oItem);
        
        
        $oItem=new complex('Z_Relacion_Mipres_2019','Id_Relacion_Mipres_2019',$prod["Id_Relacion_Mipres_2019"]);
        $oItem->Relacionado = 1;
        $oItem->save();
        unset($oItem);
        
        echo "<tr><td>".$prod["ID"]."</td><td>".$dis["Id_Dispensacion"]."</td><td>".$dis["Id_Producto_Dispensacion"]."</td><td>".$uno["Id_Producto_Dispensacion_Mipres"]."</td></tr>";
        
    }else{
        
        echo "<tr><td>".$prod["ID"]."</td><td colspan='3'>NO SE ENCUENTRA EL ID EN PRODUCTO MIPRES</td></tr>";
        
    }

    /*
    $dishoy=$mipres->GetDireccionamientoPorPrescripcion($prod["NoPrescripcion"]);
    
    
    if(is_array($dishoy)){
        
        usort($dishoy,'OrderByNumeroEntrega');
        
        $oItem=new complex('Z_Relacion_Mipres_2019','Id_Relacion_Mipres_2019',$prod["Id_Relacion_Mipres_2019"]);
        $oItem->Procesado = 1;
        $oItem->save();
        unset($oItem);
            
        $i=0;
        foreach($dishoy as $d){ $i++;
            $query="SELECT *
            FROM Producto_Dispensacion_Mipres PDM 
            WHERE PDM.ID =".$d["ID"]." LIMIT 1"; 
            $oCon = new consulta();
            $oCon->setQuery($query);
            $uno = $oCon->getData();
            unset($oCon);
            
            echo $z." - ".$i." - ) ";
            
            if($uno){
                echo $prod["NoPrescripcion"]." - si esta Creado - Estado: ".$d["EstDireccionamiento"]." - ".$uno["IdProgramacion"]."<br>";
            }else{
                echo $prod["NoPrescripcion"]." - No esta Creado - Estado: ".$d["EstDireccionamiento"]."<br>";
                if($d['TipoTec']=='M'){ 
                    GuardarEntrega($d); 
                }else if ($d['TipoTec']!='P'){
                    GuardarDireccionamiento($d);      
                }
            }
        }
        UnificarDireccionamientos($prod["NoPrescripcion"]);
        ProgramarDireccionamientos($prod["NoPrescripcion"]);
        
    }else{
        echo $prod["NoPrescripcion"]." - ".$prod["ID"]." NO SE ENCUENTA EN MIPRES<br><br>";
    }
    
    */
}


function GuardarEntrega($dis){

    $dispensacion=$dis;
    $dispensacion['Fecha']=date('Y-m-d H:i:s');
    $dispensacion['Id_Paciente']=$dis['NoIDPaciente'];
    $dispensacion['Fecha_Maxima_Entrega']=$dis['FecMaxEnt'];
    $dispensacion['Numero_Entrega']=$dis['NoEntrega'];
    $dispensacion['Fecha_Direccionamiento']=$dis['FecDireccionamiento'];
    $dispensacion['Id_Servicio']=1;
    $dispensacion['Id_Tipo_Servicio']=3;
    $dispensacion['Codigo_Municipio']=$dis['CodMunEnt'];
    $dispensacion['Tipo_Tecnologia']=$dis['TipoTec'];
    $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres');
    foreach ($dispensacion as $key => $value) {
       $oItem->$key=$value;
    }
    $oItem->save();
    $id_dis = $oItem->getId();
    unset($oItem);
   
    $c=explode('-',$dis['CodSerTecAEntregar']);
    $cum=(INT)$c[0].'-'.$c[1];
    $cum = trim($cum,"-");
    $prr=GetIdProducto($cum);
    
    $idproducto=$prr["Id_Producto"];
    $pres = $prr["Cantidad_Presentacion"];
    $dis['Codigo_Cum']=$cum;
    $dis['Id_Producto']=$idproducto!= false ? $idproducto : '0';
    $dis['Tipo_Tecnologia']=$dis['TipoTec'];
    
    $dis['Id_Dispensacion_Mipres']=$id_dis;
    /*
    if($dis['CantTotAEntregar']<$pres){
       $dis['Cantidad']=$dis['CantTotAEntregar']; // *$pres
    }elseif($dis['CantTotAEntregar']==$pres){
       $dis['Cantidad']=$dis['CantTotAEntregar'];  
    }elseif($dis['CantTotAEntregar']>$pres){
        $mod = $dis['CantTotAEntregar'] % $pres;
        $div = $dis['CantTotAEntregar'] / $pres;
        if($mod!=0){
            $dis['Cantidad']=$dis['CantTotAEntregar']-$mod;
        }else{
           $dis['Cantidad']=$dis['CantTotAEntregar']; 
        }
    } */
    $dis["Cantidad"]=$dis['CantTotAEntregar'];
    $dis['CantidadMipres']=$dis['CantTotAEntregar']; 
    $dis["Tipo"]="Augusto";
    $oItem=new complex ('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres');
    foreach ($dis as $key => $value) {
        $oItem->$key=$value;
    }
    $oItem->save();
    unset($oItem);

}


function GuardarDireccionamiento($dis){

    $dispensacion=$dis;
    $dispensacion['Fecha']=date('Y-m-d H:i:s');
    $dispensacion['Id_Paciente']=$dis['NoIDPaciente'];
    $dispensacion['Fecha_Maxima_Entrega']=$dis['FecMaxEnt'];
    $dispensacion['Numero_Entrega']=$dis['NoEntrega'];
    $dispensacion['Fecha_Direccionamiento']=$dis['FecDireccionamiento'];
    $dispensacion['Id_Servicio']=1;
    $dispensacion['Id_Tipo_Servicio']=3;
    $dispensacion['Codigo_Municipio']=$dis['CodMunEnt'];
    $dispensacion['Tipo_Tecnologia']=$dis['TipoTec'];
 
    $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres');
    foreach ($dispensacion as $key => $value) {
       $oItem->$key=$value;
    }
    $oItem->save();
    $id_dis = $oItem->getId();
    unset($oItem);
   
    $c=explode('-',$dis['CodSerTecAEntregar']);
    $cum=str_pad((INT)$c[0], 2, "0", STR_PAD_LEFT); 
    
    $idproducto=GetIdProductoAsociado($cum, $dis['TipoTec']);
    $dis['Codigo_Cum']=str_replace('-','',$cum);
    $dis['Id_Producto']=$idproducto!= false ? $idproducto : '0';
    $dis['Tipo_Tecnologia']=$dis['TipoTec'];
    
    $dis['Id_Dispensacion_Mipres']=$id_dis;
    $dis["Cantidad"]=$dis['CantTotAEntregar'];
    $dis['CantidadMipres']=$dis['CantTotAEntregar']; 
    $dis["Tipo"]="Augusto";
    $oItem=new complex ('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres');
    foreach ($dis as $key => $value) {
        $oItem->$key=$value;
    }
    $oItem->save();
    unset($oItem);
  
}
function OrderByIdPaciente($a,$b){
    return strnatcmp($a['NoIDPaciente'],$b['NoIDPaciente']);
}
function OrderByNumeroEntrega($a,$b){
    return strnatcmp($a['NoEntrega'],$b['NoEntrega']);
}

function GetIdProducto($cum){

    $tem=explode('-',$cum);
    $cum2=$tem[0].'-'.(INT)$tem[1];
    $query="SELECT Id_Producto, Cantidad_Presentacion FROM Producto WHERE Codigo_Cum='$cum' OR Codigo_Cum='$cum2'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum;
}
function GetIdProductoAsociado($cum, $tec){

    $tem=explode('-',$cum);
    $cum2=$tem[0].'-'.(INT)$tem[1];
    $cum=str_replace('-','',$cum);
    $query="SELECT Id_Producto FROM Producto_Tipo_Tecnologia_Mipres PD INNER JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres WHERE (Codigo_Actual='$cum') AND M.Codigo='$tec' LIMIT 1"; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum['Id_Producto'];
}

function UnificarDireccionamientos($pres){
    $query="SELECT COUNT(DISTINCT(PD.Id_Dispensacion_Mipres)) as Conteo, PD.NoPrescripcion, GROUP_CONCAT(DISTINCT(PD.Id_Dispensacion_Mipres)) AS Id 
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    WHERE D.Estado ='Pendiente' AND D.Bandera='Normal' AND PD.NoPrescripcion ='".$pres."'
    GROUP BY PD.NoPrescripcion,D.Id_Paciente,D.Numero_Entrega,DATE(D.Fecha_Direccionamiento) HAVING Conteo>1 ORDER BY D.Id_Dispensacion_Mipres DESC"; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
    
    foreach ($productos as $p) {
        $id=$p['Id'];
        $tem=explode(',',$id);
        for ($i=0; $i <count($tem) ; $i++) { 
           if($i==0){
                $query='UPDATE Producto_Dispensacion_Mipres SET Id_Dispensacion_Mipres ='.$tem[0].'
                WHERE Id_Dispensacion_Mipres IN ('.$id.')';
                
                $oCon= new consulta();
                $oCon->setQuery($query);     
                $oCon->createData();     
                unset($oCon);
           }else{
                $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $tem[$i]); 
                $oItem->delete();
                unset($oItem);
           }
        }
    }
}
function ProgramarDireccionamientos($pres){
    global $mipres;
    $query="SELECT D.Id_Dispensacion_Mipres, D.Fecha_Maxima_Entrega ,PD.* 
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    WHERE D.Estado ='Pendiente' AND PD.NoPrescripcion = '".$pres."'
    AND DATE(D.Fecha) = CURDATE() 
    "; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $direccionamientos = $oCon->getData();
    unset($oCon);
    
    foreach ($direccionamientos as $pm) {
        $codigo_sede_mp=GetCodigoSede();
        $nit_mp=GetNitProh();
        
        $data_mp['ID']=(INT)$pm['ID'];
        $data_mp['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
        $data_mp['TipoIDSedeProv']='NI';
        $data_mp['NoIDSedeProv']=$nit_mp;
        $data_mp['CodSedeProv']=$codigo_sede_mp;
        $data_mp['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
        $data_mp['CantTotAEntregar']=$pm['Cantidad'];
        
        $respuesta=[];
        $respuesta=$mipres->Programacion($data_mp);
        
        //var_dump($respuesta);
        //echo "<br><br>";
        
        if($respuesta[0]['Id']){
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm["Id_Producto_Dispensacion_Mipres"]);
            $oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
            $oItem->Fecha_Programacion = date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            
            $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$pm["Id_Dispensacion_Mipres"]);
            $oItem->Estado="Programado";
            $oItem->save();
            unset($oItem);
            
            echo "-- ".$pm["NoPrescripcion"]." ID: ".(INT)$pm['ID']." - Programado Exitosamente<br>";
        }else{
            $error= $respuesta["Errors"][0];
            echo "-- ".$pm["NoPrescripcion"]." ID: ".(INT)$pm['ID']." - ".$error."<br>";
           
            
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
}

function GetCodigoSede(){
    $query = 'SELECT Codigo_Sede FROM Configuracion WHERE Id_Configuracion=1';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $dato = $oCon->getData();
    
    return $dato['Codigo_Sede'];
}

function GetNitProh(){
    $query = 'SELECT NIT FROM Configuracion WHERE Id_Configuracion=1';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $dato = $oCon->getData();

    $n=explode('-',$dato['NIT']);
    $nit=$n[0];
    $nit=str_replace('.','',$nit);
    return $nit;
    
}

?>