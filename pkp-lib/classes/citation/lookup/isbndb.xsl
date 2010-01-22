<?xml version="1.0"?>
<!--
  * isbndb.xsl
  *
  * Copyright (c) 2000-2010 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Crosswalk from ISBNdb API XML to PKP citation elements
  *
  * $Id$
  -->

<xsl:transform version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	exclude-result-prefixes="xsl">

<xsl:output omit-xml-declaration='yes'/>

<xsl:strip-space elements="*"/>

<!--============================================
	START TRANSFORMATION AT THE ROOT NODE
==============================================-->
<xsl:template match="/">
	<citation>
		<xsl:apply-templates select="ISBNdb/BookList/BookData/*"/>
	</citation>
</xsl:template>

<!-- Book title -->
<xsl:template match="TitleLong">
	<bookTitle>
		<xsl:choose>
			<xsl:when test=". != ''">
				<xsl:value-of select="."/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="../Title"/>
			</xsl:otherwise>
		</xsl:choose>
	</bookTitle>
</xsl:template>

<!-- Authors -->
<xsl:template match="Authors">
	<xsl:for-each select="Person">
		<author><xsl:value-of select="."/></author>
	</xsl:for-each>
</xsl:template>

<!-- Publisher & Location -->
<xsl:template match="PublisherText">
	<place-publisher><xsl:value-of select="."/></place-publisher>

	<!-- also possible year in Details/@edition_info -->	
	<issuedDate><xsl:value-of select="."/></issuedDate>
</xsl:template>

<!-- possible edition in Details/@edition_info -->

<!-- Ignore everything else -->
<xsl:template match="*"/>

</xsl:transform>
