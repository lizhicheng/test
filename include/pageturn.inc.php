<?
/*
------------------------------------------------------------------------------------
类名:PAGETURN
说明:PHP+MySQL分页类
作者:龙卫国
网络user:lwg888
邮箱:lwg888@163.com
使用、修改、传播请保留作者信息
------------------------------------------------------------------------------------
*/

if(!defined("__CLASS_PAGETURN__"))
{
	define("__CLASS_PAGETURN__",1);

	class PageTurn
	{
		var $maxnum;                 //每页显示数
		var $navchar;
		//导航条的显示字符，值可以自定义，如一个img标签
		//$navchar[0]表示第一页，$navchar[1]表示前一页，$navchar[2]表示后一页，$navchar[3]表示最后页

		var $key;                    //如果一个页面中有多个分页时作为区别标记

		var $totalnum;               //总记录数
		var $totalpage;              //总页数
		var $startnum;               //本页的第一条在总数中的序数
		var $endnum;                 //本页的最后一条在总数中的序数
		var $pagenum;                //本页在总页数中的序数
		var $shownum;                //本页实际显示数
		var $field;                  //结果记录的集合
		var $linkhead;               //链接指定的url及要传递的相关参数
		var $form_vars = array();

		function PageTurn($totalnum='', $maxnum='',$key='',$form_vars = '')
		{
		    $this->navchar = Li::lang('navchar');

			$this->totalnum = $totalnum;
			$this->maxnum   = $maxnum;
			$this->key      = $key;

			if (!empty($form_vars))
			{
				if (!is_array($form_vars)) $form_vars = (array)$form_vars;
				$this->form_vars = $form_vars;
			}

			$ifpost=false;           //是否有$_POST变量,如果有的话,则在翻页时只传递其值,其它的一律省略

			if (sizeof($this->form_vars)>0)
			{
				$formlink = "";

				foreach ($this->form_vars as $val)
				{
					if (isset($_POST[$val])) $formlink.= $val."=".urlencode($_POST[$val])."&";
				}

				if ($formlink != "")
				{
					$ifpost=true;
					$querystring = $formlink;
				}
			}
			else  if (count($_GET) > 0)                  //如果没有$_POST变量,则将$_GET变量分析后作为翻页时传递的参数
			{
				$querystring = '';
				foreach ($_GET as $key => $val)
				{
					if ($key != "totalnum".$this->key && $key != "pagenum".$this->key)$querystring .= $key."=".urlencode($val)."&";
				}
			}

			if (isset($_GET["maxnum".$this->key]) && $_GET["maxnum".$this->key] > 0)
			{
				$this->maxnum = sprintf('%d',$_GET["maxnum".$this->key]);
			}

			if ($this->maxnum < 1 ) $this->maxnum = $this->totalnum;

			if ($this->totalnum < 1)
			{
				$this->totalnum  = 0 ;
				$this->totalpage = 0 ;
				$this->pagenum   = 0 ;
				$this->startnum  = 0 ;
				$this->endnum    = 0 ;
				$this->shownum   = 0 ;
			}
			else
			{
				$this->totalpage = ceil($this->totalnum/$this->maxnum);

				$this->pagenum   = (isset($_GET["pagenum".$this->key]) && $_GET["pagenum".$this->key]>0 && !$ifpost)
				? sprintf('%d',$_GET["pagenum".$this->key])
				: 1;

				if ($this->pagenum > $this->totalpage) $this->pagenum = $this->totalpage;

				$this->startnum = max(($this->pagenum - 1) * $this->maxnum,0);
				$this->endnum   = min($this->startnum + $this->maxnum, $this->totalnum);
				$this->shownum  = $this->endnum - $this->startnum;
			}

			$querystring .= "totalnum" . $this->key . "=" . $this->totalnum;

			if (isset($_GET["maxnum" . $this->key])) $querystring .= "&maxnum" . $this->key . "=" . $this->maxnum;
			$this->linkhead = $_SERVER['PHP_SELF'] . "?" . $querystring;
		}

		//显示如"共14页27条"
		function total()
		{
			return $this->getSysMsg('5007',$this->totalpage)." ".$this->getSysMsg('5008',$this->totalnum)." ";
		}

		//显示如"本页从第9条到第10条"
		function fromto()
		{
			$startnum = $this->startnum + 1;
			if ($this->totalnum==0)$startnum = 0;

			return $this->getSysMsg('5009',$startnum)." ".$this->getSysMsg('5010',$this->endnum)." ";
		}

		//navbar方法显示页数导航条
		//$num_size表示多少个导航数字,如$num_size=5则显示" 1 2 3 4 5 "
		//$nolink_show没有链接的导航字符是否显示，true显示，false不显示
		//$nolink_color没有链接的导航字符显示的颜色
		function navbar($num_size=10,$nolink_show=false,$nolink_color="#ff0000")
		{
			if ($this->totalpage <= 1) return;

			$str_first = $str_pre = $str_frontell = $str_num = $str_backell = $str_next = $str_last = '';
			$spacer = '';

			if ($num_size>0)
			{
				$tmpnum    = floor($num_size/2);
				$startpage = max(min($this->pagenum - $tmpnum, $this->totalpage - $num_size + 1), 1);
				$endpage   = min($startpage + $num_size - 1, $this->totalpage);

				if ($startpage > 1)              $str_frontell = "<a class=\"dott\">…</a>" . $spacer;

				if ($endpage < $this->totalpage) $str_backell  = "<a class=\"dott\">…</a>" . $spacer;

				$str_num = "";

				for ($i = $startpage; $i <= $endpage; $i++)
				{
					if ($i == $this->pagenum) $str_num .= "<a class=\"nowpage\"><font color=\"".$nolink_color."\">".$i."</font></a>" . $spacer;
					else                      $str_num .= "<a href=\"".$this->linkhead."&pagenum".$this->key."=".$i."\" title=\"".$this->getSysMsg('5005',$i)."\">".$i."</a>" . $spacer;
				}
			}

			if ($this->pagenum > 1)
			{
				$str_first = "<a class=\"dott\" href=\"".$this->linkhead."&pagenum".$this->key."=1\" title=\"".$this->getSysMsg('5001')."\">".$this->navchar[0]."</a>" . $spacer;
				$str_pre   = "<a class=\"dott\" href=\"".$this->linkhead."&pagenum".$this->key."=".($this->pagenum-1)."\" title=\"".$this->getSysMsg('5002')."\">".$this->navchar[1]."</a>" . $spacer;
			}
			else if ($nolink_show)
			{
				$str_first = "<a class=\"dott\"><font color=\"".$nolink_color."\">".$this->navchar[0]."</font></a>" . $spacer;
				$str_pre   = "<a class=\"dott\"><font color=\"".$nolink_color."\">".$this->navchar[1]."</font></a>" . $spacer;
			}

			if ($this->pagenum<$this->totalpage)
			{
				$str_next  = "<a class=\"dott\" href=\"".$this->linkhead."&pagenum".$this->key."=".($this->pagenum+1)."\" title=\"".$this->getSysMsg('5003')."\">".$this->navchar[2]."</a>" . $spacer;
				$str_last  = "<a class=\"dott\" href=\"".$this->linkhead."&pagenum".$this->key."=".$this->totalpage."\" title=\"".$this->getSysMsg('5004')."\">".$this->navchar[3]."</a>" . $spacer;
			}
			else if ($nolink_show)
			{
				$str_next  ="<a class=\"dott\"><font color=\"".$nolink_color."\">".$this->navchar[2]."</font></a>" . $spacer;
				$str_last  ="<a class=\"dott\"><font color=\"".$nolink_color."\">".$this->navchar[3]."</font></a>" . $spacer;
			}
			//$str_total = "<a class=\"dott\">".$this->totalpage.$this->navchar[4]."/".$this->totalnum.$this->navchar[5]."</a>";
			return "<div class=\"pagebar\">".$str_first.$str_pre.$str_frontell.$str_num.$str_backell.$str_next.$str_last."</div>";
		}

		//用下拉列表显示如"到第n页\共m页"
		function pagejump($class = '')
		{
			if ($this->totalpage <= 1) return;

			$name  = "pagenum".$this->key;

			$write ="<select name='".$name."' ";

			if (!empty($class)) $write .= "class='".$class."' ";

			$write .= "onchange='javascript:location.href=this.options[this.selectedIndex].value'>";

			for ($i = 1; $i <= $this->totalpage; $i++)
			{
				$write .= "<option value=".$this->linkhead."&".$name."=".$i;

				if ($this->pagenum == $i) $write .= " selected";

				$write .= ">".$i."</option>";
			}

			$write .= "</select>";

			return $this->getSysMsg('5006',$write)."/".$this->getSysMsg('5007',$this->totalpage)." ";
		}

		//显示如"每页显示n条 "
		function maxnum()
		{
			return $this->getSysMsg('5011',$this->maxnum)." ";
		}

		function getSysMsg($num,$otherMsg='')
		{
			global $system_msg;
			$msg = sprintf($system_msg[$num],$otherMsg) ;
			return $msg;
		}

	} //end class

}//end if defined

?>