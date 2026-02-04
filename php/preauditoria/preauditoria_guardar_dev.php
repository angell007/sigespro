<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require('../../class/class.guardar_archivos.php');

//Objeto de la clase que almacena los archivos    
$storer = new FileStorer();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );
$origen = ( isset( $_REQUEST['origen'] ) ? $_REQUEST['origen'] : 'Auditor' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$celular_paciente = ( isset( $_REQUEST['celular_paciente'] ) ? $_REQUEST['celular_paciente'] : '' );

$datos = (array) json_decode($datos , true);
$soportes=(array) json_decode($soportes , true);
$productos=(array) json_decode($productos , true);

/* var_dump($datos);
// var_dump($soportes);
// var_dump($productos);
// var_dump($_FILES);
exit; */

if (isset($datos['Id_Auditoria']) && $datos['Id_Auditoria'] != '') {
    $oItem = new complex('Auditoria','Id_Auditoria',$datos['Id_Auditoria']);
    $dispensador_preauditoria = $oItem->Funcionario_Preauditoria;
    $oItem->Funcionario_Preauditoria = $datos['Funcionario_Preauditoria'];
    $oItem->Punto_Preauditoria = $datos['Punto_Preauditoria'];
    $oItem->Dispensador_Preauditoria = $dispensador_preauditoria;
    $oItem->save();
    unset($oItem);
} else {
    $datos["Fecha_Preauditoria"]=date("Y-m-d H:i:s");

$datos['Origen'] = $origen;

$oItem = new complex("Auditoria","Id_Auditoria");
if ($origen == 'Auditor') {
    if($datos["Turnero"]!=""){
        $datos["Id_Turnero"]=$datos["Turnero"];
    }
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_auditoria = $oItem->getId();
unset($oItem);

/* ## ACTUALIZAR CELULAR PACIENTE
if ($celular_paciente != '' && $celular_paciente != null) {
    updateCelularPaciente($datos['Id_Paciente'], $celular_paciente);
} */

$nombre_archivo = '';

// if (!file_exists( $MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria)) {
//     mkdir($MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria, 0777, true);
// }
if (!empty($_FILES['Archivo']['name'])){
    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$id_auditoria.'/');
    $nombre_archivo = $nombre_archivo[0];

    // $posicion1 = strrpos($_FILES['Archivo']['name'],'.')+1;
    // $extension1 =  substr($_FILES['Archivo']['name'],$posicion1);
    // $extension1 =  strtolower($extension1);
    // $_filename1 = uniqid() . "." . $extension1;
    // $_file1 = $MY_FILE . "IMAGENES/AUDITORIAS/".$id_auditoria."/" . $_filename1;
    
    // $subido1 = move_uploaded_file($_FILES['Archivo']['tmp_name'], $_file1);
    // if ($subido1){		
    //     @chmod ( $_file1, 0777 );
    //     $nombre_archivo = $_filename1;
    // } 
}
if( $nombre_archivo){
    $oItem = new complex("Auditoria","Id_Auditoria",$id_auditoria );
    $oItem->Archivo=$nombre_archivo;
    $oItem->save();
    unset($oItem);
}
foreach($soportes as $soporte){ $i++;
    $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria");
    $soporte['Id_Auditoria']=$id_auditoria;
    foreach($soporte as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
}

if ($origen == 'Auditor') {
    if($datos["Turnero"]!==""){
        $oItem = new complex("Turnero","Id_Turnero",$datos["Turnero"]);
        $oItem->Id_Auditoria = $id_auditoria;
        $oItem->Estado = "Espera";
        $oItem->save();
        unset($oItem);
    }else{
        if($punto!=''&& $punto!=0){
            $oItem = new complex("Turnero","Id_Turnero");
            $oItem->Identificacion_Persona = $datos["Id_Paciente"];
            $oItem->Persona =$datos["Nombre"];
            $oItem->Fecha=date("Y-m-d");
            // $oItem->Hora_Turno = date("H:i:s");
            $oItem->Hora_Turno = "23:59:59";
            $oItem->Id_Turneros = $punto;
            $oItem->Tipo ='OtroServicio';
            $oItem->Id_Auditoria = $id_auditoria;
            $oItem->Estado = "Espera";
            $oItem->save();
            unset($oItem);   
        }
      
    }
}

}


$resultado['mensaje'] = "¡Pre-Auditoria Guardada Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

function updateCelularPaciente($paciente, $celular)
{
    $oItem = new complex('Paciente','Id_Paciente',$paciente,'Varchar');
    $oItem->Telefono = $celular;
    $oItem->save();
    unset($oItem);

    return true;
}

?>