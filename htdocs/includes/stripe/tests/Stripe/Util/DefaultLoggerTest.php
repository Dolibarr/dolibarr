<?php

// Test in a slightly different namespace than usual. See comment on
// `error_log` below.
namespace Stripe\Util;

class UtilLoggerTest extends \Stripe\TestCase
{
    public function testDefaultLogger()
    {
        $logger = new DefaultLogger();
        $logger->error("message");

        global $lastMessage;
        $this->assertSame($lastMessage, "message");
    }
}

// This is a little terrible, but unfortunately there's no clean way to stub a
// call to `error_log`. Here we overwrite it so that we can get the last arguments
// that went to it. This is obviously bad, but luckily it's constrained to
// being just in \Stripe\Util (i.e. won't interfere with PHPUnit for example)
// and _just_ present when tests are running.
function error_log($message)
{
    global $lastMessage;
    $lastMessage = $message;
}
