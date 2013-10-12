﻿/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.setLang( 'a11yhelp', 'tr',
{
	accessibilityHelp :
	{
		title : 'Erişilebilirlik Talimatları',
		contents : 'Yardım içeriği. Bu pencereyi kapatmak için ESC tuşuna basın.',
		legend :
		[
			{
				name : 'Genel',
				items :
						[
							{
								name : 'Araç Çubuğu Editörü',
								legend:
									'Araç çubuğunda gezinmek için ${toolbarFocus} basın. TAB ve SHIFT-TAB ile önceki ve sonraki araç çubuğu grubuna taşıyın. SAĞ OK veya SOL OK ile önceki ve sonraki bir araç çubuğu düğmesini hareket ettirin. SPACE tuşuna basın veya araç çubuğu düğmesini etkinleştirmek için ENTER tuşna basın.'
							},

							{
								name : 'Dialog Editörü',
								legend :
									'Dialog penceresi içinde, sonraki iletişim alanına gitmek için SEKME tuşuna basın, önceki alana geçmek için SHIFT + TAB tuşuna basın, pencereyi göndermek için ENTER tuşuna basın, dialog penceresini iptal etmek için ESC tuşuna basın. Birden çok sekme sayfaları olan diyalogların, sekme listesine gitmek için ALT + F10 tuşlarına basın. Sonra TAB veya SAĞ OK sonraki sekmeye taşıyın. SHIFT + TAB veya SOL OK ile önceki sekmeye geçin. Sekme sayfayı seçmek için SPACE veya ENTER tuşuna basın.'
							},

							{
								name : 'İçerik Menü Editörü',
								legend :
									'İçerik menüsünü açmak için ${contextMenu} veya UYGULAMA TUŞU\'na basın. Daha sonra SEKME veya AŞAĞI OK ile bir sonraki menü seçeneği taşıyın. SHIFT + TAB veya YUKARI OK ile önceki seçeneğe gider. Menü seçeneğini seçmek için SPACE veya ENTER tuşuna basın. Seçili seçeneğin alt menüsünü SPACE ya da ENTER veya SAĞ OK açın. Üst menü öğesini geçmek için ESC veya SOL OK ile geri dönün. ESC ile bağlam menüsünü kapatın.'
							},

							{
								name : 'Liste Kutusu Editörü',
								legend :
									'Liste kutusu içinde, bir sonraki liste öğesine SEKME VEYA AŞAĞI OK ile taşıyın. SHIFT + TAB veya YUKARI önceki liste öğesi taşıyın. Liste seçeneği seçmek için SPACE veya ENTER tuşuna basın. Liste kutusunu kapatmak için ESC tuşuna basın.'
							},

							{
								name : 'Element Yol Çubuğu Editörü',
								legend :
									'Elementlerin yol çubuğunda gezinmek için ${ElementsPathFocus} basın. SEKME veya SAĞ OK ile sonraki element düğmesine taşıyın. SHIFT + TAB veya SOL OK önceki düğmeye hareket ettirin. Editör içindeki elementi seçmek için ENTER veya SPACE tuşuna basın.'
							}
						]
			},
			{
				name : 'Komutlar',
				items :
						[
							{
								name : 'Komutu geri al',
								legend : '${undo} basın'
							},
							{
								name : ' Tekrar komutu uygula',
								legend : '${redo} basın'
							},
							{
								name : ' Kalın komut',
								legend : '${bold} basın'
							},
							{
								name : ' İtalik komutu',
								legend : '${italic} basın'
							},
							{
								name : ' Alttan çizgi komutu',
								legend : '${underline} basın'
							},
							{
								name : ' Bağlantı komutu',
								legend : '${link} basın'
							},
							{
								name : ' Araç çubuğu Toplama komutu',
								legend : '${toolbarCollapse} basın'
							},
							{
								name : 'Erişilebilirlik Yardımı',
								legend : '${a11yHelp} basın'
							}
						]
			}
		]
	}
});
