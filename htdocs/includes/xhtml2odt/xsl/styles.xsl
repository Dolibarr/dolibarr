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
    exclude-result-prefixes="office xsl dc text style table draw fo xlink meta number svg chart dr3d math form script dom xforms xsd xsi presentation h"
    version="1.0">

<!-- SETTINGS -->
<xsl:decimal-format name="staff" digit="D" />
<xsl:output method="xml" indent="yes" omit-xml-declaration="no" encoding="utf-8"/>
<!--<xsl:strip-space elements="*"/>-->
<!--<xsl:preserve-space elements=""/>-->


<xsl:include href="param.xsl"/>
<xsl:include href="styles/automatic-styles.xsl"/>
<xsl:include href="styles/main-styles.xsl"/>
<xsl:include href="styles/fonts.xsl"/>
<xsl:include href="styles/highlight.xsl"/>
<xsl:include href="styles/inline.xsl"/>


<xsl:template match="/">
    <xsl:apply-templates/>
</xsl:template>


<!-- process automatic styles -->
<xsl:template match="office:automatic-styles">
    <office:automatic-styles>
        <!-- copy the existing styles -->
        <xsl:for-each select="child::*">
            <xsl:copy>
                <xsl:copy-of select="@*"/>
                <xsl:apply-templates/>
            </xsl:copy>
        </xsl:for-each>
        <!-- add missing styles -->
        <xsl:call-template name="autostyles"/>
        <!-- add missing syntax highlighting styles -->
        <xsl:call-template name="highlight"/>
        <!-- add missing inline styles -->
        <xsl:call-template name="inline"/>
    </office:automatic-styles>
</xsl:template>

<!-- process main styles -->
<xsl:template match="office:styles">
    <office:styles>
        <!-- copy the existing styles -->
        <xsl:for-each select="child::*">
            <xsl:copy>
                <xsl:copy-of select="@*"/>
                <xsl:apply-templates/>
            </xsl:copy>
        </xsl:for-each>
        <!-- add missing styles -->
        <xsl:call-template name="mainstyles"/>
    </office:styles>
</xsl:template>

<!-- process font declarations -->
<xsl:template match="office:font-face-decls">
    <office:font-face-decls>
        <!-- copy the existing fonts -->
        <xsl:for-each select="child::*">
            <xsl:copy>
                <xsl:copy-of select="@*"/>
                <xsl:apply-templates/>
            </xsl:copy>
        </xsl:for-each>
        <!-- add missing fonts -->
        <xsl:call-template name="fonts"/>
    </office:font-face-decls>
</xsl:template>

<!-- Convert the <span> tags which have inline CSS properties in the style
     attribute. See the styles/inline.xsl stylesheet for details -->
<xsl:template match="h:span[@style]">
    <text:span>
        <xsl:attribute name="text:style-name">
            <xsl:text>inline-style.</xsl:text>
            <xsl:value-of select="generate-id(.)"/>
        </xsl:attribute>
        <xsl:apply-templates/>
    </text:span>
</xsl:template>

<!-- Leave alone unknown tags -->
<xsl:template match="*">
    <xsl:copy>
        <xsl:copy-of select="@*"/>
        <xsl:apply-templates/>
    </xsl:copy>
</xsl:template>

</xsl:stylesheet>
