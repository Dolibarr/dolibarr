<?xml version="1.0" encoding="UTF-8"?>
<!--

    xhtml2odt - XHTML to ODT XML transformation.
    Copyright (C) 2009 Aurelien Bompard
    Inspired by the work on docbook2odt, by Roman Fordinal
    http://open.comsultia.com/docbook2odf/

    License: LGPL v2.1 or later <http://www.gnu.org/licenses/lgpl-2.1.html>

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
    MA  02110-1301  USA

-->
<xsl:stylesheet
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
    xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"
    xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"
    xmlns:math="http://www.w3.org/1998/Math/MathML"
    xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"
    xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"
    xmlns:dom="http://www.w3.org/2001/xml-events"
    xmlns:xforms="http://www.w3.org/2002/xforms"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0"
    version="1.0">

<xsl:template name="mainstyles">

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Caption']) = 0">
        <style:style style:name="Caption" style:display-name="Caption" style:class="extra"
                     style:family="paragraph" style:parent-style-name="Standard">
            <style:paragraph-properties fo:margin-top="0.2cm" fo:margin-bottom="0.2cm"
                                        text:number-lines="false" text:line-number="0"/>
            <style:text-properties fo:font-size="95%" fo:font-style="italic"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Citation']) = 0">
        <style:style style:name="Citation" style:display-name="Citation" style:family="text">
            <style:text-properties fo:font-style="italic"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Footnote']) = 0">
        <style:style style:name="Footnote" style:display-name="Footnote"
                     style:family="paragraph" style:class="extra"
                     style:parent-style-name="Standard">
            <style:paragraph-properties fo:margin-left="0.5cm" fo:margin-right="0cm"
                   fo:text-indent="-0.5cm" style:auto-text-indent="false"
                   text:number-lines="false" text:line-number="0"/>
            <style:text-properties fo:font-size="10pt"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Heading_20_1']) = 0">
        <style:style style:name="Heading_20_1" style:display-name="Heading 1"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Text_20_body" style:class="text"
                     style:default-outline-level="1">
            <style:text-properties fo:font-size="115%" fo:font-weight="bold"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Heading_20_2']) = 0">
        <style:style style:name="Heading_20_2" style:display-name="Heading 2"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Text_20_body" style:class="text"
                     style:default-outline-level="2">
            <style:text-properties fo:font-size="110%" fo:font-weight="bold"
                                   fo:font-style="italic"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Heading_20_3']) = 0">
        <style:style style:name="Heading_20_3" style:display-name="Heading 3"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Text_20_body" style:class="text"
                     style:default-outline-level="3">
            <style:text-properties fo:font-size="105%" fo:font-weight="bold"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Heading_20_4']) = 0">
        <style:style style:name="Heading_20_4" style:display-name="Heading 4"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Text_20_body" style:class="text"
                     style:default-outline-level="4">
            <style:text-properties fo:font-size="100%" fo:font-weight="bold"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Heading_20_5']) = 0">
        <style:style style:name="Heading_20_5" style:display-name="Heading 5"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Text_20_body" style:class="text"
                     style:default-outline-level="5">
            <style:text-properties fo:font-size="100%" fo:font-style="italic"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Heading_20_6']) = 0">
        <style:style style:name="Heading_20_6" style:display-name="Heading 6"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Text_20_body" style:class="text"
                     style:default-outline-level="6">
            <style:text-properties fo:font-size="90%" fo:font-weight="bold"
                                   style:text-underline-style="solid"
                                   style:text-underline-width="auto"
                                   style:text-underline-color="font-color"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Title']) = 0">
        <style:style style:name="Title" style:display-name="Title"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Subtitle" style:class="chapter">
            <style:paragraph-properties fo:text-align="center"
                                        style:justify-single-word="false"/>
            <style:text-properties fo:font-size="120%" fo:font-weight="bold"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Subtitle']) = 0">
        <style:style style:name="Subtitle" style:display-name="Subtitle"
                     style:family="paragraph" style:parent-style-name="Heading"
                     style:next-style-name="Text_20_body" style:class="chapter">
            <style:paragraph-properties fo:text-align="center"
                                        style:justify-single-word="false"/>
            <style:text-properties fo:font-size="110%" fo:font-style="italic"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Horizontal_20_Line']) = 0">
        <style:style style:name="Horizontal_20_Line" style:display-name="Horizontal Line"
                     style:family="paragraph" style:parent-style-name="Standard"
                     style:next-style-name="Text_20_body" style:class="html">
            <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.5cm"
                         style:border-line-width-bottom="0.002cm 0.035cm 0.002cm"
                         fo:padding="0cm" fo:border-left="none" fo:border-right="none"
                         fo:border-top="none" fo:border-bottom="0.04cm double #808080"
                         text:number-lines="false" text:line-number="0"
                         style:join-border="false"/>
            <style:text-properties fo:font-size="6pt"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'List_20_1']) = 0">
        <text:list-style style:name="List_20_1" style:display-name="List 1">
            <text:list-level-style-bullet text:level="1"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="•">
                <style:list-level-properties
                    text:space-before="0.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="2"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="◦">
                <style:list-level-properties
                    text:space-before="1cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="3"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="▪">
                <style:list-level-properties
                    text:space-before="1.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="4"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="•">
                <style:list-level-properties
                    text:space-before="2cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="5"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="◦">
                <style:list-level-properties
                    text:space-before="2.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="6"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="▪">
                <style:list-level-properties
                    text:space-before="3cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="7"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="•">
                <style:list-level-properties
                    text:space-before="3.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="8"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="◦">
                <style:list-level-properties
                    text:space-before="4cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="9"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="▪">
                <style:list-level-properties
                    text:space-before="4.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
            <text:list-level-style-bullet text:level="10"
                    text:style-name="Bullet_20_Symbols"
                    text:bullet-char="•">
                <style:list-level-properties
                    text:space-before="5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-bullet>
        </text:list-style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Numbering_20_1']) = 0">
        <text:list-style style:name="Numbering_20_1" style:display-name="Numbering 1">
            <text:list-level-style-number text:level="1"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="0.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="2"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="1cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="3"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="1.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="4"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="2cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="5"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="2.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="6"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="3cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="7"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="3.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="8"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="4cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="9"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="4.5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
            <text:list-level-style-number text:level="10"
                    text:style-name="Numbering_20_Symbols"
                    style:num-suffix="."
                    style:num-format="1">
                <style:list-level-properties
                    text:space-before="5cm"
                    text:min-label-width="0.5cm"/>
            </text:list-level-style-number>
        </text:list-style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Definition_20_Term']) = 0">
        <style:style style:name="Definition_20_Term"
                     style:display-name="Definition Term" style:family="paragraph"
                     style:parent-style-name="Text_20_body" style:class="html">
            <style:text-properties fo:font-weight="bold"/>
            <style:paragraph-properties fo:margin-bottom="0cm"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Definition_20_Description']) = 0">
        <style:style style:name="Definition_20_Description"
                     style:display-name="Definition Description" style:family="paragraph"
                     style:parent-style-name="Text_20_body" style:class="html">
            <style:paragraph-properties fo:margin-top="0cm" fo:margin-left="1cm"
                                        fo:margin-bottom="0.2cm"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Preformatted_20_Text']) = 0">
        <style:style style:name="Preformatted_20_Text"
                     style:display-name="Preformatted Text" style:family="paragraph"
                     style:parent-style-name="Standard" style:class="html">
            <style:paragraph-properties fo:margin-left="1cm" fo:margin-right="1cm"
                                        fo:margin-top="0cm" fo:margin-bottom="0cm"/>
            <style:text-properties style:font-name="DejaVu Sans Mono"
                                   fo:font-size="9pt"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Source_20_Code']) = 0">
        <style:style style:name="Source_20_Code"
                     style:display-name="Source Code" style:family="paragraph"
                     style:parent-style-name="Preformatted_20_Text">
            <style:paragraph-properties fo:padding="0.05cm" style:shadow="none"
                                        fo:border="0.002cm solid #c0c0c0"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Source_20_Code_20_Numbered']) = 0">
        <style:style style:name="Source_20_Code_20_Numbered"
                     style:display-name="Source Code Numbered" style:family="paragraph"
                     style:list-style-name="Numbering_20_1"
                     style:parent-style-name="Source_20_Code">
            <style:paragraph-properties text:number-lines="true" text:line-number="1"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Quotations']) = 0">
        <style:style style:name="Quotations" style:family="paragraph"
                     style:display-name="Quotations"
                     style:parent-style-name="Standard" style:class="html">
            <style:paragraph-properties fo:margin-left="1cm" fo:margin-right="1cm"
                                        fo:margin-top="0cm" fo:margin-bottom="0.5cm"
                                        fo:text-indent="0cm" style:auto-text-indent="false"
                                        fo:padding="0.2cm"
                                        fo:border-left="0.088cm solid #999999"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Sender']) = 0">
        <style:style style:name="Sender" style:display-name="Sender" style:class="extra"
                     style:family="paragraph" style:parent-style-name="Standard">
            <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.100cm"
                                        text:number-lines="false" text:line-number="0"/>
            <style:text-properties fo:font-style="italic"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Table_20_Contents']) = 0">
        <style:style style:name="Table_20_Contents" style:display-name="Table Contents"
                     style:family="paragraph" style:parent-style-name="Standard"
                     style:class="extra">
            <style:paragraph-properties text:number-lines="false" text:line-number="0"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Table_20_Heading']) = 0">
        <style:style style:name="Table_20_Heading" style:display-name="Table Heading"
                     style:family="paragraph" style:parent-style-name="Table_20_Contents"
                     style:class="extra">
            <style:paragraph-properties fo:text-align="center" style:justify-single-word="false"
                                        text:number-lines="false" text:line-number="0"/>
            <style:text-properties fo:font-weight="bold"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Teletype']) = 0">
        <style:style style:name="Teletype" style:display-name="Teletype"
                     style:family="text">
            <style:text-properties style:font-name="DejaVu Sans Mono"
                                   fo:font-size="9pt"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:styles/style:style[@style:name = 'Marginalia']) = 0">
        <style:style style:name="Marginalia" style:display-name="Marginalia"
                     style:family="graphic">
            <style:graphic-properties svg:width="8.5cm" style:rel-width="50%"
                                      fo:min-height="0.5cm"
                                      text:anchor-type="paragraph"
                                      svg:x="0cm" svg:y="0cm"
                                      fo:margin-left="0.2cm"
                                      fo:margin-right="0cm"
                                      fo:margin-top="0.1cm"
                                      fo:margin-bottom="0.1cm"
                                      style:wrap="parallel"
                                      style:number-wrapped-paragraphs="no-limit"
                                      style:wrap-contour="false"
                                      style:vertical-pos="top"
                                      style:vertical-rel="paragraph"
                                      style:horizontal-pos="right"
                                      style:horizontal-rel="paragraph"
                                      fo:background-color="transparent"
                                      style:background-transparency="100%"
                                      fo:padding="0.15cm"
                                      fo:border="0.002cm solid #000000"
                                      style:shadow="none">
            </style:graphic-properties>
        </style:style>
    </xsl:if>

</xsl:template>

</xsl:stylesheet>
