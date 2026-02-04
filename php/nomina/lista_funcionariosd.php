<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$oItem = new complex("Configuracion","Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);

$salario_minimo = $config["Salario_Base"];
$auxilio_transporte = $config["Subsidio_Transporte"];
$maximo_liquidacion = $config["Maximo_Cotizacion"];

$salario_maximo = $salario_minimo * $maximo_liquidacion;

if(date("Y-m-d")<=date("Y-m-15")){
   $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-01" );
   $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-15") );
   $quincena=1;
}else{
   $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-15" );
   $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1))); 
   $quincena=2;
}

$dias=15;

$condicion = '';

$condicion.=' WHERE F.Fecha_Ingreso <="'.$ffin.'" AND F.Fecha_Retiro>="'.$ffin.'" AND F.Tipo="Propio"';

if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
$condicion .= " AND CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%$_REQUEST[funcionario]%'";
}

if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
$condicion .= " AND F.Id_Grupo = $_REQUEST[grupo]";
}


$query = 'SELECT COUNT(DISTINCT(F.Identificacion_Funcionario))  AS Total
          FROM Funcionario F
          LEFT JOIN Cargo C 
          on F.Id_Cargo=C.Id_Cargo 
          LEFT JOIN Dependencia D 
          on D.Id_Dependencia = F.Id_Dependencia 
          LEFT JOIN Grupo G 
          on G.Id_Grupo = F.Id_Grupo
          ' . $condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
} 

$query = 'SELECT F.*, 
          CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario, 
          (Select SUM(Valor) 
          FROM Movimiento_Funcionario ME 
          INNER JOIN Tipo_Ingreso TI ON ME.Id_Tipo=TI.Id_Tipo_Ingreso 
          WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Ingreso" AND TI.Tipo="Prestacional" AND ME.Quincena="'.date("Y-m;").$quincena.'") as Ingresos_S,
          
          (Select SUM(Valor) 
          FROM Movimiento_Funcionario ME 
          INNER JOIN Tipo_Ingreso TI ON ME.Id_Tipo=TI.Id_Tipo_Ingreso 
          WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Ingreso" AND TI.Tipo="No_Prestacional" AND ME.Quincena="'.date("Y-m;").$quincena.'") as Ingresos_N,
          
          (Select SUM(Valor) 
          FROM Movimiento_Funcionario ME 
          WHERE ME.Identificacion_Funcionario=F.Identificacion_Funcionario AND ME.Tipo="Egreso" AND ME.Quincena="'.date("Y-m;").$quincena.'") as Egresos,
          
          C.Nombre as Cargo , 
          D.Nombre as Dependencia , 
          G.Nombre as Grupo
          FROM Funcionario F
          LEFT JOIN Cargo C 
          on F.Id_Cargo=C.Id_Cargo 
          LEFT JOIN Dependencia D 
          on D.Id_Dependencia = F.Id_Dependencia 
          LEFT JOIN Grupo G 
          on G.Id_Grupo = F.Id_Grupo
          '.$condicion.'
          ORDER BY Funcionario
          ';
//echo $query;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);

$total_sueldos=0;
$total_bonos=0;
$total_deducciones=0;

$i=-1;
foreach($funcionarios as $func){ $i++;
    $query2 = 'SELECT TI.*, MF.Valor, MF.Id_Movimiento_Funcionario
    FROM Tipo_Ingreso TI
    LEFT JOIN Movimiento_Funcionario MF
    ON TI.Id_Tipo_Ingreso = MF.Id_Tipo AND MF.Tipo="Ingreso" AND MF.Identificacion_Funcionario='.$func["Identificacion_Funcionario"].' AND MF.Quincena="'.date("Y-m;").$quincena.'"
    WHERE TI.Tipo="Prestacional" 
    ';     
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query2);
    $ingresos_salariales = $oCon->getData();
    unset($oCon);
    
    $query3 = 'SELECT TI.*, MF.Valor, MF.Id_Movimiento_Funcionario
    FROM Tipo_Ingreso TI
    LEFT JOIN Movimiento_Funcionario MF
    ON TI.Id_Tipo_Ingreso = MF.Id_Tipo AND MF.Tipo="Ingreso" AND MF.Identificacion_Funcionario='.$func["Identificacion_Funcionario"].' AND MF.Quincena="'.date("Y-m;").$quincena.'"
    WHERE TI.Tipo="No_Prestacional" 
    ';     
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query3);
    $ingresos_no_salariales = $oCon->getData();
    unset($oCon);
    
    $query4 = 'SELECT TE.*, MF.Valor, MF.Id_Movimiento_Funcionario
    FROM Tipo_Egreso TE
    LEFT JOIN Movimiento_Funcionario MF
    ON TE.Id_Tipo_Egreso = MF.Id_Tipo AND MF.Tipo="Egreso" AND MF.Identificacion_Funcionario='.$func["Identificacion_Funcionario"].' AND MF.Quincena="'.date("Y-m;").$quincena.'"
    ';     
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query4);
    $lista_egresos = $oCon->getData();
    unset($oCon);
    

    //var_dump($ingresos_salariales); exit;
    $aux_trans = 0;
    $sueldo_dia = $func["Salario"]/30;
    $salario_quincena= $sueldo_dia*$dias;
    if($func["Salario"]>$salario_maximo){
        $deduccion_salud=($salario_maximo/2)*4/100;
        $deduccion_pension=($salario_maximo/2)*4/100;
    }elseif($func["Salario"]<=($salario_minimo*2)){
        $deduccion_salud=$salario_quincena*4/100;
        $deduccion_pension=$salario_quincena*4/100;
        $aux_trans = ($auxilio_transporte/2);
    }else{
        $deduccion_salud=$salario_quincena*4/100;
        $deduccion_pension=$salario_quincena*4/100;
    }
    
    $egresos=(INT)$func["Egresos"];
    $bonos=(INT)$func["Auxilio_No_Salarial"];
    $ingresos_ns= (INT)$func["Ingresos_N"];
    $funcionarios[$i]["Ingresos_S"]= (INT)$func["Ingresos_S"];
    $funcionarios[$i]["Salario_Quincena"]=(INT)number_format($salario_quincena,0,"","");
    $funcionarios[$i]["Salario_Dia"]=(INT)number_format($sueldo_dia,0,"","");
    $funcionarios[$i]["Deduccion_Salud"]=(INT)number_format($deduccion_salud,0,"","");
    $funcionarios[$i]["Deduccion_Pension"]=(INT)number_format($deduccion_pension,0,"","");
    $funcionarios[$i]["Egresos"]=(INT)number_format($egresos,0,"","");
    
    $funcionarios[$i]["Lista_Ingresos_Salariales"]=$ingresos_salariales;
    $funcionarios[$i]["Lista_Ingresos_No_Salariales"]=$ingresos_no_salariales;
    $funcionarios[$i]["Lista_Egresos"]=$lista_egresos;
    
    
    $total_sueldos+=$salario_quincena;
    $total_deducciones+=($deduccion_salud+$deduccion_pension+$egresos);
    if($quincena==2){
      $ingresos_ns+=(INT)number_format($bonos,0,"","");  
    }
    $funcionarios[$i]["Ingresos_NS"]=(INT)number_format($ingresos_ns+$aux_trans,0,"","");
    $total_bonos+=$ingresos_ns+$aux_trans;
}

$resultado['Funcionarios'] = $funcionarios;
$resultado['numReg'] = $numReg;
$resultado['Total_Auxilio'] = (INT)number_format($total_bonos,0,"","");
$resultado['Total_Sueldos'] = (INT)number_format($total_sueldos,0,"","");
$resultado['Total_Deducciones'] = (INT)number_format($total_deducciones,0,"","");

echo json_encode($resultado);

?>
