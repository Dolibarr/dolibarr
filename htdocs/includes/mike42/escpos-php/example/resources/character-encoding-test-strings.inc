<?php
/* All strings from EscposPrintBufferTest are included below- These are fully supported
 * on the default profile, so you can use them to test modified profiles (using the wrong
 * profile for a printer produces mojibake) */
$inputsOk = array(
        "Danish" => "Quizdeltagerne spiste jordbær med fløde, mens cirkusklovnen Wolther spillede på xylofon.\n",
        "German" => "Falsches Üben von Xylophonmusik quält jeden größeren Zwerg.\n",
        "Greek" => "Ξεσκεπάζω την ψυχοφθόρα βδελυγμία\n",
        "English" => "The quick brown fox jumps over the lazy dog.\n",
        "Spanish" => "El pingüino Wenceslao hizo kilómetros bajo exhaustiva lluvia y frío, añoraba a su querido cachorro.\n",
        "French" => "Le cœur déçu mais l'âme plutôt naïve, Louÿs rêva de crapaüter en canoë au delà des îles, près du mälström où brûlent les novæ.\n",
        "Irish Gaelic" => "D'fhuascail Íosa, Úrmhac na hÓighe Beannaithe, pór Éava agus Ádhaimh.\n",
        "Hungarian" => "Árvíztűrő tükörfúrógép.\n",
        "Icelandic" => "Kæmi ný öxi hér ykist þjófum nú bæði víl og ádrepa.\n",
        "Latvian" => "Glāžšķūņa rūķīši dzērumā čiepj Baha koncertflīģeļu vākus.\n",
        "Polish" => "Pchnąć w tę łódź jeża lub ośm skrzyń fig.\n",
        "Russian" => "В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!\n",
        "Turkish" => "Pijamalı hasta, yağız şoföre çabucak güvendi.\n",
        "Japanese (Katakana half-width)" => implode("\n", array("ｲﾛﾊﾆﾎﾍﾄ ﾁﾘﾇﾙｦ ﾜｶﾖﾀﾚｿ ﾂﾈﾅﾗﾑ",  "ｳｲﾉｵｸﾔﾏ ｹﾌｺｴﾃ ｱｻｷﾕﾒﾐｼ ｴﾋﾓｾｽﾝ")) . "\n",
        "Vietnamese" => "Tiếng Việt, còn gọi tiếng Việt Nam hay Việt ngữ, là ngôn ngữ của người Việt (người Kinh) và là ngôn ngữ chính thức tại Việt Nam.\n"
        );

/*
 * These strings are not expected to print correctly, if at all, even on an Epson printer. This is due to a mix of
 * escpos driver, printer, and PHP language support issues.
 * 
 * They are included here as a collection of things not yet implemented.
 */
$inputsNotOk = array(
        "Thai (No character encoder available)" => "นายสังฆภัณฑ์ เฮงพิทักษ์ฝั่ง ผู้เฒ่าซึ่งมีอาชีพเป็นฅนขายฃวด ถูกตำรวจปฏิบัติการจับฟ้องศาล ฐานลักนาฬิกาคุณหญิงฉัตรชฎา ฌานสมาธิ\n",
        "Japanese (Hiragana)" => implode("\n", array("いろはにほへとちりぬるを",  " わかよたれそつねならむ", "うゐのおくやまけふこえて",  "あさきゆめみしゑひもせす")) . "\n",
        "Japanese (Katakana full-width)" => implode("\n", array("イロハニホヘト チリヌルヲ ワカヨタレソ ツネナラム",  "ウヰノオクヤマ ケフコエテ アサキユメミシ ヱヒモセスン")) . "\n",
        "Arabic (RTL not supported, encoding issues)" => "صِف خَلقَ خَودِ كَمِثلِ الشَمسِ إِذ بَزَغَت — يَحظى الضَجيعُ بِها نَجلاءَ مِعطارِ" . "\n",
        "Hebrew (RTL not supported, line break issues)" => "דג סקרן שט בים מאוכזב ולפתע מצא לו חברה איך הקליטה" . "\n"
        );
