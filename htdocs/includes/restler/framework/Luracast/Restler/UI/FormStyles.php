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
 * @version    3.0.0rc5
 */
class FormStyles
{
    public static $html = array(
        'form' => 'form[role=form id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
        'input' => '.row>section>label{$label#}^input[name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept#]',
        'textarea' => '.row>label{$label#}^textarea[name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3]{$value#}',
        'radio' => '.row>section>label{$label#}^span>label*options>input[name=$name# value=$value# type=radio checked=$selected# required=$required#]+{ $text#}',
        'select' => '.row>label{$label#}^select[name=$name# required=$required#]>option[value]+option[value=$value# selected=$selected#]{$text#}*options',
        'submit' => '.row>label{ &nbsp; }^button[type=submit]{$label#}',
        'fieldset' => 'fieldset>legend{$label#}',
        'checkbox' => '.row>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept#]+{$label#}',
        //------------- TYPE BASED STYLES ---------------------//
        'checkbox-array' => 'fieldset>legend{$label#}+section*options>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept#]+{ $text#}',
        'select-array' => 'label{$label#}+select[name=$name# required=$required# multiple style="height: auto;background-image: none; outline: inherit;"]>option[value=$value# selected=$selected#]{$text#}*options',
    );
    public static $bootstrap3 = array(
        'form' => 'form[role=form id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
        'input' => '.form-group>label{$label#}+input.form-control[name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept#]',
        'textarea' => '.form-group>label{$label#}+textarea.form-control[name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3]{$value#}',
        'radio' => 'fieldset>legend{$label#}>.radio*options>label>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required#]{$text#}',
        'select' => '.form-group>label{$label#}+select.form-control[name=$name# multiple=$multiple# required=$required#]>option[value]+option[value=$value# selected=$selected#]{$text#}*options',
        'submit' => 'button.btn.btn-primary[type=submit]{$label#}',
        'fieldset' => 'fieldset>legend{$label#}',
        'checkbox' => '.checkbox>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept#]+{$label#}',
        //------------- TYPE BASED STYLES ---------------------//
        'checkbox-array' => 'fieldset>legend{$label#}>.checkbox*options>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required#]{$text#}',
        'select-array' => '.form-group>label{$label#}+select.form-control[name=$name# multiple=$multiple# required=$required#] size=$options#>option[value=$value# selected=$selected#]{$text#}*options',
        //------------- CUSTOM STYLES ---------------------//
        'radio-inline' => '.form-group>label{$label# : &nbsp;}+label.radio-inline*options>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required#]+{$text#}',
    );
    public static $foundation5 = array(
        'form' => 'form[id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
        'input' => 'label{$label#}+input[name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept#]',
        'textarea' => 'label{$label#}+textarea[name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3]{$value#}',
        'radio' => 'label{$label# : &nbsp;}+label.radio-inline*options>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required#]+{$text#}',
        'select' => 'label{$label#}+select[name=$name# required=$required#]>option[value]+option[value=$value# selected=$selected#]{$text#}*options',
        'submit' => 'button.button[type=submit]{$label#}',
        'fieldset' => 'fieldset>legend{$label#}',
        'checkbox' => 'label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept#]+{ $label#}',
        //------------- TYPE BASED STYLES ---------------------//
        'checkbox-array' => 'fieldset>legend{$label#}+label*options>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept#]+{ $text#}',
        'select-array' => 'label{$label#}+select[name=$name# required=$required# multiple style="height: auto;background-image: none; outline: inherit;"]>option[value=$value# selected=$selected#]{$text#}*options',
        //------------- CUSTOM STYLES ---------------------//
    );
}