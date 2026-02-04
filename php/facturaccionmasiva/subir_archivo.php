<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.facturaccionmasiva.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');

require($MY_CLASS . 'PHPExcel.php');
include $MY_CLASS . 'PHPExcel/IOFactory.php';
include $MY_CLASS . 'PHPExcel/Writer/Excel5.php';

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();
$facturaccion=new  Facturacion_Masiva();

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

$modelo = (array) json_decode($modelo);


if (!empty($_FILES['archivo']['name'])){ // Archivo de la archivo de Entrega.
    $posicion1 = strrpos($_FILES['archivo']['name'],'.')+1;
    $extension1 =  substr($_FILES['archivo']['name'],$posicion1);
    $extension1 =  strtolower($extension1);
    $_filename1 = uniqid() . "." . $extension1;
    $_file1 = $MY_FILE . "ARCHIVOS/FACTURACCION/" . $_filename1;
    
    $subido1 = move_uploaded_file($_FILES['archivo']['tmp_name'], $_file1);
        
}

$inputFileName = $MY_FILE . "ARCHIVOS/FACTURACCION/" . $_filename1;

try {
    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    $objPHPExcel = $objReader->load($inputFileName);
} catch(Exception $e) {
    die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
}


$sheet = $objPHPExcel->getSheet(0); 
$highestRow = $sheet->getHighestRow(); 
$highestColumn = 'A';

$facturas=[];
$produc=[];
$prodPen=[];
$tem=[];
$i=-1;
for ($row = 1; $row <= $highestRow; $row++){ $i++;
    //  Read a row of data into an array
    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row);
    if($rowData[0][0]){
        array_push($facturas,$rowData[0][0]);
    }  
}





$file=$MY_FILE . "ARCHIVOS/FACTURACCION/" . $_filename1;
unlink($file);
/* $resultado['Facturas']=$fact;
$resultado['Faltantes']=$facturas_no_encontradas; */

$dis_para_facturar='';
$dispensacionesAnuladasFacturadas=[];

foreach ($facturas as  $value) {
   $dis=ValidarDispensacion($value);
    if($dis){
        $productos=GetProductos($dis['Id_Dispensacion']);
       
        if($productos){
            $dis_para_facturar.=$dis['Id_Dispensacion'].",";
        }
    }else{
        $dis=GetInformacionDispensacion($value);
        array_push($dispensacionesAnuladasFacturadas,$dis);
    }

}



if($dis_para_facturar!=''){
   $dis_para_facturar= trim($dis_para_facturar,','); 
   $facturaccion->Facturacion($dis_para_facturar,$modelo['Funcionario'],$modelo['Servicio']);
}

$resultado['Id_Dispensacion']=$dis_para_facturar;
$resultado['Producto_Sin_Precio']=$produc;
$resultado['Productos_Con_Pendientes']=$prodPen;
$resultado['Dis_Anuladas_Facturadas']=$dispensacionesAnuladasFacturadas;

echo json_encode($resultado);

function ValidarDispensacion($id){
    global $queryObj, $modelo;
    $condicion='';
    //1-BUSCAR IDS DE LOS TIPOS DE SERVICIOS
    //2-REALIZAR LA CONDICION OR CON LOS IDS DE TIPOS DE SERVICIOS
    //2-COLOCAR LA CONDICION OR CON LOS IDS DE TIPOS DE SERVICIOS Y CAMBIAR D.Tipo = POR D.Tipo IN ($ids)
    $ids_tipo_servicio = GetIdsTipoServicio($modelo['Servicio']);
    // if($modelo['Servicio']=='Cohortes'){
    //     $condicion .=" OR Tipo_Servicio=6 ";
    // }

    $query="SELECT D.Id_Dispensacion FROM Dispensacion D INNER JOIN 
     (SELECT 
        Id_Paciente, EPS, Nit
    FROM
        Paciente) P ON D.Numero_Documento = P.Id_Paciente
    WHERE D.Estado_Dispensacion!='Anulada' AND D.Estado_Facturacion='Sin Facturar' AND D.Id_Tipo_Servicio IN($ids_tipo_servicio) AND D.Codigo='$id' AND P.Nit=$modelo[Id_Cliente]";

    $queryObj->SetQuery($query);
    $dis = $queryObj->ExecuteQuery('simple');

  
    
    return $dis;
    
}

function GetProductos($id){
    global $queryObj,$prodPen,$produc,$tem;

    
    $query="SELECT PD.Id_Dispensacion,PD.Id_Producto,(SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=PD.Id_Dispensacion) as Codigo, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,P.Codigo_Cum
    FROM Producto_Dispensacion PD 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE PD.Id_Dispensacion=$id AND PD.Cantidad_Formulada !=PD.Cantidad_Entregada";

    $queryObj->SetQuery($query);
    $pendientes = $queryObj->ExecuteQuery('Multiple');
  

    $agregar=false;

    if(count($pendientes)==0){
        $productos_sin_precio=GetProductosSinPrecio($id);
      
        if(count($productos_sin_precio)==0){
            $agregar=true;
        }else{
            foreach ($productos_sin_precio as $key => $value) {
                $pos=array_search($value['Id_Producto'],$tem);
                if($pos==false){
                    array_push($produc,$value);
                    array_push($tem,$value['Id_Producto']);
                }               
             }
        }
         
    }else{
       foreach ($pendientes as $key => $value) {
        $pos=array_search($value['Id_Producto'],$prodPen);
        if($pos==false){
            array_push($prodPen,$value);
        } 
         
       }
    }

    return $agregar;
   
}

function GetProductosSinPrecio($id){

    global $queryObj,$modelo;

    if($modelo['Servicio']=="Evento"){
        $exits=" AND NOT exists (SELECT Codigo_Cum FROM Producto_Evento WHERE Codigo_Cum=P.Codigo_Cum AND Nit_EPS=$modelo[Id_Cliente] AND Precio>0 )  ";
    }elseif ($modelo['Servicio']=='Cohortes'){
        $exits=" AND NOT exists (SELECT Id_Producto FROM Producto_Cohorte WHERE Id_Producto=PD.Id_Producto AND Nit_EPS=$modelo[Id_Cliente] ) ";
    }

    $query="SELECT PD.Id_Producto,P.Nombre_Comercial,P.Codigo_Cum, IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=P.Codigo_Cum),0) as Precio, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre
    FROM Producto_Dispensacion PD 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE PD.Id_Dispensacion=$id ".$exits." GROUP BY PD.Id_Producto HAVING Precio=0 ";
    

    $queryObj->SetQuery($query);
    $productos_sin_precio = $queryObj->ExecuteQuery('Multiple');

    return $productos_sin_precio;
}

function GetInformacionDispensacion($cod){
    global $queryObj;   

    $query="SELECT D.Codigo,D.Estado_Facturacion ,D.Estado_Dispensacion as Estado, (SELECT CONCAT(Nombre) FROM Servicio WHERE Id_Servicio=D.Id_Servicio ) as Tipo,
    (SELECT CONCAT(Nombre) FROM Tipo_Servicio WHERE Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo_Servicio
     FROM Dispensacion D WHERE  D.Codigo='$cod'";

    $queryObj->SetQuery($query);
    $dis = $queryObj->ExecuteQuery('simple');

    if($dis['Tipo_Servicio']!=''){
        $dis['Tipo']=$dis['Tipo'].' - '.$dis['Tipo_Servicio'];
    }

    return $dis;
}

function GetIdsTipoServicio($tipo_servicio){
    global $queryObj;

    $query="
        SELECT 
            GROUP_CONCAT(DISTINCT Id_Tipo_Servicio) AS Ids
     FROM Tipo_Servicio 
     WHERE  
        Nombre='$tipo_servicio'";

    $queryObj->SetQuery($query);
    $dis = $queryObj->ExecuteQuery('simple');

    return $dis["Ids"];
}

?>