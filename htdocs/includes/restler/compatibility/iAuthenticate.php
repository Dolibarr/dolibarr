<?php

/**
 * Interface iAuthenticate only exists for compatibility mode for Restler 2 and below, it should
 * not be used otherwise.
 */
interface iAuthenticate
{
    public function __isAuthenticated();
}