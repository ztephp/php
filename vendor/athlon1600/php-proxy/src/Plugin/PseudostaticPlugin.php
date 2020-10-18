<?php

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class PseudostaticPlugin extends AbstractPlugin {

	
	public function onCompleted(ProxyEvent $event){
		$response = $event['response'];
		$html0 = $response->getContent();
		$yy='http://coof.cf/index.php?q=';
		
	$isM = preg_match_all('/href="http:\/\/coof\.cf\/index\.php\?q=(.*?)\"/', $html0, $mat);
	$html0 =str_replace($yy,'http://coof.cf/',$html0);
		//print_r($mat[1]);
		foreach($mat[1] as $value){ 
		   
		   $b='$value';
		   $html0=str_replace($value,$b,$html0);  
		    
		}
	$response->setContent($html0);
	}
	
}

?>