<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
include_once('class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.querybasedatos.php');

    
class CalculosParafiscales{
    private $total_ibc;
    private $ley_1607;
    private $total_porcentaje_seguridad=0;
    private $total_porcentaje_parafiscal=0;
    private $total_seguridad=0;
    private $total_parafiscal=0;
    private $seguridad_social;
    private $paraficales;
    private $salario;
    private $funcionario;
    private $base;
    private $datos_seguridad_social;
    private $Datos;
    private $conceptos_contabilizacion;
    private $datos_funcionario;
    
    
      
      function __construct($total_ibc, $salario, $funcionario){
        $this->queryObj = new QueryBaseDatos();
        $this->total_ibc=$total_ibc;
        $this->salario=$salario;
        $this->funcionario=$funcionario;
        
      }

      function __destruct(){
        $this->queryObj = null;
        unset($queryObj);	
      }
   

    public function CalcularParafiscales(){
        $this->datos_funcionario=$this->GetSalarioFuncionario();
        $this->base=$this->ObtenerSalarioBase();
        $this->ley_1607=$this->Ley1607();
        $this->seguridad_social= $this->ObtenerConceptosSeguridadSocial();
        $this->riesgos=$this->GetRiesgos();
        $this->seguridad_social=array_merge($this->seguridad_social, $this->riesgos);
        $this->paraficales= $this->ObtenerParafiscales();
        $para['Seguridad']=$this->ArmarSeguridad($this->seguridad_social, $this->paraficales);      
        $para['Ibc_Salud']=$this->ArmarIBC('IBC Salud y Pensión');
        $para['Ibc_Riesgos']=$this->ArmarIBC('IBC Riesgos');
        $para['Aportes']=$this->ArmarIBC('IBC Aportes Parafiscales');
        $para['Total']=$this->total_parafiscal+$this->total_seguridad;     
        $para['Contabilizacion']=$this->conceptos_contabilizacion;
     
        return $para;
    
    }
    private function Ley1607(){
        $query='SELECT Ley_1607 FROM Configuracion WHERE Id_Configuracion=1';

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');
        return $datos['Ley_1607'];
    }


    private function ObtenerConceptosSeguridadSocial(){
        $condicion='';
        if($this->ley_1607=='Si' && $this->ValidarSalario() ){
            $condicion.=" WHERE Concepto !='Salud'";
        }
        if($this->Datos['Aporte_Pension']=='No' ){
            if($condicion!=''){
                $condicion.=" AND Concepto!='Pension'";
            }else{
                $condicion.=" WHERE Concepto !='Pension'";
            }
        }
       

        

        $query='SELECT Concepto, Porcentaje FROM Aporte_Seguridad_Empresa'.$condicion;

        if(!$this->Validar()){
            $query ="SELECT Concepto, (Porcentaje+(SELECT Porcentaje FROM Aporte_Seguridad_Funcionario WHERE Concepto='Salud') ) as Porcentaje  FROM Aporte_Seguridad_Empresa WHERE Concepto ='Salud'";
        }
   
   
        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');

        $i=0;
        foreach ($datos as $value) {
            $tem=$value['Porcentaje']*$this->total_ibc;
            $datos[$i]['Valor']=$tem;
            $this->total_seguridad+=$tem;
            $datos[$i]['IBC']=$this->total_ibc;
            $this->total_porcentaje_seguridad+=$value['Porcentaje'];
            $datos[$i]['Porcentaje']=$value['Porcentaje']*100;
           $this->conceptos_contabilizacion[$value['Concepto']]=$tem;
            $i++;
        }

      
 
        return $datos;
    }

    private function ObtenerParafiscales()
    {
        $datos=[];

        if($this->Validar()){
            $condicion='';
            if($this->ley_1607=='Si'  && $this->ValidarSalario() ){
                $condicion.=' WHERE Prefijo LIKE "%CAJA%" ';
            }
           
    
            $query='SELECT  Concepto, Porcentaje FROM Parafiscal'.$condicion;
    
            $this->queryObj->SetQuery($query);
            $datos=$this->queryObj->ExecuteQuery('multiple');
           // var_dump($datos);
            $i=0;
            foreach ($datos as $value) {
                $tem=$value['Porcentaje']*$this->total_ibc;
                $datos[$i]['Valor']=$tem;
                $this->total_parafiscal+=$tem;
                $datos[$i]['IBC']=$this->total_ibc;
                $this->total_porcentaje_parafiscal+=$value['Porcentaje'];
                $datos[$i]['Porcentaje']=$value['Porcentaje']*100; 
                $this->conceptos_contabilizacion[$value['Concepto']]=$tem;          
                $i++;
            }
    
        }
       
        return $datos;
    }



    private function ArmarSeguridad($seguridad_social,$paraficales){
       
       $seguridad=[
           [
                'Concepto'=>'Seguridad Social',
                'Porcentaje'=>($this->total_porcentaje_seguridad)*100,
                'Valor'=>$this->total_seguridad,
                'IBC'=>''
                
           ]
       ];
       $parafiscal=[
            [
                'Concepto'=>'Parafiscales',
                'Porcentaje'=>($this->total_porcentaje_parafiscal)*100,
                'Valor'=>$this->total_parafiscal,
                'IBC'=>''
                
            ]
       ];
       $total=[
            [            
                'Concepto'=>'Total Seguridad Social y Parafiscales',
                'Porcentaje'=>($this->total_porcentaje_parafiscal+$this->total_porcentaje_seguridad )*100,   
                'Valor'=>$this->total_parafiscal+$this->total_seguridad,
                'IBC'=>''
                        
            ]
       ];
      

       return array_merge($seguridad,$seguridad_social,$parafiscal,$paraficales,$total);
    }

    private function ArmarIBC($concepto)
    { 
        $sueldo=$this->salario;
        
        if($this->salario<$this->base && $concepto=='IBC Riesgos'){
            $sueldo=$this->base;
        }
        $salario=[
            [
                'Concepto'=>'Salario',
                'Valor'=>$this->salario
            ],
            [
                'Concepto'=>$concepto,
                'Valor'=>$sueldo
            ]
        ];

        return $salario;
    }

    private function ObtenerSalarioBase()
    {
        $query='SELECT 	Salario_Base,Salarios_Minimos_Cobro_Seguridad_Social,Maximo_Cotizacion FROM Configuracion WHERE Id_Configuracion=1 ';

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');
        $this->datos_seguridad_social=$datos;

        $base= $this->datos_seguridad_social['Salario_Base']*$this->datos_seguridad_social['Maximo_Cotizacion'];
        if($this->salario>$base){
            $this->total_ibc=($base/2);
        }
        
        return $datos['Salario_Base'];
    }

    private function ValidarSalario(){

        $datos=true;
        $base=$this->base*$this->datos_seguridad_social['Salarios_Minimos_Cobro_Seguridad_Social'];
        $query='SELECT Valor,Aporte_Pension FROM Contrato_Funcionario WHERE Estado="Activo" AND Identificacion_Funcionario='.$this->funcionario;

        $this->queryObj->SetQuery($query);
        $sueldo=$this->queryObj->ExecuteQuery('simple');
        $this->Datos=$sueldo;

        if($sueldo['Valor']>$base){
            $datos=false;
        }
        return $datos; 
    }

    private function GetRiesgos(){
        $query='SELECT (SELECT Porcentaje FROM Riesgo WHERE Id_Riesgo=CF.Id_Riesgo ) as Porcentaje, (SELECT Concepto FROM Riesgo WHERE Id_Riesgo=CF.Id_Riesgo ) as Concepto FROM Contrato_Funcionario CF WHERE CF.Estado="Activo" AND CF.Identificacion_Funcionario='.$this->funcionario.' HAVING Porcentaje>0';

        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('multiple');
        $i=0;
        foreach ($datos as $value) {
            $tem=$value['Porcentaje']*$this->total_ibc;
            $datos[$i]['Valor']=$tem;
            $this->total_seguridad+=$tem;
            $datos[$i]['IBC']=$this->total_ibc;
            $this->total_porcentaje_seguridad+=$value['Porcentaje'];
            $datos[$i]['Porcentaje']=$value['Porcentaje']*100; 
            $this->conceptos_contabilizacion[$value['Concepto']]=$tem;          
            $i++;
        }

        return $datos;

    }

    private function Validar(){
        $datos=false;
        if($this->datos_funcionario['Id_Tipo_Contrato']!=8 && $this->datos_funcionario['Id_Tipo_Contrato']!=7 && $this->datos_funcionario['Id_Tipo_Contrato']!=6 &&  $this->datos_funcionario['Id_Tipo_Contrato']!=9 && $this->datos_funcionario['Id_Tipo_Contrato']!=10 && $this->datos_funcionario['Id_Tipo_Contrato']!=10 ){
            $datos=true;
        }

        return $datos;
    }

    
    private function GetSalarioFuncionario(){
        $query='SELECT CF.*, (SELECT Subsidio_Transporte FROM Configuracion WHERE Id_Configuracion=1) as Subsidio_Transporte,(SELECT Salarios_Minimo_Cobro_Incapacidad FROM Configuracion WHERE Id_Configuracion=1 ) as Minimo_Incapacidad, (SELECT Salario_Base FROM Configuracion WHERE Id_Configuracion=1 ) as Salario_Base,(SELECT Maximo_Cotizacion FROM Configuracion WHERE Id_Configuracion=1 ) as Maximo_Cotizacion,(SELECT Valor_Uvt FROM Configuracion WHERE Id_Configuracion=1 ) as Valor_Uvt, (SELECT Salario_Auxilio_Transporte FROM Configuracion WHERE Id_Configuracion=1 ) as Salario_Auxilio_Transporte FROM Contrato_Funcionario CF WHERE CF.Identificacion_Funcionario='.$this->funcionario;
       
        $this->queryObj->SetQuery($query);
        $datos=$this->queryObj->ExecuteQuery('simple');
        
        return $datos;
    }


}

?>