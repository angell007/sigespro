<?php

if(enviarEmailFail())
{
    echo "Se envió";
} else {
    echo "No se envió";
}

function enviarEmailFail()
{
    // $to = "pedro.castillo@corvuslab.co, kendry.ortiz@corvuslab.co, augustoacarrillo@gmail.com";
    $to = "ortizkendry95@gmail.com";
    $subject = "Error al eliminar borradores";
    $mensaje = "Error al eliminar los siguientes borradores: ";

    $envio = mail($to,$subject,$mensaje);

    return $envio;
}

?>