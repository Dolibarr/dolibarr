/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: ru.js
 * 	Russian language file.
 * 
 * File Authors:
 * 		Andrey Grebnev (andrey.grebnev@blandware.com)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Свернуть панель инструментов",
ToolbarExpand		: "Развернуть панель инструментов",

// Toolbar Items and Context Menu
Save				: "Сохранить",
NewPage				: "Новая страница",
Preview				: "Предварительный просмотр",
Cut					: "Вырезать",
Copy				: "Копировать",
Paste				: "Вставить",
PasteText			: "Вставить только текст",
PasteWord			: "Вставить из Word",
Print				: "Печать",
SelectAll			: "Выделить все",
RemoveFormat		: "Убрать форматирование",
InsertLinkLbl		: "Ссылка",
InsertLink			: "Вставить/Редактировать ссылку",
RemoveLink			: "Убрать ссылку",
Anchor				: "Вставить/Редактировать якорь",
InsertImageLbl		: "Изображение",
InsertImage			: "Вставить/Редактировать изображение",
InsertFlashLbl		: "Flash",
InsertFlash			: "Вставить/Редактировать Flash",
InsertTableLbl		: "Таблица",
InsertTable			: "Вставить/Редактировать таблицу",
InsertLineLbl		: "Линия",
InsertLine			: "Вставить горизонтальную линию",
InsertSpecialCharLbl: "Специальный символ",
InsertSpecialChar	: "Вставить специальный символ",
InsertSmileyLbl		: "Смайлик",
InsertSmiley		: "Вставить смайлик",
About				: "О FCKeditor",
Bold				: "Жирный",
Italic				: "Курсив",
Underline			: "Подчеркнутый",
StrikeThrough		: "Зачеркнутый",
Subscript			: "Подстрочный индекс",
Superscript			: "Надстрочный индекс",
LeftJustify			: "По левому краю",
CenterJustify		: "По центру",
RightJustify		: "По правому краю",
BlockJustify		: "По ширине",
DecreaseIndent		: "Уменьшить отступ",
IncreaseIndent		: "Увеличить отступ",
Undo				: "Отменить",
Redo				: "Повторить",
NumberedListLbl		: "Нумерованный список",
NumberedList		: "Вставить/Удалить нумерованный список",
BulletedListLbl		: "Маркированный список",
BulletedList		: "Вставить/Удалить маркированный список",
ShowTableBorders	: "Показать бордюры таблицы",
ShowDetails			: "Показать детали",
Style				: "Стиль",
FontFormat			: "Форматирование",
Font				: "Шрифт",
FontSize			: "Размер",
TextColor			: "Цвет текста",
BGColor				: "Цвет фона",
Source				: "Источник",
Find				: "Найти",
Replace				: "Заменить",
SpellCheck			: "Проверить орфографию",
UniversalKeyboard	: "Универсальная клавиатура",
PageBreakLbl		: "Разрыв страницы",
PageBreak			: "Вставить разрыв страницы",

Form			: "Форма",
Checkbox		: "Флаговая кнопка",
RadioButton		: "Кнопка выбора",
TextField		: "Текстовое поле",
Textarea		: "Текстовая область",
HiddenField		: "Скрытое поле",
Button			: "Кнопка",
SelectionField	: "Список",
ImageButton		: "Кнопка с изображением",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "Вставить ссылку",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "Вставить строку",
DeleteRows			: "Удалить строки",
InsertColumn		: "Вставить колонку",
DeleteColumns		: "Удалить колонки",
InsertCell			: "Вставить ячейку",
DeleteCells			: "Удалить ячейки",
MergeCells			: "Соединить ячейки",
SplitCell			: "Разбить ячейку",
TableDelete			: "Удалить таблицу",
CellProperties		: "Свойства ячейки",
TableProperties		: "Свойства таблицы",
ImageProperties		: "Свойства изображения",
FlashProperties		: "Свойства Flash",

AnchorProp			: "Свойства якоря",
ButtonProp			: "Свойства кнопки",
CheckboxProp		: "Свойства флаговой кнопки",
HiddenFieldProp		: "Свойства скрытого поля",
RadioButtonProp		: "Свойства кнопки выбора",
ImageButtonProp		: "Свойства кнопки с изображением",
TextFieldProp		: "Свойства текстового поля",
SelectionFieldProp	: "Свойства списка",
TextareaProp		: "Свойства текстовой области",
FormProp			: "Свойства формы",

FontFormats			: "Нормальный;Форматированный;Адрес;Заголовок 1;Заголовок 2;Заголовок 3;Заголовок 4;Заголовок 5;Заголовок 6;Нормальный (DIV)",

// Alerts and Messages
ProcessingXHTML		: "Обработка XHTML. Пожалуйста подождите...",
Done				: "Сделано",
PasteWordConfirm	: "Текст, который вы хотите вставить, похож на копируемый из Word. Вы хотите очистить его перед вставкой?",
NotCompatiblePaste	: "Эта команда доступна для Internet Explorer версии 5.5 или выше. Вы хотите вставить без очистки?",
UnknownToolbarItem	: "Не известный элемент панели инструментов \"%1\"",
UnknownCommand		: "Не известное имя команды \"%1\"",
NotImplemented		: "Команда не реализована",
UnknownToolbarSet	: "Панель инструментов \"%1\" не существует",
NoActiveX			: "Настройки безопасности вашего браузера могут ограничивать некоторые свойства редактора. Вы должны включить опцию \"Запускать элементы управления ActiveX и плугины\". Вы можете видеть ошибки и замечать отсутствие возможностей.",
BrowseServerBlocked : "Ресурсы браузера не могут быть открыты. Проверьте что блокировки всплывающих окон выключены.",
DialogBlocked		: "Не возможно открыть окно диалога. Проверьте что блокировки всплывающих окон выключены.",

// Dialogs
DlgBtnOK			: "ОК",
DlgBtnCancel		: "Отмена",
DlgBtnClose			: "Закрыть",
DlgBtnBrowseServer	: "Просмотреть на сервере",
DlgAdvancedTag		: "Расширенный",
DlgOpOther			: "<Другое>",
DlgInfoTab			: "Информация",
DlgAlertUrl			: "Пожалуйста вставьте URL",

// General Dialogs Labels
DlgGenNotSet		: "<не определено>",
DlgGenId			: "Идентификатор",
DlgGenLangDir		: "Направление языка",
DlgGenLangDirLtr	: "Слева на право (LTR)",
DlgGenLangDirRtl	: "Справа на лево (RTL)",
DlgGenLangCode		: "Язык",
DlgGenAccessKey		: "Горячая клавиша",
DlgGenName			: "Имя",
DlgGenTabIndex		: "Последовательность перехода",
DlgGenLongDescr		: "Длинное описание URL",
DlgGenClass			: "Класс CSS",
DlgGenTitle			: "Заголовок",
DlgGenContType		: "Тип содержимого",
DlgGenLinkCharset	: "Кодировка",
DlgGenStyle			: "Стиль CSS",

// Image Dialog
DlgImgTitle			: "Свойства изображения",
DlgImgInfoTab		: "Информация о изображении",
DlgImgBtnUpload		: "Послать на сервер",
DlgImgURL			: "URL",
DlgImgUpload		: "Закачать",
DlgImgAlt			: "Альтернативный текст",
DlgImgWidth			: "Ширина",
DlgImgHeight		: "Высота",
DlgImgLockRatio		: "Сохранять пропорции",
DlgBtnResetSize		: "Сбросить размер",
DlgImgBorder		: "Бордюр",
DlgImgHSpace		: "Горизонтальный отступ",
DlgImgVSpace		: "Вертикальный отступ",
DlgImgAlign			: "Выравнивание",
DlgImgAlignLeft		: "По левому краю",
DlgImgAlignAbsBottom: "Абс понизу",
DlgImgAlignAbsMiddle: "Абс посередине",
DlgImgAlignBaseline	: "По базовой линии",
DlgImgAlignBottom	: "Понизу",
DlgImgAlignMiddle	: "Посередине",
DlgImgAlignRight	: "По правому краю",
DlgImgAlignTextTop	: "Текст наверху",
DlgImgAlignTop		: "По верху",
DlgImgPreview		: "Предварительный просмотр",
DlgImgAlertUrl		: "Пожалуйста введите URL изображения",
DlgImgLinkTab		: "Ссылка",

// Flash Dialog
DlgFlashTitle		: "Свойства Flash",
DlgFlashChkPlay		: "Авто проигрывание",
DlgFlashChkLoop		: "Повтор",
DlgFlashChkMenu		: "Включить меню Flash",
DlgFlashScale		: "Масштабировать",
DlgFlashScaleAll	: "Показывать все",
DlgFlashScaleNoBorder	: "Без бордюра",
DlgFlashScaleFit	: "Точное совпадение",

// Link Dialog
DlgLnkWindowTitle	: "Ссылка",
DlgLnkInfoTab		: "Информация ссылки",
DlgLnkTargetTab		: "Цель",

DlgLnkType			: "Тип ссылки",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Якорь на эту страницу",
DlgLnkTypeEMail		: "Эл. почта",
DlgLnkProto			: "Протокол",
DlgLnkProtoOther	: "<другое>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Выберите якорь",
DlgLnkAnchorByName	: "По имени якоря",
DlgLnkAnchorById	: "По идентификатору элемента",
DlgLnkNoAnchors		: "<Нет якорей доступных в этом документе>",
DlgLnkEMail			: "Адрес эл. почты",
DlgLnkEMailSubject	: "Заголовок сообщения",
DlgLnkEMailBody		: "Тело сообщения",
DlgLnkUpload		: "Закачать",
DlgLnkBtnUpload		: "Послать на сервер",

DlgLnkTarget		: "Цель",
DlgLnkTargetFrame	: "<фрейм>",
DlgLnkTargetPopup	: "<всплывающее окно>",
DlgLnkTargetBlank	: "Новое окно (_blank)",
DlgLnkTargetParent	: "Родительское окно (_parent)",
DlgLnkTargetSelf	: "Тоже окно (_self)",
DlgLnkTargetTop		: "Самое верхнее окно (_top)",
DlgLnkTargetFrameName	: "Имя целевого фрейма",
DlgLnkPopWinName	: "Имя всплывающего окна",
DlgLnkPopWinFeat	: "Свойства всплывающего окна",
DlgLnkPopResize		: "Изменяющееся в размерах",
DlgLnkPopLocation	: "Панель локации",
DlgLnkPopMenu		: "Панель меню",
DlgLnkPopScroll		: "Полосы прокрутки",
DlgLnkPopStatus		: "Строка состояния",
DlgLnkPopToolbar	: "Панель инструментов",
DlgLnkPopFullScrn	: "Полный экран (IE)",
DlgLnkPopDependent	: "Зависимый (Netscape)",
DlgLnkPopWidth		: "Ширина",
DlgLnkPopHeight		: "Высота",
DlgLnkPopLeft		: "Позиция слева",
DlgLnkPopTop		: "Позиция сверху",

DlnLnkMsgNoUrl		: "Пожалуйста введите URL ссылки",
DlnLnkMsgNoEMail	: "Пожалуйста введите адрес эл. почты",
DlnLnkMsgNoAnchor	: "Пожалуйста выберете якорь",

// Color Dialog
DlgColorTitle		: "Выберите цвет",
DlgColorBtnClear	: "Очистить",
DlgColorHighlight	: "Подсвеченный",
DlgColorSelected	: "Выбранный",

// Smiley Dialog
DlgSmileyTitle		: "Вставить смайлик",

// Special Character Dialog
DlgSpecialCharTitle	: "Выберите специальный символ",

// Table Dialog
DlgTableTitle		: "Свойства таблицы",
DlgTableRows		: "Строки",
DlgTableColumns		: "Колонки",
DlgTableBorder		: "Размер бордюра",
DlgTableAlign		: "Выравнивание",
DlgTableAlignNotSet	: "<Не уст.>",
DlgTableAlignLeft	: "Слева",
DlgTableAlignCenter	: "По центру",
DlgTableAlignRight	: "Справа",
DlgTableWidth		: "Ширина",
DlgTableWidthPx		: "пикселей",
DlgTableWidthPc		: "процентов",
DlgTableHeight		: "Высота",
DlgTableCellSpace	: "Промежуток (spacing)",
DlgTableCellPad		: "Отступ (padding)",
DlgTableCaption		: "Заголовок",
DlgTableSummary		: "Резюме",

// Table Cell Dialog
DlgCellTitle		: "Свойства ячейки",
DlgCellWidth		: "Ширина",
DlgCellWidthPx		: "пикселей",
DlgCellWidthPc		: "процентов",
DlgCellHeight		: "Высота",
DlgCellWordWrap		: "Заворачивание текста",
DlgCellWordWrapNotSet	: "<Не уст.>",
DlgCellWordWrapYes	: "Да",
DlgCellWordWrapNo	: "Нет",
DlgCellHorAlign		: "Гор. выравнивание",
DlgCellHorAlignNotSet	: "<Не уст.>",
DlgCellHorAlignLeft	: "Слева",
DlgCellHorAlignCenter	: "По центру",
DlgCellHorAlignRight: "Справа",
DlgCellVerAlign		: "Верт. выравнивание",
DlgCellVerAlignNotSet	: "<Не уст.>",
DlgCellVerAlignTop	: "Сверху",
DlgCellVerAlignMiddle	: "Посередине",
DlgCellVerAlignBottom	: "Снизу",
DlgCellVerAlignBaseline	: "По базовой линии",
DlgCellRowSpan		: "Диапазон строк (span)",
DlgCellCollSpan		: "Диапазон колонок (span)",
DlgCellBackColor	: "Цвет фона",
DlgCellBorderColor	: "Цвет бордюра",
DlgCellBtnSelect	: "Выберите...",

// Find Dialog
DlgFindTitle		: "Найти",
DlgFindFindBtn		: "Найти",
DlgFindNotFoundMsg	: "Указанный текст не найден.",

// Replace Dialog
DlgReplaceTitle			: "Заменить",
DlgReplaceFindLbl		: "Найти:",
DlgReplaceReplaceLbl	: "Заменить на:",
DlgReplaceCaseChk		: "Учитывать регистр",
DlgReplaceReplaceBtn	: "Заменить",
DlgReplaceReplAllBtn	: "Заменить все",
DlgReplaceWordChk		: "Совпадение целых слов",

// Paste Operations / Dialog
PasteErrorPaste	: "Настройки безопасности вашего браузера не позволяют редактору автоматически выполнять операции вставки. Пожалуйста используйте клавиатуру для этого (Ctrl+V).",
PasteErrorCut	: "Настройки безопасности вашего браузера не позволяют редактору автоматически выполнять операции вырезания. Пожалуйста используйте клавиатуру для этого (Ctrl+X).",
PasteErrorCopy	: "Настройки безопасности вашего браузера не позволяют редактору автоматически выполнять операции копирования. Пожалуйста используйте клавиатуру для этого (Ctrl+C).",

PasteAsText		: "Вставить только текст",
PasteFromWord	: "Вставить из Word",

DlgPasteMsg2	: "Пожалуйста вставьте текст в прямоугольник используя сочетание клавиш (<STRONG>Ctrl+V</STRONG>) и нажмите <STRONG>OK</STRONG>.",
DlgPasteIgnoreFont		: "Игнорировать определения гарнитуры",
DlgPasteRemoveStyles	: "Убрать определения стилей",
DlgPasteCleanBox		: "Очистить",

// Color Picker
ColorAutomatic	: "Автоматический",
ColorMoreColors	: "Цвета...",

// Document Properties
DocProps		: "Свойства документа",

// Anchor Dialog
DlgAnchorTitle		: "Свойства якоря",
DlgAnchorName		: "Имя якоря",
DlgAnchorErrorName	: "Пожалуйста введите имя якоря",

// Speller Pages Dialog
DlgSpellNotInDic		: "Нет в словаре",
DlgSpellChangeTo		: "Заменить на",
DlgSpellBtnIgnore		: "Игнорировать",
DlgSpellBtnIgnoreAll	: "Игнорировать все",
DlgSpellBtnReplace		: "Заменить",
DlgSpellBtnReplaceAll	: "Заменить все",
DlgSpellBtnUndo			: "Отменить",
DlgSpellNoSuggestions	: "- Нет предположений -",
DlgSpellProgress		: "Идет проверка орфографии...",
DlgSpellNoMispell		: "Проверка орфографии закончена: ошибок не найдено",
DlgSpellNoChanges		: "Проверка орфографии закончена: ни одного слова не изменено",
DlgSpellOneChange		: "Проверка орфографии закончена: одно слово изменено",
DlgSpellManyChanges		: "Проверка орфографии закончена: 1% слов изменен",

IeSpellDownload			: "Модуль проверки орфографии не установлен. Хотите скачать его сейчас?",

// Button Dialog
DlgButtonText	: "Текст (Значение)",
DlgButtonType	: "Тип",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Имя",
DlgCheckboxValue	: "Значение",
DlgCheckboxSelected	: "Выбранная",

// Form Dialog
DlgFormName		: "Имя",
DlgFormAction	: "Действие",
DlgFormMethod	: "Метод",

// Select Field Dialog
DlgSelectName		: "Имя",
DlgSelectValue		: "Значение",
DlgSelectSize		: "Размер",
DlgSelectLines		: "линии",
DlgSelectChkMulti	: "Разрешить множественный выбор",
DlgSelectOpAvail	: "Доступные варианты",
DlgSelectOpText		: "Текст",
DlgSelectOpValue	: "Значение",
DlgSelectBtnAdd		: "Добавить",
DlgSelectBtnModify	: "Модифицировать",
DlgSelectBtnUp		: "Вверх",
DlgSelectBtnDown	: "Вниз",
DlgSelectBtnSetValue : "Установить как выбранное значение",
DlgSelectBtnDelete	: "Удалить",

// Textarea Dialog
DlgTextareaName	: "Имя",
DlgTextareaCols	: "Колонки",
DlgTextareaRows	: "Строки",

// Text Field Dialog
DlgTextName			: "Имя",
DlgTextValue		: "Значение",
DlgTextCharWidth	: "Ширина",
DlgTextMaxChars		: "Макс. кол-во символов",
DlgTextType			: "Тип",
DlgTextTypeText		: "Текст",
DlgTextTypePass		: "Пароль",

// Hidden Field Dialog
DlgHiddenName	: "Имя",
DlgHiddenValue	: "Значение",

// Bulleted List Dialog
BulletedListProp	: "Свойства маркированного списка",
NumberedListProp	: "Свойства нумерованного списка",
DlgLstType			: "Тип",
DlgLstTypeCircle	: "Круг",
DlgLstTypeDisc		: "Диск",
DlgLstTypeSquare	: "Квадрат",
DlgLstTypeNumbers	: "Номера (1, 2, 3)",
DlgLstTypeLCase		: "Буквы нижнего регистра (a, b, c)",
DlgLstTypeUCase		: "Буквы верхнего регистра (A, B, C)",
DlgLstTypeSRoman	: "Малые римские буквы (i, ii, iii)",
DlgLstTypeLRoman	: "Большие римские буквы (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Общие",
DlgDocBackTab		: "Задний фон",
DlgDocColorsTab		: "Цвета и отступы",
DlgDocMetaTab		: "Мета данные",

DlgDocPageTitle		: "Заголовок страницы",
DlgDocLangDir		: "Направление текста",
DlgDocLangDirLTR	: "Слева на право (LTR)",
DlgDocLangDirRTL	: "Справа на лево (RTL)",
DlgDocLangCode		: "Код языка",
DlgDocCharSet		: "Кодировка набора символов",
DlgDocCharSetOther	: "Другая кодировка набора символов",

DlgDocDocType		: "Заголовок типа документа",
DlgDocDocTypeOther	: "Другой заголовок типа документа",
DlgDocIncXHTML		: "Включить XHTML объявления",
DlgDocBgColor		: "Цвет фона",
DlgDocBgImage		: "URL изображения фона",
DlgDocBgNoScroll	: "Нескроллируемый фон",
DlgDocCText			: "Текст",
DlgDocCLink			: "Ссылка",
DlgDocCVisited		: "Посещенная ссылка",
DlgDocCActive		: "Активная ссылка",
DlgDocMargins		: "Отступы страницы",
DlgDocMaTop			: "Верхний",
DlgDocMaLeft		: "Левый",
DlgDocMaRight		: "Правый",
DlgDocMaBottom		: "Нижний",
DlgDocMeIndex		: "Ключевые слова документа (разделенные запятой)",
DlgDocMeDescr		: "Описание документа",
DlgDocMeAuthor		: "Автор",
DlgDocMeCopy		: "Авторские права",
DlgDocPreview		: "Предварительный просмотр",

// Templates Dialog
Templates			: "Шаблоны",
DlgTemplatesTitle	: "Шаблоны содержимого",
DlgTemplatesSelMsg	: "Пожалуйста выберете шаблон для открытия в редакторе<br>(текущее содержимое будет потеряно):",
DlgTemplatesLoading	: "Загрузка списка шаблонов. Пожалуйста подождите...",
DlgTemplatesNoTpl	: "(Ни одного шаблона не определено)",

// About Dialog
DlgAboutAboutTab	: "О программе",
DlgAboutBrowserInfoTab	: "Информация браузера",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "Версия",
DlgAboutLicense		: "Лицензировано в соответствии с условиями GNU Lesser General Public License",
DlgAboutInfo		: "Для большей информации, посетите"
}