#!/usr/bin/env php
<?php

/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
 */
/**
 * \file scripts/company/sync_contacts_dolibarr2ldap.php
 * \ingroup ldap company
 * \brief Script to update all contacts from Dolibarr into a LDAP database
 */

include "../../htdocs/master.inc.php";
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

print ">>> dol_escape_htmltag(eée < > bb<b>bold) - should not happen</b>\n";
print dol_escape_htmltag("eée < > bb<b>bold</b>", 1);
print "\n";
print ">>> dol_escape_htmltag(eée &lt; &gt; bb<b>bold)</b>\n";
print dol_escape_htmltag("eée &lt; &gt; bb<b>bold</b>", 1);
print "\n";
print '>>> dol_escape_htmltag(&lt;script&gt;alert("azerty")&lt;/script&gt;)'."\n";
print dol_escape_htmltag('&lt;script&gt;alert("azerty")&lt;/script&gt;', 1);
print "\n";

print "\n";

// dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($s), 1, 1, 1, array())), 1, 1, 'common', 0, 1);
print ">>> dolPrintHtml(eée < > bb<b>bold</b>) - should not happen\n";
print dolPrintHtml("eée < > bb<b>bold</b>");
print "\n";
print ">>> dolPrintHtml(eée &lt; &gt; bb<b>bold</b>)\n";
print dolPrintHtml("eée &lt; &gt; bb<b>bold</b>");
print "\n";
print '>>> dolPrintHtml(&lt;script&gt;alert("azerty")&lt;/script&gt;)'."\n";
print dolPrintHtml('&lt;script&gt;alert("azerty")&lt;/script&gt;');
print "\n";

print "\n";

// dol_escape_htmltag(dol_string_onlythesehtmltags(dol_htmlentitiesbr($s), 1, 0, 0, 0, array('br', 'b', 'font', 'hr', 'span')), 1, -1, '', 0, 1);
print ">>> dolPrintHtmlForattribute(eée < > bb<b>bold</b>)\n";
print dolPrintHTMLForAttribute("eée < > bb<b>bold</b>");
print "\n";
print ">>> dolPrintHTMLForAttribute(eée &lt; &gt; bb<b>bold</b>)\n";
print dolPrintHTMLForAttribute("eée &lt; &gt; bb<b>bold</b>");
print "\n";
print '>>> dolPrintHtmlForattribute(&lt;script&gt;alert("azerty")&lt;/script&gt;)'."\n";
print dolPrintHTMLForAttribute('&lt;script&gt;alert("azerty")&lt;/script&gt;');
print "\n";
