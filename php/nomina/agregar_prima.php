<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

if(date("Y-m-d")<=date("Y-m-15")){
    $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-01" );
    $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-15") );
    $quincena=1;
 }else{
    $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-16" );
    $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1))); 
    $quincena=2;
 }
 
$query='SELECT * FROM Movimiento_Funcionario WHERE Tipo="Prima" AND Quincena LIKE "%'.date('Y-m').'%"' ;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$primas = $oCon->getData();
unset($oCon);
if(count($primas)==0){
    
        $query = 'SELECT F.Identificacion_Funcionario, F.Imagen, CF.Valor as Salario
        FROM Contrato_Funcionario CF 
        INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario        
        WHERE F.Autorizado="Si" AND CF.Estado="Activo"
        GROUP BY CF.Identificacion_Funcionario ';

        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $funcionarios = $oCon->getData();
        unset($oCon);

        $i=-1;
        foreach($funcionarios as $func){ $i++;

        $query="SELECT SUM(Valor) as Prima FROM Provision_Funcionario WHERE Tipo='Prima' AND Estado='Pendiente' AND Identificacion_Funcionario=".$func['Identificacion_Funcionario'];
        $oCon= new consulta();
        $oCon->setQuery($query);
        $valor = $oCon->getData();
        unset($oCon);

        if($valor['Prima']){
            $oItem = new complex("Movimiento_Funcionario","Id_Movimiento_Funcionario");
            $oItem->Tipo='Prima';
            $oItem->Identificacion_Funcionario=$func['Identificacion_Funcionario'];
            $oItem->Valor=round($valor['Prima'],-2);
            $oItem->Quincena=date("Y-m").";".$quincena;
            $oItem->save();
            unset($oItem);
        }

        $query="UPDATE Provision_Funcionario SET Estado='Pagadas' WHERE Tipo='Prima' AND Estado='Pendiente' AND Identificacion_Funcionario=".$func['Identificacion_Funcionario'];
        $oCon= new consulta();
        $oCon->setQuery($query);     
        $oCon->createData();     
        unset($oCon);

    }

    $resultado['Titulo']="Operacion exitosa";
    $resultado['Mensaje']="La prima fue incluidad correctamente";
    $resultado['Tipo']="success";

}else{
    $resultado['Titulo']="Upps! Operacion fallida";
    $resultado['Mensaje']="La prima ya fue incluida anteriormente, por favor revise";
    $resultado['Tipo']="error";
}



echo json_encode($resultado);

?>

