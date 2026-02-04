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

$query = 'SELECT PDM.Id_Producto_Dispensacion_Mipres, PDM.NoPrescripcion, PDM.ID, PDM.Codigo_Cum, PDM.Cantidad, PDM.IdProgramacion, PDM.IdEntrega, PDM.IdReporteEntrega, PDM.Tipo_Tecnologia, PDM.ConTec,
                DM.Numero_Entrega, DM.CodEPS, DM.NoIDEPS, DM.Cum_Reportado
FROM Producto_Dispensacion_Mipres PDM 
INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres
WHERE PDM.IdReporteEntrega>0
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

    $query="SELECT D.Id_Dispensacion, D.Numero_Documento ,SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada,
    SUM(PF.Cantidad) AS Cantidad_Factura, PF.Precio, PF.Impuesto,
                    P.Tipo_Documento, PRD.CantUnMinDis
                    IFNULL( F.Cufe , F.Codigo) AS Codigo_Factura
           FROM Producto_Dispensacion PD
           INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
           INNER JOIN Producto_Factura PF ON PF.Id_Producto_Dispensacion = PD.Id_Producto_Dispensacion
           INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
           INNER JOIN Paciente P ON P.Id_Paciente ON D.Numero_Documento
           INNER JOIN Producto PRD ON PRD.Id_Producto = PD.Id_Producto 
           
           WHERE   F.Estado_Factura != 'Anulada' 
           
           AND F.Nota_Credito IS NULL AND (
               
                ( F.Tipo='Factura') OR  (   F.Tipo LIKE 'Homologo' and F.Id_Factura_Asociada = 0  )
           )
           
           #AND F.Procesada='true' 
           AND PD.Id_Producto_Dispensacion_Mipres =".$dir["Id_Producto_Dispensacion_Mipres"]." 
     "; 
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    //$oCon->setTipo("Multiple");
    $dis = $oCon->getData();
    unset($oCon);
    
    $valor = number_format(($dis["Precio"]*$dis["Cantidad_Factura"])+($dis["Precio"]*($dis["Cantidad_Factura"]*$dis["Impuesto"]/100)),2,",","");
    
    $valor_unitario = number_format(  $dis["Precio"] * ( $dis["Impuesto"]/100 ) ,2,",","");
    
    echo "<tr><td>".$i."</td><td>".$dir["NoPrescripcion"]."</td><td>".$dir["ID"]."</td><td>".$dir["IdProgramacion"]."</td><td>".$dir["IdEntrega"]."</td>";
    
    if(isset($dis["Id_Dispensacion"])&&$dis["Id_Dispensacion"]!=''){ 
        
        //DATOS PUT API
        $data['NoPrescripcion'] = $dir['NoPrescripcion'];
        $data['TipoTec'] = $dir['Tipo_Tecnologia'];
        $data['ConTec'] = (INT)$dir['ConTec'];
        $data['TipoIDPaciente'] = $dis['Tipo_Documento'];
        $data['NoIDPaciente'] = $dis['Numero_Documento'];
        $data['NoEntrega'] = (INT)$dir['Numero_Entrega'];
        //NoSubEntrega
        $data['NoFactura'] = $dis['Codigo_Factura'];
        $data['NoIDEPS'] = $dir['NoIDEPS'];
        $data['CodEPS'] = $dir['CodEPS'];
        
        $data['CodSerTecAEntregado'] = $dir['Cum_Reportado']; 
        $data['CantUnMinDis'] = $dis['CantUnMinDis'];
        $data['ValorUnitFacturado'] = $valor_unitario;
        $data['ValorTotFacturado'] = $valor;
        $data['CuotaModer'] = $dir[''];
        $data['Copago'] = $dir[''];
       
        
        
      /*  $data['EstadoEntrega']=1;
        $data['CausaNoEntrega']=0;
        $data['ValorEntregado']=$valor;*/
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