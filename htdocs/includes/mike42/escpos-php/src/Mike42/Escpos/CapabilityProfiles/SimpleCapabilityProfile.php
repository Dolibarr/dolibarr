<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */
namespace Mike42\Escpos\CapabilityProfiles;

use Mike42\Escpos\CapabilityProfile;

class SimpleCapabilityProfile
{
    public static function getInstance()
    {
        return CapabilityProfile::load('simple');
    }
}
