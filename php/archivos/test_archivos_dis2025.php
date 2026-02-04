<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/class.barcode.php');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');

$dispensaciones = getDispensaciones();

$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);

ob_start(); // Se Inicializa el gestor de PDF
$i=0;
$content = '';

foreach($dispensaciones as $dis){ $i++;
    $nom = $dis["PRIMER_NOMBRE"]." ".$dis["PRIMER_APELLIDO"]." ".$dis["SEGUNDO_APELLIDO"];
    $dir = $dis["DIRECCION"];
    $mun = $dis["Municipios"];
    $tel = $dis["CELULARPAL"];
    $des= "Talla: ".$dis["TALLA"]." - Cant:".$dis["CantidadReal"];
    $dis = $dis["DISPENSACION"];
    $content .= '<page backtop="0mm" backbottom="0mm">
                    <div class="page-content" style="width:238mm;height:157mm;background-image: url('.$_SERVER["DOCUMENT_ROOT"].'assets/images/fondo-sticker-01.jpg);background-attachment: fixed;background-repeat: no-repeat; background-position: left top; background-size:cover;" >
                        <div style="width:230mm;height:120mm;padding:15px;text-align:center;vertical-align:middle;font-size:40px;line-height:40px;text-transform:uppercase;font-weight:bold;">'.$nom.'<br>'.$tel.'<br>'.$dir." <br> ".$mun.'<br>'.$des.'<br><br><span style="font-size:28px;line-height:30px;">'.$dis.'<br></span>
                        </div>
                    </div>
                </page>';
}

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array('238','158'), 'Es', true, 'UTF-8', array(0,0,0,0));
    $html2pdf->writeHTML($content);
    $direc = 'sticker.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function getDispensaciones()
{
	$queryObj = new QueryBaseDatos();
	$query_productos =
		'SELECT *
		    FROM A_Entrega_Nutriciones_2025 E
		    WHERE E.Estado=1 AND E.Lote=2
		    #AND E.DISPENSACION IN ("DIS1352858","DIS1352859")
		';
/*

SELECT *
		    FROM A_Entrega_Panales_2025 E
		    WHERE E.Estado=1
		    AND E.CRUCE_JURIDICA = "" 
		    AND E.Columna1 NOT LIKE "NORTE DE SANTANDER"
		    ORDER BY E.Municipios ASC, E.DISPENSACION ASC;
		    
		    */
	$queryObj->SetQuery($query_productos);
	$resultado = $queryObj->ExecuteQuery('multiple');

	return $resultado;
}



?>