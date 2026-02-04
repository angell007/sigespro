<?php
class template {
	var $error;
	var $content;
	
	//CONSTRUCTOR
	public function __construct($template)	
	{
		$text = "";
   		$fp = fopen($template,"r");
   		while ($line= fgets($fp,1024))
   		{
	  		$text .= $line;
   		}
		$this->content=$text;
	}
	
	//DESTRUCTOR
	public function __destruct()
	{		
		unset($error);		
		unset($content);
	}
	

	//METODOS
	//privados
	private function xtrct_tag($text)
	{
	   $tagregexp="/\[\[(.*?)\]\]/is";
		preg_match_all($tagregexp, $text, $regsTag);
		return $regsTag[0];
		
	}
	
	private function clean_orphans($content)
	{
		$clean_content=$content;
		$tags=$this->xtrct_tag($content);
		foreach ($tags as $tag) {
			$clean_content=str_replace($tag,"",$clean_content);
		}
		return $clean_content;
	}
	

	//publicos
	
	public function getError()
	{
		return $this->error;
	}
		
	public function get()
	{
		return $this->clean_orphans($this->content);
	}
	
	public function custom($tag,$value)
	{
		$this->content=str_replace($tag,$value,$this->content);
	}
	
}
?>