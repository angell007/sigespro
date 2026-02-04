<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$configuracion = new Configuracion();

$codigos_rem='';

date_default_timezone_set('America/Bogota');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array) json_decode(utf8_decode($datos));

$dispensacion=GetDispensacion($datos['Id_Dispensacion']);

Guardarhistorico();
GenerarAlerta();
EliminarActa();
GenerarActividadDispensacion();

$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha eliminado el acta de entrega de la dispensación corectamente! ');
$response = $http_response->GetRespuesta();

echo json_encode($response);

  function BodegaAplicaCategoriasSeparables($id_bodega) {
       global $queryObj;
     $query = "SELECT * FROM Bodega WHERE Id_Bodega = $id_bodega";  

     $queryObj->SetQuery($query);
     $res = $queryObj->ExecuteQuery('simple');

     return $res['Aplica_Separacion_Categorias'] == 'Si' ? true : false;
  }

  function GetDispensacion($id){
    global $queryObj;
    $query = "SELECT Identificacion_Funcionario, Id_Dispensacion, Acta_Entrega,Codigo FROM Dispensacion WHERE Id_Dispensacion = $id";  

    $queryObj->SetQuery($query);
    $res = $queryObj->ExecuteQuery('simple');

    return $res;
  }

  function Guardarhistorico(){
      global $dispensacion,$datos;

      $oItem=new complex("Actas_Dispensacion_Eliminada", "Id_Actas_Dispensacion_Eliminada");
      $oItem->Id_Dispensacion=$datos['Id_Dispensacion'];
      $oItem->Acta_Entrega=$dispensacion['Acta_Entrega'];
      $oItem->Identificacion_Funcionario=$datos['Funcionario'];
      $oItem->Observacion=$datos['Observacion'];
      $oItem->save();
      unset($oItem);
  }

  function GenerarAlerta(){
      global $dispensacion,$datos;
    $oItem= new complex("Alerta","Id_Alerta");
    $oItem->Identificacion_Funcionario=$dispensacion['Identificacion_Funcionario'];
    $oItem->Tipo="Dispensacion";
    $oItem->Detalles="Se ha eliminado el acta de entrega de la dispensacion: $datos[Codigo] por el siguiente motivo: $datos[Observacion] por favor vuelva adjuntar el acta!";
    $oItem->Modulo="$dispensacion[Codigo]";
    $oItem->save();
    unset($oItem);
  }

  function EliminarActa(){
    global $datos;
  $query = 'UPDATE Dispensacion SET Acta_Entrega = NULL, Estado_Acta = "Con Observacion"
            WHERE Id_Dispensacion = '.$datos['Id_Dispensacion'];

    $oCon= new consulta();
    $oCon->setQuery($query);     
    $oCon->createData();     
    unset($oCon);
  }

  function GenerarActividadDispensacion(){   
    global $datos;
 
     $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
     $ActividadDis["Id_Dispensacion"] = $datos['Id_Dispensacion'];
     $ActividadDis["Identificacion_Funcionario"] = $datos['Funcionario'];
     $ActividadDis["Detalle"] = $datos['Observacion'];
     $ActividadDis["Estado"] = "Eliminada";
     
     $oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
     foreach($ActividadDis as $index=>$value) {
         $oItem->$index=$value;
     }
     $oItem->save();
     unset($oItem);
   
  }
?>





