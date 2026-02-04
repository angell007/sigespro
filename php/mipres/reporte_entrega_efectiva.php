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

$query = 'SELECT PDM.Id_Producto_Dispensacion_Mipres, PDM.NoPrescripcion, PDM.ID, PDM.Codigo_Cum, PDM.Cantidad, PDM.IdProgramacion, PDM.IdEntrega, PDM.IdReporteEntrega
FROM Producto_Dispensacion_Mipres PDM 
INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
WHERE PDM.IdEntrega>0 AND (PDM.IdReporteEntrega IS NULL OR PDM.IdReporteEntrega = 0)
';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

 
$i=0;
$j=0;

echo "<table border='1' cellspacing='1' cellpadding='1'><tr><td>#</td><td>Prescripcion</td><td>ID</td><td>Id Programacion</td><td>Id Entrega</td><td>Id Reporte Entrega</td><td>Valor Reportado</td><td>Estado</td></tr>";
foreach($productos as $dir){ $i++;

    $query="SELECT D.Id_Dispensacion, SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada, SUM(PF.Cantidad) AS Cantidad_Factura, PF.Precio, PF.Impuesto
           FROM Producto_Dispensacion PD
           INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
           INNER JOIN Producto_Factura PF ON PF.Id_Producto_Dispensacion = PD.Id_Producto_Dispensacion
           INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
           WHERE   F.Estado_Factura != 'Anulada' 
           
           AND F.Nota_Credito IS NULL AND (
               
                ( F.Tipo='Factura') OR  (   F.Tipo LIKE 'Homologo' and F.Id_Factura_Asociada = 0  )
           )
           #AND F.Procesada='true' 
           AND PD.Id_Producto_Dispensacion_Mipres =".$dir["Id_Producto_Dispensacion_Mipres"]." 
           #AND F.Codigo NOT IN ('FENP24031','FENP24032','FENP24000','FENP23999','FENP23722','FENP23724','FENP23554','FENP23555','FEEP1682','FEEP1683','FENP22697','FENP22698','FENP22659','FENP22660','FENP21983','FENP21984','FENP21848','FENP21847','FENP21677','FENP21678','FENP21387','FENP21388','FENP20896','FENP20898','FENP20345','FENP20346','FENP19861','FENP19862','FENP12800','FENP12801','FENP3988','FENP3992','FENP3733','FENP3734','FENP2824','FENP2826','FENP1997','FENP1999','FENP1808','FENP1809','FENP1799','FENP1803','FENP1598','FENP1601','FENP978','FENP979','FENP931','FENP933','FENP795','FENP797','FENP698','FENP699','FENP688','FENP692','FENP632','FENP635','FENP949','FENP3589','NP129559','NP129561','NP128683','NP129402','NP128348','NP128352','NP128720','NP125271','NP125273','NP124464','FENP21845','NP122467','NP122469','NP122471','NP122324','NP122326','NP117739','FEEP247','NP116162','NP116163','NP112619','NP112621','NP117341','NP111648','NP111654','NP111645','NP116080','NP111644','NP111649','NP111643','NP111646','NP111647','NP111983','NP111642','NP111650','NP109304','NP109306','NP107651','NP107653','NP113997','NP107249','NP107251','NP106760','NP123804','NP106745','NP123625','NP106736','NP106737','NP124278','NP106728','NP124285','NP106608','NP106609','NP123805','NP106586','NP106587','NP106588','NP106589','NP106590','NP124282','NP106519','NP123629','NP106459','NP123628','NP106446','NP124230','NP106344','NP123494','NP106337','NP123479','NP106284','NP106285','NP124252','NP106015','NP106187','NP124254','NP106012','NP106013','NP123480','NP105956','NP105957','NP105962','NP106567','NP123478','NP105849','NP105850','NP105852','NP105853','NP105854','NP105856','NP105859','NP105861','NP105863','NP105864','NP105865','NP105867','NP105869','NP105871','NP105875','NP105882','NP123230','NP103347','NP123106','NP100854','NP101839','NP101930','NP104673','NP105087','NP105620','NP105713','NP108025','NP109123','NP110819','NP110890','NP111020','NP112191','NP112891','NP113815','NP114540','NP114546','NP114559','NP114575','NP114597','NP114680','NP117313','NP119139','NP119844','NP121014','NP121814','NP125286','NP129230','FENP1994','FENP2057','FENP2406','FENP2454','FENP4837','FENP4926','FENP9269')
    "; 
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    //$oCon->setTipo("Multiple");
    $dis = $oCon->getData();
    unset($oCon);
    
    $valor=number_format(($dis["Precio"]*$dis["Cantidad_Factura"])+($dis["Precio"]*($dis["Cantidad_Factura"]*$dis["Impuesto"]/100)),2,",","");
    
    echo "<tr><td>".$i."</td><td>".$dir["NoPrescripcion"]."</td><td>".$dir["ID"]."</td><td>".$dir["IdProgramacion"]."</td><td>".$dir["IdEntrega"]."</td>";
    if(isset($dis["Id_Dispensacion"])&&$dis["Id_Dispensacion"]!=''){ 
        $data['ID']=(INT)$dir['ID'];
        $data['EstadoEntrega']=1;
        $data['CausaNoEntrega']=0;
        $data['ValorEntregado']=$valor;
        $respuesta2=$mipres->ReportarEntregaEfectiva($data);
       
        if($respuesta2[0]['Id']){
            
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$dir['Id_Producto_Dispensacion_Mipres']);
            $oItem->IdReporteEntrega=$respuesta2[0]['IdReporteEntrega'];
            $oItem->Fecha_Reporte_Entrega=date("Y-m-d H:i:s");
            $oItem->Valor_Reportado=str_replace(",",".",$valor);
            $oItem->Actualizado = "Si";
            $oItem->save();
            unset($oItem);
            echo "<td>".$respuesta2[0]['IdReporteEntrega']."</td><td>".$valor."</td><td>REPORTE ENTREGA EFECTIVA EXITOSO</td>";
        }else{
            
            echo "<td>0</td><td>".$valor."</td><td>".$respuesta2["Errors"][0]."</td>";
        }
        
    }else{
        
        echo "<td>0</td><td>0</td><td>NO TIENE FACTURA ASOCIADA</td>";
    }
    echo "</tr>";
   
}
echo "<table>";              


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