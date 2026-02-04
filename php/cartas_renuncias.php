<?php

date_default_timezone_set('America/Bogota');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');



$query='SELECT F.Identificacion_Funcionario, CONCAT_WS(" ",F.Nombres,F.Apellidos) as Nombre, F.Firma, C.Nombre as Cargo
FROM Funcionario F
INNER JOIN Cargo C ON C.Id_Cargo = F.Id_Cargo
WHERE F.Identificacion_Funcionario IN (
43187853,
39179164,
21500967,
8329738,
1046906921,
1036663596,
1036626750,
43612713,
39416635,
11003044,
8163530,
64867349,
1020406896,
1128432756,
71214132,
39413160,
1027891126,
108511318,
43657650,
40399440,
1148144865,
1039461254,
21500918,
43923471,
1046902781,
1076331972,
1128460511,
1035428772,
1039679953,
39313671,
39316937,
1036956548,
63536251
)
';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$funcionarios = $oCon->getData();
unset($oCon); 

ob_start();
$style='<style>
   
.page-content{
width:750px;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:15px;
    line-height: 20px;
}
p{
    font-family: "Arial";
    font-size:12px;
    line-height:14px;
}
</style>';
$i=0;
$content='';
foreach($funcionarios as $fun){ $i++;
   // echo utf8_decode($fun["Nombre"]).'<br>';
    $content .= '<page>
                <div class="page-content">
                     <p style="font-size:16px;"><strong>30 de abril de 2020</strong></p>
                     <p style="font-size:16px;"><strong>Asunto: Carta de renuncia irrevocable</strong></p>
                     <p style="font-size:16px;"><strong>Señores Productos Hospitalarios PRO H S.A.</strong></p><br><br>
                     <p style="text-align:justify;font-size:16px;line-height:25px;">
                        Yo, '.utf8_decode($fun["Nombre"]).', con cédula de ciudadanía '.number_format($fun["Identificacion_Funcionario"],0,",",".").' me dirijo a ustedes con el mayor respeto y agradecimiento, con el fin de presentar mi renuncia voluntaria al cargo de '.utf8_decode($fun["Cargo"]).', Es preciso aclarar que mi decisión es voluntaria e irrevocable y que no tiene nada que ver con decisiones de la empresa. El motivo de mi renuncia es el cumplimiento o culminación de mi ciclo laboral en la organización, debido a esto, no puedo continuar ejerciendo mis funciones dentro de la empresa a partir de hoy 30 de abril de 2020.
                        <br><br>Agradezco la oportunidad ofrecida, que me permitió el crecimiento personal y profesional. 
                        <br><br>Sin más que agregar, me despido.
                        <br><br>Atentamente,
                        <br>
                        <br>
                        <br>
                        <br>
                        ____________________________________
                        <br>'.utf8_decode($fun["Nombre"]).'
                        <br>C.C.'.number_format($fun["Identificacion_Funcionario"],0,",",".").'
                        

                     </p>
    	             
                   </div>
                </page>';
}

try{
  
   $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(30, 30, 30, 30));
   $html2pdf->writeHTML($content);
   $direc = 'cartas_renuncia.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
   $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}


?>


