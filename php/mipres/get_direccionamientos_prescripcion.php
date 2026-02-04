<?php
ini_set("memory_limit","256M");
ini_set('max_execution_time', 480);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.mipres.php');

$queryObj = new QueryBaseDatos();

$mipres= new Mipres();

$prescripcion = ( isset( $_REQUEST['prescripcion'] ) ? $_REQUEST['prescripcion'] : '' );

$dishoy=$mipres->GetDireccionamientoPorPrescripcion($prescripcion);
    
usort($dishoy,'OrderByNumeroEntrega');

//var_dump($dishoy);

$i=-1;
foreach($dishoy as $d){ $i++;
        $query="SELECT *
        FROM Producto_Dispensacion_Mipres PDM 
        WHERE PDM.ID =".$d["ID"]." LIMIT 1"; 
        $oCon = new consulta();
        $oCon->setQuery($query);
        $uno = $oCon->getData();
        unset($oCon);
        if($uno){
            $dishoy[$i]["Existe"]="Si";
        }else{
            $dishoy[$i]["Existe"]="No";
        }
        
        if($d["EstDireccionamiento"]==0){
           $dishoy[$i]["Estado"]="Anulado"; 
        }elseif($d["EstDireccionamiento"]==1){
           $dishoy[$i]["Estado"]="Activo"; 
        }if($d["EstDireccionamiento"]==2){
           $dishoy[$i]["Estado"]="Procesado"; 
        }
        if($d["TipoTec"]=="M"){
            $tem=explode('-',$d["CodSerTecAEntregar"]);
            $cum2=$tem[0].'-'.(INT)$tem[1];
            $query="SELECT *
            FROM Producto P
            WHERE P.Codigo_Cum LIKE '%".$cum2."%' LIMIT 1"; 
            $oCon = new consulta();
            $oCon->setQuery($query);
            $dos = $oCon->getData();
            unset($oCon);
            if($dos){
                $dishoy[$i]["Producto"]=$dos["Nombre_Comercial"];
            }else{
                $dishoy[$i]["Producto"]="";
            }
        }else{
            $query="SELECT *
            FROM Producto_Tipo_Tecnologia_Mipres P
            WHERE P.Codigo_Actual LIKE '".$d["CodSerTecAEntregar"]."' LIMIT 1"; 
            $oCon = new consulta();
            $oCon->setQuery($query);
            $dos = $oCon->getData();
            unset($oCon);
            if($dos){
                $dishoy[$i]["Producto"]=$dos["Descripcion"];
            }else{
                $dishoy[$i]["Producto"]="";
            }
        }
        if($d["CodMunEnt"]!=''){
            
            $query="Select M.Nombre as Municipio, D.Nombre as Departamento FROM Municipio M INNER JOIN Departamento D ON D.Id_Departamento = M.Id_Departamento WHERE M.Codigo=".$d["CodMunEnt"]."";
    
            $oCon = new consulta();
            $oCon->setQuery($query);
            $municipio = $oCon->getData();
            unset($oCon);
        
            if($municipio){
                $dishoy[$i]["Municipio"]=$municipio["Municipio"];
                $dishoy[$i]["Departamento"]=$municipio["Departamento"];
            }else{
                $dishoy[$i]["Municipio"]="";
                $dishoy[$i]["Departamento"]="";
            }
    
        }
    
}

echo json_encode($dishoy);



function OrderByNumeroEntrega($a,$b){
    return strnatcmp($a['NoEntrega'],$b['NoEntrega']);
}

?>