<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../../config/start.inc.php');
    include_once('../../../class/class.querybasedatos.php');
    include_once('../../../class/class.mensajes.php');
    include_once('../../../class/class.http_response.php');

    $sms_sender = new Mensaje();
    $http_response = new HttpResponse();
    $response = '';

    $modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
    $modelo = (array) json_decode($modelo);



    // if(isset($modelo["editar"])&&$modelo["editar"] == true){   
    // 	$oItem = new complex('Paciente', 'Id_Paciente', $modelo["Id_Paciente"],'Varchar');	
    // }else{	
    //     $oItem = new complex('Paciente', 'Id_Paciente');
    // }

    // foreach($modelo as $index=>$value) {
    //     $oItem->$index= camposLimpiar($index) !== false ? limpiar($value) : $value;
    // }

    // $id_pac = $oItem->save();
    // unset($oItem);

    $enviado = $sms_sender->Enviar($modelo['Numero_Telefono'], $modelo['Mensaje']);

    if ($enviado) {
        $oItem = new complex('Mensaje',"Id_Mensaje");
        $oItem->Mensaje = $modelo['Mensaje'];
        $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'] == '' ? '1095815196' : $modelo['Identificacion_Funcionario'];
        $oItem->Fecha = date('Y-m-d H:i:s');
        $oItem->save();
        unset($oItem);
    }

    $resultado = $enviado ? 'Enviado' : 'No enviado';

    echo json_encode($resultado);
?>