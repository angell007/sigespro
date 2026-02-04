<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$datos = (array) json_decode($datos, true);
$funcionario = (int) json_decode($funcionario, true);


// var_dump($datos);exit;

$oItem = new complex("Envio_Evaluacion","Id_Envio_Evaluacion");
        $oItem->Id_Jefe                 = $datos["Identificacion_Funcionario"];
        $oItem->Id_Funcionario_Envia    = $funcionario;
        $oItem->Id_Evaluacion_Desempeno = $datos["Id_Evaluacion_Desempeno"];
        $oItem->Fecha                   = date("Y-m-d");
        $oItem->save();
        $Id_Envio_Evaluacion = $oItem->getId(); 
        unset($oItem);    

$query ='SELECT * FROM Funcionario WHERE Id_Cargo = '.$datos["Id_Cargo"].' ';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $funcionarios = $oCon->getData();
        unset($oCon);

   if($funcionarios){
       foreach($funcionarios as $fun){
            $oItem = new complex("Envio_Evaluacion_Funcionario","Id_Envio_Evaluacion_Funcionario");
            $oItem->Id_Envio_Evaluacion = $Id_Envio_Evaluacion;
            $oItem->Identificacion_Funcionario = $fun['Identificacion_Funcionario'];
            $oItem->save();
            $Id_Envio_Evaluacion_Funcionario = $oItem->getId();
            unset($oItem); 
            
            $oItem = new complex('Alerta','Id_Alerta');
            $oItem->Identificacion_Funcionario=$fun['Identificacion_Funcionario'];
            $oItem->Tipo="Formulario";
            $oItem->Detalles="Tiene una Evaluación de Desempeño Pendiente";
            $oItem->Id = $Id_Envio_Evaluacion_Funcionario;

            $oItem->save();
            unset($oItem);
       }
   }

$resultado["Mensaje"]="Evaluaciones Enviadas correctamente";      
$resultado["Titulo"]="Operacion Exitosa";      
$resultado["Tipo"]="success";      

echo json_encode($resultado);
