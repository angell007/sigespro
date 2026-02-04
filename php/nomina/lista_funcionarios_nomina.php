<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.nomina.php');
include_once('../../class/class.parafiscales.php');
include_once('../../class/class.provisiones.php');

$fini   = isset($_REQUEST['fini']) ? $_REQUEST['fini'] : '';
$ffin   = isset($_REQUEST['ffin']) ? $_REQUEST['ffin'] : '';
$nomina = isset($_REQUEST['nomina']) ? $_REQUEST['nomina'] : '';
$nom    = isset($_REQUEST['nom']) ? $_REQUEST['nom'] : '';

// Se extraen los componentes de la fecha inicial
$d = explode("-", $fini);

// Se obtiene el mes, año y día actuales
$mes_actual = date('m', strtotime($fini));
$anio_actual = date('Y', strtotime($fini));
$dia_actual = date('d', strtotime($fini));
$dia_fin = date('d', strtotime($ffin));

// Si el día de fin es mayor a 30, se ajusta a 30 (mismo comportamiento del primer código)
if ($dia_fin > 30) {
    $dia_fin = 30;
}

$concepto = '';

// Si la nómina es mensual, se ajusta el concepto
if ($nom == 'Mensual') {
    $concepto = "$anio_actual-$mes_actual%";
    $quincena = "%"; // Para el caso mensual
} else {
    // Si la fecha es antes o después del 15 del mes, se ajusta la quincena
    $quincena = ($d[2] <= 15) ? "1" : "2";

    // Si se pasó una nómina, se utiliza esa, de lo contrario se genera una con el año y la quincena
    $concepto = isset($_REQUEST['nomina']) ? $_REQUEST['nomina'] : "$anio_actual-$mes_actual;$quincena";
}

// Variable para el número de días (con valor fijo como en el primer código)
$dias = 15;

$condicion = '';

// Filtros adicionales de funcionario y grupo
if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
    $condicion .= " AND CONCAT(F.Nombres, ' ', F.Apellidos) LIKE '%$_REQUEST[funcionario]%'";
}

if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
    $condicion .= " AND F.Id_Grupo = $_REQUEST[grupo]";
}



// Consulta para obtener el total de registros
$query = 'SELECT COUNT(DISTINCT(F.Identificacion_Funcionario)) AS Total
           FROM Contrato_Funcionario CF 
          INNER JOIN Funcionario F ON CF.Identificacion_Funcionario = F.Identificacion_Funcionario
          LEFT JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo 
          LEFT JOIN Dependencia D ON D.Id_Dependencia = F.Id_Dependencia 
          LEFT JOIN Grupo G ON G.Id_Grupo = F.Id_Grupo
          WHERE CF.Estado = "Activo" 
            AND F.Liquidado = "NO" 
            AND NOT EXISTS (SELECT * FROM Nomina_Funcionario WHERE Periodo_Pago LIKE "' . $concepto . '" AND Identificacion_Funcionario = F.Identificacion_Funcionario)
            AND CF.Fecha_Fin_Contrato >= "' . $fini . '" 
            AND CF.Fecha_Inicio_Contrato <= "' . $ffin . '" 
            ' . $condicion; 

$oCon = new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

// PAGINACIÓN
$tamPag = 20;
$numReg = $total["Total"];
$paginas = ceil($numReg / $tamPag);
$limit = "";
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') {
    $paginaAct = 1;
    $limit = 0;
} else {
    $paginaAct = $_REQUEST['pag'];
    $limit = ($paginaAct - 1) * $tamPag;
}

// Consulta para obtener los funcionarios
$query = 'SELECT F.Identificacion_Funcionario, F.Imagen, CF.Valor AS Salario,
                 CONCAT(F.Nombres, " ", F.Apellidos) AS Funcionario,     
                 C.Nombre AS Cargo, 
                 D.Nombre AS Dependencia, 
                 G.Nombre AS Grupo, CF.Fecha_Inicio_Contrato, CF.Fecha_Fin_Contrato, 
                 F.Id_Banco, F.Cuenta, CF.Auxilio_No_Prestacional
          FROM Contrato_Funcionario CF 
          INNER JOIN Funcionario F ON CF.Identificacion_Funcionario = F.Identificacion_Funcionario
          LEFT JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo 
          LEFT JOIN Dependencia D ON D.Id_Dependencia = F.Id_Dependencia 
          LEFT JOIN Grupo G ON G.Id_Grupo = F.Id_Grupo
          WHERE CF.Estado = "Activo" 
            AND F.Liquidado = "NO" 
            AND NOT EXISTS (
                SELECT * FROM Nomina_Funcionario 
                WHERE Periodo_Pago LIKE "' . $concepto . '" 
                  AND Identificacion_Funcionario = F.Identificacion_Funcionario
            ) 
            AND CF.Fecha_Fin_Contrato >= "' . $fini . '" 
            AND CF.Fecha_Inicio_Contrato <= "' . $ffin . '" 
            ' . $condicion . '
          GROUP BY CF.Identificacion_Funcionario 
          ORDER BY Funcionario';


$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);

$total_deducciones = 0;
$total_sueldos = 0;
$total_auxilio = 0;
$total_efectivo = 0;
$total_banco = 0;

$i = -1;

foreach($funcionarios as $func){ $i++;

        $ini = $fini;
        if($func['Fecha_Inicio_Contrato']>$fini){
            $ini=$func['Fecha_Inicio_Contrato'];
        }
        $fin = $ffin;
        if($func['Fecha_Fin_Contrato']>=$fini && $func['Fecha_Fin_Contrato']<$ffin ){

            $fin=$func['Fecha_Fin_Contrato'];
        }
        
        

        $funcionario=new CalculoNomina($func['Identificacion_Funcionario'], $quincena, $ini, $fin, 'Nomina', $nom);
        $funcionario=$funcionario->CalculosNomina();   
    
        $paraficales=new CalculosParafiscales($funcionario['Total_IBC'],$funcionario['Sueldo'],$func['Identificacion_Funcionario']);  
        $paraficales=$paraficales->CalcularParafiscales();
        
        
        $base_aux = $funcionario['Salario_Quincena'] + $funcionario['Auxilio'] + $funcionario['Total_Incapacidades'];
        $provisiones=new CalculosProvisiones($base_aux,$func['Identificacion_Funcionario'],$ini,$fin, $funcionario['Salario_Quincena']);  
        $provisiones=$provisiones->CalcularProvisiones();

    
        $funcionarios[$i]["Provision"]=$provisiones['Provision'];
        $funcionarios[$i]["Ingresos_S"]= number_format((INT)$funcionario["Ingresos_S"],0,"","");
        $funcionarios[$i]["Ingresos_NS"]=number_format((INT)$funcionario["Ingresos_NS"],0,"","");
        $funcionarios[$i]["Deducciones"]=(INT)number_format($funcionario["Deducciones"],0,"","");
        $funcionarios[$i]["Total_Quincena"]=(INT)number_format($funcionario["Total_Quincena"], 0, "", "");
       $funcionarios[$i]["Dias_Laborados"] = (INT)$funcionario["Dias_Laborados"];
        $funcionarios[$i]["Resumen"]=$funcionario["Resumen"];
        $total_deducciones+=(INT)$funcionario["Deducciones"];
        $total_sueldos+=$funcionario["Salario_Neto"];
        
        $total_auxilio+=$funcionario["Auxilio"];
        $funcionarios[$i]['Lista_Ingresos_Salariales']=$funcionario['Lista_Ingresos_Salariales'];
        $funcionarios[$i]['Lista_Ingresos_No_Salariales']=$funcionario['Lista_Ingresos_No_Salariales'];

        $funcionarios[$i]['Lista_Egresos']= $funcionario['Lista_Egresos']; 
        //$funcionarios[$i]['Lista_Egresos']=GetIngresosSalariales($func['Identificacion_Funcionario'],$quincena,'Egresos'); 

        $funcionarios[$i]['Conceptos_Renta']=GetConceptos($func['Identificacion_Funcionario']);
        $funcionarios[$i]['Salario_Mes']=(INT)number_format($funcionario["Salario_Mes"],0,"","");
        $funcionarios[$i]['Conceptos_Contabilizacion']=$funcionario["Conceptos_Contabilizacion"];
        $funcionarios[$i]['Conceptos_Contabilizacion_Parafiscales']=$paraficales["Contabilizacion"];
        $funcionarios[$i]['Conceptos_Contabilizacion_Provision']=$provisiones["Contabilizacion"];
        $funcionarios[$i]['Vacaciones_Acumuladas']=$provisiones["Vacaciones_Acumuladas"];
        
       // ObtenerFechas();

       if ($funcionario['Id_Banco'] != '' && $funcionario['Cuenta'] != '') {
           $total_banco += ($funcionario["Total_Quincena"]-(($func['Auxilio_No_Prestacional']/30)*$funcionario['Dias_Laborados'])) + (($func['Auxilio_No_Prestacional']/30)*$funcionario['Dias_Laborados']);
       } else {
           $total_efectivo += (($func['Auxilio_No_Prestacional']/30)*$funcionario['Dias_Laborados']);
       }  
}

$query='SELECT *, DATE(Fecha) as Fecha FROM Prima WHERE Fecha LIKE "%'.date('Y-m').'%"' ;


$oCon= new consulta();
$oCon->setQuery($query);
$prima = $oCon->getData();
unset($oCon);

$resultado['Funcionarios'] = $funcionarios;
$resultado['numReg'] = $numReg;
$resultado['Total_Auxilio'] = (INT)number_format($total_auxilio,0,"","");
$resultado['Total_Sueldos'] = (INT)number_format($total_sueldos,0,"","");
$resultado['Total_Deducciones'] = (INT)number_format($total_deducciones,0,"","");
$resultado["Fecha_Quincena"]= CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual);
$resultado["Quincena"]= $quincena;
$resultado["Total_Efectivo"]= $total_efectivo;
$resultado["Total_Banco"]= $total_banco;
$resultado["Prima"]= $prima;
echo json_encode($resultado);

function CalcularFechaQuincena($dia_actual, $mes_actual, $anio_actual){
    

    if ($dia_actual > 15) {

        $fechas = ArmarFecha($mes_actual, $anio_actual);        
        $fecha_quincena = $fechas['quincena2'];

        return $fecha_quincena;
    }else{
       
       // $mes_anio_anterior = CalcularMes($mes_actual, 1, $anio_actual);
        $mes_anio_actual = CalcularMes($mes_actual, 0, $anio_actual);

       // $fechas = ArmarFecha($mes_anio_anterior['mes'], $mes_anio_anterior['anio']);
        $fechas2 = ArmarFecha($mes_anio_actual['mes'], $mes_anio_actual['anio']);
       
        $fecha_quincena = $fechas2['quincena1'];

        return $fecha_quincena;
    }

}
function ArmarFecha($mes, $anio, $ColocarCeroAlMes = false){
    $fechas = array();

    if ($ColocarCeroAlMes) {
        
        $mes = MesDosDigitos($mes);
    }else{
        $mes = $mes;
    }

    $fechas['quincena1'] = array('inicio' => $anio."-".$mes."-01", 'fin' => $anio."-".$mes."-15");
    $fechas['quincena2'] = array('inicio' => $anio."-".$mes."-16", 'fin' => $anio."-".$mes."-". date("d",(mktime(0,0,0,date($mes)+1,1,date($anio))-1)));

    return $fechas;
}
function MesDosDigitos($mes){
    if ($mes < 10) {
        return "0".$mes;
    }

    return $mes;
}
function CalcularMes($mes_actual, $restar_meses, $anio){

    $mes = $mes_actual - $restar_meses;
    $anio = $anio;

    if ($mes <= 0) {
        $mes = $mes + 12;
        $anio = $anio - 1;      
    }else{
        $mes = $mes;
    }

    return array('anio' => $anio, 'mes' => MesDosDigitos($mes));
}

function GetConceptos($id){
    $query='SELECT Nombre,Prefijo, Id_Concepto_Retencion_Fuente FROM Concepto_Retencion_Fuente Group BY Prefijo ';
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $datos = $oCon->getData();
    unset($oCon);
    $i=0;
    foreach ($datos as $value) {
        $query='SELECT CRF.*, IFNULL((SELECT Valor FROM Deduccion_Renta_Funcionario WHERE Identificacion_Funcionario='.$id.' AND Id_Concepto_Retencion_Fuente=CRF.Id_Concepto_Retencion_Fuente), 0 ) as Valor, IFNULL((SELECT Id_Deduccion_Renta_Funcionario FROM Deduccion_Renta_Funcionario WHERE Identificacion_Funcionario='.$id.' AND Id_Concepto_Retencion_Fuente=CRF.Id_Concepto_Retencion_Fuente), 0 ) as Id_Deduccion_Renta_Funcionario FROM Concepto_Retencion_Fuente CRF WHERE Prefijo LIKE "%'.$value['Prefijo'].'%"';
       
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $conceptos = $oCon->getData();
        $datos[$i]['Conceptos']=$conceptos;
        $i++;
    }

    return $datos;
}
function ObtenerFechas(){
    global $ffin,$fini,$quincena;

    if(date("Y-m-d")<=date("Y-m-15")){
        $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-01" );
        $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-15") );
        $quincena=1;
     }else{
        $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-16" );
        $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1))); 
        $quincena=2;
     }
}

?>

