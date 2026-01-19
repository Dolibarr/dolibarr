<?php
/*
* File: MultipartWithoutBodyTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

/**
 * Class MultipartWithoutBodyTest
 *
 * @package Tests\fixtures
 */
class MultipartWithoutBodyTest extends FixtureTestCase {

    /**
     * Test the fixture multipart_without_body.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("multipart_without_body.eml");

        self::assertEquals("This mail will not contain a body", $message->subject);
        self::assertEquals("This mail will not contain a body", $message->getTextBody());
        self::assertEquals("d76dfb1ff3231e3efe1675c971ce73f722b906cc049d328db0d255f8d3f65568", hash("sha256", $message->getHTMLBody()));
        self::assertEquals("2023-03-11 08:24:31", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("Foo Bülow Bar <from@example.com>", $message->from);
        self::assertEquals("some one <someone@somewhere.com>", $message->to);
        self::assertEquals([
            0 => 'from AS8PR02MB6805.eurprd02.prod.outlook.com (2603:10a6:20b:252::8) by PA4PR02MB7071.eurprd02.prod.outlook.com with HTTPS; Sat, 11 Mar 2023 08:24:33 +0000',
            1 => 'from omef0ahNgeoJu.eurprd02.prod.outlook.com (2603:10a6:10:33c::12) by AS8PR02MB6805.eurprd02.prod.outlook.com (2603:10a6:20b:252::8) with Microsoft SMTP Server (version=TLS1_2, cipher=TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384) id 15.20.6178.19; Sat, 11 Mar 2023 08:24:31 +0000',
            2 => 'from omef0ahNgeoJu.eurprd02.prod.outlook.com ([fe80::38c0:9c40:7fc6:93a7]) by omef0ahNgeoJu.eurprd02.prod.outlook.com ([fe80::38c0:9c40:7fc6:93a7%7]) with mapi id 15.20.6178.019; Sat, 11 Mar 2023 08:24:31 +0000',
        ], $message->received->all());
        self::assertEquals("This mail will not contain a body", $message->thread_topic);
        self::assertEquals("AdlT8uVmpHPvImbCRM6E9LODIvAcQA==", $message->thread_index);
        self::assertEquals("omef0ahNgeoJuEB51C568ED2227A2DAABB5BB9@omef0ahNgeoJu.eurprd02.prod.outlook.com", $message->message_id);
        self::assertEquals("da-DK, en-US", $message->accept_language);
        self::assertEquals("en-US", $message->content_language);
        self::assertEquals("Internal", $message->x_ms_exchange_organization_authAs);
        self::assertEquals("04", $message->x_ms_exchange_organization_authMechanism);
        self::assertEquals("omef0ahNgeoJu.eurprd02.prod.outlook.com", $message->x_ms_exchange_organization_authSource);
        self::assertEquals("", $message->x_ms_Has_Attach);
        self::assertEquals("aa546a02-2b7a-4fb1-7fd4-08db220a09f1", $message->x_ms_exchange_organization_Network_Message_Id);
        self::assertEquals("-1", $message->x_ms_exchange_organization_SCL);
        self::assertEquals("", $message->x_ms_TNEF_Correlator);
        self::assertEquals("0", $message->x_ms_exchange_organization_RecordReviewCfmType);
        self::assertEquals("Email", $message->x_ms_publictraffictype);
        self::assertEquals("ucf:0;jmr:0;auth:0;dest:I;ENG:(910001)(944506478)(944626604)(920097)(425001)(930097);", $message->X_Microsoft_Antispam_Mailbox_Delivery->first());
        self::assertEquals("0712b5fe22cf6e75fa220501c1a6715a61098983df9e69bad4000c07531c1295", hash("sha256", $message->X_Microsoft_Antispam_Message_Info));
        self::assertEquals("multipart/alternative", $message->Content_Type->last());
        self::assertEquals("1.0", $message->mime_version);

        self::assertCount(0, $message->getAttachments());
    }
}