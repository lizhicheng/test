<?php
class WDCDB
{
	var $server;
	var $database;
	var $dbcharset;
	var $connect_id;
	var $query_result;
	var $query_nums = 0;
	
	function WDCDB($server,$username,$password,$database,$dbcharset,$pconnection = false)
	{
		$this->server = $server;
		$this->database = $database;
		$this->dbcharset = $dbcharset;
				 
		if($pconnection) {
			$this->connect_id = @mysql_pconnect($this->server, $username, $password);
		}	else {
			$this->connect_id = @mysql_connect($this->server, $username, $password);
		}
		
		if($this->version() > '4.1') {
			mysql_query("SET character_set_connection=$dbcharset, character_set_results=$dbcharset, character_set_client=binary");
		}

		if($this->version() > '5.0.1') {
			mysql_query("SET sql_mode=''");
		}
				
		if($this->connect_id)	{
			$dbselect = @mysql_select_db($this->database);
			if(!$dbselect) {
				@mysql_close($this->connect_id);
				$this->connect_id = false;
			}
			return $this->connect_id;
		}	else	{
			return $this->error("Can not connact the database!"); 
		}
	}
	
	function error($msg)
	{
		echo "MYSQL ERROR:<br />" . $msg . "<br />" . mysql_error();
		exit(0);
	}
	
	function close()
	{
		if($this->connect_id)
		{
			if($this->query_result)
			{
				@mysql_free_result($this->query_result);
			}
			$result = @mysql_close($this->connect_id);
			return $result;
		}
		else
		{
			return $this->error(); 
		}
	}
	
	function query($sql = "", $method = "")
	{
		if(stristr($sql, 'truncate') || stristr($sql, 'drop')) return $this->error("SQL:1");
		if(stristr($sql, 'friends_') && stristr($sql, 'delete from')) return $this->error("SQL:2");

		if($method == 'U_B' && function_exists('mysql_unbuffered_query')){
			$this->query_result = @ mysql_unbuffered_query($sql,$this->connect_id);
		} else {
			$this->query_result = @ mysql_query($sql,$this->connect_id);
		}
		
		$this->query_nums++;
		//echo $sql;
		if($this->query_result)
		{
			return $this->query_result;
		}
		else
		{
			return $this->error("SQL:".$sql); 
		}
	} 
	
	function num_rows()
	{
		if($this->query_result)
		{
			$result = @mysql_num_rows($this->query_result);
			return $result;
		}
		else
		{
			return 0;
		}
	}
	
	function get_one($SQL,$result_type = MYSQL_ASSOC)
	{//MYSQL_ASSOC，MYSQL_NUM，MYSQL_BOTH
		$query = $this->query($SQL,'U_B');
		$rt =& mysql_fetch_array($query,$result_type);
		return $rt;
	}	
	
	function fetch_row()
	{
		if($this->query_result)
		{
			return @mysql_fetch_array($this->query_result, MYSQL_ASSOC);
		}
		else
		{
			return null;
		}
	}
	
	function fetch_array($query, $type = MYSQL_ASSOC)
	{
		if(empty($query))$query = $this->query_result;
		
		return @mysql_fetch_array($query, $type);
	}
	
	function free_result($query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}

		if ($query_id)
		{
			@mysql_free_result($query_id);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function last_id()
	{
		return mysql_insert_id(); 
	}
	
	function version()
	{
		return mysql_get_server_info();
	}
	
	function restore($sqlfile = '')
	{
		require_once $sqlfile;
		
		foreach ($sqls as $sql) {
			$this->query($sql);
		}
		
		return 1;
	}
	
	function backup($table, $start, $file)
	{
		$size = 50;
		$data = "";
		$i = 0;
		$fp = 0;
		$query = $this->query("SELECT * FROM " . $table . " LIMIT $start, $size");
		if($this->num_rows() < 1) return 0;
		while($row = $this->fetch_array($query))
		{
			if($i == 0) {
				$fp = @fopen($file, "a");
				fputs($fp, "<?php\n");
			}
			$data = "REPLACE INTO " . $table . " VALUES (";
			foreach($row as $try) {
				if(!isset($try)) {
					$data .= "NULL,";
				}
				elseif($try == '') {
					$data .= "\"\",";
				}
				elseif($try == '0') {
					$data .= "\"0\",";
				}
				else/*if(!empty($try))*/ {
					$try = addslashes($try);
					$try = ereg_replace("\n#", "\n" . '\#', $try);
					$data .= "\"". $try . "\",";
				}
			}
			$data .= ");";
			$data = str_replace(",);", ");", $data);
			fputs($fp, "\$sqls[]=\"".addslashes($data)."\";\n");
			$i++;
		}
		fputs($fp, "?>");
		fclose($fp);
		return $i;		
	}
}
?>
