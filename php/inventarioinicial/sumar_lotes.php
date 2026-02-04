<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$query='SELECT Id_Producto, Lote, COUNT(Lote), SUM(Cantidad) as Total, GROUP_CONCAT(Id_Inventario_Inicial as Id, GROUP_CONCAT(Codigo) as Codigo  FROM Inventario_Inicial GROUP BY Id_Producto, Lote HAVING COUNT(Lote)>1';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);


foreach($resultado as $result){
    $vari=explode(',',$result['Id']);  
    $codigo=unset($result['Codigo']);
    $codigo=unset($codigo[0]);
    for ($i=0; $i < count($vari); $i++) { 
            if($i==0){
                $oItem=new complex('Inventario_Inicial', 'Id_Inventario_Inicial', $vari[$i]);
                $oItem->Cantidad=$result['Total'];                
                $oItem->Alternativo=$codigo;                
                $oItem->save();
                unset($oItem);
            }else{                        
                $oItem = new complex('Inventario_Inicial', 'Id_Inventario_Inicial', $vari[$i]);
                $oItem->delete();
                unset($oItem);
            }
        }
}

?>