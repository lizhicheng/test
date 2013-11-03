<?php

/**
* HTML相关类
* @author lizhicheng <li_zhicheng@126.com>
*/
class CHTMLhelper
{

    public static function make_file($name = '', $others = '')
    {
        $file = '<input name="' . $name . '" type="file" ' . $others . ' />';
        
        return $file;
    }

    public static function make_text($name = '', $value = '', $cols = 50, $rows = 5, $others = '')
    {
        $text = '<textarea name="' . $name . '" cols="' . $cols . '" rows="' . $rows . '" ' . $others . '>';
        $text .= $value;
        $text .= '</textarea>';
        
        return $text;
    }

    public static function make_input($name = '', $value = '', $ispassword = false, $others = '')
    {
        $input = '<input type="';
        
        if ($ispassword)
            $input .= 'password" ';
        else
            $input .= 'text" ';
        
        $input .= 'name="' . $name . '" value="' . $value . '" ' . $others . ' />';
        
        return $input;
    }

    public static function make_select($name = '', $values = array(), $txts = array(), $selected = '', $others = '')
    {
        $select = '<select name="' . $name . '" ' . $others . '>';
        $select .= "\n";
        
        $options = self::make_options($values, $txts, $selected);
        
        $select .= $options;
        $select .= '</select>';
        
        return $select;
    }

    public static function make_options($values = array(), $txts = array(), $selected = '')
    {
        $options = '';
        
        for ($i = 0; $i < sizeof($values); $i ++) {
            $options .= '<option value="' . $values[$i] . '"';
            if ($values[$i] == $selected)
                $options .= ' selected';
            $options .= ">" . $txts[$i] . "</option>\n";
        }
        return $options;
    }

    public static function make_radios($name = '', $items = array(), $values = array(), $checked = '', $num = 7)
    {
        $radios = '';
        for ($i = 0; $i < sizeof($values); $i ++) {
            $radios .= '<input name="' . $name . '" type="radio" value="' . $values[$i] . '"';
            if ($values[$i] == $checked)
                $radios .= ' checked';
            $radios .= '>';
            $radios .= $items[$i];
            if (($i + 1) % $num == 0)
                $radios .= '<br />';
        }
        return $radios;
    }

    public static function make_checkbox($name = '', $items = array(), $values = array(), $checked = array(), $num = 7)
    {
        $checkbox = '';
        for ($i = 0; $i < sizeof($values); $i ++) {
            $checkbox .= '<input type="checkbox" name="' . $name . '" value="' . $values[$i] . '"';
            for ($j = 0; $j < sizeof($checked); $j ++)
                if ($checked[$j] == $values[$i])
                    $checkbox .= ' checked';
            $checkbox .= '>';
            $checkbox .= $items[$i];
            if (($i + 1) % $num == 0)
                $checkbox .= '<br />';
        }
        return $checkbox;
    }
}
?>