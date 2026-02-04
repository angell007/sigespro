<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query="SELECT ROUND(Salario_Base*2) as Maximo_Salario, Subsidio_Transporte 
        FROM Configuracion 
        WHERE Id_Configuracion=1";
$oCon= new consulta();
$oCon->setQuery($query);
$conf = $oCon->getData();
unset($oCon);
 
$hoy = date("Y-m-d");
$query = 'SELECT CF.*, F.Identificacion_Funcionario, F.Imagen, CF.Valor as Salario,CONCAT(F.Nombres," ", Apellidos) as Funcionario
          FROM Contrato_Funcionario CF 
          INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario        
          WHERE F.Liquidado="No" AND CF.Estado="Activo" AND CF.Id_Tipo_Contrato < 5 
          AND (CF.Fecha_Fin_Contrato>="'.$hoy.'" OR CF.Fecha_Fin_Contrato IS NULL OR CF.Fecha_Fin_Contrato="0000-00-00")
          GROUP BY CF.Identificacion_Funcionario
          ORDER BY F.Nombres ASC 
          ';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);
$i=-1;

$total_prima=0;
foreach($funcionarios as $func){ $i++;
    $fechahoy=$fechahoy=date("Y-m-30");
    $mes=date("m",strtotime($func['Fecha_Inicio_Contrato']));
    $dias_des=0;
    if($mes=="01"){
        $dias_des=3;
    }elseif($mes=="02" || $mes=="03"){
        $dias_des=2;
    }elseif($mes=="04"|| $mes=="05"){
        $dias_des=1;
    }

    if($mes=="07"){
        $dias_des=3;
    }elseif($mes=="08"){
        $dias_des=2;
    }elseif($mes=="09"|| $mes=="10"){
        $dias_des=1;
    }

    $fechainicio= new DateTime($func['Fecha_Inicio_Contrato']);
    $fechahoy= new DateTime($fechahoy);
    $dias_trabajados = $fechainicio->diff($fechahoy);
    $dias_trabajados= $dias_trabajados->format('%R%a');
    $dias_trabajados=trim($dias_trabajados,'+');   
    $dias_trabajados=$dias_trabajados+3-$dias_des;

    if($dias_trabajados>180){
        $dias_trabajados=180;
    }

    if($func['Salario']<=$conf['Maximo_Salario']){
        $valor_prima=round((($dias_trabajados*($func['Salario']+$conf['Subsidio_Transporte']))/360),0);
        $funcionarios[$i]['Salario']=$funcionarios[$i]['Salario']+($conf['Subsidio_Transporte']);
    }else{
        $valor_prima=round((($dias_trabajados*$func['Salario'])/360),0);
    }
    
    $funcionarios[$i]['Dias_Trabajados']=$dias_trabajados;
    $funcionarios[$i]['Valor_Prima']=$valor_prima;
    $total_prima+= $valor_prima;
}

$resultado['Funcionarios']=$funcionarios;
$resultado['Total_Prima']=$total_prima;
$resultado['Total_Funcionarios']=count($funcionarios);
       

echo json_encode($resultado);

?>

