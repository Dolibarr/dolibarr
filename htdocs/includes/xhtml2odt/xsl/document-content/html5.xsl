<?xml version="1.0" encoding="utf-8"?>
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
    xmlns:h="http://www.w3.org/1999/xhtml"
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


<xsl:template match="h:section">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:header">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:footer">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:summary">
    <!-- TODO: Add space on the left and right of the text -->
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:article">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:nav"/> <!-- only keep the content -->

<xsl:template match="h:aside">
    <text:p text:style-name="Text_20_body">
        <draw:frame draw:style-name="Marginalia"
                    text:anchor-type="paragraph"
                    svg:width="8.5cm" style:rel-width="50%">
            <draw:text-box fo:min-height="0.5cm">
                <xsl:apply-templates/>
            </draw:text-box>
        </draw:frame>
    </text:p>
</xsl:template>

<xsl:template match="h:hgroup">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:time" mode="inparagraph">
    <xsl:apply-templates mode="inparagraph"/>
</xsl:template>
<xsl:template match="h:time"/>

<xsl:template match="h:mark" mode="inparagraph">
    <!-- TODO: make the text background color yellow -->
    <xsl:apply-templates mode="inparagraph"/>
</xsl:template>

<xsl:template match="h:canvas"/>

<!-- TODO: include the source ? -->
<xsl:template match="h:audio"/>
<xsl:template match="h:video"/>
<xsl:template match="h:source"/>

<!-- form elements -->
<xsl:template match="h:command|h:datalist|h:details|h:meter|h:output|h:progress|h:keygen"/>

<!-- TODO: make a frame around it -->
<xsl:template match="h:figure"/>
<xsl:template match="h:figcaption"/>

<xsl:template match="h:ruby|h:rt|h:rp" mode="inparagraph">
    <xsl:apply-templates mode="inparagraph"/>
</xsl:template>
<xsl:template match="h:ruby|h:rt|h:rp"/>

</xsl:stylesheet>
