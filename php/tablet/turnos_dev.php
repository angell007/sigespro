<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    $punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );
    $no_pos = ( isset( $_REQUEST['no_pos'] ) ? $_REQUEST['no_pos'] : '' );

    $query = '
        SELECT
            *
        FROM Turnero
        WHERE
            Fecha = "'.date("Y-m-d")
            .'" AND Id_Turneros = '.$punto
            .' AND Estado = "Espera" 
             ORDER BY Hora_Turno ASC, Prioridad ASC';

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $turnos = $oCon->getData();
    unset($oCon);

    $i=-1;
    foreach($turnos as $turno){ $i++;
        $turnos[$i]["Hora_Turno"]=date("h:ia",strtotime($turno["Hora_Turno"]));
        $turnos[$i]["Tiempo_Espera"]="8 Minutos";
        $oItem = new complex("Auditoria","Id_Auditoria",$turno["Id_Auditoria"]);
        $auditoria = $oItem->getData();
        unset($oItem);
        if(isset($auditoria["Id_Auditoria"])){
          $turnos[$i]["Documento"]=$auditoria["Archivo"];
        }else{
          $turnos[$i]["Documento"]='';  
        }
    }

    $final["Turnos"]=$turnos;
    $final["Cantidad"]=count($turnos);
    echo json_encode($final);


// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

// require_once('../../config/start.inc.php');
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
// include_once('../../class/class.consulta.php');

// $punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );

// $oLista = new Lista("Turnero");
// $oLista->setRestrict("Fecha","=",date("Y-m-d"));
// $oLista->setRestrict("Id_Turneros","=",$punto);
// $oLista->setRestrict("Estado","=","Espera");
// $oLista->setOrder("Hora_Turno","ASC");
// $turnos = $oLista->getList();
// unset($oLista);

// $i=-1;
// foreach($turnos as $turno){ $i++;
//     $turnos[$i]["Hora_Turno"]=date("h:ia",strtotime($turno["Hora_Turno"]));
//     $turnos[$i]["Tiempo_Espera"]="8 Minutos";
//     $oItem = new complex("Auditoria","Id_Auditoria",$turno["Id_Auditoria"]);
//     $auditoria = $oItem->getData();
//     unset($oItem);
//     if(isset($auditoria["Id_Auditoria"])){
//       $turnos[$i]["Documento"]=$auditoria["Archivo"];
//     }else{
//       $turnos[$i]["Documento"]='';  
//     }
// }

// $final["Turnos"]=$turnos;
// $final["Cantidad"]=count($turnos);
// echo json_encode($final);
?>