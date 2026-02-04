<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$lista = ( isset( $_REQUEST['lista'] ) ? $_REQUEST['lista'] : '' );

$query_fin='';



$i=-1;
$x=1;
?>
<html>
<head><meta charset="gb18030">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
    <div class="container" style="margin-top:60px;" >
           <div class="row">
               <div class="col-sm-2">
                    <div class="form-group">
                        <label for="exampleFormControlSelect1">Tipo Reporte</label>
                        <select class="form-control" id="tipo">
                          <option value="Entrega">Entrega</option>
                          <option value="ReporteEntrega">Reporte Entrega</option>
                        </select>
                    </div>
               </div>
                <div class="col-sm-10">
                <form name="formBuscar" id="formBuscar">
                    
                     <!--  <div class="form-group">
                            <label for="tipo">Facturas</label>
                            <div class="col-sm-10">
                                <textarea name="lista" id="lista" rows="10" class="form-control input-sm" required placeholder="DEBE ESCRIBIR LOS CODIGOS DE LAS FACTURAS SEPARADOS POR COMA">></textarea>
                            </div>
                        </div> -->
                          <div class="form-group">
                            <label for="facturas">Id's</label>
                            <textarea class="form-control" id="ids" placeholder="DEBE ESCRIBIR LOS ID'S DE LOS REPORTES SEPARADOS POR COMA" rows="10"></textarea>
                          </div>

                      
                </form>
                </div>
            <div class="col-sm-12"  style="margin-top:40px;">
                   
                            <button id="send" class="btn btn-danger btn-block">LIBERAR</button>
                  
            </div>       
            <div id="response" class="col-md-12 d-flex justify-content-center" style="margin-top:40px;">
            <hr>
            </div>
        </div>
        

  </div>

</div>

<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
<script>

$( "#send" ).click(function() {
    
    ids =  $('#ids').val();
    tipo = $('#tipo').val();

    var formData = new FormData();
    
    formData.append("tipo", tipo);
    formData.append("ids", ids); 
    
    fetch('test_reporte_anulacion_mipres_api.php', {
       method: 'POST',
       body: formData
    }).then((response) => response.json())
    .then((responseJSON) => {
       // do stuff with responseJSON here...
       console.log(responseJSON);
       $("#response").html(responseJSON);
    });
    
  /*  $.ajax({
	    url:"test_reporte_anulacion_mipres_api.php", 
	    type:"POST", 
	    body: formData,
	    contentType: "application/json",
	    processData:false, 
	    cache:false 
	 }).done(function(msg){
	    	alert(msg);	
	 }); */

});



	    
		
</script>
</body>
</html>