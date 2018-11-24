<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/theme/md/graph-color.php
 *	\brief      File to declare colors to use to build graphics with theme Material Design
 *  \ingroup    core
 *
 *  To include file, do this:
 *              $color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/graph-color.php';
 *              if (is_readable($color_file)) include_once $color_file;
 */

require_once DOL_DOCUMENT_ROOT . '/includes/ariColor/aricolor.php';

global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;

$colornames = array(
    'aliceblue',
    'antiquewhite',
    'aqua',
    'aquamarine',
    'azure',
    'beige',
    'bisque',
    'black',
    'blanchedalmond',
    'blue',
    'blueviolet',
    'brown',
    'burlywood',
    'cadetblue',
    'chartreuse',
    'chocolate',
    'coral',
    'cornflowerblue',
    'cornsilk',
    'crimson',
    'cyan',
    'darkblue',
    'darkcyan',
    'darkgoldenrod',
    'darkgray',
    'darkgreen',
    'darkgrey',
    'darkkhaki',
    'darkmagenta',
    'darkolivegreen',
    'darkorange',
    'darkorchid',
    'darkred',
    'darksalmon',
    'darkseagreen',
    'darkslateblue',
    'darkslategray',
    'darkslategrey',
    'darkturquoise',
    'darkviolet',
    'deeppink',
    'deepskyblue',
    'dimgray',
    'dimgrey',
    'dodgerblue',
    'firebrick',
    'floralwhite',
    'forestgreen',
    'fuchsia',
    'gainsboro',
    'ghostwhite',
    'gold',
    'goldenrod',
    'gray',
    'green',
    'greenyellow',
    'grey',
    'honeydew',
    'hotpink',
    'indianred',
    'indigo',
    'ivory',
    'khaki',
    'lavender',
    'lavenderblush',
    'lawngreen',
    'lemonchiffon',
    'lightblue',
    'lightcoral',
    'lightcyan',
    'lightgoldenrodyellow',
    'lightgray',
    'lightgreen',
    'lightgrey',
    'lightpink',
    'lightsalmon',
    'lightseagreen',
    'lightskyblue',
    'lightslategray',
    'lightslategrey',
    'lightsteelblue',
    'lightyellow',
    'lime',
    'limegreen',
    'linen',
    'magenta',
    'maroon',
    'mediumaquamarine',
    'mediumblue',
    'mediumorchid',
    'mediumpurple',
    'mediumseagreen',
    'mediumslateblue',
    'mediumspringgreen',
    'mediumturquoise',
    'mediumvioletred',
    'midnightblue',
    'mintcream',
    'mistyrose',
    'moccasin',
    'navajowhite',
    'navy',
    'oldlace',
    'olive',
    'olivedrab',
    'orange',
    'orangered',
    'orchid',
    'palegoldenrod',
    'palegreen',
    'paleturquoise',
    'palevioletred',
    'papayawhip',
    'peachpuff',
    'peru',
    'pink',
    'plum',
    'powderblue',
    'purple',
    'red',
    'rosybrown',
    'royalblue',
    'saddlebrown',
    'salmon',
    'sandybrown',
    'seagreen',
    'seashell',
    'sienna',
    'silver',
    'skyblue',
    'slateblue',
    'slategray',
    'slategrey',
    'snow',
    'springgreen',
    'steelblue',
    'tan',
    'teal',
    'thistle',
    'tomato',
    'turquoise',
    'violet',
    'wheat',
    'white',
    'whitesmoke',
    'yellow',
    'yellowgreen',
);

$theme_datacolornames = array(
    'dimgray',
    'teal',
    'mediumpurple',
    'rosybrown',
    'darkkhaki',
    'slategray',
    'olivedrab',
    'goldenrod',
    'darkseagreen',
    'lightslategray',
    'peru',
    'darkorchid',
    'olivedrab',
    'dimgray',
    'olivedrab',
    'darkolivegreen',
    'darkslateblue',
    'seagreen',
    'darkslategray',
    'darkslateblue',
    'darkgoldenrod',
    'darkolivegreen',
    'brown',
);
$theme_bordercolor = array(235, 235, 224);
foreach ($theme_datacolornames as $colorname) {
    $color = aricolor::newColor($colorname, 'hex');
    $theme_datacolor[] = array($color->red, $color->green, $color->blue);
}
// $test_datacolor = array(
//     array(136, 102, 136),
//     array(0, 130, 110),
//     array(140, 140, 220),
//     array(190, 120, 120),
//     array(190, 190, 100),
//     array(115, 125, 150),
//     array(100, 170, 20),
//     array(250, 190, 30),
//     array(150, 135, 125),
//     array(80, 135, 155),
//     array(150, 135, 80),
//     array(150, 80, 150),
//     array(115, 135, 80),
//     array(85, 80, 115),
//     array(115, 135, 80),
//     array(85, 80, 40),
//     array(45, 80, 145),
//     array(75, 135, 80),
//     array(85, 80, 75),
//     array(85, 45, 115),
//     array(175, 135, 80),
//     array(85, 25, 40),
// );
// foreach ($test_datacolor as $test) {
//     $ecart = 200;
//     $choice = 'white';
//     $oldchoice = 'black';
//     foreach ($colornames as $colorname) {
//         $color = aricolor::newColor($colorname, 'hex');
//         $theme_datacolor[] = array($color->red, $color->green, $color->blue);
//         $newecart = abs($test[0]-$color->red) *2  + abs($test[1]-$color->green)*2 + abs($test[2]-$color->blue) *2;
//         if ($newecart < $ecart) {
//             $oldchoice = $choice;
//             $choixcolor = $color;
//             $choice = $colorname;
//             $oldecart = $ecart;
//             $ecart = $newecart;
//         }
//     }
//     var_dump($test);
//     var_dump($choixcolor);
//     var_dump($choice);
//     var_dump($ecart);
//     var_dump($oldchoice);
//     var_dump($oldecart);
// }
$theme_bgcolor = array(hexdec('F4'),hexdec('F4'),hexdec('F4'));
$theme_bgcoloronglet = array(hexdec('DE'),hexdec('E7'),hexdec('EC'));

