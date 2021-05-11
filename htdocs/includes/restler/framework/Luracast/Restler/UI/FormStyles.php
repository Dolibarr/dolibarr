<?php
namespace Luracast\Restler\UI;

/**
 * Utility class for providing preset styles for html forms
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class FormStyles
{
    public static $html = array(
        'form' => 'form[role=form id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
        'input' => '.row>section>label{$label#}^input[id=$id# name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept# disabled=$disabled#]',
        'textarea' => '.row>label{$label#}^textarea[id=$id# name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3 disabled=$disabled#]{$value#}',
        'radio' => '.row>section>label{$label#}^span>label*options>input[id=$id# name=$name# value=$value# type=radio checked=$selected# required=$required# disabled=$disabled#]+{ $text#}',
        'select' => '.row>label{$label#}^select[id=$id# name=$name# required=$required#]>option[value]+option[value=$value# selected=$selected# disabled=$disabled#]{$text#}*options',
        'submit' => '.row>label{ &nbsp; }^button[id=$id# type=submit disabled=$disabled#]{$label#}',
        'fieldset' => 'fieldset>legend{$label#}',
        'checkbox' => '.row>label>input[id=$id# name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept# disabled=$disabled#]+{$label#}',
        //------------- TYPE BASED STYLES ---------------------//
        'checkbox-array' => 'fieldset>legend{$label#}+section*options>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept#]+{ $text#}',
        'select-array' => 'label{$label#}+select[name=$name# required=$required# multiple style="height: auto;background-image: none; outline: inherit;"]>option[value=$value# selected=$selected#]{$text#}*options',
    );
    public static $bootstrap3 = array(
        'form' => 'form[role=form id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
        'input' => '.form-group.$error#>label{$label#}+input.form-control[id=$id# name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept# disabled=$disabled#]+small.help-block>{$message#}',
        'textarea' => '.form-group>label{$label#}+textarea.form-control[id=$id# name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3 disabled=$disabled#]{$value#}+small.help-block>{$message#}',
        'radio' => 'fieldset>legend{$label#}>.radio*options>label>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required# disabled=$disabled#]{$text#}+p.help-block>{$message#}',
        'select' => '.form-group>label{$label#}+select.form-control[id=$id# name=$name# multiple=$multiple# required=$required#]>option[value]+option[value=$value# selected=$selected# disabled=$disabled#]{$text#}*options',
        'submit' => 'button.btn.btn-primary[id=$id# type=submit]{$label#} disabled=$disabled#',
        'fieldset' => 'fieldset>legend{$label#}',
        'checkbox' => '.checkbox>label>input[id=$id# name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# disabled=$disabled#]+{$label#}^p.help-block>{$error#}',
        //------------- TYPE BASED STYLES ---------------------//
        'checkbox-array' => 'fieldset>legend{$label#}>.checkbox*options>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required#]{$text#}',
        'select-array' => '.form-group>label{$label#}+select.form-control[name=$name# multiple=$multiple# required=$required#] size=$options#>option[value=$value# selected=$selected#]{$text#}*options',
        //------------- CUSTOM STYLES ---------------------//
        'radio-inline' => '.form-group>label{$label# : &nbsp;}+label.radio-inline*options>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required#]+{$text#}',
    );
    public static $foundation5 = array(
        'form' => 'form[id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
        'input' => 'label{$label#}+input[id=$id# name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept# disabled=$disabled#]',
        'textarea' => 'label{$label#}+textarea[id=$id# name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3 disabled=$disabled#]{$value#}',
        'radio' => 'label{$label# : &nbsp;}+label.radio-inline*options>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required# disabled=$disabled#]+{$text#}',
        'select' => 'label{$label#}+select[id=$id# name=$name# required=$required#]>option[value]+option[value=$value# selected=$selected# disabled=$disabled#]{$text#}*options',
        'submit' => 'button.button[id=$id# type=submit disabled=$disabled#]{$label#}',
        'fieldset' => 'fieldset>legend{$label#}',
        'checkbox' => 'label>input[id=$id# name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# disabled=$disabled#]+{ $label#}',
        //------------- TYPE BASED STYLES ---------------------//
        'checkbox-array' => 'fieldset>legend{$label#}+label*options>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus#]+{ $text#}',
        'select-array' => 'label{$label#}+select[name=$name# required=$required# multiple style="height: auto;background-image: none; outline: inherit;"]>option[value=$value# selected=$selected#]{$text#}*options',
        //------------- CUSTOM STYLES ---------------------//
    );
}