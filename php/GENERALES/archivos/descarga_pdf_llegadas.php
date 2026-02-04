<?php
date_default_timezone_set('America/Bogota');

set_time_limit(120);

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/html2pdf.class.php');

$fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : '');
$ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : '');

$oLista= new lista('Llegada_Tarde');
$oLista->setRestrict("Fecha","<=",$ffin);
$oLista->setRestrict("Fecha",">=",$fini);
$oLista->setRestrict("Cuenta","=","Si");
//$oLista->setOrder("Fecha","DESC");
$oLista->setOrder("Identificacion_Funcionario","ASC");
$llegadas_tarde_actual=$oLista->getList();
unset($oLista);


function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}


$oItem= new complex("Configuracion","id",1);
$config = $oItem->getData();
unset($oItem);

$startTime = strtotime( $fini.' 00:00:00' );
$endTime = strtotime(date($ffin.' 23:59:59'));

$empleados_tarde='';
$persona=0; $cant=0; $tiempo=0; $txt=''; 
foreach($llegadas_tarde_actual as $act){ 
    	if($persona==$act["Identificacion_Funcionario"]){
    		$cant++;
    		$tiempo+=$act["Tiempo"];
    		$txt.='<tr>
    		<td style="text-align:center;">'.fecha($act["Fecha"]).'</td>
    		<td style="text-align:center;" >'.$act["Entrada_Turno"].'</td>
    		<td style="text-align:center;" >'.$act["Entrada_Real"].'</td>
    		<td style="text-align:center;" >'.floor($act["Tiempo"]/3600).":".floor($act["Tiempo"]%3600/60).":".($act["Tiempo"]%60).'</td>
    		</tr>';
    	}else{
    		if($persona!="0"){ 
    		$prom=$tiempo/$cant;
    		$oItem = new complex("Funcionario","Identificacion_Funcionario",$persona);
    		$func=$oItem->getData();
    		unset($oItem);
	    		if($cant>=$config["Llegadas_Tarde"]){ 
		    	  $empleados_tarde.='<tr>
			            <td style="width:60px;vertical-align:top;"  rowspan="2 valign="top">';
			                	if($func["Imagen"]!=""){
			                    $img=$URL."php/funcionario/image.php?w=50&h=50&img=".$URL.'IMAGENES/FUNCIONARIOS/'. $func["Imagen"];
			                    }else{
			                    $img=$URL."php/funcionario/image.php?w=50&h=50&img=".$URL.'assets/img/placeholder.jpg';
								}
								$empleados_tarde.='
								<div style="background:url('.$img.') center no-repeat;border-radius:25px;
								width:50px;
								margin-top:25px;
								margin-left:0px;
								height:50px;">&nbsp;</div>
			            </td>
			            <td><strong style="margin-top:25px;">'.$func["Nombres"]." ".$func["Apellidos"].'</strong></td>
			            <td  style="text-align:center;">
			                <strong style="margin-top:25px;">'.$cant.'</strong>
			            </td>
			            <td  style="text-align:center;">
			                <strong style="margin-top:25px;">'.floor($tiempo/3600).":".floor($tiempo%3600/60).":".($tiempo%60).'</strong>
			            </td>
			        </tr>
			        <tr>
			        	
        				<td colspan="3"><table class="table table-hover table-bordered" style="font-size:8px;width:600px;">
			        	<tr>
			        	<td style="width:25%;text-align:center;">Fecha</td>
			        	<td style="width:25%;text-align:center;">Entrada Turno</td>
			        	<td style="width:25%;text-align:center;">Entrada Real</td>
			        	<td style="width:25%;text-align:center;">Tiempo Retraso</td>
			        	</tr>'.$txt.'</table>
			        	</td>
			        </tr>';
	    		}
    		}
    		$txt='';
    		$cant=1;
    		$tiempo=$act["Tiempo"];
    		$persona=$act["Identificacion_Funcionario"];
    		$txt.='<tr>
    		<td style="text-align:center;">'.fecha($act["Fecha"]).'</td>
    		<td style="text-align:center;">'.$act["Entrada_Turno"].'</td>
    		<td style="text-align:center;">'.$act["Entrada_Real"].'</td>
    		<td style="text-align:center;">'.floor($act["Tiempo"]/3600).":".floor($act["Tiempo"]%3600/60).":".($act["Tiempo"]%60).'</td>
    		</tr>';
    	}
} 
 /*       
if($persona!=0){ 
	//$prom=$tiempo/$cant;
	$oItem = new complex("Funcionario","Identificacion_Funcionario",$persona);
	$func=$oItem->getData();
	unset($oItem);

	if($cant>=$config["Llegadas_Tarde"]){ 
		$empleados_tarde.='<tr>
            <td style="width:60px;vertical-align:top;"  rowspan="2 valign="top">';
                	if($func["Imagen"]!=""){
	                    $img=$URL."php/funcionario/image.php?w=50&h=50&img=".$URL.'IMAGENES/FUNCIONARIOS/'. $func["Imagen"];
	                    }else{
	                    $img=$URL."php/funcionario/image.php?w=50&h=50&img=".$URL.'assets/img/placeholder.jpg';
						}
						$empleados_tarde.='
						<div style="background:url('.$img.') center no-repeat;border-radius:25px;
						width:50px;
						margin-top:25px;
						margin-left:0px;
						height:50px;">&nbsp;</div>
            </td>
            <td><strong style="margin-top:25px;">'.$func["Nombres"]." ".$func["Apellidos"].'</strong></td>
            <td style="text-align:center;" >
                <strong style="margin-top:25px;">'.$cant.'</strong>
            </td>
            <td style="text-align:center;">
                <strong style="margin-top:25px;">'.floor($tiempo/3600).":".floor($tiempo%3600/60).":".($tiempo%60).'</strong>
            </td>
            
        </tr>
        <tr>
        	<td colspan="3">
        	<table class="table table-hover table-bordered" style="font-size:8px;width:600px;">
        	<tr><td><Fecha</td>
        	<td>Entrada Turno</td>
        	<td>Entrada Real</td>
        	<td>Tiempo Retraso</td>
        	</tr>'.$txt.'</table>
        	</td>
        </tr>';
    }
}

*/

ob_start();
ob_get_clean();

$content = '
<style>
.page-content{
width:740px;
}
.invoice .invoice-logo p {
padding: 3px 0;
font-size: 18px;
line-height: 19px;
text-align: right;
}
.col-md-12 {
width:100%;
}
.col-md-6 {
width:40%;
float: left;
display:inline-block;
}
.invoice .invoice-logo p span {
display: block;
font-size: 10px;
margin-bottom:2px;
line-height:12px;
}
.invoice .invoice-logo-space {
margin-bottom: 10px;
}
.table thead tr th, .table tbody tr th, .table tfoot tr th, .table thead tr td, .table tbody tr td, .table tfoot tr td{
padding: 4px;
line-height: 1;
vertical-align: top;
border-top: 1px solid #ddd;
}
.table thead tr th {
vertical-align: bottom;
border-bottom: 1px solid #ddd;
}
table{
width:740px;
cellspacing:0;
cellpadding:0;
border-spacing: 0;
}
.table thead tr th {
font-size: 14px;
font-weight: 600;
border-bottom: 0;
}

hr {
margin: 10px 0;
border: 0;
border-top: 1px solid #E0DFDF;
border-bottom: 1px solid #FEFEFE;
}
ul, ol {
margin: 0;
margin-bottom: 5px;
}
h1, h2, h3, h4, h5, h6 {
font-weight: 200 !important;
}
h4, h5, h6 {
margin-top: 3px;
margin-bottom: 3px;
}
h4, .h4 {
font-size: 16px;
}
h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
font-family: Helvetica,Arial,sans-serif;
font-weight: 200;
line-height: 1;
}
.invoice .invoice-block .amounts {
margin-top: 10px;
font-size: 12px;
}
.invoice .invoice-block {
text-align: right;
margin-right:10px;
}

p{
	margin:0 0 4px 0; 
}
</style>
<page>
<link href="https://sevicol.programing.com.co/assets/css/bootstrap.min.css" rel="stylesheet" >
<link href="https://sevicol.programing.com.co/assets/css/amaze.css" rel="stylesheet" >
<link href="https://sevicol.programing.com.co/assets/css/demo.css" rel="stylesheet" >
   <div class="page-content" style="font-size:14px;">              
          <div class="invoice">
            <div class="row invoice-logo">
				<table style="margin-top:20px;">
					<tr>
						<td style="width:30%">
               				<div class="col-md-12 invoice-logo-space"><img src="http://sevicol.programing.com.co/assets/img/logo-sevicol-color.jpg" alt="" style="width:100%;" /> </div>
						</td>
						<td style="width:40%;text-align:center;vertical-align:middle;" valign="middle">
							<h4 style="text-align:center" >LLEGADAS TARDE</h4>
						</td>
						<td style="width:30%">
               				<div class="col-md-12">
                  				<p><span>Inicio:</span> '.$fini.'<br><span>Fin:</span> '.$ffin.'</p>
               				</div>
						</td>
					</tr>
				</table>
            </div>
            
            <hr style="margin:0;" />
            <div class="row">
            <table class="table" style="margin-top:20px;" >
				<tr>
					<td style="width:10%;border-bottom:1px solid #ccc;"></td>
					<td style="width:70%;border-bottom:1px solid #ccc;">Funcionario</td>
					<td style="width:10%;border-bottom:1px solid #ccc;text-align:center;">Cantidad</td>
					<td style="width:10%;border-bottom:1px solid #ccc;text-align:center;">Tiempo</td>
				</tr>
				'.$empleados_tarde.'
			</table>
            </div>
			
     </div>    
   </div> 
</page>';


//$content=$empleados_tarde;
//echo $content;
 
    try
    {   		
    	$html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 0, 5, 0));
        $html2pdf->writeHTML($content);
		ob_end_clean();
        $direc = 'llegadas-tarde'.$fini.'-'.$ffin.'.pdf';
        $html2pdf->Output($direc,"D");
    }
    catch(HTML2PDF_exception $e) {
        echo $e;
        exit;
    }



?>

