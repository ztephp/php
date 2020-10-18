<?php

use Proxy\Config;

// strip away extra parameters text/html; charset=UTF-8
function clean_content_type($content_type){
	return preg_replace('@;.*@', '', $content_type);
}

function is_html($content_type){

	$content_type = clean_content_type($content_type);
	
	$text = array(
		//'text/cmd',
		//'text/css',
		//'text/csv',
		//'text/example',
		'text/html'
		//'text/javascript',
		//'text/plain',
		//'text/rtf',
		//'text/vcard',
		//'text/vnd.abc',
		//'text/xml'
	);

	return in_array($content_type, $text);
}

function base64_url_encode($input){
	// = at the end is just padding to make the length of the str divisible by 4
	return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function base64_url_decode($input){
	return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) % 4, '=', STR_PAD_RIGHT));
}

function in_arrayi($needle, $haystack){
	return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

// regular array_merge does not work if arrays have numeric keys...
function array_merge_custom()
{
    $array      = array();
    $arguments  = func_get_args();
    foreach($arguments as $args)
        foreach($args as $key => $value)
            $array[$key] = $value;
    return $array;
}

// rotate each string character based on corresponding ascii values from some key
function str_rot_pass($str, $key, $decrypt = false){
	
	// if key happens to be shorter than the data
	$key_len = strlen($key);
	
	$result = str_repeat(' ', strlen($str));
	
	for($i=0; $i<strlen($str); $i++){

		if($decrypt){
			$ascii = ord($str[$i]) - ord($key[$i % $key_len]);
		} else {
			$ascii = ord($str[$i]) + ord($key[$i % $key_len]);
		}
	
		$result[$i] = chr($ascii);
	}
	
	return $result;
}

function app_url(){
	return (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
}

function render_string($str, $vars = array()){

	preg_match_all('@{([a-z0-9_]+)}@s', $str, $matches, PREG_SET_ORDER);
	
	foreach($matches as $match){
	
		extract($vars, EXTR_PREFIX_ALL, "_var");
		
		$var_val = ${"_var_".$match[1]};
		
		$str = str_replace($match[0], $var_val, $str);
	}
	
	return $str;
}

function render_template($file_path, $vars = array()){

	// variables to be used within that template
	extract($vars);
	
	ob_start();
	
	if(file_exists($file_path)){
		include($file_path);
	} else {
		die("Failed to load template: {$file_path}");
	}
	
	$contents = ob_get_contents();
	ob_end_clean();
	
	return $contents;
}

function add_http($url){

	if(!preg_match('#^https?://#i', $url)){
		$url = 'http://' . $url;
	}
	
	return $url;
}

function time_ms(){
	return round(microtime(true) * 1000);
}

function contains($haystack, $needle){
	return strpos($haystack, $needle) !== false;
}

function base64_encrypt($data, $key = false){

	if($key){
		$data = str_rot_pass($data, $key);
	} else if(Config::get('encryption_key')){
		$data = str_rot_pass($data, Config::get('encryption_key'));
	}
	
	return base64_url_encode($data);
}

function base64_decrypt($data, $key = false){

	$data = base64_url_decode($data);
	
	if($key){
		$data = str_rot_pass($data, $key, true);
	} else if(Config::get('encryption_key')){
		$data = str_rot_pass($data, Config::get('encryption_key'), true);
	}
	
	return $data;
}

// www.youtube.com TO proxy-app.com/index.php?q=encrypt_url(www.youtube.com)
function proxify_url($url, $base_url = ''){
	
	$url = htmlspecialchars_decode($url);
	
	if($base_url){
		$url = rel2abs($url, $base_url);
	}
	
	return app_url().'?q='.base64_encrypt($url);
}

function vid_player($url, $width, $height, $extension = false){

	$path = parse_url($url, PHP_URL_PATH);
	
	$html5 = false;
	
	if($path){
	
		$extension = $extension ? $extension : pathinfo($path, PATHINFO_EXTENSION);
		
		if($extension == 'mp4' || $extension == 'webm' || $extension == 'ogg'){
			$html5 = true;
		}
	}
	
	// this better be an absolute url
	$video_url = proxify_url($url);

	if($html5){
	
		$html = '<video width="100%" height="100%" controls autoplay>
			<source src="'.$video_url.'" type="video/'.$extension.'">
			Your browser does not support the video tag.
		</video>';
		
	} else {
	
		// encode before embedding it into player's parameters
		$video_url = rawurlencode($video_url);
	
		$html = '<object id="flowplayer" width="'.$width.'" height="'.$height.'" data="//releases.flowplayer.org/swf/flowplayer-3.2.18.swf" type="application/x-shockwave-flash">
 	 
       	<param name="allowfullscreen" value="true" />
		<param name="wmode" value="transparent" />
        <param name="flashvars" value=\'config={"clip":"'.$video_url.'", "plugins": {"controls": {"autoHide" : false} }}\' />
		
		</object>';
	}
	
	return $html;
}

function element_find($id, $html){

	if(preg_match('/<(\w+)[^>]+id="'.$id.'"/is', $html, $matches, PREG_OFFSET_CAPTURE)){
	
		$element_start = $matches[0][1];
		$element_end = 0;
		
		// tag stuff
		$tag_name = $matches[1][0];
		$tag_len = strlen($tag_name);
		
		$run_count = 300;
		$start = $element_start;
		
		// "unclosed" <tag elements we found so far...
		$open_count = 0;
		
		while($run_count > 0){
		
			$open_tag = strpos($html, "<{$tag_name}", $start);
			$close_tag = strpos($html, "</{$tag_name}", $start);

			// we encountered the start of another tag...
			if($open_tag < $close_tag){
				$open_count++;
				$start = $open_tag + $tag_len + 1;
			// we encountered a closed tag first
			} else if($close_tag < $open_tag){
				$open_count--;
				$start = $close_tag + $tag_len + 2;
			}
			
			if($open_count == 0){
				$element_end = $close_tag + $tag_len + 3;
				break;
			}
			
			$run_count--;
		}
		
		// something went wrong... don't bother returning anything
		if($run_count == 0){
			return false;
		}
		
		return array($element_start, $element_end);
	}
	
	return false;
}

function element_remove($id, $html){

	$arr = element_find($id, $html);
	
	if($arr){
		return str_remove($html, $arr[0], $arr[1]);
	}
	
	return $html;
}

function str_remove($str, $start, $end){
	return substr_replace($str, "", $start, $end-$start);
}

function rel2abs($rel, $base)
{
	if (strpos($rel, "//") === 0) {
		return "http:" . $rel;
	}
	
	if($rel == ""){
		return "";
	}
	
	/* return if  already absolute URL */
	if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
	/* queries and  anchors */
	if ($rel[0] == '#' || $rel[0] == '?') return $base . $rel;
	/* parse base URL  and convert to local variables:
	$scheme, $host,  $path */
	extract(parse_url($base));
	/* remove  non-directory element from path */
	@$path = preg_replace('#/[^/]*$#', '', $path);
	/* destroy path if  relative url points to root */
	if ($rel[0] == '/') $path = '';
	/* dirty absolute  URL */
	$abs = "$host$path/$rel";
	/* replace '//' or  '/./' or '/foo/../' with '/' */
	$re = array(
		'#(/\.?/)#',
		'#/(?!\.\.)[^/]+/\.\./#'
	);
	for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
	}

	/* absolute URL is  ready! */
	return $scheme . '://' . $abs;
}

?>