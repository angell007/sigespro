<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.nomina.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$dias_calculo=0;
$base_liquidacion=0;
if(date("Y-m-d")<=date("Y-m-15")){
    $fini  =  date("Y-m")."-01" ;
    $ffin  =  date("Y-m-15");
    $quincena=1;
 }else{
    $fini  = date("Y-m")."-16" ;
    $ffin  =  date("Y-m-d"); 
    $quincena=2;
 }

$funcionario=new CalculoNomina($id,$quincena,$fini,$ffin,'Nomina');
$funcionario=$funcionario->CalculosNomina();  

$concepto_contabilizacion=[];
$pago_anio_anterior_vacaciones=0;

$query = 'SELECT CF.*, CONCAT(F.Nombres, " ",F.Apellidos) as Nombre_Funcionario,
(SELECT Salario_Base FROM Configuracion WHERE Id_Configuracion=1) as Salario_Base,
(SELECT Salario_Auxilio_Transporte FROM Configuracion WHERE Id_Configuracion=1) as Salario_Auxilio_Transporte,
(SELECT Subsidio_Transporte FROM Configuracion WHERE Id_Configuracion=1) as Subsidio_Transporte, F.Vacaciones_Acumuladas
 FROM Contrato_Funcionario CF INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario WHERE CF.Estado="Activo" AND CF.Identificacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$contrato = $oCon->getData();
unset($oCon);
$contrato['Valor_Mostrar_Vacaciones']='$'.number_format($contrato['Valor'],0,"",".");

$base=$contrato['Salario_Base']*$contrato['Salario_Auxilio_Transporte'];

if($contrato['Valor']<$base){
    $contrato['Valor']=$contrato['Valor']+$contrato['Subsidio_Transporte'];
}


$fecha=date('Y-m-d');
$fecha1=$contrato['Fecha_Inicio_Contrato'];

if($fecha>$contrato['Fecha_Fin_Contrato']){
    $fecha=$contrato['Fecha_Fin_Contrato'];
}

$fechainicio= new DateTime($fecha1);
$fechahoy= new DateTime($fecha);


$dias_trabajados = $fechainicio->diff($fechahoy);
$dias_trabajados= $dias_trabajados->format('%R%a');
$dias_trabajados=trim($dias_trabajados,'+');
$dias_trabajados=$dias_trabajados+1;


if($contrato['Fecha_Inicio_Contrato']<date('Y').'-01-01'){
    $fechavacaciones=date('Y').'-01-01';
}else{
    $fechavacaciones=$contrato['Fecha_Inicio_Contrato'];
}


ValidarVacaciones();


$pago_vacaciones=0;
if(Validar()){
    $fecha=date('Y-m-d');
    $fechainicio= new DateTime($fechavacaciones);
    $fechahoy= new DateTime($fecha);
    //se calculan los dias de vacaciones 
    $dias_vacaciones = $fechainicio->diff($fechahoy);
    $dias_vacaciones= $dias_vacaciones->format('%R%a');
    $dias_vacaciones=trim($dias_vacaciones,'+');

    $dias_faltantes=0;

    if($fecha<$contrato['Fecha_Fin_Contrato']){
        $fechafin= new DateTime($contrato['Fecha_Fin_Contrato']);
        $dias_faltantes = $fechahoy->diff($fechafin);
        $dias_faltantes= $dias_faltantes->format('%R%a');
        $dias_faltantes=trim($dias_faltantes,'+');
    }

$pago_vacaciones=(($contrato['Valor']/720)*$dias_vacaciones)+$pago_anio_anterior_vacaciones;
}




$cesantias=GetValor('Cesantias');
$interes_cesantias=GetValor('Interes_Cesantias');
$prima=GetValor('Prima');



$concepto_contabilizacion['Intereses a las Cesantias']=$interes_cesantias;
$concepto_contabilizacion['Cesantias']=$cesantias;
$concepto_contabilizacion['Prima']=$prima;
$concepto_contabilizacion['Caja de compensacion']=round($pago_vacaciones*0.04,0);
$concepto_contabilizacion['Vacaciones']=round($pago_vacaciones,0);
$concepto_contabilizacion['Bancos']=round($pago_vacaciones,0)+$interes_cesantias+$cesantias+$prima;

$dias_vacaciones=$dias_vacaciones*15/360;

$total=$cesantias+$prima+$interes_cesantias+round($pago_vacaciones,0)+round($funcionario['Total_Quincena'],0);

$contrato['Dias']=$dias_trabajados;
$contrato['Dias_Vacaciones']=round($dias_vacaciones,3);
$contrato['Cesantias']=$cesantias;
$contrato['Prima']=$prima;
$contrato['Interes_Cesantia']=$interes_cesantias;
$contrato['Vacaciones']=round($pago_vacaciones,0);
$contrato['Total_Quincena']=$funcionario['Total_Quincena'];
$contrato['Contabilizacion_Quincena']=$funcionario['Conceptos_Contabilizacion'];
$contrato['Salario_Neto']=$funcionario['Salario_Neto'];
$contrato['Auxilio']=$funcionario['Auxilio'];
$contrato['Total_Salud']=$funcionario['Total_Salud'];
$contrato['Total_Pension']=$funcionario['Total_Pension'];
$contrato['Contabilizacion_Liquidacion']=$concepto_contabilizacion;
$contrato['Total']=$total;
$contrato['Valor_Mostrar']='$'.number_format($contrato['Valor'],0,"",".");
$contrato['Dias_Faltantes']=$dias_faltantes;
$contrato['Conceptos']=ArmarConceptos($contrato);
$contrato['Dias_Prestaciones']=$dias_calculo;
$contrato['Base_Liquidacion']=$base_liquidacion;

echo json_encode($contrato);

function ValidarVacaciones(){
    global $fechavacaciones;
    global $pago_anio_anterior_vacaciones;
    global $id;

    $query='SELECT N.Fecha FROM Provision_Funcionario  PF INNER JOIN Nomina N ON PF.Id_Nomina=N.Id_Nomina WHERE PF.Identificacion_Funcionario='.$id.' AND PF.Estado="Pagadas" AND PF.Tipo="Vacaciones" ORDER BY PF.Id_Provision_Funcionario DESC LIMIT 1';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $fechaultimopago = $oCon->getData();
    unset($oCon);

    if($fechaultimopago['Fecha']){
       $fechaultimopago= explode(';',$fechaultimopago['Fecha']);
       if($fechaultimopago[1]=='1'){
           $fechavacaciones=$fechaultimopago[0]."-16";
       }else{
        $fechavacaciones=$fechaultimopago[0]."-01";
       }
    }
    $fecha=(date('Y')-1)."-12-31";
    $query='SELECT IFNULL((SELECT B.Credito_PCGA FROM Balance_Inicial_Contabilidad B WHERE B.Fecha LIKE "'.$fecha.'" AND B.Nit='.$id.' AND B.Id_Plan_Cuentas=371),0) as Valor';
   
    $oCon= new consulta();
    $oCon->setQuery($query);
    $pago_anio_anterior_vacaciones = $oCon->getData()['Valor'];
    unset($oCon);

}

function GetValor($tipo){
    global $id;
    global $contrato;
  
    global  $dias_calculo, $base_liquidacion;
    $fecha=date('Y-m-d');
    $valor=0;
    

    if(Validar()){    
    
        $fechainicio=$contrato['Fecha_Inicio_Contrato'];
        $anio_contrato=explode('-',$contrato['Fecha_Inicio_Contrato']);
        if($anio_contrato[0]!=date('Y')){
            $fechainicio=date('Y').'-01-01';
        }
    
        $fechainicio= new DateTime($fechainicio);
        $fechahoy= new DateTime($fecha);
        $dias_calculo = $fechainicio->diff($fechahoy);
        $dias_calculo= $dias_calculo->format('%R%a');
        $dias_calculo=trim($dias_calculo,'+');
        $dias_calculo=$dias_calculo+1;
    
        $base_liquidacion=$contrato['Valor'];
    
      
        $valor=($base_liquidacion/360)*$dias_calculo;
    
        if($tipo=='Interes_Cesantias'){
            $query='SELECT Porcentaje FROM Provision WHERE Prefijo="Interes_Cesantias" ';
            $oCon= new consulta();
            $oCon->setQuery($query);
            $porcentaje = $oCon->getData();
            unset($oCon);
            $valor=round($valor*$porcentaje['Porcentaje'],0);
        }
    }   
    return round($valor,0); 
}

function ArmarConceptos($contrato){
    $concepto=[
        [
            'Concepto'=>'Dias de Vacaciones Pendientes',
            'Valor'=>$contrato['Vacaciones'],
        ]
        ,[
            'Concepto'=>'Cesantias',
            'Valor'=>$contrato['Cesantias'],
        ]
        ,[
            'Concepto'=>'Interes Cesantias',
            'Valor'=>$contrato['Interes_Cesantia'],
        ],[
            'Concepto'=>'Prima de Servicios',
            'Valor'=>$contrato['Prima'],
        ],
        [
            'Concepto'=>'Sueldo Pendiente por Cancelar',
            'Valor'=>$contrato['Salario_Neto']-$contrato['Auxilio'],
        ],
        [
            'Concepto'=>'Aux. Transp. Pendiente por Cancelar',
            'Valor'=>$contrato['Auxilio'],
        ],[
            'Concepto'=>'Indemnizacion por despido',
            'Valor'=>0,
        ],[
            'Concepto'=>'Otros',
            'Valor'=>0,
        ],
        [
            'Concepto'=>'Salud',
            'Valor'=>'-'.$contrato['Total_Salud'],
        ],[
            'Concepto'=>'Pension',
            'Valor'=>'-'.$contrato['Total_Pension'],
        ]
        ];
        return $concepto;
}
function Validar(){
    global $contrato;
    $datos=false;
    
    if( $contrato['Id_Tipo_Contrato']!=8 &&  $contrato['Id_Tipo_Contrato']!=7 &&  $contrato['Id_Tipo_Contrato']!=6 &&   $contrato['Id_Tipo_Contrato']!=9 &&  $contrato['Id_Tipo_Contrato']!=10 &&  $contrato['Id_Tipo_Contrato']!=10 ){
        $datos=true;
    }

    return $datos;
}
?>