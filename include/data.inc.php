<?php
class Data
{
	var $dbObj;//数据库操作对象
	var $fieldFunc = array();//在执行select操作后,应用到相应字段的回调函数,从此,模板的变量调节器就可丢到垃圾堆里去了
	var $table;//数据表名

	/* 构造函数
	* $dbObj 是数据库操作对象
	* $table 是数据表名
	*/
	function Data(& $dbObj)
	{
		$this->dbObj = $dbObj;
	}
	
	//设置数据表
	function setTable($table)
	{
		$this->table = $table;
	}	

	/* 设置字段的回调函数
	* $fieldName  此函数应用的字段名
	* $funcName   函数名或对象的方法名
	* $funcParams 函数的参数(数组)
	* $paramsAdd  字段数据本身作为函数的参数时,应放到其它参数($funcParams)的左边还是右边
	* $objName    如果$funcName是一个对象的方法,则$objName为该对象
	* $table      数据表
	* 例:sql为:select title from user,现在要将结果中title字段的值中的\r改成<br> ,按如下方式写:
	* setFieldFunc('title','str_replace',array('\r','<br>'),'right','','user')
	* 如果用对象的方法作回调函数,如有个对象$obj,$obj有个方法myreplace() ,则按如下方式写:
	* setFieldFunc('title','myreplace',array('\r','<br>'),'right','obj','user')
	*/
	function setFieldFunc($fieldName,$funcName,$funcParams='',$paramsAdd='left',$objName='',$table='')
	{
		if (empty($table)) $table = $this->table ;

		if (empty($fieldName)) return $this->err->catchErr($this->getSysMsg('3001','$fieldName'),__FILE__,__LINE__);
		if (empty($funcName)) return $this->err->catchErr($this->getSysMsg('3001','$funcName'),__FILE__,__LINE__);

		$tmp['fieldName'] = $fieldName;
		$tmp['funcName']  = (!empty($objName)) ? array($objName,$funcName) : $funcName;
		if (empty($funcParams))$funcParams = array();
		if (!is_array($funcParams)) $funcParams = (array)$funcParams;
		$tmp['funcParams'] = $funcParams;
		$tmp['paramsAdd']  = $paramsAdd;

		$n = count($this->fieldFunc[$table]);
		$this->fieldFunc[$table][$n] = & $tmp;
	}


	/* 根据主键取得一行数据
	* $value     主键的值
	* $fieldname 主键名
	*/
	function getDataById($value,$fieldname="id",$table='')
	{
		if (empty($table)) $table = $this->table ;

		$sql = "select * from `$table` where `$fieldname` = '$value'" ;
		$this->dbObj->query($sql);
		$result = $this->dbObj->fetch_row();

		$this->useFieldFunc($result);                                  //对结果应用回调函数

		return $result;
	}

	/* 取得一行数据
	* $sql_where 是sql的where子句
	*/
	function getOneRow($sql_where,$table='')
	{
		if (empty($table)) $table = $this->table ;

		$sql = "select * from ".$table." where ".$sql_where;
		$this->dbObj->query($sql);
		$result = $this->dbObj->fetch_row();

		$this->useFieldFunc($result);

		return $result;
	}

	//应用回调函数
	function useFieldFunc(& $data)
	{
		if (isset($this->fieldFunc[$this->table]) && count($this->fieldFunc[$this->table]) > 0)
		{
			foreach ($this->fieldFunc[$this->table] as $val)
			{
				$fieldName = $val['fieldName'];
				if (isset($data[$fieldName]))
				{
					if ($val['paramsAdd'] == 'left') array_unshift ($val['funcParams'], $data[$fieldName]);
					else array_push($val['funcParams'], $data[$fieldName]) ;
					$data[$fieldName] = call_user_func_array( $val['funcName'],$val['funcParams']);
				}
			}
		}
	}

	/* 根据sql语句取得所有数据,取得的数据放在翻页类PageTurn中
	* $sql       是sql语句
	* $maxnum    每页显示数
	* form_vars  翻页时需要传递的表单字段名(数组)
	* $key       有多个翻页时作为区分标识
	* $parts     总数量大时只取出部分数据
	*/
	function getList($sql,$maxnum=0,$key='',$parts=0)
	{
		$maxnum = intval($maxnum);
		$parts = intval($parts);
		
		if($parts > 0)
		{
			$totalnum = $parts;
		}
		elseif(!isset($_GET['totalnum'.$key]))
		{
			$this->dbObj->query($sql);
			$totalnum = $this->dbObj->num_rows();                     //取得总数
		}
		else 
		{
			$totalnum = $_GET['totalnum'.$key];
		}

		include_once(dirname(__FILE__)."/pageturn.inc.php");          //翻页类

		$objPt = new PageTurn($totalnum,$maxnum,$key);
		
		$newsql = $sql." limit ".$objPt->startnum.", ".$objPt->maxnum;
		$this->dbObj->query($newsql);                                 //执行sql,只取得当前面的数据
		while ($row = $this->dbObj->fetch_row())
		{
			$this->useFieldFunc($row);                                //对结果应用回调函数
			$result[] = $row;
		}

		$this->dbObj->free_result();
		$objPt->field = & $result;                                    //将结果放在$objPt的field属性中,方便统一调用
		return $objPt;
	}
}
?>