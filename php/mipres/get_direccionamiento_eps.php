<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    //header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
    include_once('../../class/class.mipres.php');
    include_once('../../class/class.php_mailer.php');

    $queryObj = new QueryBaseDatos();

    $mipres= new Mipres();
    $mail= new EnviarCorreo();

    $fecha = ( isset( $_REQUEST['fecha'] ) ? $_REQUEST['fecha'] : date('Y-m-d') );
    $nit_eps = ( isset( $_REQUEST['eps'] ) ? $_REQUEST['eps'] : false);
    $correos=['aux.compras@prohsa.com','compras@prohsa.com','fernando.arciniegas@prohsa.com','freddy.arciniegas@prohsa.com','sistemas@prohsa.com'];
    $oItem  = new complex('Eps','Nit',$nit_eps);
    $eps = $oItem->getData();
    
   
    if(!$nit_eps){
        echo 'Ingrese el nit de la EPS.';
        exit;
    }
 
 
 
 for($x = 1 ; $x<=31; $x++){
     
     
 }
 
 
    $dishoy=$mipres->GetDireccionamientoPorFecha($fecha);

usort($dishoy,'OrderByNumeroEntrega');


$dis_borrar='';
$producto_lista='';
$eps_lista='';
$idpaciente='';
$entrega='';
$dis=[];
$productos_dis=[];
$producto_lista_array=[];



foreach ($dishoy as $value) {
       /* var_dump($value);
    exit;*/
        echo "<br>PACIENTE -- " . $value['NoIDPaciente'] . "  - Direccionamiento: ".$value["IDDireccionamiento"]. "  - Prescipcion: ".$value["NoPrescripcion"]. "  - EPS: ".$value["NoIDEPS"]. "  - Tipo Tecnologia: ".$value["TipoTec"]."<br>";
        if($value["NoIDEPS"]!="" && $value["NoIDEPS"] == $nit_eps){ 
          
        
            $oItem=new complex ('Z_Mipres','Id_Z_Mipres');
            $oItem->NoIDEPS                 =   $value["NoIDEPS"];
            $oItem->ID                      =   $value["ID"];
            $oItem->IDDireccionamiento      =   $value["IDDireccionamiento"];
            $oItem->NoPrescripcion          =   $value["NoPrescripcion"];
            $oItem->Tipo_Tecnologia         =   $value["TipoTec"];
            $oItem->Cantidad                =   $value['CantTotAEntregar'];
            $oItem->CodSerTecAEntregar      =   $value['CodSerTecAEntregar'];
            $oItem->Numero_Entrega          =   $value['NoEntrega'];
            $oItem->Id_Paciente             =   $value['NoIDPaciente'];
            $oItem->Fecha_Maxima_Entrega    =   $value['FecMaxEnt'];
            $oItem->Fecha_Direccionamiento  =   $value['FecDireccionamiento'];
            $oItem->Codigo_Municipio        =   $value['CodMunEnt'];
            $oItem->Estado                  =   $eps['Nombre'];
            $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            echo "<br>$eps[Nombre]<br>";
        
        }else{
          /*  $oItem=new complex ('Z_Mipres','Id_Z_Mipres');
            $oItem->NoIDEPS                 =   $value["NoIDEPS"];
            $oItem->ID                      =   $value["ID"];
            $oItem->IDDireccionamiento      =   $value["IDDireccionamiento"];
            $oItem->NoPrescripcion          =   $value["NoPrescripcion"];
            $oItem->Tipo_Tecnologia         =   $value["TipoTec"];
            $oItem->Cantidad                =   $value['CantTotAEntregar'];
            $oItem->CodSerTecAEntregar      =   $value['CodSerTecAEntregar'];
            $oItem->Numero_Entrega          =   $value['NoEntrega'];
            $oItem->Id_Paciente             =   $value['NoIDPaciente'];
            $oItem->Fecha_Maxima_Entrega    =   $value['FecMaxEnt'];
            $oItem->Fecha_Direccionamiento  =   $value['FecDireccionamiento'];
            $oItem->Codigo_Municipio        =   $value['CodMunEnt'];
            $oItem->Estado                  =   'EPS VACIA O INCORRECTA';
            $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            echo "<br>EPS VACIA O INCORRECTA<br>";*/
            
        }  
        echo "<br>====================================================<br>";
}  
/*
if($dis_borrar!=''){
    $dis_borrar=trim($dis_borrar,',');
    DeleteDireccionamientos($dis_borrar); 

}else{
    echo "termina"; 
}

UnificarDireccionamientos();
ProgramarDireccionamientos();
if($producto_lista!=''){
   EnviarCorreoF();
}*/

//require("crear_productos.php");

/*
if($producto_lista!=''){
   EnviarCorreo();
}

EnviarCorreoNoCreados();
*/
