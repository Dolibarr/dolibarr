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

<xsl:template name="autostyles">

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'big']) = 0">
        <style:style style:name="big" style:family="text">
            <style:text-properties fo:font-size="120%"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'left']) = 0">
        <style:style style:name="left" style:family="paragraph"
                     style:parent-style-name="Text_20_body">
            <style:paragraph-properties fo:text-align="left"
                                        style:justify-single-word="false"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'center']) = 0">
        <style:style style:name="center" style:family="paragraph"
                     style:parent-style-name="Text_20_body">
            <style:paragraph-properties fo:text-align="center"
                                        style:justify-single-word="false"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'right']) = 0">
        <style:style style:name="right" style:family="paragraph"
                     style:parent-style-name="Text_20_body">
            <style:paragraph-properties fo:text-align="right"
                                        style:justify-single-word="false"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'justify']) = 0">
        <style:style style:name="justify" style:family="paragraph"
                     style:parent-style-name="Text_20_body">
            <style:paragraph-properties fo:text-align="justify"
                                        style:justify-single-word="false"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'emphasis']) = 0">
        <style:style style:name="emphasis" style:family="text">
            <style:text-properties fo:font-style="italic"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'image-center']) = 0">
    <style:style style:name="image-center" style:family="graphic"
                 style:parent-style-name="Graphics">
        <style:graphic-properties style:wrap="none"
                                  style:horizontal-pos="center"
                                  style:horizontal-rel="paragraph"/>
    </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'image-inline']) = 0">
        <style:style style:name="image-inline" style:family="graphic"
                     style:parent-style-name="Graphics">
            <style:graphic-properties style:vertical-pos="middle"
                                      style:vertical-rel="text"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'image-left']) = 0">
        <style:style style:name="image-left" style:family="graphic"
                     style:parent-style-name="Graphics">
            <style:graphic-properties style:wrap="right" style:vertical-pos="top"
                                      style:vertical-rel="paragraph-content"
                                      style:horizontal-pos="left"
                                      style:horizontal-rel="paragraph"
                                      style:flow-with-text="true"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'image-right']) = 0">
        <style:style style:name="image-right" style:family="graphic"
                     style:parent-style-name="Graphics">
            <style:graphic-properties style:wrap="left" style:vertical-pos="top"
                                      style:vertical-rel="paragraph-content"
                                      style:horizontal-pos="right"
                                      style:horizontal-rel="paragraph"
                                      style:flow-with-text="true"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'list-item-bullet']) = 0">
        <style:style style:name="list-item-bullet" style:family="paragraph"
                     style:parent-style-name="Text_20_body"
                     style:list-style-name="List_20_1"/>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'list-item-number']) = 0">
        <style:style style:name="list-item-number" style:family="paragraph"
                     style:parent-style-name="Text_20_body"
                     style:list-style-name="Numbering_20_1"/>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'small']) = 0">
        <style:style style:name="small" style:family="text">
            <style:text-properties fo:font-size="80%"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'strike']) = 0">
        <style:style style:name="strike" style:family="text">
            <style:text-properties style:text-line-through-style="solid"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'strong']) = 0">
        <style:style style:name="strong" style:family="text">
            <style:text-properties fo:font-weight="bold"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'sub']) = 0">
        <style:style style:name="sub" style:family="text">
             <style:text-properties style:text-position="sub 58%"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'sup']) = 0">
        <style:style style:name="sup" style:family="text">
             <style:text-properties style:text-position="super 58%"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-A1']) = 0">
        <style:style style:name="table-default.cell-A1" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.03cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.01cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-A2']) = 0">
        <style:style style:name="table-default.cell-A2" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.03cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="none"
                                         fo:border-bottom="0.01cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-A3']) = 0">
        <style:style style:name="table-default.cell-A3" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.03cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="none"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-A4']) = 0">
        <style:style style:name="table-default.cell-A4" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.03cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-B1']) = 0">
        <style:style style:name="table-default.cell-B1" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.01cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-B2']) = 0">
        <style:style style:name="table-default.cell-B2" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="none"
                                         fo:border-bottom="0.01cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-B3']) = 0">
        <style:style style:name="table-default.cell-B3" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="none"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-B4']) = 0">
        <style:style style:name="table-default.cell-B4" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-C1']) = 0">
        <style:style style:name="table-default.cell-C1" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="0.03cm solid #000000"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.01cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-C2']) = 0">
        <style:style style:name="table-default.cell-C2" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="0.03cm solid #000000"
                                         fo:border-top="none"
                                         fo:border-bottom="0.01cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-C3']) = 0">
        <style:style style:name="table-default.cell-C3" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="0.03cm solid #000000"
                                         fo:border-top="none"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-C4']) = 0">
        <style:style style:name="table-default.cell-C4" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="0.03cm solid #000000"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-F-A3']) = 0">
        <style:style style:name="table-default.cell-F-A3" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.03cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-F-B3']) = 0">
        <style:style style:name="table-default.cell-F-B3" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-F-C3']) = 0">
        <style:style style:name="table-default.cell-F-C3" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="0.03cm solid #000000"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-H-A1']) = 0">
        <style:style style:name="table-default.cell-H-A1" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.03cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-H-B1']) = 0">
        <style:style style:name="table-default.cell-H-B1" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="none"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-H-C1']) = 0">
        <style:style style:name="table-default.cell-H-C1" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.01cm solid #000000"
                                         fo:border-right="0.03cm solid #000000"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default.cell-single']) = 0">
        <style:style style:name="table-default.cell-single" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm"
                                         fo:border-left="0.03cm solid #000000"
                                         fo:border-right="0.03cm solid #000000"
                                         fo:border-top="0.03cm solid #000000"
                                         fo:border-bottom="0.03cm solid #000000"/>
        </style:style>
    </xsl:if>

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'table-default']) = 0">
        <style:style style:name="table-default" style:family="table">
            <style:table-properties style:width="100%" table:align="margins"/>
        </style:style>
    </xsl:if>


<!-- Specific -->

    <xsl:if test="count(//office:automatic-styles/style:style[@style:name = 'underline']) = 0">
        <style:style style:name="underline" style:family="text">
            <style:text-properties style:text-underline-style="solid"
                                   style:text-underline-width="auto"
                                   style:text-underline-color="font-color"/>
        </style:style>
    </xsl:if>

</xsl:template>

</xsl:stylesheet>
