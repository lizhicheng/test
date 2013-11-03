<?php
if(!defined("__FUNC_VANTPLUGINS__"))
{
	define("__FUNC_VANTPLUGINS__",1);
	
	function vant_plu_link($url, $linktext=false, $target=false)
	{
		if (empty($url)) return false;

		$url = preg_replace('/[\n\r\t\f,，、\ ]+/',' ',$url);
		$url = explode(" ",$url);

		$str="";

		foreach ($url as $val)
		{
			if (!empty($str)) $str.= " ";

			$str.= sprintf("<a href=\"%s\"%s>%s</a>",
			$val,
			($target   ? ' target="'.$target.'"' : ''),
			($linktext ? $linktext               : $val)
			);
		}
		return $str;
	}

	function vant_plu_image($src, $alt = '') {
		$image = '<img src="' . $src . '" border="0"';
		if ($alt) {
			$image .= ' alt="' . $alt . '" title=" ' . $alt . ' "';
		}
		$image .= '>';

		return $image;
	}

	function vant_plu_gbSubstr($str,$strlen=10,$other=true) {
	
	  $str = strip_tags($str, '<p><a><br><br />');
		$j = 0;

		for($i=0;$i<$strlen;$i++)
		{
			if(ord(substr($str,$i,1))>0xa0) $j++;
		}

		if($j%2!=0) $strlen--;

		$rstr=substr($str,0,$strlen);

		if (strlen($str)>$strlen && $other) $rstr.='...';

		return $rstr;
	}

	//格式化显示
	function vant_plu_replaceChar($str) {
		$str=HTMLSpecialChars($str); //将特殊字元转成 HTML 格式。
		$str=str_replace(" ","&nbsp;",$str); //替换空格替换为&nbsp;
		$str=nl2br($str); //将回车替换为<br>
		$str=str_replace("<?","< ?",$str); //替换PHP标记
		return $str;
	}

	function vant_plu_default($str,$val)
	{
		if (!isset($str) || empty($str) || (is_array($str) && count($str) == 0)) $str = $val;
		return $str;
	}
}
?>
