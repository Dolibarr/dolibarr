<?php
/*
* File: NestesEmbeddedWithAttachmentTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Support\AttachmentCollection;

/**
 * Class NestesEmbeddedWithAttachmentTest
 *
 * @package Tests\fixtures
 */
class NestesEmbeddedWithAttachmentTest extends FixtureTestCase {

    /**
     * Test the fixture nestes_embedded_with_attachment.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("nestes_embedded_with_attachment.eml");

        self::assertEquals("Nuu", $message->subject);
        self::assertEquals("Dear Sarah", $message->getTextBody());
        self::assertEquals("<HTML><HEAD></HEAD>\r\n<BODY dir=ltr>\r\n<DIV>Dear Sarah,</DIV>\r\n</BODY></HTML>", $message->getHTMLBody());

        self::assertEquals("2017-09-13 11:05:45", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from->first()->mail);
        self::assertEquals("to@here.com", $message->to->first()->mail);

        $attachments = $message->attachments();
        self::assertInstanceOf(AttachmentCollection::class, $attachments);
        self::assertCount(2, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("first.eml", $attachment->name);
        self::assertEquals('eml', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("message/rfc822", $attachment->content_type);
        self::assertEquals("From: from@there.com\r\nTo: to@here.com\r\nSubject: FIRST\r\nDate: Sat, 28 Apr 2018 14:37:16 -0400\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed;\r\n	boundary=\"----=_NextPart_000_222_000\"\r\n\r\nThis is a multi-part message in MIME format.\r\n\r\n------=_NextPart_000_222_000\r\nContent-Type: multipart/alternative;\r\n	boundary=\"----=_NextPart_000_222_111\"\r\n\r\n\r\n------=_NextPart_000_222_111\r\nContent-Type: text/plain;\r\n	charset=\"UTF-8\"\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nPlease respond directly to this email to update your RMA\r\n\r\n\r\n2018-04-17T11:04:03-04:00\r\n------=_NextPart_000_222_111\r\nContent-Type: text/html;\r\n	charset=\"UTF-8\"\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n<HTML><HEAD></HEAD>\r\n<BODY dir=3Dltr>\r\n<DIV>Please respond directly to this =\r\nemail to=20\r\nupdate your RMA</DIV></BODY></HTML>\r\n\r\n------=_NextPart_000_222_111--\r\n\r\n------=_NextPart_000_222_000\r\nContent-Type: image/png;\r\n	name=\"chrome.png\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment;\r\n	filename=\"chrome.png\"\r\n\r\niVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAMAAADXqc3KAAAB+FBMVEUAAAA/mUPidDHiLi5Cn0Xk\r\nNTPmeUrkdUg/m0Q0pEfcpSbwaVdKskg+lUP4zA/iLi3msSHkOjVAmETdJSjtYFE/lkPnRj3sWUs8\r\nkkLeqCVIq0fxvhXqUkbVmSjwa1n1yBLepyX1xxP0xRXqUkboST9KukpHpUbuvRrzrhF/ljbwalju\r\nZFM4jELaoSdLtElJrUj1xxP6zwzfqSU4i0HYnydMtUlIqUfywxb60AxZqEXaoifgMCXptR9MtklH\r\npEY2iUHWnSjvvRr70QujkC+pUC/90glMuEnlOjVMt0j70QriLS1LtEnnRj3qUUXfIidOjsxAhcZF\r\no0bjNDH0xxNLr0dIrUdmntVTkMoyfL8jcLBRuErhJyrgKyb4zA/5zg3tYFBBmUTmQTnhMinruBzv\r\nvhnxwxZ/st+Ktt5zp9hqota2vtK6y9FemNBblc9HiMiTtMbFtsM6gcPV2r6dwroseLrMrbQrdLGd\r\nyKoobKbo3Zh+ynrgVllZulTsXE3rV0pIqUf42UVUo0JyjEHoS0HmsiHRGR/lmRz/1hjqnxjvpRWf\r\nwtOhusaz0LRGf7FEfbDVmqHXlJeW0pbXq5bec3fX0nTnzmuJuWvhoFFhm0FtrziBsjaAaDCYWC+u\r\nSi6jQS3FsSfLJiTirCOkuCG1KiG+wSC+GBvgyhTszQ64Z77KAAAARXRSTlMAIQRDLyUgCwsE6ebm\r\n5ubg2dLR0byXl4FDQzU1NDEuLSUgC+vr6urq6ubb29vb2tra2tG8vLu7u7uXl5eXgYGBgYGBLiUA\r\nLabIAAABsElEQVQoz12S9VPjQBxHt8VaOA6HE+AOzv1wd7pJk5I2adpCC7RUcHd3d3fXf5PvLkxh\r\neD++z+yb7GSRlwD/+Hj/APQCZWxM5M+goF+RMbHK594v+tPoiN1uHxkt+xzt9+R9wnRTZZQpXQ0T\r\n5uP1IQxToyOAZiQu5HEpjeA4SWIoksRxNiGC1tRZJ4LNxgHgnU5nJZBDvuDdl8lzQRBsQ+s9PZt7\r\ns7Pz8wsL39/DkIfZ4xlB2Gqsq62ta9oxVlVrNZpihFRpGO9fzQw1ms0NDWZz07iGkJmIFH8xxkc3\r\na/WWlubmFkv9AB2SEpDvKxbjidN2faseaNV3zoHXvv7wMODJdkOHAegweAfFPx4G67KluxzottCU\r\n9n8CUqXzcIQdXOytAHqXxomvykhEKN9EFutG22p//0rbNvHVxiJywa8yS2KDfV1dfbu31H8jF1RH\r\niTKtWYeHxUvq3bn0pyjCRaiRU6aDO+gb3aEfEeVNsDgm8zzLy9egPa7Qt8TSJdwhjplk06HH43ZN\r\nJ3s91KKCHQ5x4sw1fRGYDZ0n1L4FKb9/BP5JLYxToheoFCVxz57PPS8UhhEpLBVeAAAAAElFTkSu\r\nQmCC\r\n\r\n------=_NextPart_000_222_000--", $attachment->content);
        self::assertEquals(2535, $attachment->size);
        self::assertEquals(3, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[1];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("second.eml", $attachment->name);
        self::assertEquals('eml', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("message/rfc822", $attachment->content_type);
        self::assertEquals("From: from@there.com\r\nTo: to@here.com\r\nSubject: SECOND\r\nDate: Sat, 28 Apr 2018 13:37:30 -0400\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative;\r\n	boundary=\"----=_NextPart_000_333_000\"\r\n\r\nThis is a multi-part message in MIME format.\r\n\r\n------=_NextPart_000_333_000\r\nContent-Type: text/plain;\r\n	charset=\"UTF-8\"\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nT whom it may concern:\r\n------=_NextPart_000_333_000\r\nContent-Type: text/html;\r\n	charset=\"UTF-8\"\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n<HTML><HEAD></HEAD>\r\n<BODY dir=3Dltr>\r\n<DIV>T whom it may concern:</DIV>\r\n</BODY></HTML>\r\n\r\n------=_NextPart_000_333_000--", $attachment->content);
        self::assertEquals(631, $attachment->size);
        self::assertEquals(4, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}