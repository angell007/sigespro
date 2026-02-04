<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','256M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array) json_decode($datos,true);
$band_estado = isset($_REQUEST['estado']) ? $_REQUEST['estado'] : false;


function limpiar($String)
{
$String = str_replace("  "," ",$String);
$String = str_replace("á","a",$String);
$String = str_replace("Á","A",$String);
$String = str_replace("Í","I",$String);
$String = str_replace("í","i",$String);
$String = str_replace("é","e",$String);
$String = str_replace("É","E",$String);
$String = str_replace("ó","o",$String);
$String = str_replace("Ó","O",$String);
$String = str_replace("ú","u",$String);
$String = str_replace("Ú","U",$String);
$String = str_replace("ç","c",$String);
$String = str_replace("Ç","C",$String);
$String = str_replace("ñ","n",$String);
$String = str_replace("Ñ","N",$String);
$String = str_replace("Ý","Y",$String);
$String = str_replace("ý","y",$String);
$String = str_replace("'","",$String);
$String = str_replace('"',"",$String);
$String = str_replace('\'',"",$String);
$String = str_replace('º',"",$String);
$String = str_replace('\n'," ",$String);
$String = str_replace('\t'," ",$String);
$String = str_replace('\r'," ",$String);
str_replace('?',"",$String);
$String = utf8_encode(strtoupper(trim($String)));
return $String;
}

if ($band_estado) {
    if ($band_estado == 'Si') {
        inactivarPacientes($datos);
    }
}

$respuesta=[];
$query_insert=[];
$errores=[];
$insert = true; // Variable bandera para insertar.

$query_cabecera='INSERT IGNORE INTO `Paciente` (`Tipo_Documento`, `Id_Paciente`, `Primer_Apellido`, `Segundo_Apellido`, `Primer_Nombre`, `Segundo_Nombre`, `Fecha_Nacimiento`, `Genero`, `Id_Departamento`, `Cod_Departamento`, `Codigo_Municipio`, `Cod_Municipio_Dane`, `Cod_Municipio_Dian`, `Id_Nivel`, `Id_Regimen`, `Direccion`, `Telefono`,`EPS`,`Nit`,`Estado`)';

if (!empty($_FILES['Archivo']['name'])){

    $handle = fopen($_FILES['Archivo']['tmp_name'], "r");
    
    if($handle){
    /* $query = "SELECT Id_Paciente FROM Paciente";
    
    $oCon= new consulta();
    $oCon->setTipo("Multiple");
    $oCon->setQuery($query);
    $pacientes = $oCon->getData();
    unset($oCon);
    $id_pacientes = array_column($pacientes, 'Id_Paciente'); */

        
    $i=0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) { $i++;

            if (count($data)==11) {
                    // $pos = array_search($data[1], $id_pacientes );
                    
                    $pos = pacienteRegistrado($data[1]);
                
                    $id_dep = explode('-',$datos['Dep'])[0];
                    $cod_dep = explode('-',$datos['Dep'])[1];
                    $id_mun = explode('-',$datos['Mun'])[0];
                    $cod_mun_dane = explode('-',$datos['Mun'])[2];
                    $cod_mun_dian = explode('-',$datos['Mun'])[1];
                    $nom_eps = explode('-',$datos['EPS'])[0];
                    $nit_eps = explode('-',$datos['EPS'])[1];
                    $id_reg = $datos['Reg'];

                    if($pos){

                    //    $errores[]=str_replace("?","Ñ",limpiar(utf8_decode($data[0])));
                    $oItem = new complex('Paciente','Id_Paciente',$data[1], 'Varchar');
                    $oItem->Tipo_Documento = $data[0];
                    $oItem->Primer_Apellido = utf8_encode($data[2]);
                    $oItem->Segundo_Apellido = utf8_encode($data[3]);
                    $oItem->Primer_Nombre = utf8_encode($data[4]);
                    $oItem->Segundo_Nombre = utf8_encode($data[5]);
                    $oItem->Fecha_Nacimiento = $data[6];
                    $oItem->Genero = $data[7];
                    $oItem->Id_Departamento = $id_dep;
                    $oItem->Cod_Departamento = $cod_dep;
                    $oItem->Codigo_Municipio = $id_mun;
                    $oItem->Codigo_Municipio_Dane = $cod_mun_dane;
                    $oItem->Codigo_Municipio_Dian = $cod_mun_dian;
                    $oItem->Id_Nivel = $data[8];
                    $oItem->Id_Regimen = $id_reg;
                    $oItem->Direccion = utf8_encode($data[9]);
                    $oItem->Telefono = utf8_encode($data[10]);
                    $oItem->EPS = $nom_eps;
                    $oItem->Nit = $nit_eps;
                    $oItem->Estado = 'Activo';

                    $oItem->save();
                    unset($oItem);
                }else{

                    $query_insert[]="('$data[0]','$data[1]','".utf8_encode($data[2])."','".utf8_encode($data[3])."','".utf8_encode($data[4])."','".utf8_encode($data[5])."','$data[6]','$data[7]',$id_dep,$cod_dep,'$id_mun',$cod_mun_dane,$cod_mun_dian,$data[8],$id_reg,'".utf8_encode($data[9])."','".utf8_encode($data[10])."','$nom_eps','$nit_eps','Activo')";

                    if (count($query_insert) == 1000) {
                        $oCon= new consulta();
                        $query = $query_cabecera." VALUES ".implode(",",$query_insert);
                        $oCon->setQuery($query);
                        $consultas = $oCon->createData();
                        unset($oCon);

                        $query_insert = [];
                    
                    }
                    
                            
                }
            } else {
                $insert = false;
            }
            
        }

        if ($insert) {
            /* $query_cabecera='INSERT IGNORE INTO `Paciente` (`Tipo_Documento`, `Id_Paciente`, `Primer_Apellido`, `Segundo_Apellido`, `Primer_Nombre`, `Segundo_Nombre`, `Fecha_Nacimiento`, `Genero`, `Id_Departamento`, `Cod_Departamento`, `Codigo_Municipio`, `Cod_Municipio_Dane`, `Cod_Municipio_Dian`, `Id_Nivel`, `Id_Regimen`, `Direccion`, `Telefono`,`EPS`,`Nit`)'; */

            // echo $query_cabecera." VALUES ".trim($query_insert,",");
            // exit;

            /* if (count($query_insert) > 0) {
                $oCon= new consulta();
                $query = $query_cabecera." VALUES ".implode(",",$query_insert);
                $oCon->setQuery($query);
                $consultas = $oCon->createData();
                unset($oCon);
            
            } */

            if (count($query_insert) > 0) {
                $oCon= new consulta();
                $query = $query_cabecera." VALUES ".implode(",",$query_insert);
                $oCon->setQuery($query);
                $consultas = $oCon->createData();
                unset($oCon);
            
            }
            
            $respuesta["Tipo"]="success";
            $respuesta["Mensaje"]="Pacientes Actualizado Correctamente";
            $respuesta["Titulo"]="Carga Exitosa";
        } else {
            $respuesta["Tipo"]="error";
            $respuesta["Mensaje"]="El numero de columnas no corresponde.";
            $respuesta["Titulo"]="Error con Archivo";
        }
        
    }else{
        $respuesta["Tipo"]="error";
        $respuesta["Mensaje"]="El Archivo CSV no se deja abrir";
        $respuesta["Titulo"]="Error con Archivo";
    }
    
   
}else{
    $respuesta["Tipo"]="error";
    $respuesta["Mensaje"]="El Archivo CSV no existe o se encuentra vacio";
    $respuesta["Titulo"]="Error con Archivo";
}



echo json_encode($respuesta);

function pacienteRegistrado($id_paciente){
    $oItem = new complex('Paciente','Id_Paciente',$id_paciente, 'Varchar');
    $resultado = $oItem->getData();
    unset($oItem);

    if ($resultado) {
        return true;
    }

    return false;
}

function inactivarPacientes($datos) {
    $id_reg = $datos['Reg'];
    $id_mun = explode('-',$datos['Mun'])[0];
    
    $query = "UPDATE Paciente SET Estado = 'Inactivo' WHERE Id_Regimen = $id_reg AND Codigo_Municipio = $id_mun";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}
?>