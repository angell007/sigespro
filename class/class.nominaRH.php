<?php
//require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
require_once('../../config/start.inc.php');
include_once('class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.querybasedatos.php');

    
class CalculoNomina{
    private $ingresos_salariales;
    private $ingresos_no_salarales;
    private $lista_egresos;
    private $lista_novedades;
    private $funcionario;
    private $quincena; 
    private $queryObj;
    private $fini;
    private $ffin;
    private $total_extras;
    private $datos_funcionario;
    private $horas_extras;
    private $total_vacaciones;
    private $total_incapacidades;
    private $total_licencias;
    private $dias_ausente=0;
    private $salario_funcionario;
    private $valor_dia;
    private $valor_auxilio;
    private $salario_quincena;
    private $total_auxilio;
    private $total_ibc;
    private $salario_maximo;
    private $deduccion_salud;
    private $deduccion_pension;
    private $fondo_subsistencia=[];
    private $fondo_solidaridad=[];
    private $total_quincena;
    private $dias_trabajados;
    private $lista_vacaciones=[];
    private $lista_incapacidades=[];
    private $lista_licencias=[];
    private $porcentaje_salud;
    private $porcentaje_pension;
    private $total_solidaridad;
    private $total_subsistencia=0;
    private $total_ingresosn_salarial=0;
    private $dias_vacaciones=0;
    private $dias_incapacidad=0;
    private $dias_licencia=0;
    private $dias_permiso_no_remunerado=0;
    private $total_deduccion_renta=0;
    private $conceptos_contabilizacion=[];
    private $prima=0;
    private $tipo;
    private $total_llegadas_tarde=0;
    private $lista_llegadas_tarde=[];
    private $lista_libranzas = [];
    private $total_libranzas = 0;
    private $ibc_seguridad = 0;
      
      function __construct($funcionario, $quincena,$fini,$ffin,$tipo=null){
        
        $this->queryObj = new QueryBaseDatos();
        $this->funcionario=$funcionario;
        $this->quincena=$quincena;
        $this->fini = $fini;
        $this->ffin= $ffin;
        $this->tipo= $tipo;
      }

      function __destruct(){
        $this->queryObj = null;
        unset($queryObj);	
      }


    public function CalculosNomina(){
        $this->GetDiasAusente(); 
        $this->CalculaDiasLaborados();
        
        $this->salario_funcionario = $this->GetSalarioFuncionario();   
        $this->datos_funcionario   = $this->GetDatosFuncionario();
       
        $this->ingresos_salariales   = $this->GetIngresosSalariales();        
        $this->ingresos_no_salarales = $this->GetIngresosNoSalariales();
        $this->lista_egresos         = $this->GetEgresos();        
        $this->lista_novedades       = $this->GetNovedades(); 
        $this->lista_libranzas       = $this->GetEgresosLibranzas();       
        //$this->horas_extras=$this->GetHorasExtras();
        //$this->total_llegadas_tarde=$this->GetLlegadasTarde(); 
        $this->horas_extras         = [];
        $this->total_llegadas_tarde = 0;
        $this->GetPrima();
        $this->CalcularSueldo();     
        $this->ArmarConceptoContabilizacion(); 
        $quin['Total_Quincena']     = $this->total_quincena;
        $quin['Dias_Laborados']     = $this->dias_trabajados;
        $quin['Egresos']            = $this->datos_funcionario['Egresos'];
        $quin['Ingresos_NS']        = $this->datos_funcionario['Ingresos_NS'];
        $quin['Ingresos_S']         = $this->datos_funcionario['Ingresos_S'];     
        $quin['Resumen']            = $this->ArmarResumen();      
        $quin['Lista_Ingresos_Salariales']    = $this->ingresos_salariales;
        $quin['Lista_Ingresos_No_Salariales'] = $this->ingresos_no_salarales;
        $quin['Lista_Egresos']                = $this->lista_egresos;
        $quin['Total_Licencias']              = $this->total_licencias;
        $quin['Total_Incapacidades']          = $this->total_incapacidades;
        $quin['Total_Vacaciones']             = $this->total_vacaciones;
        $quin['Total_Extras']                 = $this->total_extras;
        $quin['Ingresos_Constitutivos']       = $this->ArmarIngresosConstitutivos();
        $quin['Retenciones']                  = $this->ArmarRetenciones($this->fondo_subsistencia,$this->fondo_solidaridad);
        $quin['Lista_Novedades']              = $this->lista_novedades;
        $quin['Lista_Extras']                 = $this->horas_extras;
        $quin['Lista_Vacaciones']             = $this->lista_vacaciones;
        $quin['Lista_Incapacidades']          = $this->lista_incapacidades;
        $quin['Lista_Licencias']              = $this->lista_licencias;
        $quin['Sueldo']                       = $this->salario_funcionario['Valor'];
        $quin['Salario_Quincena']             = $this->salario_quincena;
        $quin['Salario']                      = $this->ArmarSalarioBase();
        $quin['Dias_Periodo']                 = 15;
        $quin['Total_IBC']                    = $this->total_ibc;
        $quin['Total_Salud']                  = $this->deduccion_salud;
        $quin['Total_Pension']                = $this->deduccion_pension;
        $quin['Total_Subsistencia']           = (INT)$this->total_subsistencia;
        $quin['Total_Solidaridad']            = (INT)$this->total_solidaridad;
        $quin['Auxilio']                      = $this->total_auxilio;
        $quin['Dias_Vacaciones']              = $this->dias_vacaciones;
        $quin['Dias_Licencia']                = $this->dias_licencia;
        $quin['Dias_Incapacidad']             = $this->dias_incapacidad;
        $quin['Deducciones']                  = $this->CalcularTotalEgresos();
        $quin['Salario_Neto']                 = $this->CalcularTotalIngresos()+$this->prima;
        $quin['Total_Renta']                  = $this->total_deduccion_renta;
        $quin['Conceptos_Contabilizacion']    = $this->conceptos_contabilizacion;
        $quin['Lista_LLegadas_Tarde']         = $this->lista_llegadas_tarde;
        $quin['Lista_Libranzas']              = $this->lista_libranzas;
        $quin['Total_Libranzas']              = $this->total_libranzas;

        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1') {
            $quin['_debug_nominaRH_version'] = 'vacaciones-tomadas-cap-2';
            $quin['_debug_dias_vacaciones'] = $this->dias_vacaciones;
            $quin['_debug_total_vacaciones'] = $this->total_vacaciones;
        }
        
        return $quin;       
    }
    private function CalculaDiasLaborados(){
        if($this->tipo=='Nomina'){
            $fechahoy=$this->ffin;
        }else{
            $fechahoy=date('Y-m-d');
        }
        $fechainicio= new DateTime($this->fini);
        $fechahoy= new DateTime($fechahoy);
        $dias_trabajados = $fechainicio->diff($fechahoy);
        $dias_trabajados= $dias_trabajados->format('%R%a');
        $dias_trabajados=trim($dias_trabajados,'+');
       
        $dias_trabajados=$dias_trabajados+1;

        if($dias_trabajados>15 && $this->tipo=='Nomina' ){
            $dias_trabajados=15;
        }
        if($dias_trabajados<15 && $this->tipo=='Nomina' ){
            //echo $this->ffin."--".strpos($this->ffin, "-02")."<br>";
            if(strpos($this->ffin, "-02")!==false&&($dias_trabajados==13||$dias_trabajados==14) ){
               $dias_trabajados=15; 
            }
        }
        if($this->tipo==null){
            $dias_trabajados=15;
        }
        $this->dias_trabajados=$dias_trabajados-$this->dias_ausente;
        if($this->dias_trabajados<0){
            $this->dias_trabajados=0;
        }       
    }
    private function CalcularSueldo(){
        
        if($this->ValidarPrecontrato()){
            if($this->datos_funcionario["Id_Grupo"]==1){
                $val = round(($this->dias_trabajados*$this->salario_funcionario["Dia_Administrativo"]),0);
            }else{
                $val = round(($this->dias_trabajados*$this->salario_funcionario["Dia_Operario"]),0);
            }
            $this->salario_quincena = $val;
            $this->total_auxilio = 0;
            $this->total_ibc =$val;
            $this->ibc_seguridad = $this->total_ibc + ($this->dias_permiso_no_remunerado * $this->valor_dia);
            $this->total_quincena= $val-$this->CalcularTotalEgresos();
            $this->conceptos_contabilizacion['Salarios por pagar']=$val; 
        }else{

            $this->salario_quincena=round(($this->dias_trabajados*$this->valor_dia),0);
            $this->total_auxilio=$this->CalcularAuxilioTransporte($this->dias_trabajados);       
            $this->total_ibc= round($this->salario_quincena+$this->total_extras+$this->total_vacaciones+$this->total_incapacidades+$this->total_licencias+(INT)$this->datos_funcionario["Ingresos_S"],0);      
            $this->ibc_seguridad = $this->total_ibc + ($this->dias_permiso_no_remunerado * $this->valor_dia);
            $this->CalcularDeducciones();
            $this->GetRetenciones();
            $this->total_quincena=$this->CalcularTotalIngresos()-$this->CalcularTotalEgresos();        
            $this->conceptos_contabilizacion['Salarios por pagar']=round(($this->total_quincena-$this->total_vacaciones),0); 
            $this->total_quincena= $this->total_quincena+$this->prima;

        }
        
    
    }

    private function GetSalarioFuncionario(){
        $query='SELECT CF.*, TC.Nombre AS Tipo_Contrato,
        (SELECT Subsidio_Transporte FROM Configuracion WHERE Id_Configuracion=1) as Subsidio_Transporte,
        (SELECT Salarios_Minimo_Cobro_Incapacidad FROM Configuracion WHERE Id_Configuracion=1 ) as Minimo_Incapacidad, 
        (SELECT Salario_Base FROM Configuracion WHERE Id_Configuracion=1 ) as Salario_Base,
        (SELECT Maximo_Cotizacion FROM Configuracion WHERE Id_Configuracion=1 ) as Maximo_Cotizacion,
        (SELECT Valor_Uvt FROM Configuracion WHERE Id_Configuracion=1 ) as Valor_Uvt, 
        (SELECT Salario_Auxilio_Transporte FROM Configuracion WHERE Id_Configuracion=1 ) as Salario_Auxilio_Transporte
        FROM Contrato_Funcionario CF
        LEFT JOIN Tipo_Contrato TC On TC.Id_Tipo_Contrato = CF.Id_Tipo_Contrato
        WHERE CF.Identificacion_Funcionario='.$this->funcionario;
       
    //    (SELECT Dia_Operativo FROM Configuracion WHERE Id_Configuracion=1 ) as Dia_Operario, 
    //    (SELECT Dia_Administrativo FROM Configuracion WHERE Id_Configuracion=1 ) as Dia_Administrativo 

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');
        $this->GetValorDia($datos);
        return $datos;
    }

    private function GetDatosFuncionario(){
        $bonificacion=(INT)($this->salario_funcionario['Auxilio_No_Prestacional']/30)*$this->dias_trabajados;
        //$bonificacion=(INT)($this->salario_funcionario['Auxilio_No_Prestacional']/2); 
        
        $query='SELECT F.*, 
        (Select SUM(Valor) 
        FROM Movimiento_Funcionario ME 
        INNER JOIN Concepto_Parametro_Nomina CPN ON ME.Id_Tipo    = CPN.Id_Concepto_Parametro_Nomina 
        INNER JOIN  Parametro_Nomina PN ON PN.Id_Parametro_Nomina = CPN.Id_Parametro_Nomina
        WHERE ME.Identificacion_Funcionario = F.Identificacion_Funcionario 
        AND   ME.Tipo                       = "Ingreso" 
        AND   PN.Tipo                       = "Prestacional"
        AND   ME.Quincena                   = "'.date("Y-m;",strtotime($this->fini)).$this->quincena.'") as Ingresos_S,
        

        IFNULL((Select ROUND(SUM(Valor)+'.$bonificacion.',0) 
        FROM Movimiento_Funcionario ME 
        INNER JOIN Concepto_Parametro_Nomina CPN ON ME.Id_Tipo    = CPN.Id_Concepto_Parametro_Nomina 
        INNER JOIN  Parametro_Nomina PN ON PN.Id_Parametro_Nomina = CPN.Id_Parametro_Nomina
        WHERE ME.Identificacion_Funcionario  = F.Identificacion_Funcionario 
        AND   ME.Tipo                        = "Ingreso"
        AND   PN.Tipo                        = "No_Prestacional"
        AND   ME.Quincena                    = "'.date("Y-m;",strtotime($this->fini)).$this->quincena.'"),'.$bonificacion.') AS Ingresos_NS,
        
        (Select SUM(Valor) 
        FROM Movimiento_Funcionario ME 
        WHERE ME.Identificacion_Funcionario = F.Identificacion_Funcionario 
        AND   ME.Tipo                       = "Egreso"
        AND   ME.Quincena                   = "'.date("Y-m;",strtotime($this->fini)).$this->quincena.'") as Egresos,
        C.Nombre as Cargo
        FROM  Funcionario F
        INNER JOIN Cargo C
        ON F.Id_Cargo = C.Id_Cargo
        WHERE F.Identificacion_Funcionario = '.$this->funcionario;


        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');

        return $datos;
    }
//Se  Obtienen los Ingresos Salariales del Empleado 
    private function GetIngresosSalariales(){
        $query='SELECT CPN.*, IFNULL(MF.Valor,0) as Valor, MF.Id_Movimiento_Funcionario
        FROM Parametro_Nomina PN
        INNER JOIN  Concepto_Parametro_Nomina  CPN
                ON  PN.Id_Parametro_Nomina   = CPN.Id_Parametro_Nomina
        LEFT  JOIN  Movimiento_Funcionario MF
                ON  CPN.Id_Concepto_Parametro_Nomina = MF.Id_Tipo  
               AND  MF.Tipo                          = "Ingreso"
               AND  MF.Identificacion_Funcionario    = '.$this->funcionario.'
               AND  MF.Quincena="'.date("Y-m;",strtotime($this->fini)).$this->quincena.'"
        WHERE PN.Tipo="Prestacional"  HAVING Valor>0 ';

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple'); 
        
        foreach ($datos as $value) {
            if($value['Nombre']=='Bonificacion prestacional'){
                $this->conceptos_contabilizacion['Bonificacion prestacional']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='Comisiones') {
                $this->conceptos_contabilizacion['Comisiones']=round($value['Valor']);
            }elseif ($value['Nombre']=='Bonificacion no prestacional') {
                $this->conceptos_contabilizacion['Bonificacion no prestacional']=round($value['Valor'],0);
            }
        }
        return $datos;
    }



    //Obtener Valor del dia 
    private function GetValorDia($datos){
        $this->valor_dia=$datos['Valor']/30;
        $this->valor_auxilio=$datos['Subsidio_Transporte']/30;
        $this->salario_maximo=$datos['Maximo_Cotizacion']*$datos['Salario_Base'];

    }
//Se Obtienen los ingresos no salariales 
    private function GetIngresosNoSalariales(){
        $auxilio=0;
        
        $auxilio=$this->salario_funcionario['Auxilio_No_Prestacional']!='' ? number_format(($this->salario_funcionario['Auxilio_No_Prestacional']/30)*$this->dias_trabajados,0,"","") : 0;
        
        //$auxilio=$this->salario_funcionario['Auxilio_No_Prestacional']!='' ? number_format($this->salario_funcionario['Auxilio_No_Prestacional']/2,0,"","") : 0;

        $query='SELECT CPN.*,IF(CPN.Nombre LIKE "%Bonificacion no prestacional%", IFNULL(MF.Valor,0)+'.(INT)$auxilio.',  IFNULL(MF.Valor,0) ) as Valor, MF.Id_Movimiento_Funcionario
        FROM Parametro_Nomina PN
        INNER JOIN  Concepto_Parametro_Nomina CPN ON PN.Id_Parametro_Nomina=CPN.Id_Parametro_Nomina
        LEFT  JOIN  Movimiento_Funcionario MF
        ON CPN.Id_Concepto_Parametro_Nomina = MF.Id_Tipo AND MF.Tipo="Ingreso" AND MF.Identificacion_Funcionario='.$this->funcionario.' AND MF.Quincena="'.date("Y-m;",strtotime($this->fini)).$this->quincena.'"
        WHERE PN.Tipo="No_Prestacional" HAVING Valor>0 ';
               
        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');

        foreach ($datos as $value) {
            $this->total_ingresosn_salarial+=$value['Valor'];
        }

        foreach ($datos as $value) {
            if($value['Nombre']=='Auxilio de movilizacion'){
                $this->conceptos_contabilizacion['Auxilio de movilizacion']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='Otros ingresos no prestacionales') {
                $this->conceptos_contabilizacion['Otros ingresos no prestacionales']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='Otros ingresos') {
                $this->conceptos_contabilizacion['Otros ingresos']=round($value['Valor'],0);
            }
        }
        return $datos;
    }

//Se Obtienen los egresos del funcionario 
    private function GetEgresos(){

        $query='SELECT CPN.*, IFNULL(MF.Valor,0) as Valor , MF.Id_Movimiento_Funcionario
         FROM Parametro_Nomina PN
        INNER JOIN  Concepto_Parametro_Nomina CPN ON PN.Id_Parametro_Nomina=CPN.Id_Parametro_Nomina
        LEFT JOIN Movimiento_Funcionario MF
        ON CPN.Id_Concepto_Parametro_Nomina = MF.Id_Tipo AND MF.Tipo="Egreso" AND MF.Identificacion_Funcionario='.$this->funcionario.' AND MF.Quincena="'.date("Y-m;",strtotime($this->fini)).$this->quincena.'" WHERE PN.Tipo="Deduccion" 
        HAVING Valor>0';
        
        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');

        foreach ($datos as $value) {
            if($value['Nombre']=='Librazas'){
                $this->conceptos_contabilizacion['Librazas']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='Prestamo') {
                $this->conceptos_contabilizacion['Prestamo']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='Celular') {
                $this->conceptos_contabilizacion['Celular']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='Otras deducciones') {
                $this->conceptos_contabilizacion['Otras deducciones']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='Aportes voluntarios a pension') {
                $this->conceptos_contabilizacion['Aportes voluntarios a pension']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='POLIZA FUNERARIA') {
                $this->conceptos_contabilizacion['POLIZA FUNERARIA']=round($value['Valor'],0);
            }elseif ($value['Nombre']=='RESPONSABILIDADES') {
                $this->conceptos_contabilizacion['RESPONSABILIDADES']=round($value['Valor'],0);
            }
        }
        return $datos;
    }

    private function GetEgresosLibranzas(){

        $query='SELECT PC.*
        FROM Prestamo_Cuota PC
        INNER JOIN Prestamo P ON P.Id_Prestamo = PC.Id_Prestamo
        WHERE P.Identificacion_Funcionario ='.$this->funcionario.' AND PC.Fecha="'.$this->ffin.'"
        ';
        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');

        foreach($datos as $item){
            $this->total_libranzas+=$item["Valor_Cuota"];
        }
        return $datos;
    }

    private function GetDiasAusente(){
        $query='SELECT TN.*, N.*
        FROM Tipo_Novedad TN
        INNER JOIN Novedad N
        On TN.Id_Tipo_Novedad = N.Id_Tipo_Novedad AND TN.Tipo_Novedad!="Hora_Extra" AND TN.Tipo_Novedad!="Recargo" AND N.Identificacion_Funcionario='.$this->funcionario.' 
        AND ((N.Fecha_Inicio>="'.$this->fini.'" AND N.Fecha_Inicio<="'.$this->ffin.'") OR (N.Fecha_Fin>="'.$this->fini.'" AND N.Fecha_Fin<="'.$this->ffin.'") OR (N.Fecha_Inicio<="'.$this->fini.'" AND N.Fecha_Fin>="'.$this->ffin.'"))';
  

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');
        $y=-1;
        foreach($datos as $nov){ $y++;
            if($nov["Fecha_Inicio"]<=$this->fini){
                $ini_nov = $this->fini;
            }else{
                $ini_nov=$nov["Fecha_Inicio"];
            }
            if($nov["Fecha_Fin"]>=$this->ffin){
                $fin_nov = $this->ffin;
            }else{
                $fin_nov=$nov["Fecha_Fin"];
            }
            $dias_nov = round((strtotime($fin_nov) - strtotime($ini_nov))/ 86400);
            $dias_nov=$dias_nov+1;
            $es_permiso_no_remunerado = ($nov["Tipo_Novedad"] == "Permiso")
                && ((isset($nov["Novedad"]) && stripos($nov["Novedad"], "no remunerado") !== false)
                    || (isset($nov["Observaciones"]) && stripos($nov["Observaciones"], "no remunerado") !== false));
            if ($es_permiso_no_remunerado) {
                $this->dias_permiso_no_remunerado += $dias_nov;
            }
            $this->dias_ausente += $dias_nov;
        } 

    }

//Se Obtienen las novedades del funcionario 
    private function GetNovedades(){
        $query='SELECT TN.*, N.*
        FROM Tipo_Novedad TN
        INNER JOIN Novedad N
        On TN.Id_Tipo_Novedad = N.Id_Tipo_Novedad AND TN.Tipo_Novedad!="Hora_Extra" AND TN.Tipo_Novedad!="Recargo" AND N.Identificacion_Funcionario='.$this->funcionario.' 
        AND ((N.Fecha_Inicio>="'.$this->fini.'" AND N.Fecha_Inicio<="'.$this->ffin.'") OR (N.Fecha_Fin>="'.$this->fini.'" AND N.Fecha_Fin<="'.$this->ffin.'") OR (N.Fecha_Inicio<="'.$this->fini.'" AND N.Fecha_Fin>="'.$this->ffin.'"))';
  

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');
        $y=-1;
        $incapacidad_laboral=0;
        $incapacidad_general=0;
        $licencia_maternidad=0;
        $licencia_paternidad=0;
        $licencia_remunerada=0;
        foreach($datos as $nov){ $y++;
            if($nov["Fecha_Inicio"]<=$this->fini){
                $ini_nov = $this->fini;
            }else{
                $ini_nov=$nov["Fecha_Inicio"];
            }
            if($nov["Fecha_Fin"]>=$this->ffin){
                $fin_nov = $this->ffin;
            }else{
                $fin_nov=$nov["Fecha_Fin"];
            }
            $dias_nov = round((strtotime($fin_nov) - strtotime($ini_nov))/ 86400);
            $dias_nov=$dias_nov+1;
            $dias_descuento = $dias_nov;
            if($nov["Tipo_Novedad"]=="Vacaciones" && isset($nov["Vacaciones_Tomadas"]) && is_numeric($nov["Vacaciones_Tomadas"])){
                $dias_descuento = min($dias_nov, (int)$nov["Vacaciones_Tomadas"]);
            }
            $datos[$y]["Dias"]=$dias_descuento;
           // $this->dias_ausente += $dias_descuento;  
            if($nov["Tipo_Novedad"]=="Vacaciones"){
                $this->dias_vacaciones+=$dias_descuento;
                $this->lista_vacaciones[]=$datos[$y];
                $this->total_vacaciones+=($this->valor_dia*$dias_descuento);
            }
            if($nov["Tipo_Novedad"]=="Incapacidad"){
                if($nov["Novedad"]=='Incapacidad general'){
                    $valor_incapacidad=$this->GetValoresIncapacidad('General');
                    if($dias_nov>2 && $this->ValidarIncapacidad()){
                        $dias_incapacidad=$dias_nov-2;
                        $this->total_incapacidades+=(2*$this->valor_dia)+(($dias_incapacidad*$this->valor_dia)*$valor_incapacidad);
                        $incapacidad_general+=(2*$this->valor_dia)+(($dias_incapacidad*$this->valor_dia)*$valor_incapacidad);
                    }else{
                        $this->total_incapacidades+=($this->valor_dia*$datos[$y]["Dias"]);
                        $incapacidad_general+=($this->valor_dia*$datos[$y]["Dias"]);
                    }
                    $this->conceptos_contabilizacion['Incapacidad general']=round($incapacidad_general,0);
                }else{
                    $this->total_incapacidades+=($this->valor_dia*$datos[$y]["Dias"]);
                    $incapacidad_laboral+=($this->valor_dia*$datos[$y]["Dias"]);
                    $this->conceptos_contabilizacion['Incapacidad laboral']=round($incapacidad_laboral,0);
                } 
                $this->dias_incapacidad+=$dias_nov;
                $this->lista_incapacidades[]=$datos[$y];
            }
            if($nov["Tipo_Novedad"]=="Licencia"){
                $this->lista_licencias[]=$datos[$y];
                if($nov["Novedad"]!="Licencia no Remunerada"){
                    $this->total_licencias+=($this->valor_dia*$datos[$y]["Dias"]);
                    $this->dias_licencia+=$dias_nov;
                    if($nov['Novedad']=='Licencia de maternidad'){
                        $licencia_maternidad+=($this->valor_dia*$datos[$y]["Dias"]);
                        $this->conceptos_contabilizacion['Licencia de maternidad']=round($licencia_maternidad,0);
                    }elseif ($nov['Novedad']=='Licencia de paternidad') {
                        $licencia_paternidad+=($this->valor_dia*$datos[$y]["Dias"]);
                        $this->conceptos_contabilizacion['Licencia de paternidad']=round($licencia_paternidad,0);
                    }elseif ($nov['Novedad']=='Licencia remunerada') {
                        $licencia_remunerada+=($this->valor_dia*$datos[$y]["Dias"]);
                        $this->conceptos_contabilizacion['Licencia remunerada']=round($licencia_remunerada,0);
                    }
                }
                
            }
            
        }

        if($this->total_vacaciones!=0){
            $this->conceptos_contabilizacion['Vacaciones']=round($this->total_vacaciones,0);
        }


        return $datos;
    }
//Se Obtienen las horas extras 
    private function GetHorasExtras(){
        
        $query='SELECT TN.*,
        IFNULL((SELECT SUM(Tiempo) FROM Novedad WHERE Id_Tipo_Novedad = TN.Id_Tipo_Novedad AND Identificacion_Funcionario='.$this->funcionario.' AND CAST(Fecha_Inicio AS DATE)>='.$this->fini.' AND CAST(Fecha_Fin AS DATE)<='.$this->ffin.'),0) as Tiempo
        FROM Tipo_Novedad TN
        WHERE TN.Tipo_Novedad="Hora_Extra" OR TN.Tipo_Novedad="Recargo" HAVING Tiempo>0';

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');

        foreach($datos as $extra){
            $val_extra = ($this->datos_funcionario["Salario"]*$extra["Valor"]*$extra["Tiempo"])/(30*8);
            $this->total_extras+=$val_extra;
         }

        return $datos;
    }

//Se validad si el salario del funcionario aplica para el pago del 100%  
    private function ValidarIncapacidad(){
        $datos=true;
        /*
        $salario=$this->salario_funcionario['Valor']/$this->salario_funcionario['Salario_Base'];
        if($salario>$this->salario_funcionario['Minimo_Incapacidad']){
            $datos=true;
        }*/
        return $datos;
    }

    // Obtener los porcentajes de las incapacidades 
    private function GetValoresIncapacidad($tipo){
        $query='SELECT * FROM Incapacidad WHERE Prefijo="'.$tipo.'"';

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');
        return (FLOAT)$datos['Porcentaje'];
    }

    private function CalcularAuxilioTransporte($dias)
    {
        $datos=0;
        $aux=($this->salario_funcionario['Valor']/$this->salario_funcionario['Salario_Base']);
        if($aux<=$this->salario_funcionario['Salario_Auxilio_Transporte'] && $this->Validar()){
            $datos=($dias)*$this->valor_auxilio;
         
        }
        return $datos;
    }
    private  function CalcularDeducciones()
    {
        if($this->salario_funcionario['Valor']>$this->salario_maximo){
            $valor=($this->salario_maximo/2);
            //Pension
            $this->deduccion_pension=0;
            $this->porcentaje_pension=0;
            if($this->salario_funcionario['Aporte_Pension']=='Si'){
            $datos=$this->ObtenerPorcentajeDeduccion('Pension','Aporte_Seguridad_Funcionario');
            $this->porcentaje_pension=$datos['Porcentaje'];
            $this->deduccion_pension=$this->Calcular($valor,$datos['Porcentaje']);
            //Salud
            }
          
        
            $datos=$this->ObtenerPorcentajeDeduccion('Salud Ka','Aporte_Seguridad_Funcionario');
            $this->porcentaje_salud=$datos['Porcentaje'];
            $this->deduccion_salud=$this->Calcular($valor,$datos['Porcentaje']);
           
            
          
         

        }else{
            if($this->salario_funcionario['Id_Tipo_Contrato']!=6 && $this->salario_funcionario['Id_Tipo_Contrato']!=7 && $this->salario_funcionario['Id_Tipo_Contrato']!=14){
                $valor=intVal($this->GetIbcSeguridad());
                $this->deduccion_pension=0;
                $this->porcentaje_pension=0;
                if($this->salario_funcionario['Aporte_Pension']=='Si'){
                    $datos=$this->ObtenerPorcentajeDeduccion('Pension','Aporte_Seguridad_Funcionario');
                    $this->porcentaje_pension=$datos['Porcentaje'];
                    $this->deduccion_pension=intval($this->Calcular($valor,$datos['Porcentaje']));
                //Salud
                }
                $datos=$this->ObtenerPorcentajeDeduccion('Salud','Aporte_Seguridad_Funcionario');
                 $this->porcentaje_salud=$datos['Porcentaje'];
                 $this->deduccion_salud=intval($this->Calcular($valor,$datos['Porcentaje']));
            }else{
                $this->deduccion_pension=0;
                $this->deduccion_salud=0;
            }
          

        }

        $this->fondo_subsistencia = $this->ValidarFondo();
        $this->fondo_solidaridad  = $this->ValidarFondoSolidaridad();      
        $this->conceptos_contabilizacion['Salud']   = round($this->deduccion_salud,0);   
        $this->conceptos_contabilizacion['Pension'] = round($this->deduccion_pension,0);   
    }

    private  function ObtenerPorcentajeDeduccion($tipo, $tabla)
    {
        $query='SELECT * FROM '.$tabla.' WHERE Prefijo="'.$tipo.'"';

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');

        return $datos;
    }

    private function Calcular($valor,$porcentaje){
        $datos=$valor*$porcentaje;
        return $datos;
    }

    private function ValidarFondo(){
        $datos=[];
        if($this->salario_funcionario['Aporte_Pension']=='Si'){
            $valor=$this->salario_funcionario['Valor'];
            if($valor>$this->salario_maximo){
                $valor=$this->salario_maximo;
            }
            $base         = ($valor/2);
            $salario_base = $this->salario_funcionario['Salario_Base'];
            $query        = "SELECT
            (CASE
                WHEN CHAR_LENGTH(Rango)=2 THEN IF( $valor>(Rango*$salario_base), Porcentaje*($valor/2),0)
                WHEN CHAR_LENGTH(Rango)>2 THEN IF( $valor>=(SUBSTRING(Rango,1,2))*$salario_base  AND  $valor < (SUBSTRING(Rango,4,5))*$salario_base, Porcentaje*$valor,0)
    
            END 
            ) as Valor,Concepto, (Porcentaje*100)
              as Porcentaje, CONCAT('$".number_format($base,0,"",".")."') 
              as IBC,        CONCAT('$".number_format($base,0,"",".")." * ',Porcentaje*100,'%' )
              as Formula 
             FROM Aporte_Seguridad_Funcionario WHERE Concepto LIKE '%Fondo de Subsistencia%' GROUP BY Id_Aporte_Seguridad_Funcionario HAVING Valor>0 ";
    
    
            $this->queryObj->SetQuery($query);
            $datos=$this->queryObj->ExecuteQuery('multiple');
           
            
            if($datos[0]['Concepto']){
                $datos[0]['Porcentaje']=number_format($datos[0]['Porcentaje'],1,",",""); 
                $this->total_subsistencia= round($datos[0]['Valor'],0); 
            }
           
            if($this->total_subsistencia!=0){
                $this->conceptos_contabilizacion['Fondo pensional de subsistencia']=round($this->total_subsistencia,0);
              
            }
          
       
        }
       
        return $datos;

    }

    private function ValidarFondoSolidaridad()
    {
        $datos=[
           
        ];
        if($this->salario_funcionario['Aporte_Pension']=='Si'){
           
            $valor=$this->salario_funcionario['Valor'];
            if($valor>$this->salario_maximo){
                $valor=$this->salario_maximo;
            }
            $salario_base=$this->salario_funcionario['Salario_Base'];
    
            $query="SELECT
            (CASE
                WHEN CHAR_LENGTH(Rango)=1 THEN IF($valor>(Rango*$salario_base), Porcentaje*($valor/2),0)           
    
            END 
            ) as Valor, Concepto,(Porcentaje*100) as Porcentaje,CONCAT('$".number_format($this->total_ibc,0,"",".")."') as IBC, CONCAT('$".number_format($this->total_ibc,0,"",".")." * ',Porcentaje*100,'%' ) as Formula 
             FROM Aporte_Seguridad_Funcionario WHERE Concepto LIKE '%Fondo de Solidaridad%' GROUP BY Id_Aporte_Seguridad_Funcionario HAVING Valor>0 ";
            $this->queryObj->SetQuery($query);
            $datos=$this->queryObj->ExecuteQuery('multiple');
            
            if($datos[0]['Concepto']){
                $datos[0]['Porcentaje']=number_format($datos[0]['Porcentaje'],1,",","");
                $this->total_solidaridad= round($datos[0]['Valor'],0);
            }
            if($this->total_solidaridad!=0){
                $this->conceptos_contabilizacion['Fondo pensional de solidaridad']=round($this->total_solidaridad,0);
            }

           
            
           
        }
        return $datos;
    }
    private function GetPrima(){
        $query='SELECT IFNULL((SELECT IFNULL(MF.Valor,0) as Valor 
        FROM  Movimiento_Funcionario MF
        WHERE MF.Identificacion_Funcionario='.$this->funcionario.' AND MF.Quincena="'.date("Y-m;",strtotime($this->fini)).$this->quincena.'" AND MF.Tipo="Prima"),0) as Valor ';
       
    
        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');
        $this->prima=round($datos['Valor'],0);
        if($this->prima>0){
            $this->conceptos_contabilizacion['Prima de Servicios']=$this->prima;
            $base=$this->salario_funcionario['Salario_Base']*$this->salario_funcionario['Salario_Auxilio_Transporte'];
            if($this->salario_funcionario['Valor']>$base ){
                if($this->prima<($this->salario_funcionario['Valor']/2)){
                    $this->prima=$this->salario_funcionario['Valor']/2;
                }
            }else{
                if($this->prima<($this->salario_funcionario['Valor']/2)){
                    $this->prima=$this->salario_funcionario['Valor']/2+$this->salario_funcionario['Subsidio_Transporte'];
                }
            }
        }
       
    }

    private function CalcularTotalIngresos()
    {
        $bono=0;
        //if($this->quincena==2){
        $bono=($this->salario_funcionario['Auxilio_No_Prestacional']/30)*$this->dias_trabajados;
        //}

        
        $total=(round($this->total_ibc,0)+round($this->total_auxilio,0)+round($this->total_ingresosn_salarial,0));
        
        return round($total,0);
    }

    private function GetIbcSeguridad()
    {
        if ($this->ibc_seguridad > 0) {
            return $this->ibc_seguridad;
        }
        return $this->total_ibc;
    }
    private function CalcularTotalEgresos()
    {
       
        $total=(round($this->deduccion_pension,0)+round($this->deduccion_salud,0)+round($this->total_solidaridad,0)+ round($this->total_subsistencia,0)+round($this->datos_funcionario['Egresos'],0)+round($this->total_deduccion_renta,0))+round($this->total_llegadas_tarde)+round($this->total_libranzas);
      
        return round($total,0);
    }

    private function ArmarResumen(){

        $bono=0;
        //if($this->quincena==2){
            $bono=($this->salario_funcionario['Auxilio_No_Prestacional']/30)*$this->dias_trabajados;
        //}
        //$bono = $this->salario_funcionario['Auxilio_No_Prestacional']/2;

        $prima=[];
        if($this->prima!=0){
            $prima=[[
                'Concepto'=>'Prima de Servicios',
                'Valor'=>$this->prima
            ]];
           
        }

       $salario=[
           [
            'Concepto'=>'Salario',
            'Valor'=>$this->salario_quincena
           ],
           [
            'Concepto'=>'Subsidio de Transporte',
            'Valor'=>$this->total_auxilio
           ],
          /* [
            'Concepto'=>'Ingresos Salariales',
            'Valor'=>(INT)$this->datos_funcionario['Ingresos_S']
           ],*/
           [
            'Concepto'=>'Ingresos No Salariales',
            'Valor'=>(INT)$this->datos_funcionario['Ingresos_NS']
           ],/*
           [
            'Concepto'=>'Horas extras y Recargos',
            'Valor'=>(INT)$this->total_extras
           ],*/
           [
            'Concepto'=>'Vacaciones, Licencias e Incapacidades',
            'Valor'=>(INT)$this->total_vacaciones+(INT)$this->total_licencias+(INT)$this->total_incapacidades
           ],
           [
            'Concepto'=>'Deduccion Salud',
            'Valor'=>'-'.round($this->deduccion_salud,0)
           ],
           [
            'Concepto'=>'Deduccion Pension',
            'Valor'=>'-'.round($this->deduccion_pension,0)
           ],
           [
            'Concepto'=>'Prestamos y/o Facturas',
            'Valor'=>'-'.round($this->datos_funcionario['Egresos'],0) 
           ],
           [
            'Concepto'=>'Libranzas, Prestamos o Sanciones',
            'Valor'=>'-'.round($this->total_libranzas,0)
           ],
           [
            'Concepto'=>'LLegadas Tarde',
            'Valor'=>'-'.round($this->total_llegadas_tarde,0) 
           ],
           [
            'Concepto'=>'Fondo Solidaridad',
            'Valor'=>'-'.round($this->total_solidaridad,0) 
           ]
          
       ];
       $salario=array_merge($salario,$prima);

       $total=[
            [
                'Concepto'=>'Total neto a pagar al empleado',
                'Valor'=>$this->CalcularTotalIngresos()-$this->CalcularTotalEgresos()+$this->prima
            ]
        ];



        return array_merge($salario,$total);
    }

    private function ArmarIngresosConstitutivos()
    {

        $prima=[];
        if($this->prima!=0){
            $prima=[[
                'Concepto'=>'Prima',
                'Valor'=>$this->prima
            ]];
        }
       $salario=[
           [
            'Concepto'=>'Salario',
            'Valor'=>$this->salario_quincena
           ],
           [
            'Concepto'=>'Horas Extras y Recargos',
            'Valor'=>(INT)$this->total_extras
           ],
           [
            'Concepto'=>'Vacaciones',
            'Valor'=>(INT)$this->total_vacaciones
           ],
           [
            'Concepto'=>'Incapacidades',
            'Valor'=>(INT)$this->total_incapacidades
           ],
           [
            'Concepto'=>'Licencias',
            'Valor'=>(INT)$this->total_licencias
           ],
           [
            'Concepto'=>'Ingresos Adicionales',
            'Valor'=>(INT)$this->datos_funcionario['Ingresos_S']
           ],
          
       ];

       $salario=array_merge($salario,$prima);

       $total=[ [
        'Concepto'=>'IBC Seguridad Social',
        'Valor'=>$this->GetIbcSeguridad()
    ]];

       return array_merge($salario,$total);
    }

    private function ArmarRetenciones($fondo_subsistencia,$fondo_solidaridad){ 
        $rentencion=[];
        if($this->total_deduccion_renta!=0){    
            $rentencion=[[
                        'Concepto'=>'Retencion en la Fuente',
                        'Valor'=>$this->total_deduccion_renta,
                        'Porcentaje'=>'',
                        'IBC'=>'',
                        'Formula'=>''
            ]];
        }   
        $total=[                
                    [
                        'Concepto'=>'Total Retenciones',
                        'Valor'=>$this->deduccion_salud+(INT)$this->deduccion_pension+(INT)$this->total_solidaridad+(INT)$this->total_subsistencia+$this->total_deduccion_renta,
                        'Porcentaje'=>'',
                        'IBC'=>'',
                        'Formula'=>''
                    ]
            
                ];
        $ibc_seguridad = $this->GetIbcSeguridad();
        $salario=[
                    [
                        'Concepto'   => 'Salud',
                        'Valor'      => intval($this->deduccion_salud),
                        'Porcentaje' => ($this->porcentaje_salud)*100,
                        'IBC'        => '$'.number_format($ibc_seguridad,0,"","."),
                        'Formula'    => '$'.number_format($ibc_seguridad,0,"",".").' * '.($this->porcentaje_salud*100).'%'
                    ],
                    [
                        'Concepto'   => 'Pension',
                        'Valor'      => intval($this->deduccion_pension),
                        'Porcentaje' => ($this->porcentaje_pension)*100,
                        'IBC'        => '$'.number_format($ibc_seguridad,0,"","."),
                        'Formula'    => '$'.number_format($ibc_seguridad,0,"",".").' * '.($this->porcentaje_pension*100).'%'
                    ]
                   
        ]; 
      
        return array_merge($salario,$fondo_solidaridad,$fondo_subsistencia,$rentencion,$total);
    }

    private function ArmarSalarioBase(){
       
        $text='$'.$this->salario_funcionario['Valor'].' * '.$this->dias_trabajados.' / 30';
        $text2='$'.$this->salario_funcionario['Subsidio_Transporte'].' * '.$this->dias_trabajados.' / 30';
        $salario=[
            [
                'Concepto'=>'Salario',
                'Campo'=>$this->salario_funcionario['Valor'],
                'Dias'=>$this->dias_trabajados,
                'Formula'=>$text,
                'Valor'=>($this->dias_trabajados)*$this->valor_dia
            ],
            [
                'Concepto'=>'Subsidio de Transporte',
                'Campo'=>$this->salario_funcionario['Subsidio_Transporte'],
                'Dias'=>$this->dias_trabajados,
                'Formula'=>$text2,
                'Valor'=>$this->total_auxilio
            ]            
        ]; 
        return $salario;
    }

    private function GetRetenciones(){
        /*
        if($this->quincena==2){
            
            $total=($this->salario_funcionario['Valor']-(($this->deduccion_pension+$this->deduccion_salud)*2));
          
            $query='SELECT IFNULL((SELECT SUM(DRF.Valor) FROM Deduccion_Renta_Funcionario DRF INNER JOIN Concepto_Retencion_Fuente CRF ON DRF.Id_Concepto_Retencion_Fuente=CRF.Id_Concepto_Retencion_Fuente WHERE Identificacion_Funcionario='.$this->funcionario.' AND Prefijo="Deduccion" ),0) as Deducciones, IFNULL((SELECT SUM(DRF.Valor) FROM Deduccion_Renta_Funcionario DRF INNER JOIN Concepto_Retencion_Fuente CRF ON DRF.Id_Concepto_Retencion_Fuente=CRF.Id_Concepto_Retencion_Fuente WHERE Identificacion_Funcionario='.$this->funcionario.' AND Prefijo="Rentas_Exentas" ),0) as Rentas';

            $this->queryObj->SetQuery($query);
            $datos=$this->queryObj->ExecuteQuery('simple');
           
            $total=$total-($datos['Deducciones']+$datos['Rentas']);
            
            $renta_trabajo_exenta=round(($total*0.25),-3);

            $ingreso_laboral_mesual_base=$total-$renta_trabajo_exenta;

            //Se convierte la base a uvt 

            $uvt=round($ingreso_laboral_mesual_base/$this->salario_funcionario['Valor_Uvt'],0);
          
            $query='SELECT MIN(Top) as Minimo FROM Parametro_Retencion_Fuente ';
            $this->queryObj->SetQuery($query);
            $datos=$this->queryObj->ExecuteQuery('simple');

            $this->total_deduccion_renta=0;
            if($uvt>$datos['Minimo']){
                $query="SELECT
                (CASE
                    WHEN $uvt>=Min AND $uvt<Top THEN (($uvt-Min)*Porcentaje)+Sumar_Uvt
                END
                ) as Valor
                FROM Parametro_Retencion_Fuente HAVING Valor>0 ";

                $this->queryObj->SetQuery($query);                
                $datos=$this->queryObj->ExecuteQuery('simple');
                
                $this->total_deduccion_renta=round((($datos['Valor'])*$this->salario_funcionario['Valor_Uvt']),-3);
                $this->conceptos_contabilizacion['RETENCION EN LA FUENTE']= round($this->total_deduccion_renta,0);
               
            }
        } */
       
    }

    private function ArmarConceptoContabilizacion(){
        $salario=$this->dias_trabajados*$this->valor_dia;
        $this->conceptos_contabilizacion['Salario']=round($salario,0);
        if($this->total_auxilio!=0){
            $this->conceptos_contabilizacion['Auxilio Transporte']=round($this->total_auxilio,0);
        }
    }
    private function Validar(){
        $datos=true;
    
        if($this->salario_funcionario['Id_Tipo_Contrato']==8 || $this->salario_funcionario['Id_Tipo_Contrato']==7 || $this->salario_funcionario['Id_Tipo_Contrato']==6 ||  $this->salario_funcionario['Id_Tipo_Contrato']==9 || $this->salario_funcionario['Id_Tipo_Contrato']==10  ){
            $datos=false;
        }

        return $datos;
    }

    private function ValidarPrecontrato(){
        $datos=false;
    
        if($this->salario_funcionario['Tipo_Contrato']=='PRECONTRATO'){
            $datos=true;
        }

        return $datos;
    }

    private function GetLlegadasTarde(){

        $query='SELECT  Tiempo 
        FROM  Llegada_Tarde
        WHERE Identificacion_Funcionario='.$this->funcionario.' AND Fecha BETWEEN "'.$this->fini.'" AND "'.$this->ffin.'"  ';
        $this->queryObj->SetQuery($query);
        $llegadas_tarde=$this->queryObj->ExecuteQuery('multiple');

        
        $query='SELECT *, 0 as Cantidad, 0 as Tiempo, CONCAT("De ",ROUND(Rango_Inferior/60), " a ",ROUND(Rango_Superior/60), " Min." ) as Titulo
        FROM  Porcentaje_LLegada_Tarde     ';
        $this->queryObj->SetQuery($query);
        $porcentajes=$this->queryObj->ExecuteQuery('multiple');

        foreach ($llegadas_tarde as $key => $value) {
            for ($i=0; $i < $porcentajes; $i++) { 
                if($value['Tiempo']>$porcentajes[$i]['Rango_Inferior'] && $value['Tiempo']<=$porcentajes[$i]['Rango_Superior']){
                    $porcentajes[$i]['Cantidad']=$porcentajes[$i]['Cantidad']+1;
                    $porcentajes[$i]['Tiempo']=$porcentajes[$i]['Tiempo']+$value['Tiempo'];
                //break;
                }
            }
        }

        $total=0;
        foreach ($porcentajes as $key => $value) {
            $porcentajes[$key]['Total']=($this->valor_dia*($value['Cantidad']))*$value['Porcentaje'];
            $porcentajes[$key]['Valor_LLegada']=$this->valor_dia*$value['Porcentaje'];
            $total+=($this->valor_dia*($value['Cantidad']))*$value['Porcentaje'];
        }

        $this->lista_llegadas_tarde=$porcentajes;

        return $total;
    }

}

?>
