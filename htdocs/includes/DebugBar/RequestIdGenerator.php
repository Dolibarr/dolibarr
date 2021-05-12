<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

/**
 * Request id generator based on the $_SERVER array
 */
class RequestIdGenerator implements RequestIdGeneratorInterface
{
    public function generate()
    {
        return md5(serialize($_SERVER) . microtime());
    }
}
