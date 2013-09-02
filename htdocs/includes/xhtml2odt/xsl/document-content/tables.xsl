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


<xsl:template name="table.number">
    <!-- compute number of section -->
    <xsl:value-of select="count(preceding::h:table)+1"/>
</xsl:template>

<xsl:template match="h:table">
    <table:table table:style-name="table-default">
        <table:table-column>
            <xsl:attribute name="table:number-columns-repeated">
                <xsl:value-of select="count(descendant::h:tr[1]/h:th|descendant::h:tr[1]/h:td)"/>
            </xsl:attribute>
        </table:table-column>
        <!--<xsl:attribute name="table:name"></xsl:attribute>-->
        <xsl:apply-templates/>
        <xsl:apply-templates select="h:tfoot/*"/>
    </table:table>
    <xsl:if test="h:caption">
        <xsl:variable name="number">
            <xsl:call-template name="table.number"/>
        </xsl:variable>
        <text:p text:style-name="Caption">
            <xsl:text>Table </xsl:text>
            <text:sequence text:ref-name="refTable0" text:name="Table"
                           text:formula="ooow:Table+1" style:num-format="1">
                <xsl:value-of select="$number"/>
            </text:sequence>
            <xsl:text>: </xsl:text><xsl:value-of select="h:caption"/>
        </text:p>
    </xsl:if>
</xsl:template>

<xsl:template match="h:table/h:caption"/>

<xsl:template match="h:thead">
    <!-- <table:table-header-rows> handled in <th> -->
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:tfoot">
    <!-- handled above in h:table -->
</xsl:template>

<xsl:template match="h:tbody">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="h:tr">
    <xsl:choose>
        <!-- this is header -->
        <xsl:when test="h:th">
            <table:table-header-rows>
                <xsl:call-template name="make-table-row"/>
            </table:table-header-rows>
        </xsl:when>
        <xsl:otherwise>
            <xsl:call-template name="make-table-row"/>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template name="make-table-row">
    <table:table-row>
        <!-- fill covered-table-cells for rowspans on the first column -->
        <xsl:for-each select="preceding-sibling::h:tr/*[position() = 1 and @rowspan != 1]">
            <xsl:call-template name="make-rowspan-covered-table-cell">
                <xsl:with-param name="vertical-position">
                    <xsl:value-of select="count(preceding-sibling::h:tr) + 1"/>
                </xsl:with-param>
            </xsl:call-template>
        </xsl:for-each>
        <!-- do the cell handling now -->
        <xsl:apply-templates/>
    </table:table-row>
</xsl:template>

<xsl:template match="h:th">
    <xsl:call-template name="table-cell">
        <xsl:with-param name="horizontal-position" select="count(preceding-sibling::*) + 1"/>
        <xsl:with-param name="horizontal-count" select="count(../*)"/>
        <xsl:with-param name="vertical-position" select="count(../preceding-sibling::h:tr) + 1"/>
        <xsl:with-param name="vertical-count" select="count(ancestor::h:table[1]/descendant::h:tr)"/>
    </xsl:call-template>
</xsl:template>

<xsl:template match="h:td">
    <xsl:call-template name="table-cell">
        <xsl:with-param name="horizontal-position" select="count(preceding-sibling::*) + 1"/>
        <xsl:with-param name="horizontal-count" select="count(../*)"/>
        <xsl:with-param name="vertical-position" select="count(../preceding-sibling::h:tr)
                                                       + count(ancestor::h:table[1]/descendant::h:thead/h:tr)
                                                       + 1"/>
        <xsl:with-param name="vertical-count" select="count(ancestor::h:table[1]/descendant::h:tr)"/>
    </xsl:call-template>
</xsl:template>


<!-- table cell -->

<xsl:template name="table-cell">

    <xsl:param name="horizontal-position" />
    <xsl:param name="horizontal-count" />
    <xsl:param name="vertical-position" />
    <xsl:param name="vertical-count" />

    <xsl:comment>horizontal-position=<xsl:value-of select="$horizontal-position"/></xsl:comment>
    <xsl:comment>horizontal-count=<xsl:value-of select="$horizontal-count"/></xsl:comment>
    <xsl:comment>vertical-position=<xsl:value-of select="$vertical-position"/></xsl:comment>
    <xsl:comment>vertical-count=<xsl:value-of select="$vertical-count"/></xsl:comment>

    <table:table-cell office:value-type="string">

        <xsl:attribute name="table:style-name">
            <xsl:text>table-default.cell-</xsl:text>
            <!-- prefix -->
            <xsl:if test="self::h:th and $vertical-position = 1">
                <xsl:text>H-</xsl:text>
            </xsl:if>
            <xsl:if test="parent::h:tr/parent::h:tfoot">
                <xsl:text>F-</xsl:text>
            </xsl:if>
            <!-- postfix defined by cell position -->
            <!--
                __________
                |A1|B1|C1|
                |A2|B2|C2|
                |A3|B3|C3|
                ^^^^^^^^^^
                __________
                |A4|B4|C4|
                ^^^^^^^^^^
                ____
                |C1|
                |C2|
                |C3|
                ^^^^
            -->
            <xsl:choose>

                <!-- single -->
                <xsl:when test="$horizontal-count = 1 and $vertical-count = 1">
                    <xsl:text>single</xsl:text>
                </xsl:when>

                <!-- A4 -->
                <xsl:when test="$horizontal-position = 1 and $vertical-count = 1">
                    <xsl:text>A4</xsl:text>
                </xsl:when>
                <!-- C4 -->
                <xsl:when test="$horizontal-position = $horizontal-count and $vertical-count = 1">
                    <xsl:text>C4</xsl:text>
                </xsl:when>
                <!-- B4 -->
                <xsl:when test="$vertical-count = 1">
                    <xsl:text>B4</xsl:text>
                </xsl:when>

                <!-- tfoot A -->
                <xsl:when test="ancestor::h:tfoot and $horizontal-position = 1">
                    <xsl:text>A3</xsl:text>
                </xsl:when>
                <!-- tfoot B -->
                <xsl:when test="ancestor::h:tfoot and $horizontal-position = $horizontal-count">
                    <xsl:text>C3</xsl:text>
                </xsl:when>
                <!-- tfoot C -->
                <xsl:when test="ancestor::h:tfoot">
                    <xsl:text>B3</xsl:text>
                </xsl:when>

                <!-- A3 -->
                <xsl:when test="$horizontal-position = 1 and $horizontal-count != 1 and $vertical-position = $vertical-count">
                    <xsl:text>A3</xsl:text>
                </xsl:when>
                <!-- C3 -->
                <xsl:when test="$horizontal-position = $horizontal-count and $vertical-position = $vertical-count">
                    <xsl:text>C3</xsl:text>
                </xsl:when>
                <!-- B3 -->
                <xsl:when test="$vertical-position = $vertical-count">
                    <xsl:text>B3</xsl:text>
                </xsl:when>

                <!-- A1 -->
                <xsl:when test="$horizontal-position = 1 and $horizontal-position != $horizontal-count and $vertical-position = 1">
                    <xsl:text>A1</xsl:text>
                </xsl:when>
                <!-- C1 -->
                <xsl:when test="$horizontal-position = $horizontal-count and $vertical-position = 1">
                    <xsl:text>C1</xsl:text>
                </xsl:when>
                <!-- B1 -->
                <xsl:when test="$vertical-position = 1">
                    <xsl:text>B1</xsl:text>
                </xsl:when>

                <!-- A2 -->
                <xsl:when test="$horizontal-position = 1 and $horizontal-position != $horizontal-count">
                    <xsl:text>A2</xsl:text>
                </xsl:when>
                <!-- C2 -->
                <xsl:when test="$horizontal-position = $horizontal-count">
                    <xsl:text>C2</xsl:text>
                </xsl:when>

                <!-- all other cells -->
                <xsl:otherwise>
                    <xsl:text>B2</xsl:text>
                </xsl:otherwise>

            </xsl:choose>

        </xsl:attribute>

        <xsl:if test="@colspan and @colspan != 1">
            <xsl:attribute name="table:number-columns-spanned">
                <xsl:value-of select="@colspan"/>
            </xsl:attribute>
        </xsl:if>

        <xsl:if test="@rowspan and @rowspan != 1">
            <xsl:attribute name="table:number-rows-spanned">
                <xsl:value-of select="@rowspan"/>
            </xsl:attribute>
        </xsl:if>

        <!-- Content -->
        <xsl:choose>
            <xsl:when test="h:table"> <!-- nested tables -->
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:call-template name="paragraph"/>
            </xsl:otherwise>
        </xsl:choose>

    </table:table-cell>

    <!-- fill covered-table-cells for colspans -->
    <xsl:if test="@colspan and @colspan != 1">
        <xsl:call-template name="make-covered-table-cell">
            <xsl:with-param name="num">
                <xsl:value-of select="@colspan"/>
            </xsl:with-param>
        </xsl:call-template>
    </xsl:if>

    <!-- fill covered-table-cells for rowspans -->
    <xsl:for-each select="../preceding-sibling::h:tr/*[position() = $horizontal-position + 1 and @rowspan != 1]">
        <xsl:call-template name="make-rowspan-covered-table-cell">
            <xsl:with-param name="vertical-position">
                <xsl:value-of select="$vertical-position"/>
            </xsl:with-param>
        </xsl:call-template>
    </xsl:for-each>

</xsl:template>


<xsl:template name="make-covered-table-cell">
    <xsl:param name="num"/>
    <xsl:if test="$num > 1">
        <table:covered-table-cell/>
        <xsl:call-template name="make-covered-table-cell">
            <xsl:with-param name="num">
                <xsl:value-of select="$num - 1"/>
            </xsl:with-param>
        </xsl:call-template>
    </xsl:if>
</xsl:template>

<xsl:template name="make-rowspan-covered-table-cell">
    <xsl:param name="vertical-position"/>
    <xsl:variable name="spanned-vertical-position" select="count(../preceding-sibling::h:tr) + 1"/>
    <xsl:if test="$spanned-vertical-position + @rowspan - 1 >= $vertical-position">
        <table:covered-table-cell/>
    </xsl:if>
</xsl:template>


</xsl:stylesheet>
