<?php


$config = array();

//应用密钥
$config['app_key'] = '14fbf964645e7e2073ba953d68ac8729';

//加密密钥，可自定义字符
$config['encryption_key'] = '8457DFD51ACoKipp7eAc2SouLpfnLOlq7CXZ6ikoGGFm625pKeccaiqsAIMo!5e823jez+4i70vD$1G@KL_9MA{15D6DD29E532F46FE5';


//以下供开发者使用。
$config['url_mode'] = 0;


$config['plugins'] = array(
    
	'HeaderRewrite',
	'Stream',
	
	'Cookie',
	'Proxify',
	
	'Google',
	);


$config['curl'] = array(

);


return $config;

?>