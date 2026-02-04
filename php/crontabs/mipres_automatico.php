<?php
    
    header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    //header('Content-Type: application/json');

	include_once('/home/sigesproph/public_html/class/class.querybasedatos.php');
	include_once('/home/sigesproph/public_html/class/class.http_response.php');
    include_once('/home/sigesproph/public_html/class/class.mipres.php');
    include_once('/home/sigesproph/public_html/class/class.php_mailer.php');

    $queryObj = new QueryBaseDatos();

    $mipres= new Mipres();
   $mail= new EnviarCorreo();
   $fecha_actual = date('Y-m-d'); 
    

    $fini = ( isset( $_REQUEST['fini'] ) ? $_REQUEST['fini'] : $fecha_actual );
    $ffin = ( isset( $_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : $fecha_actual );

    //$fini=$fecha_actual;
    //$ffin=$fecha_actual;
    
    //echo $fini." - ".$ffin; exit;
    //$correos=['compras@prohsa.com','fernando.arciniegas@prohsa.com','freddy.arciniegas@prohsa.com','sistemas@prohsa.com'];
    $correos=['augustoacarrillo@gmail.com'];
 
$f_ini=strtotime($fini);
$f_fin=strtotime($ffin);
for($i=$f_ini;$i<=$f_fin;$i=strtotime(date("Y-m-d",$i).' + 1 DAY')){
    // Logica...
  
  $fecha=date('Y-m-d',$i);
  
  echo $fecha.":<br><br>";
    
  $dishoy=$mipres->GetDireccionamientoPorFecha($fecha); 

  usort($dishoy,'OrderByNumeroEntrega');
  
  
  $dis_borrar='';
  $producto_lista='';
  $idpaciente='';
  $entrega='';
  $dis=[];
  $productos_dis=[];
  
  
    foreach ($dishoy as $value) {
        //var_dump($value);

        echo "<br>PACIENTE -- " . $value['NoIDPaciente'] . "  - Direccionamiento: ".$value["IDDireccionamiento"]. "  - Prescipcion: ".$value["NoPrescripcion"]. "  - EPS: ".$value["NoIDEPS"]."<br>";
        if($value["NoIDEPS"]!=""){ 
            if($value["EstDireccionamiento"]==1||$value["EstDireccionamiento"]==2){
                if($value['TipoTec']=='M'){ 
                GuardarEntrega($value); 
                }else if ($value['TipoTec']!='P'){
                    //echo "entre a P<br>";
                GuardarDireccionamiento($value);     
                }
            }elseif($value["EstDireccionamiento"]==2){
                
                echo "<br>DIRECCIONAMIENTO PROGRAMADO<br>";
            }else{
                echo "<br>DIRECCIONAMIENTO ANULADO<br>";   
            }
        
        }else{
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

    if($producto_lista!=''){
        //echo "entra a enviar correo";
    EnviarCorreo();
    }

    UnificarDireccionamientos();
   // echo "unifica";

  //require("/home/sigespro/public_html/php/mipres/crear_productos.php");
  //echo "crea productos";
}

function GuardarEntrega($dis){
    global $dis_borrar, $producto_lista;

   if(ValidarDireccionamiento($dis['ID']) && ValidarPaciente($dis['NoIDPaciente'])  && ValidarMunicipio($dis['CodMunEnt'],$dis['NoIDEPS'])){
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
            //echo $cum.'<br><br>';
            $idproducto=GetIdProducto($cum);
            $dis['Codigo_Cum']=$cum;
            $dis['Id_Producto']=$idproducto!= false ? $idproducto : '';
            $dis['Tipo_Tecnologia']=$dis['TipoTec'];
            //if($dis['Id_Producto']!=''){
                //if(ValidarProductoLista($cum,$dis['NoIDEPS'])){
                    $dis['Id_Dispensacion_Mipres']=$id_dis;
                    $dis['Cantidad']=$dis['CantTotAEntregar'];
                    $oItem=new complex ('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres');
                    foreach ($dis as $key => $value) {
                        $oItem->$key=$value;
                    }
                    $oItem->save();
                    unset($oItem);
                /*}else{
                    $dis_borrar.=$id_dis.',';
                    $producto_lista.=$dis['Id_Producto'].',';
                    //echo "No hago";
                }*/
           /* }else{
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
            }*/
        }else{
            $dis_borrar.=$id_dis.',';
        }
   }
}

function GuardarDireccionamiento($dis){
    global $dis_borrar, $producto_lista;

   if(ValidarDireccionamiento($dis['ID']) && ValidarPaciente($dis['NoIDPaciente']) && ValidarMunicipio($dis['CodMunEnt'],$dis['NoIDEPS'])){

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
 
    $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres');
    foreach ($dispensacion as $key => $value) {
       $oItem->$key=$value;
    }
    $oItem->save();
    $id_dis = $oItem->getId();
    unset($oItem);
 
   
        $c=explode('-',$dis['CodSerTecAEntregar']);
        //var_dump($c);
        if($c[0]!='cum'){ 
            $cum=str_pad((INT)$c[0], 2, "0", STR_PAD_LEFT); 
           // echo "<br>Este CUM".$cum.'-'.$dis['TipoTec'].'<br><br>';
            $vari=GetIdProductoAsociado($cum, $dis['TipoTec'],$dis['NoIDEPS']);
            $dis['Codigo_Cum']=$cum; //$vari["Codigo_Cum"];
            $dis['Id_Producto']=$vari['Id_Producto']!= false ? $vari['Id_Producto'] : '';
            $dis['Tipo_Tecnologia']=$dis['TipoTec'];
            //if($dis['Id_Producto']!=''){
                //echo "<br>entro al IDProducto<br>";
                //if(ValidarProductoLista($cum,$dis['NoIDEPS'])){ 
                    //echo "<br>Entro a Validacion Producto<br>";
                    $dis['Id_Dispensacion_Mipres']=$id_dis;
                    $dis['Cantidad']=$dis['CantTotAEntregar'];
                    $oItem=new complex ('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres');
                    foreach ($dis as $key => $value) {
                        $oItem->$key=$value;
                    }
                    $oItem->save();
                    unset($oItem);
                /*}else{
                    echo "<br>".$cum." Producto no esta en la lista<br>";
                    $dis_borrar.=$id_dis.',';
                    $producto_lista.=$dis['Id_Producto'].',';
                }*/
               
    
           /* }else{
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
            }*/
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
    $query="SELECT Id_Producto FROM Producto WHERE Codigo_Cum='$cum' OR Codigo_Cum='$cum2'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum['Id_Producto'];
}
function GetIdProductoAsociado($cum, $tec,$nit){

    $tem=explode('-',$cum);
    $cum2=$tem[0].'-'.(INT)$tem[1];
    $cum=str_replace('-','',$cum);
    $query="SELECT PD.Id_Producto, P.Codigo_Cum 
    FROM Producto_Tipo_Tecnologia_Mipres PD 
    INNER JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres 
    INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto 
    INNER JOIN Producto_NoPos PN ON PN.Cum = P.Codigo_Cum AND PN.Id_Cliente = '$nit'
    WHERE (Codigo_Actual='$cum' OR Codigo_Anterior='$cum') AND M.Codigo='$tec' "; 
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);
    
    //var_dump($cum);

    return $cum;
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
    return true;
    
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
    $query="SELECT Cum FROM Producto_NoPos WHERE Cum LIKE '%$cum[0]%'  AND Id_Cliente=$nit ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
    unset($oCon);

    return $data['Cum'] ? true : false;
    //return true;
}
function EnviarCorreo(){
    global $producto_lista,$mail;

    $producto_lista=trim($producto_lista,',');
    $query="SELECT Codigo_Cum,Nombre_Comercial,CONCAT(Principio_Activo,' ',Presentacion,' ',Concentracion,' ', Cantidad,' ', Unidad_Medida) as Producto FROM Producto WHERE Id_Producto IN ($producto_lista) GROUP bY Id_Producto Order BY Nombre_Comercial ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    $contenido=CrearContenido($productos);

    $message='<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Document</title>
    </head>
    <body>
    <center> <img src="https://sigesproph.com.co/IMAGENES/LOGOS/LogoProh.jpg" style="width:80px;"> <br> <h4> Productos que no se encuentra en la lista NoPos </h4></center><br>
    '.$contenido.'
    <br>    
    </body>
    </html>';

    echo "enviando correo ";
    $mail->EnviarMailProductos([],'Productos que no estan en la lista Nopos ',$message,'');

}

function CrearContenido($productos){
    $contenido='';
    $contenido='<table style="width:700px;border:1px dotted #ccc;margin:0 auto;">';

    foreach ($productos as  $p) {
        $con= "<tr style=''><td style='vertical-align:middle;text-align:center;border-bottom:1px dotted #ccc;padding-bottom:10px;'>".$p['Codigo_Cum']."</td><td style='vertical-align:middle;border-bottom:1px dotted #ccc;padding-bottom:10px;'><b>".$p["Nombre_Comercial"]."</b><br>".$p['Producto']."</td></tr><br>";
       
        $contenido.=$con;
    }

    $contenido.='</table>';
return $contenido;
}
 
function UnificarDireccionamientos(){
    $query="SELECT COUNT(*) as Conteo, PD.NoPrescripcion, GROUP_CONCAT(DISTINCT(PD.Id_Dispensacion_Mipres)) AS Id 
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    GROUP BY PD.NoPrescripcion,D.Id_Paciente,D.Numero_Entrega HAVING Conteo>1"; 
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

function ValidarMunicipio($codigo,$nit){
    //echo $nit;
    if($nit=='900226715'){
        return true;
    }else{
        $query="Select Id_Departamento FROM Municipio WHERE Codigo='$codigo'";
        
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
            echo "En el departamento del paciente no se tiene un punto de dispensacion con convenio de esa EPS <br><br>";
            return false;
        } 
    }
   
}

?>