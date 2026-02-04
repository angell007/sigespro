<?php


//HEADERS PARA CORS

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');


//CLASES INCLUIDAD

// Asumiendo que cron_mipres_automatico.php está en /php/
include_once(__DIR__ . '/../class/class.querybasedatos.php');
include_once(__DIR__ . '/../class/class.http_response.php');
include_once(__DIR__ . '/../class/class.mipres.php');
include_once(__DIR__ . '/../class/class.php_mailer.php');
include_once(__DIR__ . '/../class/class.complex.php');


//CREACION DE OBJETOS PRINCIPALES
$queryObj = new QueryBaseDatos();
$mipres = new Mipres();
$mail = new EnviarCorreo();


//FILTRO FECHAS POR PETICIONES POST

/*$fini = ( isset( $_REQUEST['fini'] ) ? $_REQUEST['fini'] : "2025-02-10" );
    $ffin = ( isset( $_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : "2025-02-10" );*/

//TRAER POR FECHA DIARIA DEL SISTEMA


//$fechaActual = date('Y-m-d');

$fecha = "2025-03-11";

//$fini = $fechaActual;  
//$ffin = $fechaActual;
$tipo_Agente ='';
$dis_borrar = '';
$producto_lista = '';
$idpaciente = '';
$entrega = '';
$dis = [];
$productos_dis = [];

//CORREOS A COMPARTIR
$correos = ['compras@prohsa.com', 'fernando.arciniegas@prohsa.com', 'freddy.arciniegas@prohsa.com', 'sistemas@prohsa.com'];


/*$f_ini=strtotime($fini);
    $f_fin=strtotime($ffin);*/


$dishoy = $mipres->GetDireccionamientoPorFecha($fecha);
usort($dishoy, 'OrderByNumeroEntrega');
$total_direccionamientos = count($dishoy);

if ($total_direccionamientos != 0) {

    foreach ($dishoy as $value) {

        //VALIDAR NIT DE EPS
        if ($value["NoIDEPS"] != "") {

            //VERIFICAR ESTADO DE DIRECCIONAMIENTO
            if ($value["EstDireccionamiento"] == 1) {
                if ($value['TipoTec'] == 'S') {
                    GuardarMipres($value);
                } else if ($value['TipoTec'] != 'P') {
                    GuardarDireccionamiento($value);
                }
            } else {
                echo "<br>DIRECCIONAMIENTO ANULADO<br>";
            }
        } else {
            echo "<br>EPS VACIA O INCORRECTA<br>";
        }
        echo "<br>====================================================<br>";
        echo "<br>PACIENTE -- " . $value['NoIDPaciente'] . "  - Direccionamiento: " . $value["IDDireccionamiento"] . "  - Prescipcion: " . $value["NoPrescripcion"] . "  - EPS: " . $value["NoIDEPS"] . "- NroEntrega: " . $value["NoEntrega"] . "<br>";
    }
} else {
    echo "<br>NO HAY DIRECCIONAMIENTOS MIPRES DE LA FECHA" . $fechaActual;
}






//CICLO FOR PARA TOMAR EL DIA
/*for($i=$f_ini;$i<=$f_fin;$i=strtotime(date("Y-m-d",$i).' + 1 DAY')){
        // Logica...
      
      $fecha=date('Y-m-d',$i);
        
    
     
      $dis_borrar='';
      $producto_lista='';
     $idpaciente='';
      $entrega='';
      $dis=[];
      $productos_dis=[];
      
    }*/




function GuardarMipres($dis)
{


    global $dis_borrar, $producto_lista;



    if (ValidarDireccionamiento($dis['ID']) && ValidarPaciente($dis['NoIDPaciente'])) {

        //A_Dispensacion_Mipres
        $dispensacion_Mipres = $dis;
        $dispensacion_Mipres['Fecha'] = date('Y-m-d H:i:s');
        $dispensacion_Mipres['Id_Paciente'] = $dis['NoIDPaciente'];
        $dispensacion_Mipres['Fecha_Maxima_Entrega'] = $dis['FecMaxEnt'];
        $dispensacion_Mipres['Numero_Entrega'] = $dis['NoEntrega'];
        $dispensacion_Mipres['Fecha_Direccionamiento'] = $dis['FecDireccionamiento'];
        $dispensacion_Mipres['Id_Servicio'] = 1;
        $dispensacion_Mipres['Id_Tipo_Servicio'] = 3;
        $dispensacion_Mipres['Codigo_Municipio'] = $dis['CodMunEnt'];
        $dispensacion_Mipres['Tipo_Tecnologia'] = $dis['TipoTec'];
        var_dump($dispensacion_Mipres);
        $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres');
        
        foreach ($dispensacion_Mipres as $key => $value) {
            $oItem->$key = $value;
           
        }
        $oItem->save();
        $id_dis = $oItem->getId();
        unset($oItem);

        
    }


}

function GuardarDireccionamiento_MIpres($dis)
{
    global $dis_borrar, $producto_lista;

    if (ValidarDireccionamiento($dis['ID']) && ValidarPaciente($dis['NoIDPaciente']) && ValidarMunicipio($dis['CodMunEnt'], $dis['NoIDEPS'])) {

        $dispensacion = $dis;
        $dispensacion['Fecha'] = date('Y-m-d H:i:s');
        $dispensacion['Id_Paciente'] = $dis['NoIDPaciente'];
        $dispensacion['Fecha_Maxima_Entrega'] = $dis['FecMaxEnt'];
        $dispensacion['Numero_Entrega'] = $dis['NoEntrega'];
        $dispensacion['Fecha_Direccionamiento'] = $dis['FecDireccionamiento'];
        $dispensacion['Id_Servicio'] = 1;
        $dispensacion['Id_Tipo_Servicio'] = 3;
        $dispensacion['Codigo_Municipio'] = $dis['CodMunEnt'];
        $dispensacion['Tipo_Tecnologia'] = $dis['TipoTec'];

        $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres');
        foreach ($dispensacion as $key => $value) {
            $oItem->$key = $value;
        }
        $oItem->save();
        $id_dis = $oItem->getId();
        unset($oItem);


        $c = explode('-', $dis['CodSerTecAEntregar']);
        if ($c[0] != 'cum') {
            $cum = str_pad((int)$c[0], 2, "0", STR_PAD_LEFT);
            echo $cum . '<br><br>';
            $idproducto = GetIdProductoAsociado($cum, $dis['TipoTec']);
            $dis['Codigo_Cum'] = str_replace('-', '', $cum);
            $dis['Id_Producto'] = $idproducto != false ? $idproducto : '';
            $dis['Tipo_Tecnologia'] = $dis['TipoTec'];
            if ($dis['Id_Producto'] != '') {
                if (ValidarProductoLista($cum, $dis['NoIDEPS'])) {
                    $dis['Id_Dispensacion_Mipres'] = $id_dis;
                    $dis['Cantidad'] = $dis['CantTotAEntregar'];
                    $oItem = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres');
                    foreach ($dis as $key => $value) {
                        $oItem->$key = $value;
                    }
                    $oItem->save();
                    unset($oItem);
                } else {
                    $dis_borrar .= $id_dis . ',';
                    $producto_lista .= $dis['Id_Producto'] . ',';
                }
            } else {
                echo "Producto no registrado " . $dis['CodSerTecAEntregar'] . "<br><br>";

                $id_no_encontrado = GetId($cum);
                if ($id_no_encontrado != '') {
                    $oItem = new complex('Producto_No_Encontrados', 'Id_Producto_No_Encontrados', $id_no_encontrado);
                    $oItem->Estado = 'Pendiente';
                    $oItem->save();
                    unset($oItem);
                } else {
                    $oItem = new complex('Producto_No_Encontrados', 'Id_Producto_No_Encontrados');
                    $oItem->Codigo_Cum = $cum;
                    $oItem->Fecha = date('Y-m-d H:i:s');
                    $oItem->save();
                    unset($oItem);
                }
                $dis_borrar .= $id_dis . ',';
            }
        } else {
            $dis_borrar .= $id_dis . ',';
        }
    }
}

function validaMunicipio($codMunEnt) {

    global $tipo_Agente;


    $query = "SELECT M.Id_Municipio,M.Nombre FROM Municipio M.Codigo WHERE M.Codigo='$codMunEnt'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
    unset($oCon);

    if(!$data) {
        echo "<br>El Muncipio '$codMunEnt' no está creado en sigespro.<br>";
    }

    $tipo_Agente = ($data['Nombre'] == "GIRARDOT" || $data['Nombre'] == "SOACHA" ) ? "Agente 1": "Agente 2";

    return $data ? true : false;

}

function getIdsMunicipio($codMunEnt) {
    $query = "SELECT Id_Municipio FROM Municipio M WHERE M.Codigo='$codMunEnt'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $id_municipio = $oCon->getData();
    unset($oCon);

    if(!$id_municipio) {
        echo "<br>El Muncipio '$codMunEnt' no está creado en sigespro.<br>";
    }else{
        var_dump($id_municipio);
    }

    return $id_municipio ;

}

function guardarCallcenter($dis,$id_dispensacion){

    global $tipo_Agente;

    if(validaMunicipio($dis['CodMunEnt'])){
        $Id_Municipio = getIdsMunicipio($dis['CodMunEnt']);
        $call = $dis;
        $call['Id_Dispensacion_Mipres_Call']= $id_dispensacion;
        $call['Id_Municipio_Call']= $Id_Municipio;
        $call['Tipo_agente']= $tipo_Agente;
        $call['Fecha_Creacion'] = date('Y-m-d H:i:s');
        $oItem = new complex('Call_Center_Mipres', 'Id_Seguimiento');
        foreach ($call as $key => $value) {
            $oItem->$key = $value;
        }
        $oItem->save();
        unset($oItem);
    }

}

function ValidarDireccionamiento($id){
    $query = "SELECT PDM.ID, PDM.NoPrescripcion FROM Producto_Dispensacion_Mipres PDM INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres WHERE PDM.ID='$id'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $data = $oCon->getData();
    unset($oCon);

    if ($data['ID']) {
        echo "La Prescipcion " . $data['NoPrescripcion'] . " ya esta creada en el sistema<br>";
    }

    return $data['ID'] ? false : true;
}


function ValidarPaciente($idpaciente)
{
    if ($idpaciente == '') {
        $data = false;
    } else {
        $query = "SELECT Id_Paciente FROM Paciente WHERE Id_Paciente='$idpaciente'";

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
/*function ValidarMunicipio($codigo,$nit){
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
   
}*/
