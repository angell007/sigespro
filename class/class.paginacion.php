<?php

	class PaginacionData{

		public $ItemsPorPagina;
		public $ConsultaCount;
		public $CurrentPage;
		public $TipoConsulta;

		function __construct($itemsPorPagina, $consultaCount, $currentPage, $queryType = 'Simple'){
			$this->ItemsPorPagina = $itemsPorPagina;
			$this->ConsultaCount = $consultaCount;
			$this->CurrentPage = $currentPage;
			$this->TipoConsulta = $queryType;
		}

		function __destruct(){
			$this->ItemsPorPagina = '';
			$this->ConsultaCount = '';
			$this->CurrentPage = '';
			unset($this->ItemsPorPagina);
			unset($this->ConsultaCount);
			unset($this->CurrentPage);
		}
	}
          
?>