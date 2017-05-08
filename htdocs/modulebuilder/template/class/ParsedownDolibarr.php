<?php
/*
 * Copyright (C) 2016  Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/ParsedownDolibarr.php
 * \ingroup mymodule
 * \brief   Custom Parsedown class to display inline images from the README.md in the about page
 */

/** Includes */
require __DIR__ . '/../vendor/autoload.php';

/**
 * Class ParsedownDolibarr
 */
class ParsedownDolibarr extends Parsedown
{
    /**
     * Resolve inline images relative URLs to the module's path
     *
     * @param $Excerpt
     * @return array|void
     */
    protected function inlineImage($Excerpt)
    {
        $image = parent::inlineImage($Excerpt);
        $path = new \Enrise\Uri($image['element']['attributes']['src']);
        if ($path->isRelative()) {
            $image['element']['attributes']['src'] = dol_buildpath('/mymodule/' . $path, 1);
        }
        return $image;
    }
}
