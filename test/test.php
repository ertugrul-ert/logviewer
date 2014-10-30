<?php namespace Ahir\Ctrl\Controllers;

use Ahir\Ctrl\Render;



class Test extends Render {

	public $logpath;
	public $pagination = [ 'currentpage' => 1   ];

	public function sample($filename="" , $pageno=1 , $itemperpage=5 )  //$filename = '2014-10-21.log'
	{
 		$this->logpath =  $this->getlogdir('logPathCi') ;

		$this->pagination['itemperpage'] =  $itemperpage ;
		// $this->pagination['totalpage'] = calctotalpage( , );
		$this->pagination['pgPath'] = $filename;
		$this->pagination['currentpage'] = $pageno;
 		return $this->twig
		->render('view.html',
			[
			'ctrlpathdel' => '/ctrl/test/delete_log_line/',
			'ctrlpath' => '/ctrl/test/sample/',
			'lognames' => $this->lognames( $filename  , $this->logpath), //if exist marks as active on html
			'currentlog' => $this->fetch_if_exists(	$this->logpath . $filename,
												   	$filename,
												    $pageno,
												    $this->pagination['itemperpage'] ),  //if null, ipp=5;
			'pgn' =>  $this->pagination
			]);
	}
	// if a word(which is searched) is present in a line, that line is deleted  
	// aranan kelime o satırda bulunuyorsa , o satır silinir.
	public function delete_log_line()  //$dates , $filename
	{	
		$dates =  $this->input->post('dates') ;
		$filename =  $this->input->post('filename') ;

		// Get LogDirectory From Config File;
		$this->logpath =  $this->getlogdir('logPathCi') ;
        $filepath =  $this->logpath . trim($filename)  ;
		$dates = array_map('trim', $dates);
   		$words = $dates;
 		$cont=file( $filepath );
 	
 		foreach ($cont as $lineNumber => $line) {   
 			foreach ($words as  $word) {  		// in the line , look for all filter words
 				if (strpos($line,$word) !== false)  //if word is found in the line ,delete it
 				{
					unset ( $cont[ $lineNumber ] );
					break;
				} 
 			}
		}
		if ( count($cont) == 0 ) 
		{ // if no error is present , delete file
			unlink( $filepath );
			return;	
		}

		file_put_contents( $filepath , implode( $cont));
	 	return;
	}


	public function calc_and_set_totalpage( $linecount, $itemperpage)
	{
		$totalpage =  (int) ceil($linecount / $itemperpage);
		$this->pagination['totalpage'] = $totalpage;
		return ;
	}
	// returns string   /var/www/igniter/log/ 
	public function getlogdir( $key ='logPathCi')
	{	  
		$config_path = dirname( dirname(__FILE__) ) . '/config/config.php';
		$array = include $config_path;

		$log_dir =  dirname( dirname(__FILE__) ) . $array[$key] .'/';
		return $log_dir;
	}
	
	public function lognames($clickedName , $path)
	{		
		// /var/www/igniter/log/ 
		// $path = $this->logpath ;
		$names_fullPath = glob( $path .'*.{log,txt}', GLOB_BRACE);  
		foreach ($names_fullPath as $key => $full_path) 
		{	
			$names_only[$key]['name'] = basename($full_path ); // basename($value, '.log' ) 
			if ($clickedName == basename($full_path ) ) 
			{
				$names_only[$key]['active'] ='active';
			} else {
				$names_only[$key]['active'] ='';
			}
		}
		return  $names_only ; //$names_only;
	}
    

	public function fetch_if_exists($filepath , $filename , $pageno , $itemperpage=5 )
	{
		if ($filename =="")
		{ 
			$extract[0]['date'] = "Hoşgeldiniz!" ;
			$extract[0]['message'] = "Hoşgeldiniz!" ;			
			$extract[0]['hide_del_btn'] = true ;

			return $extract;
		}

		if ( true == file_exists( $filepath )   ) // array of lines
		{	
			
			$content = file( $filepath);
 			if ( 0 == count(  $content) )  
 			{   
 				$extract[0]['date'] = "bu dosyada hiç satır bulunmuyor!" ;
				$extract[0]['message'] = "bu dosyada hiç hata girdisi  bulunmuyor! " ;
				$extract[0]['hide_del_btn'] = true ;
				return $extract;
  			} 

			$this->pagination['itemcount'] = count($content) ;
			$this->calc_and_set_totalpage( $this->pagination['itemcount'] , $itemperpage )  ;

			//if the requestes pageno >  existing pageno ,  set pageno  to  lastpage .
			 if (  $this->pagination['totalpage'] <  $pageno  ||  !is_numeric($pageno) ) 
			 {
			 	$pageno = 1 ;
			 	$this->pagination['currentpage'] = 1 ;
			 }
			$partofcontent = $this->get_content_of_page( $content , $pageno , $itemperpage ) ;

			//	var_dump($this->pagination );
			return $this->format_lines ( $partofcontent  );
		}else
		{
			$extract[0]['date'] = "log dosyası yok!" ;
			$extract[0]['message'] = "Bu adda bir log dosyası yok!" ;
			$extract[0]['hide_del_btn'] = true ;
			return $extract;
		}
	}

	public function get_content_of_page( $content , $pageno , $itemperpage )
	{
		$startkey = $itemperpage *( $pageno -1 ) ;	
		$maxkey =  count($content);

		for ($i=0; $i <  $itemperpage; $i++) 
		{ 
			if ($startkey + $i >=  $maxkey ) { 	break; } //dont exceed the maximum lines in textfile
			$partofc[$i] = $content[$startkey + $i] ;
		}
		return $partofc ;
	}

	public function format_lines( $content)
	{
		foreach ($content as $key => $line) 
	    {
	    	$extract[$key]['date'] = $this->date_extract( $line ); 
	    	$extract[$key]['message'] = $this->delete_all_between( "[", "]", $content[$key]);
	    }
	    return $extract;
	}
  
	private function date_extract($text )
	{
		$start_tag = '[';
		$end_tag = ']';
         // preg_match 		->   return $string 	$matches[1]  //( find first occurrences)
         // preg_match_all  ->   return $array 		$matches[1]  //( find multiple occurrences)
		if (preg_match('/'.preg_quote($start_tag).'(.*?)'.preg_quote($end_tag).'/s', $text, $matches)) 
         //{ //var_dump( $matches[1] ); }
			return $matches[1];
	}

	private function delete_all_between($beginning, $end, $string) 
	{
		$beginningPos = strpos($string, $beginning);
		$endPos = strpos($string, $end);
		if ($beginningPos === false || $endPos === false) {
			return $string;
		}

		$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
		//return str_replace($textToDelete, '', $string);

		// myModified version for       "[date] text [] []"  to "text"
		$dateless = str_replace($textToDelete, '', $string);
		return $boxless = str_replace(  "[] []" , '', $dateless) ;  //deletes the 2 boxes [][]
	}

}