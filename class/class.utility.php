<?php

	class Utility{

		private $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

		public function ArrayToCommaSeparatedString($array){
			$i = 0;
			$cadena = '';
			foreach ($array as $value) {
				
				if (($i+1) == count($array)) {
					
					$cadena .= $value;
				}else if($i < count($array)){
					$cadena .= $value.',';
				}

				$i++;
			}

			return $cadena;
		}

		public function ObtenerMesString($posMes){
			//$intMes = strpos($posMes, "0");
			return $this->meses[( ((INT)$posMes) - 1)];
		}

		public function SepararFechas($fechasStr){
			$fechas_separadas = explode(" - ", $fechasStr);
			return $fechas_separadas;
		}

		/*
		SEPARA UN STRING DE FECHA EN DIA, MES, AÑO
		LA FECHA DEBE VENIR EN FORMATO YYYY-MM-DD SI ES DATE
		LA FECHA DEBE VENIR EN FORMATO YYYY-MM-DD HH:I:S SI ES DATETIME
		FUNCIONA CON TIPOS DE DATOS DATE Y DATE TIME
		*/
		public function SepararFecha($fechaStr){
			$fecha_separada = '';

			if (strlen($fechaStr) == 10) {
				$fecha_separada = explode("-", $fechaStr);
			}elseif (strlen($fechaStr) > 10){
				$fecha_hora = $this->SepararFechaHora($fechaStr);
				$fecha_separada = explode("-", $fecha_hora[0]);
			}
			
			return $fecha_separada;
		}

		public function SepararFechaHora($fecha){
			$fecha_hora = explode(" ", $fecha);
			return $fecha_hora;
		}

		/*
		OBTIENE LA DIFERENCIA ENTRE 2 FECHAS DADAS
		$tipo_resultado: años, meses, dias
		*/
		public function GetDiferenciaFechas($fecha1, $fecha2, $tipo_resultado=''){
			$diferencia = 0;

			$datetime1=new DateTime($fecha1);
	        $datetime2=new DateTime($fecha2);
	         
	        # obtenemos la diferencia entre las dos fechas
	        $interval=$datetime2->diff($datetime1);
	         
	        # obtenemos la diferencia en meses
	        $intervalMeses=$interval->format("%m");
	        # obtenemos la diferencia en años y la multiplicamos por 12 para tener los meses
	        $intervalAnos = $interval->format("%y")*12;
	                
	        $diferencia = ($intervalMeses+$intervalAnos);

	        return $diferencia;
		}

		public function redondearUp($number, $significance) { // Funcion que siempre redondea hacia arriba dependiendo del segundo parametro que funciona como una "tolerancia".
			return ceiling($number, $significance);
		}
	}

	if( !function_exists('ceiling') )
	{
		function ceiling($number, $significance = 1)
		{
			return ( is_numeric($number) && is_numeric($significance) ) ? (ceil($number/$significance)*$significance) : false;
		}
	}

          
?>