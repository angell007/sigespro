<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('/home/sigesproph/public_html/config/start.inc.php');
include_once('/home/sigesproph/public_html/class/class.lista.php');
include_once('/home/sigesproph/public_html/class/class.complex.php');
include_once('/home/sigesproph/public_html/class/class.consulta.php');
require_once('/home/sigesproph/public_html/class//html2pdf.class.php');
include_once('../class/NumeroALetra.php');


include_once('/home/sigesproph/public_html/class/class.querybasedatos.php');
include_once('/home/sigesproph/public_html/class/class.http_response.php');
require_once('/home/sigesproph/public_html/class/class.configuracion.php');
include_once('/home/sigesproph/public_html/class/class.mipres.php');

$mipres= new Mipres();
$queryObj = new QueryBaseDatos();

$query = 'SELECT PDM.ID, PDM.Id_Producto_Dispensacion_Mipres, DM.Id_Dispensacion_Mipres, PDM.NoPrescripcion, PDM.Cantidad AS CantTotAEntregar, PDM.CodSerTecAEntregar, DM.Fecha_Maxima_Entrega AS FecMaxEnt
FROM Producto_Dispensacion_Mipres PDM
INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
WHERE DM.Fecha >= "2025-03-30 00:00:00" AND PDM.IdProgramacion =0';

/*SELECT *
FROM A_Entrega_Mipres_2025 E
WHERE
E.Estado=1
*/
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
//var_dump($productos);
unset($oCon);

$codigo_sede=GetCodigoSede();
$nit=GetNitProh();

$i=0;

echo "<table border='1' cellspacing='1' cellpadding='1'><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>ID Programacion</td><td>Id Entrega</td><td>Id Entrega Efectiva</td><td>Estado</td></tr>";
foreach($productos as $pm){ $i++;

    // PROGRAMACION
    
    $data['ID']=(INT)$pm['ID'];
    $data['FecMaxEnt']=$pm['FecMaxEnt'];
    $data['TipoIDSedeProv']='NI';
    $data['NoIDSedeProv']=$nit;
    $data['CodSedeProv']=$codigo_sede;
    $data['CodSerTecAEntregar']=$pm['CodSerTecAEntregar']; // CodigoDireccionado //CodSerTecAEntregar
    $data['CantTotAEntregar']=$pm['CantTotAEntregar'];  //vCantidadDireccionada //CantidadReal
    $respuesta=$mipres->Programacion($data);
    
    // ENTREGA
    
  /* $data['ID']=(INT)$pm['ID'];
    $data['CodSerTecEntregado']= $pm["CodigoDireccionado"];
    $data['CantTotEntregada']=$pm['CantidadReal'];
    $data['EntTotal']=0;
    $data['CausaNoEntrega']=0;
    $data['FecEntrega']= '2025-03-05';
    $data['NoLote']='0000';
    $data['TipoIDRecibe']=$pm['TipoIDPaciente'];
    $data['NoIDRecibe']= $pm['NoIDPaciente'];
    $respuesta=$mipres->ReportarEntrega($data);*/
    
    
    
    // ENTREGA EFECTIVA
    
    /*$pre = (strpos($pm["TALLA"],"ETAPA")===false ? 2891.7 : 1225.7);
    
    $valor = number_format((INT)$pm['CantidadReal'] * $pre,2,",","");
    $data['ID']=(INT)$pm['ID'];
    $data['EstadoEntrega']=1;
    $data['CausaNoEntrega']=0;
    $data['ValorEntregado']=$valor;
    $respuesta=$mipres->ReportarEntregaEfectiva($data);*/
    
    if($respuesta[0]['Id']){ 
        
        //$oItem=new complex('A_Entrega_Mipres_2025','Id_Entrega_Mipres',$pm['Id_Entrega_Mipres']);
        $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
        $oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
        $oItem->Fecha_Programacion=date("Y-m-d H:i:s");
        
        
       /* $oItem->IDEntrega=$respuesta[0]['IdEntrega']; 
        $oItem->Fecha_Entrega=date("Y-m-d H:i:s");*/
        
        
        /*$oItem->IDReporteEntrega=$respuesta[0]['IdReporteEntrega'];
        $oItem->Fecha_Reporte_Entrega=date("Y-m-d H:i:s");*/
        
        $oItem->Actualizado=1;
        //$oItem->EstadoDireccionamiento='Programado';
        $oItem->save();
        unset($oItem);
        
        $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$pm['Id_Dispensacion_Mipres']);
        $oItem->Estado='Programado';
        $oItem->save();
        unset($oItem);
        
    	
        echo "<tr><td>".$i."</td><td>".$pm["NoPrescripcion"]."</td><td>".(INT)$pm['ID']."</td><td>".$pm['IDProgramacion']."</td><td>".$pm['IDEntrega']."</td><td>".$respuesta[0]['IdReporteEntrega']."</td><td>Programado</td></tr>";
    }else{
        //var_dump($respuesta);
        $error= $respuesta["Errors"][0];
        echo "<tr><td>".$i."</td><td>".$pm["NoPrescripcion"]."</td><td>".(INT)$pm['ID']."</td><td>ERROR</td><td colspan='3'>".$error."</td></tr>";
        
        
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