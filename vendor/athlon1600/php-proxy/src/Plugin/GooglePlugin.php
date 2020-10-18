<?php
//谷歌镜像插件 by：张小强
namespace Proxy\Plugin;
use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class GooglePlugin extends AbstractPlugin {
protected $url_pattern = 'google.com';
public function onCompleted(ProxyEvent $event){
	
		$response = $event['response'];
		$html9 = $response->getContent();
		if(strpos($html9,'<meta content="telephone=no" name="format-detection">')){
		 $isMatched = preg_match_all('/<div class=\"_OXf\"><h3 class=\"r\"><a href=\"(.*?)\" (.*?)>(.*?)<\/a><\/h3>/', $html9, $matches);
//print_r($matches[1]);
foreach($matches[1] as $value){ 
    $valuetmp=$value;
    $value=preg_match_all('/(.*)=(.*)/', $value, $tmp);
    $value=$tmp[2][0];
    $deurl=base64_decrypt($value);
    
    $deurl='./link.php?url='.base64_encode($deurl);
    $html9 =str_replace($valuetmp,$deurl,$html9);    
		    
		   }
		    
		}else {
		    $isMatched = preg_match_all('/<div class=\"g\">(.*?)<h3 class=\"r\"><a href=\"(.*?)\" (.*?)>(.*?)<\/a><\/h3>/', $html9, $matches);

foreach($matches[2] as $value){ 
    $valuetmp=$value;
    $value=preg_match_all('/(.*)=(.*)/', $value, $tmp);
    $value=$tmp[2][0];
   $deurl='./link.php?url='.base64_encode(base64_decrypt($value));
    $html9 =str_replace($valuetmp,$deurl,$html9);
		 
}

		}
        $html9=preg_replace("/ping=\"\/url(.*?)\" oncontextmenu=\"(.*?)\"/",'',$html9);
		$response->setContent($html9);

}}?>