<?xml version="1.0"?>
<!--
  * worldcat.xsl
  *
  * Copyright (c) 2003-2009 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Crosswalk from MARC21XML to PKP Citation elements
  *
  * Based on mappings by Raymond Yee:
  *	http://www.raymondyee.net/wiki/MarcXmlToOpenUrlCrosswalk
  *
  * $Id$
  -->

<xsl:transform version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:marc="http://www.loc.gov/MARC21/slim"
	exclude-result-prefixes="xsl marc">

<xsl:output omit-xml-declaration='yes'/>

<xsl:strip-space elements="*"/>

<!--============================================
	START TRANSFORMATION AT THE ROOT NODE
==============================================-->
<xsl:template match="/marc:record">
	<citation>
		<xsl:apply-templates/>
	</citation>
</xsl:template>

<!-- Authors/Contributors -->
<xsl:template match="marc:datafield[@tag='100' or @tag='700']">
	<author>
		<xsl:value-of select="marc:subfield[@code='a']"/>
		<xsl:if test="marc:subfield[@code='q']">
			<xsl:value-of select="marc:subfield[@code='q']"/>
		</xsl:if>
	</author>
</xsl:template>

<!-- Book title -->
<xsl:template match="marc:datafield[@tag='245'][1]">
	<bookTitle><xsl:value-of select="marc:subfield[@code='a']"/><xsl:text> </xsl:text><xsl:value-of select="marc:subfield[@code='b']"/></bookTitle>
</xsl:template>

<!-- Edition -->
<xsl:template match="marc:datafield[@tag='250'][1]">
	<edition><xsl:value-of select="marc:subfield[@code='a']"/></edition>
</xsl:template>

<!-- Publisher & Location -->
<xsl:template match="marc:datafield[@tag='260'][1]">
	<place><xsl:value-of select="marc:subfield[@code='a']"/></place>
	<publisher><xsl:value-of select="marc:subfield[@code='b']"/></publisher>
	<issuedDate><xsl:value-of select="marc:subfield[@code='c']"/></issuedDate>
</xsl:template>

<!-- ISBN not reliable, use xISBN service-->
<xsl:template match="marc:datafield[@tag='020'][1]">
	<isbn><xsl:value-of select="marc:subfield[@code='a']"/></isbn>
</xsl:template>

<!-- Journal information is in datafield 773 -->

<!-- Ignore everything else -->
<xsl:template match="*"/>

</xsl:transform>