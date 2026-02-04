<?php

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/html2pdf.class.php');

$id  = (isset($_REQUEST['id'] ) ? $_REQUEST['id'] : '');
$mes  = (isset($_REQUEST['mes'] ) ? $_REQUEST['mes'] : '');
$datos= '';
$mates= '';

$oItem = new complex("Funcionario","Identificacion_Funcionario",$id);
$func=$oItem->getData();
unset($oItem);

$oLista= new lista('Llegada_Tarde');
$oLista->setRestrict("Fecha","LIKE",$mes);
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$oLista->setOrder("Fecha","DESC");
$llegadas_tarde=$oLista->getList();
unset($oLista);


$oItem = new complex("Cargo","Id_Cargo",$func["Id_Cargo"]);
$cargo=$oItem->getData();
unset($oItem);

ob_start();
$content = '
<style>
.page-content{
width:750px;
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
width:750px;
cellspacing:0;
cellpadding:0;
}
table {
border-spacing: 0;
}
.invoice table {
margin: 20px 0 20px;
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
.list-unstyled {
padding-left: 0;
list-style: none;
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
.well {
background-color: #fafafa;
border: 1px solid #eee;
-webkit-border-radius: 0px;
-moz-border-radius: 0px;
border-radius: 0px;
-webkit-box-shadow: none !important;
-moz-box-shadow: none !important;
box-shadow: none !important;
min-height: 10px;
padding: 8px;
margin-bottom: 6px;
}
.ot{
width:15%;
}
.descrip{
width:80%;
word-break: break-all;
}
.cant, .num{
width:10%;
}
address {
display: block;
margin-bottom: 10px;
font-style: normal;
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
.nota{
background-color: #fafafa;
border: 0px solid #eee;
-webkit-border-radius: 0px;
-moz-border-radius: 0px;
border-radius: 0px;
-webkit-box-shadow: none !important;
-moz-box-shadow: none !important;
box-shadow: none !important;
min-height: 10px;
padding: 5px;
margin-bottom:5px;
}
p{
	margin:0 0 4px 0; 
}
</style>
<page>
   <div class="page-content" style="font-size:14px;">              
          <div class="invoice">
            <div class="row invoice-logo">
				<table>
					<tr>
						<td style="width:30%">
               				<div class="col-md-12 invoice-logo-space"><img src="http://sevicol.programing.com.co/assets/img/logo_sevicol.png" alt="" style="width:100%;" /> </div>
						</td>
						<td style="width:40%;text-align:center;vertical-align:middle;" valign="middle">
							<h4 style="text-align:center" >MEMORANDO INTERNO</h4>
						</td>
						<td style="width:30%">
               				<div class="col-md-12">
                  				<p><span>CÓDIGO: R-GG-07<br>VERSIÓN: 01<br>FECHA: MAYO DE 2017<br>PÁGINA: 1 de 1</span></p>
               				</div>
						</td>
					</tr>
				</table>
            </div>
            <hr style="margin:0;" />
            <div class="row" style="margin-bottom:4px;">
				<table cellspacing="0" cellpadding="0">
					<tr>
						<td style="width:100px; border:1px solid #ddd;"><strong>Fecha</strong></td>
						<td colspan="3" style="width:620px; border:1px solid #ddd;">'.date("d/m/Y").'</td>
					</tr>
					
					<tr>
						<td style="width:100px; border:1px solid #ddd;"><strong>Emite</strong></td>
						<td style="width:260px; border:1px solid #ddd;"></td>
						<td style="width:100px; border:1px solid #ddd;"><strong>Cargo</strong></td>
						<td style="width:260px; border:1px solid #ddd;">Directora Administrativa</td>
					</tr>
					
					<tr>
						<td style="width:100px; border:1px solid #ddd;"><strong>Para</strong></td>
						<td style="width:260px; border:1px solid #ddd;">'.$func["Nombres"]." ".$func["Apellidos"].'</td>
						<td style="width:100px; border:1px solid #ddd;"><strong>Cargo</strong></td>
						<td style="width:260px; border:1px solid #ddd;">'.$cargo["Nombre"].'</td>
					</tr>
					
					<tr>
						<td style="width:100px; border:1px solid #ddd;"><strong>Copia</strong></td>
						<td style="width:260px; border:1px solid #ddd;"></td>
						<td style="width:100px; border:1px solid #ddd;"><strong>Cargo</strong></td>
						<td style="width:260px; border:1px solid #ddd;">Líder de Recursos Humanos</td>
					</tr>
					
					<tr>
						<td style="width:100px; border:1px solid #ddd;"><strong>Asunto</strong></td>
						<td colspan="3" style="width:620px; border:1px solid #ddd;">Llegada tarde por tercera vez</td>
					</tr>
				</table>
			</div>
			<br><br>
			<div class="row">
               <div class="col-md-12">
                 <p>Los siguientes días usted reportó llegada Tarde:</p>
               </div>
            </div>
            <br>
			<div class="row">
               <div class="col-md-12">
                 <table cellspacing="0" cellpadding="0">
					<tr>
						<td style="width:243px; border:1px solid #ddd;text-align:center;"><strong>Fecha</strong></td>
						<td style="width:243px; border:1px solid #ddd;text-align:center;"><strong>Entrada</strong></td>
						<td style="width:243px; border:1px solid #ddd;text-align:center;"><strong>Entrada Real</strong></td>
					</tr>';
					
					foreach($llegadas_tarde as $llegada){
						$content.='
							<tr>
								<td style="border:1px solid #ddd;text-align:center;">'.$llegada["Fecha"].'</td>
								<td style="border:1px solid #ddd;text-align:center;">'.$llegada["Entrada_Turno"].'</td>
								<td style="border:1px solid #ddd;text-align:center;">'.$llegada["Entrada_Real"].'</td>
							</tr>
						';
					}
					
				$content.='</table>
               </div>
            </div>
			<div class="row" style="margin-top:15px;">
            	<div class="col-md-12">
               		<p>Le recordamos que es importante el cumplimiento al reglamento interno de trabajo y de sus obligaciones como trabajador, por lo cual lo invitamos a cumplir su horario de trabajo para así no iniciar el proceso disciplinario correspondiente.</p>
            		<br><br><br><p>Atentamente,</p>
            		<br><br><br><br><br><br>
					<p>Ma. </p>
					<p>Directora Administrativa</p>
            	</div>
            </div>
       </div>    
      </div> 
    </page>
';
    try
    {
    		
    	$html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 0, 5, 0));
        $html2pdf->writeHTML($content);
        $direc = '/home/software/sevicol.programing.com.co/MEMORANDOS/'.$id."-".date("Y-m-d").'.pdf';
        $html2pdf->Output($direc,'F');
		
		$oItem = new complex("Memorando","Id_Memorando");
		$oItem->Identificacion_Funcionario=$id;
		$oItem->Fecha=date("Y-m-d");
		$oItem->Mes=$mes;
		$oItem->Llegadas_Tarde=5;
		$oItem->Documento=$id."-".date("Y-m-d").'.pdf';
		$oItem->save();
		

    }
    catch(HTML2PDF_exception $e) {
        echo $e;
        exit;
    }
 
 
 
?>

