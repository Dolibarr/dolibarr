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


<xsl:template match="h:div[@class='code']/h:pre">
    <text:p text:style-name="Source_20_Code">
        <xsl:apply-templates mode="inparagraph"/>
    </text:p>
    <!-- The Preformatted_20_Text style has a margin-bottom of 0, so we add an
         empty line here -->
    <text:p text:style-name="Text_20_body"/>
</xsl:template>

<!--
     Code highlighting
     This is incomplete, see http://pygments.org/docs/tokens/
-->

<xsl:template match="h:div[@class='code']/h:pre/h:span[@class]" mode="inparagraph">
    <xsl:choose>
        <!-- Keyword and Keyword.* -->
        <xsl:when test="substring(@class,1,1) = 'k'">
            <text:span text:style-name="strong">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Class -->
        <xsl:when test="@class = 'nc'">
            <text:span text:style-name="syntax-highlight.class">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Function -->
        <xsl:when test="@class = 'nf'">
            <text:span text:style-name="syntax-highlight.function">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Tag -->
        <xsl:when test="@class = 'nt'">
            <text:span text:style-name="syntax-highlight.tag">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Attribute -->
        <xsl:when test="@class = 'na'">
            <text:span text:style-name="syntax-highlight.attr">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Builtin -->
        <xsl:when test="@class = 'nb'">
            <text:span text:style-name="syntax-highlight.builtin">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Namespace -->
        <xsl:when test="@class = 'nn'">
            <text:span text:style-name="syntax-highlight.namespace">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Exception -->
        <xsl:when test="@class = 'ne'">
            <text:span text:style-name="syntax-highlight.exception">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Variable -->
        <xsl:when test="@class = 'nv'">
            <text:span text:style-name="syntax-highlight.var">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Name.Builtin.Pseudo -->
        <xsl:when test="@class = 'bp'">
            <text:span text:style-name="syntax-highlight.builtin.pseudo">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- String and String.* -->
        <xsl:when test="substring(@class,1,1) = 's'">
            <text:span text:style-name="syntax-highlight.string">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Number and Number.* -->
        <xsl:when test="substring(@class,1,1) = 'm'">
            <text:span text:style-name="syntax-highlight.number">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Operator and Operator.Word -->
        <xsl:when test="substring(@class,1,1) = 'o'">
            <text:span text:style-name="strong">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Punctuation -->
        <xsl:when test="@class = 'p'">
            <xsl:apply-templates mode="inparagraph"/>
        </xsl:when>
        <!-- Comment and Comment.* -->
        <xsl:when test="substring(@class,1,1) = 'c'">
            <text:span text:style-name="syntax-highlight.comment">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <!-- Generic.Error -->
        <xsl:when test="@class = 'err'">
            <text:span text:style-name="syntax-highlight.error">
                <xsl:apply-templates mode="inparagraph"/>
            </text:span>
        </xsl:when>
        <xsl:otherwise>
            <xsl:apply-templates mode="inparagraph"/>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

</xsl:stylesheet>
