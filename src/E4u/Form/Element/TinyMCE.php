<?php
namespace E4u\Form\Element;

/**
 * @deprecated use Form\Builder instead
 */
class TinyMCE extends TextArea
{
    const MODE_SIMPLE = 'simple';
    const MODE_ADVANCED = 'advanced';

    protected $cssClass = 'text_area';
    protected $mode = self::MODE_ADVANCED;
    
    public function setMode($mode)
    {
        if (constant('self::MODE_'.  strtoupper($mode))) {
            $this->mode = $mode;
        }
        
        return $this;
    }
    
    /**
     * <textarea type="text" name="login[login]" id="login_login">xxx</textarea>
     * and tinyMCE.init() function to convert it mce Editor
     * 
     * You need to include tiny_mce.js script somewhere in <head>...</head>:
     * <script type="text/javascript" src="<?= $this->urlTo('tinymce/tiny_mce.js') ?>"></script>
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $this->setClass($this->getClass() . 'mceEditor');
        $html = parent::render($formName);
        
        
        // http://www.tinymce.com/wiki.php/Configuration
        $script = '
        <script type="text/javascript">
        tinyMCE.init({
                mode : "exact",
                theme : "'.$this->mode.'",
                language : "pl",
                elements : "'.$this->htmlId($formName).'",
                theme_advanced_toolbar_location : "top",
                theme_advanced_buttons1 : "bold,italic,underline,strikethrough,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent,cut,copy,paste,undo,redo,link,unlink,image,cleanup,help,code,hr,removeformat,formatselect,fontselect,fontsizeselect,styleselect,sub,sup,forecolor,backcolor,forecolorpicker,backcolorpicker,charmap,visualaid,anchor,newdocument,blockquote,separator",
                theme_advanced_buttons2 : "",
                theme_advanced_buttons3 : ""
        });
        </script>';
    
        /*
         * http://www.tinymce.com/wiki.php/Compressors:PHP
        require_once("public/tinymce/tiny_mce_gzip.php");
        TinyMCE_Compressor::renderTag([
            "url" => $this->urlTo("tinymce/tiny_mce_gzip.php"),
            "theme" => "advanced",
            "mode" => "specific_textareas",
            "cache_dir"  => "public/tinymce/cache",
            "editor_selector" => "mceEditor",
        ]); */
        return $html.$script;
    }
}