<?php
	class Vant
	{
		var $tplDir           =  '';
		var $compileDir       =  '';
		var $tplFile          = array();
		var $compileFile      = array();
		var $vars             = array();

		var $left_delimiter   =  '{';
		var $right_delimiter  =  '}';

		var $needPlugins      = false;

		var $errMsg         = '';
		var $errReport      = false;
		var $errHalt        = false;
		var $errPrefix      = '';

		var $functions = array(
		'noparam' => array("addslashes","htmlspecialchars","htmlentities",
		"nl2br","quotemeta","rawurlencode","bin2hex","strip_tags",
		"stripslashes","strlen","strrev","strtolower",
		"strtoupper","trim","ucfirst","ucwords","sizeof",
		"basename","dirname","base64_encode","base64_decode",
		"empty","is_array","isset","getdate","crc32","md5"
		),
		'right' => array("strrchr","strstr","str_pad","number_format","substr"
		),
		'left'  => array("date","implode","sprintf","str_replace","preg_replace"
		),
		);

		var $safeMode         = true;

		var $trustedDir       = array();

		function Vant( $tplDir = "templates" )
		{
			$this->errReport        = true;
			$this->errHalt          = true;
			$this->errPrefix        = "Vant";

			$this->setDir($tplDir);
		}

		function setDir( $dir )
		{
			if (!is_dir( $dir ))
			{
				return $this->catchErr("setDir: $dir is an invalid path.");
			}
			if (substr($dir, -1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR ;
			$this->tplDir = $dir;

			$this->setCompiteDir();
			return true;
		}

		function setCompiteDir($tmpDir='')
		{
			if (empty($tmpDir)){
				$dirbasename = @basename($this->tplDir);
				#加入data目录
				$tmpDir = ($dirbasename == "." || $dirbasename == "..") ? $this->tplDir : dirname($this->tplDir) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $dirbasename."_c";
			}
			
			if (!is_dir($tmpDir))
			{
				if (!@mkdir($tmpDir, 0777))
				{
					return $this->catchErr("setCompiteDir: can't create $tmpDir, please create it manually.");
				}
			}
			$this->compileDir = $tmpDir;
		}

		function parse($fileName,$returnVal = false)
		{
			$this->setFile($fileName);

			if ($this->checkPhp($fileName))
			{
				extract($this->vars);
				$includeFile = $this->tplFile[$fileName];
			}
			else
			{
				if ( !$this->isCompile( $fileName )) $this->createCompileFile( $fileName );
				$includeFile = $this->compileFile[$fileName];
			}

			//if (count($this->err->errInfo) > 0)return false;

			if ($returnVal){
				ob_start();
				include ( $includeFile );
				$contents = ob_get_contents();
				ob_end_clean();
				return $contents;
			}
			else include ( $includeFile );
		}

		function checkPhp($fileName)
		{
			$lastname = strtolower(strrchr(basename($this->tplFile[$fileName]),"."));
			if ($lastname == ".php" || $lastname == ".php3")
			{
				if ($this->safeMode)
				{
					if (count($this->trustedDir) == 0) return $this->catchErr("loadFile: can't find safe directory.");
					foreach ($this->trustedDir as $v)
					{
						if (!preg_match("/^".preg_quote(realpath($v))."/",realpath($this->tplFile[$fileName]))){
							return $this->catchErr("loadFile: ".$this->tplFile[$fileName]." is not in safe directory.");
						}
					}
				}
				return true;
			}
			return false;
		}

		function setFile($fileName)
		{
			$this->tplFile[$fileName] = $this->tplDir . $fileName;
			if ( !file_exists( $this->tplFile[$fileName] ) )
			return $this->catchErr("setFile: ".$this->tplFile[$fileName]." isn't exists.");

			$_filename = urlencode(basename($fileName));
			$_crc32 = crc32($fileName) . "^";
			$_crc32 = '%%' . substr($_crc32,0,3) . '^%%' . $_crc32;
			$this->compileFile[$fileName] = $this->compileDir.'/'. $_crc32 . $_filename.".php";
		}

		function isCompile($fileName)
		{
			//if ($this->compileAnyway)return false;
			if (!file_exists($this->compileFile[$fileName])) return false;
			$mtime = filemtime($this->compileFile[$fileName]);
			if ($mtime < filemtime(isset($_SERVER['SCRIPT_FILENAME']) ?
			$_SERVER['SCRIPT_FILENAME'] :
			@$GLOBALS['HTTP_SERVER_VARS']['SCRIPT_FILENAME'])
			|| $mtime < filemtime(__FILE__)
			|| $mtime < filemtime($this->tplFile[$fileName]) )
			return false;
			return true;
		}

		function createCompileFile( $fileName )
		{
			$str = $this->loadFile( $this->tplFile[$fileName]);

			$str = preg_replace("/".$this->left_delimiter."\*.*\*".$this->right_delimiter."/sU","",$str);

			$this->getVar($str);
			$this->getLoadConfig($str);
			$this->getLoop($str);
			$this->getIf($str);
			$this->getInclude($str);
			$this->getFunc($str);

			$str = preg_replace("/\?>[[:space:]]*<\?php /iU","",$str);

			if ($this->needPlugins)$str = '<?php include("'.str_replace("\\","/",dirname(__FILE__)).'/vantplugins.inc.php"); ?>'."\n".$str;

			if (!($fp = fopen($this->compileFile[$fileName], 'wb'))) {
				return $this->catchErr("createCompileFile: can't write data into ".$this->compileFile[$fileName]);
			}
			flock($fp,LOCK_EX);
			fwrite( $fp, $str );
			flock($fp, LOCK_UN);
			fclose( $fp );
			@chmod( $this->compileFile[$fileName], 0644 );
		}

		function loadFile( $fileName)
		{
			if ( !is_file($fileName) ){
				return $this->catchErr("loadFile: $fileName is not a valid fileName.");
			}

			if (!($fd = @fopen($fileName, 'rb'))) return $this->catchErr("loadFile: can't open $filename");
			flock($fd, LOCK_SH);
			$str = fread($fd, filesize($fileName));
			flock($fd, LOCK_UN);
			fclose($fd);

			$str = preg_replace('/'.$this->left_delimiter.'\*.*\*'.$this->right_delimiter.'/U','',$str);

			$str = str_replace("<?","< ?",$str);
			$str = str_replace("<%","< %",$str);
			$str = str_replace("?>","? >",$str);
			$str = str_replace("%>","% >",$str);
			$str = preg_replace("/language[[:space:]]*=[[:space:]]*['\"]?php['\"]?/","",$str);

			return $str;
		}

		function getVar(& $str)
		{
			preg_match_all("/".$this->left_delimiter." *([a-zA-Z_0-9]+(( *[\[\|].*)*)) *".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER );

			foreach ($regs as $val)
			{
				$tplStr = $val[0];
				$name   = $val[1];
				$newStr = $this->getVarStr($name);

				if (false !== $newStr)$str = str_replace($tplStr,"<?php echo $newStr; ?>", $str);
			}
			return $str;
		}

		function getVarStr($str)
		{
			preg_match("/([a-zA-Z_0-9]+) *(\[.*\])*( *\|.*)*/",$str,$regs);

			$name = $regs[1];
			$cname  = $regs[2];
			$func = trim($regs[3]);

			if (preg_match("/^(else|elseif)$/i",$name))return false;

			$varStr = "\$this->vars['$name']".$cname;

			if (!empty($func))
			{
			    // 解决调用类静态方法时::和:冲突
			    $func = strtr($func, array('::' => '->'));
				$func = explode("|",$func);
				for ($i=1,$n=count($func);$i<$n;$i++)
				{
					$cfunc = explode(":",trim($func[$i]));
					$funcname = strtr(trim($cfunc[0]), array('->' => '::')); // 解决::和:冲突
					$param = array();
					$cfunc[1] = trim($cfunc[1]);
					if (!empty($cfunc[1]))$param = explode(",",$cfunc[1]);

					if (in_array($funcname,$this->functions['noparam']))$varStr = $funcname."($varStr)" ;
					else if (in_array($funcname,$this->functions['right']))$varStr = $funcname."(".$varStr.",'".implode("','",$param)."')" ;
					else if (in_array($funcname,$this->functions['left']))$varStr = $funcname."('".implode("','",$param)."',".$varStr.")" ;
					elseif(function_exists($funcname) or is_callable($funcname))$varStr = $funcname."(".$varStr.",'".implode("','",$param)."')" ;//var string must on the left
					else {
						$this->needPlugins = true;
						$param = 'array('.$varStr.',"'.implode('","',$param).'")';
						$varStr = "call_user_func_array('vant_plu_$funcname', $param) ";
					}
				}
			}
			unset($regs);
			return  $varStr;
		}

		function getLoop(& $str)
		{
			$regs = array();
			preg_match_all("/".$this->left_delimiter."[ ]*for[ ]+name[ ]*=(.*)data[ ]*=(.*)(from[ ]*=(.*)step[ ]*=(.*))?".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER );

			foreach ($regs as $val)
			{
				$tplStr = $val[0];
				$name   = trim($val[1]);
				$data   = trim($val[2]);
				$from   = trim($val[4]);
				$step   = trim($val[5]);

				$dataStr = $this->getVarStr($data);

				$firstStr = $endStr = $keyStr = $valStr = '';

				$firstStr .= "<?php \n";
				$firstStr .= "\$this->vars['$name']['rownum'] = 0 ;\n" ;
				if (isset($val[4]))
				{
					$firstStr .= "if(is_numeric($dataStr)) \$this->vars['$name']['total'] = $dataStr + 1 ;\n" ;
					$firstStr .= "else {\n    if (empty($dataStr))$dataStr = array(); \n    else if (!is_array($dataStr))$dataStr = (array)$dataStr;\n";
					$firstStr .= "    \$this->vars['$name']['total'] = count($dataStr);\n} \n";
					if (is_numeric($this->vars[$data]))
					$firstStr .= "for (\$this->vars['$name']['item'] = \$this->vars['$name']['value'] = $from; \$this->vars['$name']['item'] < \$this->vars['$name']['total']; \$this->vars['$name']['item'] += $step,\$this->vars['$name']['value'] += $step){ \n";
					else
					{
						$firstStr .= "for (\$this->vars['$name']['item'] = $from; \$this->vars['$name']['item'] < \$this->vars['$name']['total']; \$this->vars['$name']['item'] += $step){ \n";
						$firstStr .= "\$this->vars['$name']['value'] = ".$dataStr."[\$this->vars['$name']['item']]; \n" ;
					}
				}
				else
				{
					$firstStr .= "if (empty($dataStr))$dataStr = array(); \nelse if (!is_array($dataStr)) $dataStr = (array)$dataStr;\n";
					$firstStr .= "\$this->vars['$name']['total'] = count($dataStr) ;\n" ;
					$firstStr .= "foreach ($dataStr as \$this->vars['$name']['item'] => \$this->vars['$name']['value']){ \n" ;
				}
				$firstStr .= "\$this->vars['$name']['rownum'] ++ ;\n" ;

				$firstStr .= "\$this->vars['$name']['mod2'] = \$this->vars['$name']['rownum'] % 2; \n";
				$firstStr .= "\$this->vars['$name']['first'] = (\$this->vars['$name']['rownum'] == 1) ? true : false ;\n";
				$firstStr .= "\$this->vars['$name']['last'] = (\$this->vars['$name']['rownum'] == \$this->vars['$name']['total']) ? true : false ;\n";
				$firstStr .= "?>";

				$str = str_replace($tplStr, $firstStr, $str);

				$str = preg_replace("/".$this->left_delimiter."[ ]*\/for([ ]*$name)?[ ]*".$this->right_delimiter."/U", "<?php } ?>\n", $str);
			}
			unset($regs,$val);
		}

		function getIf(& $str)
		{

			//preg_match_all("/".$this->left_delimiter."[ ]*(else if|elseif|if)[ ]+(.*)[ ]+(.*)[ ]+(.*)[ ]*".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER );
			preg_match_all("/".$this->left_delimiter."[ ]*(else if|elseif|if)(.*)(!=|>=|<=|==|>|<|%)(.*)".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER );

			foreach ($regs as $val)
			{
				$tplStr   = $val[0];
				$ifStr    = $val[1];
				$param1   = trim($val[2]); 
				$operator = $val[3];
				$param2   = trim($val[4]);

				if (!preg_match("/^(['\"].*['\"]|[0-9]+|true|false)$/i",$param1))
				{
					$param1= $this->getVarStr($param1);
				}

				if (!preg_match("/^(['\"].*['\"]|[0-9]+|true|false)$/",$param2))
				{
					$param2= $this->getVarStr($param2);
				}

				$tmpStr = '<?php ';
				if (strtolower($ifStr) == "if") $tmpStr .= $ifStr;
				else  $tmpStr .= "}".$ifStr;
				$tmpStr .= " ($param1 $operator $param2){ ?>";

				$str = str_replace($tplStr,$tmpStr,$str);
			}
			unset($regs,$val);
			$str = preg_replace("/".$this->left_delimiter."[ ]*else[ ]*".$this->right_delimiter."/iU",'<?php }else{ ?>',$str);
			$str = preg_replace("/".$this->left_delimiter."[ ]*\/if[ ]*".$this->right_delimiter."/iU",'<?php } ?>',$str);
		}

		function getInclude(& $str)
		{
			preg_match_all("/".$this->left_delimiter."[ ]*include[ ]+file[ ]*=(.*)".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER);

			foreach ($regs as $val)
			{
				$tplStr = $val[0];
				$file   = trim($val[1]);

				if (!preg_match("/^['\"].*['\"]$/",$file)) $file = $this->getVarStr($file);

				$content = '<?php $this->setInclude('.$file.'); ?>';
				$str = str_replace($tplStr,$content,$str);
			}
			unset($regs,$val);
		}

		//{func name= para=}
		function getFunc(& $str)
		{
			$regs = array();
			preg_match_all("/".$this->left_delimiter."[ ]*func[ ]+name[ ]*=(.*)para[ ]*=(.*)?".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER);
			foreach ($regs as $val)
			{
				$tplStr = $val[0];
				$name   = trim($val[1]);
				$para	= trim($val[2]);
				$paravar	= $this->getVar2($this->left_delimiter.$para.$this->right_delimiter);//$this->left_delimiter.$para.$this->right_delimiter
				$content = '<?php echo '.$name.'('.$paravar.'); ?>';
				$str = str_replace($tplStr,$content,$str);
			}
			unset($regs,$val);
		}

		function getVar2($str)
		{
			preg_match_all("/".$this->left_delimiter." *([a-zA-Z_0-9]+(( *[\[\|\.].*)*)) *".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER );

			foreach ($regs as $val)
			{
				$tplStr = $val[0];
				$name   = $val[1];
				$newStr = $this->getVarStr($name);

				if (false !== $newStr)$str = str_replace($tplStr,"$newStr", $str);
			}
			return $str;
		}

		function getLoadConfig(& $str)
		{
			preg_match_all("/".$this->left_delimiter."[ ]*load_config[ ]+file[ ]*=(.*)".$this->right_delimiter."/iU",$str,$regs,PREG_SET_ORDER);

			foreach ($regs as $val)
			{
				$tplStr = $val[0];
				$file   = trim($val[1]);

				if (!preg_match("/^['\"].*['\"]$/",$file)) $file = $this->getVarStr($file);

				$content = '<?php $this->load_config('.$file.'); ?>';
				$str = str_replace($tplStr,$content,$str);
			}
			unset($regs,$val);
		}

		function set($varname, $value = '')
		{
			if (is_array($varname)){
				foreach ($varname as $key => $val) {
					if ($key != '') {
						$this->vars[$key] = $val;
					}
				}
			} else {
				if ($varname != '')
				$this->vars[$varname] = $value;
			}
		}

		function setTrustedDir($dir='')
		{
			if (!is_array($dir))
			{
				if (empty($dir))return;
				if (!is_dir($dir)) return $this->catchErr("setTrustedDir: $dir is invalid directory.");
				if (substr($dir, -1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR ;
				if (!in_array($dir,$this->trustedDir)) $this->trustedDir[]= $dir;
			}
			else {
				reset( $dir );
				while (list(,$v) = each($dir))
				{
					if (!empty($v)){
						if (!is_dir($v)) return $this->catchErr("setTrustedDir: $v is invalid directory.");
						if (substr($v, -1) != DIRECTORY_SEPARATOR) $v .= DIRECTORY_SEPARATOR ;
						if (!in_array($v,$this->trustedDir)) $this->trustedDir[]= $v;
					}
				}
			}
		}

		function setSafeMode($boolVal)
		{
			$this->safeMode = $boolVal;
		}

		function setDelimiter($left = '{', $right = '}')
		{
			$this->left_delimiter  = $left;
			$this->right_delimiter = $right;
		}

		function clearVars()
		{
			$this->vars = array();
		}

		function setInclude($fileName,$vars = array())
		{
			$expFile = explode("?",$fileName);
			$fileName = & $expFile[0];

			$this->setFile($fileName);

			if ($this->checkPhp($fileName)){
				extract($this->vars);
				include ( $this->tplFile[$fileName] );
			}
			else
			{
				$tmpVars = $this->vars;

				$expVars = explode("&",$expFile[1]);
				foreach ($expVars as $val)
				{
					$expVar = explode("=",$val);
					if (isset($this->vars[$expVar[1]])) $expVar[1] = $this->vars[$expVar[1]];
					$this->set($expVar[0],$expVar[1]);
				}

				if ( !$this->isCompile( $fileName )) $this->createCompileFile( $fileName );
				include ( $this->compileFile[$fileName] );

				$this->vars = $tmpVars;

				unset($tmpVars,$expVars,$expVar);
			}
		}

		function load_config ( $filename,  $name = 'config' )
		{
			$expFile = explode("?",$fileName);
			$fileName = & $expFile[0];

			$filename = $this->tplDir . $filename;
			if ( !file_exists( $filename ) )
			return $this->catchErr("setFile: ".$filename." is not exists.");

			$section  =  null;
			if (is_file($filename))
			{
				$cfgfile  =  file($filename);

				if (is_array($cfgfile))
				{
					foreach ($cfgfile as $line)
					{
						if (substr($line, 0, 1) != '#')
						{
							if (substr($line, 0, 1) == '[')
							{
								if ($rbr = strpos($line, ']'))
								{
									$section  =  substr($line, 1, $rbr -1);
								}
							}
							if ($tr = strpos($line, '='))
							{
								$k  =  trim(substr($line, 0, $tr));
								$v  =  trim(substr($line, $tr+1));
								if (isset($section))
								{
									$this->vars[$name][$section][$k]  =  $v;
								}
								else
								{
									$this->vars[$name][$k]  =  $v;
								}
							}
						}
					}
				}
			}

		}

		function catchErr($msg)
		{
			if(empty($this->errMsg)) $this->errMsg = $msg;

			if ($this->errReport) $this->printErr(true);

			if ($this->errHalt) exit;

			return false;
		}

		function printErr($exit = true)
		{
			if (!empty($this->errMsg))
			{
				echo $this->errPrefix." Error:<br>";
				echo $this->errMsg."<br />";
				if ($exit)
				{
					echo "Halted.";
					exit;
				}
			}
		}
	}
?>