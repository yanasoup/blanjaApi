<?php

function print_out($data = array(),$continue = true) {

    echo '<pre style="background: #f0f0f0; border: 2px dashed #d0d0d0; width: 94%; margin:3%; padding:10px; overflow: auto;">'.print_r($data,1).'</pre>';

    if (!$continue) {
        die;
    }
}

function dropdownrowsobj($name,$rows,$valKey, $labelKey, $selected = -1,$cssClass = '',$defaultText = '',$customCss='',$defaultTextVal = 'All') {
    $html = '<select name="'.$name.'" id="'.$name.'" class="'.$cssClass.'" '.$customCss.'>';
    $html .= $defaultText != '' ? '<option value="'.$defaultTextVal.'" selected>'.$defaultText.'</option>' : '';
    foreach($rows as $r) {
        $sorot = $r->$valKey   == $selected ? 'selected ' : '';
        $html .= '<option value="'.$r->$valKey.'" '.$sorot.'>'.$r->$labelKey.'</option>';
    }
    $html .= '</select>';

    return $html;
}

function jieunDD($name,$options=array(), $terpilih=-1,$class='',$style='width: 150px;',$add_all_opt = false,$add_all_text = '-- ALL --',$attr = '')
{
    $html="";
    $html = '<select class="'.$class.'" style="'.$style.'" name="'.$name.'" id="'.$name.'" '.$attr.'>'."\n";
    $selected = $terpilih == -1 ? ' selected="selected" ' : ' ';
    if ($add_all_opt)
        $html .= "<option ".$selected."  value='-1'>".$add_all_text."</option>\n";
    foreach($options as $val => $label)
    {
        $selected = $terpilih == $val ? ' selected="selected" ' : ' ';
        $html .= "<option ".$selected."  value='" . $val . "'>" . $label . "</option>\n";
    }
    $html .= '</select>';
    return $html;
}

function jieunDDwithId($name,$id,$options=array(), $terpilih=-1,$class='',$style='width: 150px;',$add_all_opt = false,$add_all_text = '-- ALL --',$attr = '')
{
    $html="";
    $html = '<select class="'.$class.'" style="'.$style.'" name="'.$name.'" id="'.$id.'" '.$attr.'>'."\n";
    $selected = $terpilih == -1 ? ' selected="selected" ' : ' ';
    if ($add_all_opt)
        $html .= "<option ".$selected."  value='-1'>".$add_all_text."</option>\n";
    foreach($options as $val => $label)
    {
        $selected = $terpilih == $val ? ' selected="selected" ' : ' ';
        $html .= "<option ".$selected."  value='" . $val . "'>" . $label . "</option>\n";
    }
    $html .= '</select>';
    return $html;
}

//function defination to convert array to xml
function array_to_xml($array, &$xml) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml->addChild("$key");
                array_to_xml($value, $subnode);
            }else{
                $subnode = $xml->addChild("item$key");
                array_to_xml($value, $subnode);
            }
        }else {
            $xml->addChild("$key",htmlspecialchars("$value"));
        }
    }
}