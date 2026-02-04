<?php
ini_set("memory_limit","256M");
ini_set('max_execution_time', 480);

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

    $prescripcion = ( isset( $_REQUEST['prescripcion'] ) ? $_REQUEST['prescripcion'] : '' );

    $correos=['aux.compras@prohsa.com','compras@prohsa.com','fernando.arciniegas@prohsa.com','freddy.arciniegas@prohsa.com','sistemas@prohsa.com'];

    if($prescripcion!=""){
        $dishoy=$mipres->GetDireccionamientoPorPrescripcion($prescripcion);
    }else{
        echo "NO DE PRESCRIPCION NO PUEDE SER VACIO";
        exit;
    }
    
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

usort($dishoy,'OrderByNumeroEntrega');


$dis_borrar='';
$producto_lista='';
$eps_lista='';
$idpaciente='';
$entrega='';
$dis=[];
$productos_dis=[];
$producto_lista_array = [];


foreach ($dishoy as $value) {
       /* var_dump($value);
    exit;*/
        echo "<br>PACIENTE -- " . $value['NoIDPaciente'] . "  - Direccionamiento: ".$value["IDDireccionamiento"]. "  - Prescipcion: ".$value["NoPrescripcion"]. "  - EPS: ".$value["NoIDEPS"]. "  - Tipo Tecnologia: ".$value["TipoTec"]." Cod Municipio: ".$value['CodMunEnt']."<br>";
        if($value["NoIDEPS"]!=""  ){ 
            if($value["EstDireccionamiento"]==1 || $value["EstDireccionamiento"]==2 ){
                if( !validarExistenciaDireccionamiento($value["IDDireccionamiento"]) ){
                    if($value['TipoTec']=='M'){ 
                    GuardarEntrega($value); 
                    }else if ($value['TipoTec']!='P'){
                    GuardarDireccionamiento($value);     
                    }
                }
            }elseif($value["EstDireccionamiento"]==0){
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
                $oItem->Estado                  =   'DIRECCIONAMIENTO ANULADO';
                $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
                $oItem->save();
                unset($oItem);
                echo "<br>DIRECCIONAMIENTO ANULADO<br>";  
                
            }
        
        }else{
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
            $oItem->Estado                  =   'EPS VACIA O INCORRECTA';
            $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            echo "<br>EPS VACIA O INCORRECTA<br>";
            
        }  
        echo "<br>====================================================<br>";
}  

if($dis_borrar!=''){
    echo $dis_borrar; 
    $dis_borrar=trim($dis_borrar,',');
    DeleteDireccionamientos($dis_borrar); 

}else{
    echo "termina"; 
}

UnificarDireccionamientos();
ProgramarDireccionamientos();

if($producto_lista!=''){
   EnviarCorreoF();
}

/*
require("crear_productos.php");


if($producto_lista!=''){
   EnviarCorreo();
}

EnviarCorreoNoCreados();
*/

function validarExistenciaDireccionamiento($idDireccionamiento){
    
    $query = 'SELECT Id_Producto_Dispensacion_Mipres 
            FROM Producto_Dispensacion_Mipres
            WHERE IDDireccionamiento = '.$idDireccionamiento.' LIMIT 1';
            
    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
    unset($oCon);
    
    return $data;
            
}

function GuardarEntrega($dis){
    global $dis_borrar, $producto_lista,$eps_lista, $producto_lista_array;

   if(ValidarDireccionamiento($dis['ID']) && ValidarPaciente($dis['NoIDPaciente'])  && ValidarMunicipio($dis['CodMunEnt'],$dis['NoIDEPS'],$dis) && Validar_Cum($dis['CodSerTecAEntregar'],$dis['NoIDEPS'],$dis) ){
    $dispensacion=$dis;
    $dispensacion['Fecha']=date('Y-m-d H:i:s');
    $dispensacion['Id_Paciente']=$dis['NoIDPaciente'];
    $dispensacion['Fecha_Maxima_Entrega']=$dis['FecMaxEnt'];
    $dispensacion['Numero_Entrega']=$dis['NoEntrega'];
    $dispensacion['Fecha_Direccionamiento']=$dis['FecDireccionamiento'];
    $dispensacion['Id_Servicio']=1;
    $dispensacion['Id_Tipo_Servicio']=3;
    $dispensacion['Codigo_Municipio']=$dis['CodMunEnt'];
    $dispensacion['Tipo_Tecnologia']=$dis['TipoTec'];
    
    #NUEVOS CAMPOS
    $dispensacion['CodEPS']=$dis['CodEPS'];
    $dispensacion['NoIDEPS']=$dis['NoIDEPS'];
    $dispensacion['NoSubEntrega']=$dis['NoSubEntrega'];
    
    $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres');
    foreach ($dispensacion as $key => $value) {
       $oItem->$key=$value;
    }
    $oItem->save();
    $id_dis = $oItem->getId();
    unset($oItem);
   
        $c=explode('-',$dis['CodSerTecAEntregar']);
        if($c[0]!='cum'){
            $cum=(INT)$c[0].'-'.$c[1];
            $cum = trim($cum,"-");
            echo $cum.'<br><br>';
            $prr=GetIdProducto($cum);
            
            $idproducto=$prr["Id_Producto"];
            $pres = $prr["Cantidad_Presentacion"];
            $dis['Codigo_Cum']=$cum;
            $dis['Id_Producto']=$idproducto!= false ? $idproducto : '';
            $dis['Tipo_Tecnologia']=$dis['TipoTec'];
            if($dis['Id_Producto']!=''){
                if(ValidarProductoLista($cum,$dis['NoIDEPS'])){
                    $dis['Id_Dispensacion_Mipres']=$id_dis;
                    
                    if($dis['CantTotAEntregar']<$pres){
                       $dis['Cantidad']=$dis['CantTotAEntregar']; // *$pres
                    }elseif($dis['CantTotAEntregar']==$pres){
                       $dis['Cantidad']=$dis['CantTotAEntregar'];  
                    }elseif($dis['CantTotAEntregar']>$pres){
                        $mod = $dis['CantTotAEntregar'] % $pres;
                        $div = $dis['CantTotAEntregar'] / $pres;
                        if($mod!=0){
                            $dis['Cantidad']=$dis['CantTotAEntregar']-$mod;
                        }else{
                           $dis['Cantidad']=$dis['CantTotAEntregar']; 
                        }
                    } 
                    $dis['CantidadMipres']=$dis['CantTotAEntregar']; 
                    
                    
                    #->NUEVOS CAMPOS
                        
                        $dis['ConTec']=$dis['ConTec']; 
                    #<---
                    
                    $oItem=new complex ('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres');
                    foreach ($dis as $key => $value) {
                        $oItem->$key=$value;
                    }
                    $oItem->save();
                    unset($oItem);
                }else{
                    $oItem=new complex ('Z_Mipres','Id_Z_Mipres');
                    $oItem->NoIDEPS                 =   $dis["NoIDEPS"];
                    $oItem->ID                      =   $dis["ID"];
                    $oItem->IDDireccionamiento      =   $dis["IDDireccionamiento"];
                    $oItem->NoPrescripcion          =   $dis["NoPrescripcion"];
                    $oItem->Tipo_Tecnologia         =   $dis["TipoTec"];
                    $oItem->Cantidad                =   $dis['CantTotAEntregar'];
                    $oItem->CodSerTecAEntregar      =   $dis['CodSerTecAEntregar'];
                    $oItem->Numero_Entrega          =   $dis['NoEntrega'];
                    $oItem->Id_Paciente             =   $dis['NoIDPaciente'];
                    $oItem->Fecha_Maxima_Entrega    =   $dis['FecMaxEnt'];
                    $oItem->Fecha_Direccionamiento  =   $dis['FecDireccionamiento'];
                    $oItem->Codigo_Municipio        =   $dis['CodMunEnt'];
                    $oItem->Estado                  =   'PRODUCTO NO SE ENCUENTRA EN LISTA DE PRECIOS CON EPS';
                    $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
                    $oItem->save();
                    unset($oItem);
                    
                    echo "Producto no en lista de precios ".$dis['CodSerTecAEntregar']."<br><br>";
                     
                     
                    $dis_borrar.=$id_dis.',';
                    $producto_lista.=$dis['Id_Producto'].',';
                   
                    
                    $informacion = [];
                    $informacion['Id_Producto']=$dis['Id_Producto'];
                    $informacion['ID']=$dis['ID'];
                    $informacion['NoPrescripcion']=$dis['NoPrescripcion'];
                    $informacion['NoIDPaciente'] =  $dis['NoIDPaciente'];
                    $informacion['CodMunEnt'] =  $dis['CodMunEnt'];
                    $informacion['NoIDEPS'] =  $dis['NoIDEPS'];
                       
                    array_push($producto_lista_array,$informacion);
                    
                    
                    $eps_lista.=$dis["NoIDEPS"].',';
                    
                }
            }else{
                $oItem=new complex ('Z_Mipres','Id_Z_Mipres');
                $oItem->NoIDEPS                 =   $dis["NoIDEPS"];
                $oItem->ID                      =   $dis["ID"];
                $oItem->IDDireccionamiento      =   $dis["IDDireccionamiento"];
                $oItem->NoPrescripcion          =   $dis["NoPrescripcion"];
                $oItem->Tipo_Tecnologia         =   $dis["TipoTec"];
                $oItem->Cantidad                =   $dis['CantTotAEntregar'];
                $oItem->CodSerTecAEntregar      =   $dis['CodSerTecAEntregar'];
                $oItem->Numero_Entrega          =   $dis['NoEntrega'];
                $oItem->Id_Paciente             =   $dis['NoIDPaciente'];
                $oItem->Fecha_Maxima_Entrega    =   $dis['FecMaxEnt'];
                $oItem->Fecha_Direccionamiento  =   $dis['FecDireccionamiento'];
                $oItem->Codigo_Municipio        =   $dis['CodMunEnt'];
                $oItem->Estado                  =   'PRODUCTO NO REGISTRADO';
                $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
                $oItem->save();
                unset($oItem);
            
                echo "Producto no registrado ".$dis['CodSerTecAEntregar']."<br><br>";

                $id_no_encontrado=GetId($cum);
                if($id_no_encontrado!=''){
                    $oItem=new complex ('Producto_No_Encontrados','Id_Producto_No_Encontrados', $id_no_encontrado);
                    $oItem->Estado='Pendiente';
                    $oItem->save();
                    unset($oItem);
                }else{
                    $oItem=new complex ('Producto_No_Encontrados','Id_Producto_No_Encontrados');
                    $oItem->Codigo_Cum=$cum;
                    $oItem->Fecha=date('Y-m-d H:i:s');
                    $oItem->save();
                    unset($oItem);
                }
                $dis_borrar.=$id_dis.',';
            }
        }else{
            $oItem=new complex ('Z_Mipres','Id_Z_Mipres');
            $oItem->NoIDEPS                 =   $dis["NoIDEPS"];
            $oItem->ID                      =   $dis["ID"];
            $oItem->IDDireccionamiento      =   $dis["IDDireccionamiento"];
            $oItem->NoPrescripcion          =   $dis["NoPrescripcion"];
            $oItem->Tipo_Tecnologia         =   $dis["TipoTec"];
            $oItem->Cantidad                =   $dis['CantTotAEntregar'];
            $oItem->CodSerTecAEntregar      =   $dis['CodSerTecAEntregar'];
            $oItem->Numero_Entrega          =   $dis['NoEntrega'];
            $oItem->Id_Paciente             =   $dis['NoIDPaciente'];
            $oItem->Fecha_Maxima_Entrega    =   $dis['FecMaxEnt'];
            $oItem->Fecha_Direccionamiento  =   $dis['FecDireccionamiento'];
            $oItem->Codigo_Municipio        =   $dis['CodMunEnt'];
            $oItem->Estado                  =   'PRODUCTO NO ESPECIFICADO';
            $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            $dis_borrar.=$id_dis.',';
        }
   }
}


function GuardarDireccionamiento($dis){
    global $dis_borrar, $producto_lista;

   if(ValidarDireccionamiento($dis['ID']) && ValidarPaciente($dis['NoIDPaciente']) && ValidarMunicipio($dis['CodMunEnt'],$dis['NoIDEPS'],$dis)){

    $dispensacion=$dis;
    $dispensacion['Fecha']=date('Y-m-d H:i:s');
    $dispensacion['Id_Paciente']=$dis['NoIDPaciente'];
    $dispensacion['Fecha_Maxima_Entrega']=$dis['FecMaxEnt'];
    $dispensacion['Numero_Entrega']=$dis['NoEntrega'];
    $dispensacion['Fecha_Direccionamiento']=$dis['FecDireccionamiento'];
    $dispensacion['Id_Servicio']=1;
    $dispensacion['Id_Tipo_Servicio']=3;
    $dispensacion['Codigo_Municipio']=$dis['CodMunEnt'];
    $dispensacion['Tipo_Tecnologia']=$dis['TipoTec'];
 
    #NUEVOS CAMPOS
    $dispensacion['CodEPS']=$dis['CodEPS'];
    $dispensacion['NoIDEPS']=$dis['NoIDEPS'];
    $dispensacion['NoSubEntrega']=$dis['NoSubEntrega'];
 
    $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres');
    foreach ($dispensacion as $key => $value) {
       $oItem->$key=$value;
    }
    $oItem->save();
    $id_dis = $oItem->getId();
    unset($oItem);
 
   
        $c=explode('-',$dis['CodSerTecAEntregar']);
        if($c[0]!='cum'){ 
            $cum=str_pad((INT)$c[0], 2, "0", STR_PAD_LEFT); 
            echo $cum.'<br><br>';
            $idproducto=GetIdProductoAsociado($cum, $dis['TipoTec']);
            $dis['Codigo_Cum']=str_replace('-','',$cum);
            $dis['Id_Producto']=$idproducto!= false ? $idproducto : '';
            $dis['Tipo_Tecnologia']=$dis['TipoTec'];
            if($dis['Id_Producto']!=''){
                //if(ValidarProductoLista($cum,$dis['NoIDEPS'])){ 
                    $dis['Id_Dispensacion_Mipres']=$id_dis;
                    $dis['Cantidad']=$dis['CantTotAEntregar'];
                    
                    #NUEVO CAMPO
                    $dis['ConTec']=$dis['ConTec'];
                    
                    $oItem=new complex ('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres');
                    foreach ($dis as $key => $value) {
                        $oItem->$key=$value;
                    }
                    
                    
                    $oItem->save();
                    unset($oItem);
                /*}else{
                
                    $oItem=new complex ('Z_Mipres','Id_Z_Mipres');
                    $oItem->NoIDEPS                 =   $dis["NoIDEPS"];
                    $oItem->ID                      =   $dis["ID"];
                    $oItem->IDDireccionamiento      =   $dis["IDDireccionamiento"];
                    $oItem->NoPrescripcion          =   $dis["NoPrescripcion"];
                    $oItem->Tipo_Tecnologia         =   $dis["TipoTec"];
                    $oItem->Cantidad                =   $dis['CantTotAEntregar'];
                    $oItem->CodSerTecAEntregar      =   $dis['CodSerTecAEntregar'];
                    $oItem->Numero_Entrega          =   $dis['NoEntrega'];
                    $oItem->Id_Paciente             =   $dis['NoIDPaciente'];
                    $oItem->Fecha_Maxima_Entrega    =   $dis['FecMaxEnt'];
                    $oItem->Fecha_Direccionamiento  =   $dis['FecDireccionamiento'];
                    $oItem->Codigo_Municipio        =   $dis['CodMunEnt'];
                    $oItem->Estado                  =   'PRODUCTO NO SE ENCUENTRA EN LISTA DE PRECIOS CON EPS';
                    $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
                    $oItem->save();
                    unset($oItem);
                    
                    $dis_borrar.=$id_dis.',';
                    $producto_lista.=$dis['Id_Producto'].',';
                }*/
            }else{
                echo "Producto no registrado ".$dis['CodSerTecAEntregar']."<br><br>";
                
                
		$oItem=new complex ('Z_Mipres','Id_Z_Mipres');
                $oItem->NoIDEPS                 =   $dis["NoIDEPS"];
                $oItem->ID                      =   $dis["ID"];
                $oItem->IDDireccionamiento      =   $dis["IDDireccionamiento"];
                $oItem->NoPrescripcion          =   $dis["NoPrescripcion"];
                $oItem->Tipo_Tecnologia         =   $dis["TipoTec"];
                $oItem->Cantidad                =   $dis['CantTotAEntregar'];
                $oItem->CodSerTecAEntregar      =   $dis['CodSerTecAEntregar'];
                $oItem->Numero_Entrega          =   $dis['NoEntrega'];
                $oItem->Id_Paciente             =   $dis['NoIDPaciente'];
                $oItem->Fecha_Maxima_Entrega    =   $dis['FecMaxEnt'];
                $oItem->Fecha_Direccionamiento  =   $dis['FecDireccionamiento'];
                $oItem->Codigo_Municipio        =   $dis['CodMunEnt'];
                $oItem->Estado                  =   'PRODUCTO NO REGISTRADO';
                $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
                $oItem->save();
                unset($oItem);
                
                $id_no_encontrado=GetId($cum);
                if($id_no_encontrado!=''){
                    $oItem=new complex ('Producto_No_Encontrados','Id_Producto_No_Encontrados', $id_no_encontrado);
                    $oItem->Estado='Pendiente';
                    $oItem->save();
                    unset($oItem);
                }else{
                    $oItem=new complex ('Producto_No_Encontrados','Id_Producto_No_Encontrados');
                    $oItem->Codigo_Cum=$cum;
                    $oItem->Fecha=date('Y-m-d H:i:s');
                    $oItem->save();
                    unset($oItem);
                }
                $dis_borrar.=$id_dis.',';
            }
        }else{
            $dis_borrar.=$id_dis.',';
        }
   }
  
}
function OrderByIdPaciente($a,$b){
    return strnatcmp($a['NoIDPaciente'],$b['NoIDPaciente']);
}
function OrderByNumeroEntrega($a,$b){
    return strnatcmp($a['NoEntrega'],$b['NoEntrega']);
}

function GetIdProducto($cum){

    $tem=explode('-',$cum);
    $cum2=$tem[0].'-'.(INT)$tem[1];
    $query="SELECT Id_Producto, Cantidad_Presentacion FROM Producto WHERE Codigo_Cum='$cum' OR Codigo_Cum='$cum2'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum;
}
function GetIdProductoAsociado($cum, $tec){

//echo $tec;
    $tem=explode('-',$cum);
    $cum2=$tem[0].'-'.(INT)$tem[1];
    $cum=str_replace('-','',$cum);
    $query="SELECT Id_Producto FROM Producto_Tipo_Tecnologia_Mipres PD INNER JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres WHERE (Codigo_Actual='$cum') AND M.Codigo='$tec' LIMIT 1"; 
    // OR Codigo_Anterior='$cum'
    //echo $query."<br><br>";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum['Id_Producto'];
}

function Validar_Cum($cum,$eps,$value){
    
    if($eps=='901097473'){
        $tem=explode('-',$cum);
        $cum2=$tem[0].'-'.$tem[1];
        $query="SELECT Id_Cum_Excluidos FROM Z_Cum_Excluidos WHERE Cum LIKE '%".$cum2."%'";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $data = $oCon->getData();
        unset($oCon);
     
        if($data['Id_Cum_Excluidos']){
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
            $oItem->Estado                  =   'PRODUCTO EN LISTA DE EXCLUIDOS';
            $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            echo "El Cum ".$cum." Esta en la lista de excluidos por la EPS MEDIMAS<br>";
        }
    
        return $data['Id_Cum_Excluidos'] ? false : true;
    }else{
        return true;
    }
    
}


function ValidarDireccionamiento($id){
    $query="SELECT PDM.ID, PDM.NoPrescripcion FROM Producto_Dispensacion_Mipres PDM INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres WHERE PDM.ID='$id'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
    unset($oCon);
 
    if($data['ID']){
        echo "La Prescipcion ".$data['NoPrescripcion']." ya esta creada en el sistema<br>";
    }

    return $data['ID'] ? false : true;
}

function ValidarPaciente($idpaciente){
    if($idpaciente==''){
        $data = false;
    }else{
        $query="SELECT Id_Paciente FROM Paciente WHERE Id_Paciente='$idpaciente'";
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $data = $oCon->getData();
        unset($oCon);
        
    }

    if (!$data) {
        echo "<br>El paciente $idpaciente no está creado en sigespro.<br>";
    }

    return $data ? true : false;
}

function DeleteDireccionamientos($dis){
    $query="DELETE FROM Dispensacion_Mipres WHERE Id_Dispensacion_Mipres IN ($dis) ";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    $query="DELETE FROM Producto_Dispensacion_Mipres WHERE Id_Dispensacion_Mipres IN ($dis) ";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}

function GetId($cum){
    $query="SELECT Id_Producto_No_Encontrados  FROM Producto_No_Encontrados WHERE Codigo_Cum='$cum' LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
    unset($oCon);

    return $data['Id_Producto_No_Encontrados'] ? $data['Id_Producto_No_Encontrados'] : '';
}
function ValidarProductoLista($cum,$nit){
    $cum=explode('-',$cum);
    $query="SELECT Cum FROM Producto_NoPos PN INNER JOIN Lista_Producto_Nopos LPN ON LPN.Id_Lista_Producto_Nopos = PN.Id_Lista_Producto_Nopos WHERE PN.Cum LIKE '%$cum[0]%' AND LPN.Id_Cliente=$nit ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
    unset($oCon);

    return $data['Cum'] ? true : false;
    //return true;
}
function EnviarCorreoF(){
    global $producto_lista,$mail,$correos,$eps_lista,$producto_lista_array;

    /* Logica antigua
    
    $producto_lista=trim($producto_lista,',');
    $eps_lista=trim($eps_lista,',');
    
    $prods= explode(",",$producto_lista);
    $eps = explode(",",$eps_lista);
    $j=-1;
    
    $productos=[];
    $prod_act='';
    foreach($prods as $prod){ $j++;
    	if($prod!=$prod_act){
    	    $prod_act=$prod;
    	    $query="
	    SELECT Codigo_Cum,Nombre_Comercial,CONCAT(Principio_Activo,' ',Presentacion,' ',Concentracion,' ', Cantidad,' ', Unidad_Medida) as Producto,
	           (SELECT Nombre FROM Cliente WHERE Id_Cliente= ".$eps[$j]." ) AS EPS
	    FROM Producto 
	    WHERE Id_Producto = $prod 
	    GROUP BY Id_Producto ORDER BY Nombre_Comercial ";
	
	    $oCon = new consulta();
	    $oCon->setQuery($query);
	    $producto = $oCon->getData();
	    unset($oCon);
	    $productos[]=$producto;
	  }
    
    }*/
    // logica 09-06-20
    $productos_filtrados=[];
    foreach ($producto_lista_array as $key => $dato) {
        if(count(multi_array_search($productos_filtrados, array('Id_Producto' => $dato['Id_Producto'],'NoIDPaciente'=>$dato['NoIDPaciente'])))==0){
       
            array_push($productos_filtrados,$dato);
        
            $query="
            SELECT P.Codigo_Cum,P.Nombre_Comercial,CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Producto,
            (SELECT Nombre FROM Cliente WHERE Id_Cliente= ".$dato['NoIDEPS']." ) AS EPS,
            IFNULL((SELECT CONCAT_WS(' ',P.Id_Paciente,P.Primer_Nombre,P.Primer_Apellido) Nombre FROM Paciente P WHERE Id_Paciente='".$dato['NoIDPaciente']."'),'".$dato['NoIDPaciente']."') AS Paciente,
            T.Municipio, T.Departamento, $dato[ID] 'ID', $dato[NoPrescripcion] 'NoPrescripcion'
              
            FROM Producto P
            INNER JOIN (SELECT M.Codigo, M.Nombre as Municipio, D.Nombre AS Departamento FROM Municipio M INNER JOIN Departamento D ON D.Id_Departamento = M.Id_Departamento) T ON T.Codigo ='".$dato['CodMunEnt'] ."'
            WHERE P.Id_Producto = $dato[Id_Producto]
            
            
            GROUP BY Id_Producto ORDER BY Nombre_Comercial ";
           
            $oCon = new consulta();
            $oCon->setQuery($query);
            $producto = $oCon->getData();
            unset($oCon);
            $productos[]=$producto;
        
       }
    }
    
	    	

    $contenido=CrearContenido($productos);

    $message='<!DOCTYPE html>
    <html lang="en">
    <head><meta charset="gb18030">
        
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Document</title>
    </head>
    <body>
    <center> <img src="https://192.168.40.201/IMAGENES/LOGOS/LogoProh.jpg" style="width:80px;"> <br> <h4> Productos que no se encuentran en la lista NoPos </h4></center><br>
    '.$contenido.'
    <br>    
    </body>
    </html>';

    echo "enviando correo ";
    $mail->EnviarMailProductos($correos,'Productos que no estan en la lista Nopos ',$message,'');

}
function EnviarCorreoNoCreados(){
     global $mail,$correos;

    $productos = GetProductos();

    $contenido=CrearContenido2($productos);

    $message='<!DOCTYPE html>
    <html lang="en">
    <head>
        
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Document</title>
    </head>
    <body>
    <center> <img src="https://192.168.40.201/IMAGENES/LOGOS/LogoProh.jpg" style="width:80px;"> <br> <h4> Productos que no se pudieron crear / No existentes en Invima</h4></center><br>
    '.$contenido.'
    <br>    
    </body>
    </html>';

    echo "enviando correo 2";
    $mail->EnviarMailProductos($correos,'Productos que no se pudieron crear',$message,'');
}


function CrearContenido($productos){
    /* logica antigua
       $contenido='';
    $contenido='<table style="width:700px;border:1px dotted #ccc;margin:0 auto;">';

    foreach ($productos as  $p) {
        $con= "<tr style=''><td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'>".$p['EPS']."</td><td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'>".$p['Codigo_Cum']."</td><td style='vertical-align:middle;border-bottom:1px dotted #ccc;padding-bottom:10px;'><b>".$p["Nombre_Comercial"]."</b><br>".$p['Producto']."</td></tr><br>";
       
        $contenido.=$con;
    }

    */
    //logica nueva 09-06-2020
    
    
    $contenido='';
    $contenido="<table style='width:1100px;border:1px dotted #ccc;margin:0 auto;>'
            <thead style='padding-bottom:50px;border-bottom:1px dotted #ccc; '>
                <tr>
                    <th style='width:180px;'> No. Prescripción </th>
                    <th style='width:80px;'> ID </th>
                    <th style='width:100px;'> EPS </th>
                    <th style='width:180px;'> Codigo_Cum </th>
                    <th style='width:180px;'> Producto </th>
                    <th style='width:180px;'> Paciente </th>
                    <th style='width:180px;'> Departamento </th>
                    <th style='width:180px;'> Municipio </th>
                  
                <tr>
            </thead>
            <tbody>
    ";
    foreach ($productos as  $p) {
             $con= "<tr style=''>
        <td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px; ;'> "
        
        .$p['NoPrescripcion']."</td>
        <td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px; '> "
        .$p['ID']."</td>
        <td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'>"  
        .$p['EPS']."</td>
        <td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px; padding-right:15;'>".
        $p['Codigo_Cum']."</td>
        <td style='vertical-align:middle;border-bottom:1px dotted #ccc;padding-bottom:10px; padding-right:10px'><b>".$p["Nombre_Comercial"]."</b>
        
        <br>".$p['Producto']."</td>
        
        <td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'>"
        
        .$p['Paciente']."</td>

        <td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px; padding-right:10px'>"
        .$p['Departamento']."</td>
        <td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px; padding-right:10px'>"
        .$p['Municipio']."</td><td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'>
        
       
        </tr><br>";
       
        $contenido.=$con;
    }

    $contenido.='
        </tbody>
    </table>';
return $contenido;
}
function CrearContenido2($productos){
    $contenido='';
    $contenido='<table style="width:700px;border:1px dotted #ccc;margin:0 auto;">';

    foreach ($productos as  $p) {
        $con= "<tr style=''><td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'>".$p['Codigo_Cum']."</td></tr><br>";
       
        $contenido.=$con;
    }

    $contenido.='</table>';
return $contenido;
}
 
function UnificarDireccionamientos(){
    $query="SELECT COUNT(DISTINCT(PD.Id_Dispensacion_Mipres)) as Conteo, PD.NoPrescripcion, GROUP_CONCAT(DISTINCT(PD.Id_Dispensacion_Mipres)) AS Id 
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    WHERE D.Estado ='Pendiente' AND D.Bandera='Normal'
    GROUP BY PD.NoPrescripcion,D.Id_Paciente,D.Numero_Entrega,DATE(D.Fecha_Direccionamiento) HAVING Conteo>1 ORDER BY D.Id_Dispensacion_Mipres DESC"; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
    
    foreach ($productos as $p) {
        $id=$p['Id'];
        $tem=explode(',',$id);
        for ($i=0; $i <count($tem) ; $i++) { 
           if($i==0){
                $query='UPDATE Producto_Dispensacion_Mipres SET Id_Dispensacion_Mipres ='.$tem[0].'
                WHERE Id_Dispensacion_Mipres IN ('.$id.') ';
                
                $oCon= new consulta();
                $oCon->setQuery($query);     
                $oCon->createData();     
                unset($oCon);
           }else{
               
            $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $tem[$i]); 
            $oItem->delete();
            unset($oItem);
           }
        }
    }
}
function ProgramarDireccionamientos(){
    global $mipres;
    $query="SELECT D.Id_Dispensacion_Mipres, D.Fecha_Maxima_Entrega ,PD.* 
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    WHERE D.Estado ='Pendiente' 
    AND DATE(D.Fecha) = CURDATE() 
    "; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $direccionamientos = $oCon->getData();
    unset($oCon);
    
    foreach ($direccionamientos as $pm) {
        $codigo_sede_mp=GetCodigoSede();
        $nit_mp=GetNitProh();
        
        $data_mp['ID']=(INT)$pm['ID'];
        $data_mp['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
        $data_mp['TipoIDSedeProv']='NI';
        $data_mp['NoIDSedeProv']=$nit_mp;
        $data_mp['CodSedeProv']=$codigo_sede_mp;
        $data_mp['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
        $data_mp['CantTotAEntregar']=$pm['Cantidad'];
        
        $respuesta=$mipres->Programacion($data_mp);
        var_dump($respuesta);
        echo "<br><br>";
        if($respuesta[0]['Id']){
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm["Id_Producto_Dispensacion_Mipres"]);
            $oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
            $oItem->Fecha_Programacion = date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            
            $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$pm["Id_Dispensacion_Mipres"]);
            $oItem->Estado="Programado";
            $oItem->save();
            unset($oItem);
        }else{
            echo "No pude programar<br>";
        }
    } 
}

function GetCodigoSede(){
    $query = 'SELECT Codigo_Sede FROM Configuracion WHERE Id_Configuracion=1';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $dato = $oCon->getData();
    
    return $dato['Codigo_Sede'];
}

function GetNitProh(){
    $query = 'SELECT NIT FROM Configuracion WHERE Id_Configuracion=1';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $dato = $oCon->getData();

    $n=explode('-',$dato['NIT']);
    $nit=$n[0];
    $nit=str_replace('.','',$nit);
    return $nit;
    
}


function ValidarMunicipio($codigo,$nit,$value){
    $query="Select Nombre, Id_Departamento FROM Municipio WHERE Codigo='$codigo'";
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $municipio = $oCon->getData();
    unset($oCon);
    


    if($municipio){
        $query="SELECT Id_Punto_Dispensacion FROM Punto_Dispensacion WHERE Departamento=$municipio[Id_Departamento] AND Tipo_Dispensacion='Entrega'  ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $punto = $oCon->getData();
        unset($oCon);
    }
    if($punto){
        $query="SELECT Id_Punto_Dispensacion FROM Punto_Cliente WHERE Id_Cliente=$nit AND Id_Punto_Dispensacion=$punto[Id_Punto_Dispensacion] ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $c = $oCon->getData();
        unset($oCon);
    }
    if($municipio && $punto && $c){
        return true;
    }else{
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
        $oItem->Estado                  =   'DEPARTAMENTO O MUNICIPIO NO INCLUIDO EN CONVENIO CON EPS';
        $oItem->Fecha_Actualizacion     =   date("Y-m-d H:i:s");
        $oItem->save();
        unset($oItem);
        echo "En el municipio <b>".$municipio["Nombre"]."</b> del paciente no se tiene un punto de dispensacion con convenio de esa EPS <br><br>";
        return false;
    } 
   
}


function multi_array_search($array, $search){
    $result = array();
    foreach ($array as $key => $value)
    {
      foreach ($search as $k => $v)
      {
        if (!isset($value[$k]) || $value[$k] != $v)
        {
          continue 2;
        }
      }
      $result[] = $key;
    }
    return $result;
}


?>