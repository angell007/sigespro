<?php
require_once("../../config/start.inc.php");
require_once($MY_CLASS . "html2pdf.class.php");
include_once($MY_CLASS . "class.complex.php");
include_once($MY_CLASS . "class.lista.php");

date_default_timezone_set("America/Bogota");

$id = (isset($_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$oItem = new complex('Funcionario','Identificacion_Funcionario', $id);
$funcionario = $oItem->getData();
unset($oItem);

$oItem = new complex('Cargo','Id_Cargo', $funcionario["Id_Cargo"]);
$cargo = $oItem->getData();
unset($oItem);

list($nombre,$segun_n)= explode(" ",$funcionario["Nombres"]);
$apellido= explode(" ",$funcionario["Apellidos"]);
	
$content = '
<style>
*{
	color:#282828;
}
.page-content{
width:199.98mm;
}
.row{
display:inlinie-block;
width:199.98mm;
}
.invoice-logo-space {
margin-bottom: 10px;
}
.table thead tr th, .table tbody tr th, .table tfoot tr th, .table thead tr td, .table tbody tr td, .table tfoot tr td {
padding: 0;
line-height: 1;
vertical-align: top;
}
table{
width:199.98mm;
height:323.09mm;
cellspacing:0;
cellpadding:0;
}
table {
border-collapse: collapse;
border-spacing: 0;
}
table {
margin: 0;
}
.table thead tr th {
font-size: 10px;
font-weight: 600;
}

h1.saltopagina {page-break-before:always} 

.h4{
	font-size:50px;
	line-height:50px;
	font-weight:bold;
	color: #282828;
	margin:0;
}
.h5{
	font-size:40px;
	margin:0;
}
.h6{
	font-size: 25px;
	margin:0;
}
.h2{
	font-size:50px;
	line-height:50px;
	margin:0;
	max-width:100%;
	width:100%;
	padding:0;
	
}
</style>
<page>
	 		<div class="page-content" style="width:199.98mm;height:323.09mm;background: url('.$URL.'assets/img/carnet_final.png) center no-repeat;background-attachment: fixed;background-size:cover;">
	 			<table>
	 			 <tr>
	 			 	<td colspan="2" style="height:90.6mm;">&nbsp;</td>
	 			 </tr>
	 			<tr>
	 			    <td style="height:105.04mm;width:45%;float:left;vertical-align:bottom;text-align:center;">
	 			       <h2 style="font-weight:400;font-size:40px;color:#0d3243;margin:0;">Resolución:</h2>
	 			       <h2 style="font-weight:200;font-size:30px;color:#0d3243;margin:0;margin-bottom:30px;">004-322 SIVSP</h2>
	 			       <h5 class="h5" style="width:100%;font-weight:200;font-size:30px;color:#0d3243;margin:0;margin-bottom:45px;">G.S/RH: <span style="font-size:32px;font-weight:600;">'.$funcionario["Tipo_Sangre"].'</span></h5>
					</td>
					<td style="height:105.04mm;width:45%;float:left;vertical-align:middle;">'; 
						if($funcionario["Imagen"]!=""){
							$img = $img = $URL."php/funcionario/image.php?w=300&h=300&img=".$URL."IMAGENES/FUNCIONARIOS/".$funcionario["Imagen"];
							$content.='
							<div style="background:url('.$img.') center no-repeat;border-top-left-radius: 10px;
							border-top-right-radius: 10px;
							border-bottom-right-radius: 10px;
							border-bottom-left-radius: 10px;
							width:300px;
							margin-top:0px;
							margin-left:0px;
							height:300px;border:6px solid #fff;">&nbsp;</div>							
							';
						}else{
							$content.='<div style="background:url(https://placeholdit.imgix.net/~text?txtsize=25&txt=Sin%20Foto&w=300&h=300) center no-repeat;border-top-left-radius: 10px;
							border-top-right-radius: 10px;
							border-bottom-right-radius: 10px;
							border-bottom-left-radius: 10px;
							width:300px;
							margin-top:0px;
							margin-left:220px;
							height:300px;border:6px solid #fff;">&nbsp;</div> 							
							';
						}
					$content.='</td>
				 </tr>
				 <tr>
					<td colspan="2" style="height:35.04mm;width:100%;vertical-align:middle;"> 
					<h4 class="h4" style="width:100%;padding-left:10px;padding-right:10px;text-align:center;margin-top:30px;">'.ucwords(strtolower($funcionario["Nombres"])).'</h4>
					<h4 class="h4" style="width:100%;padding-left:10px;padding-right:10px;text-align:center;margin-top:5px;">'.ucwords(strtolower($funcionario["Apellidos"])).'</h4>
					<h6 class="h4" style="width:100%;padding-left:10px;padding-right:10px;text-align:center;margin:0;margin-top:10px;">C.C.: '.number_format($funcionario["Identificacion_Funcionario"],0,",",".").'</h6>
					</td>
				</tr>
				 <tr>
	 			 	<td colspan="2" style="height:20mm;">&nbsp;</td>
	 			 </tr>
				 <tr >
				 <td colspan="2" style="height:48mm;width:100%;text-align:right;vertical-align:middle;padding-right:15px;border:1px solid transparent;">
				 <h4 class="h2" style="text-align:center;color:#efefef;'.((strlen($cargo["Nombre"])>30) ? 'font-size:42px;line-height:42px;' : '').'">'.str_replace("[\n|\r|\n\r|\t||\x0B]","",$cargo["Nombre"]).'</h4>
				 </td> 
				 </tr>
				</table>
				</div>
		</page>
		<page>
		<div class="page-content" style="width:199.98mm;height:323.09mm;background: url('.$URL.'assets/img/carnet_final_atras.png) center no-repeat;background-attachment: fixed;background-size:cover;">
	 	</div>
		</page>';
try
{
    $html2pdf = new HTML2PDF('P', array('199.98','323.09'), 'Es', true, 'UTF-8', array(0, 0, 0, 0));
    $html2pdf->writeHTML($content);
    $html2pdf->Output('carnet_'.$nombre[0].'_'.$apellido[0].'.pdf','D');
}
catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}
?>