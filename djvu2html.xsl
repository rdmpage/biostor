<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>

<xsl:output method='html' encoding='utf-8' indent='yes' />

<xsl:param name="imageUrl" />
<xsl:param name="widthpx" />

<xsl:variable name="scale" select="$widthpx div //OBJECT/@width" />

<xsl:template match="/">
	<xsl:apply-templates select="//OBJECT" />
</xsl:template>

<xsl:template match="//OBJECT">
	<div>
	
		<xsl:attribute name="id">
			<xsl:text>page</xsl:text>
		</xsl:attribute>

		<xsl:attribute name="style">
			<xsl:text>position:relative;</xsl:text>
			<xsl:text>border:1px solid rgb(228,228,228);</xsl:text>
			<xsl:variable name="height" select="@height" />
			<xsl:variable name="width" select="@width" />
			<xsl:text>width:</xsl:text><xsl:value-of select="$width * $scale" /><xsl:text>px;</xsl:text>
			<xsl:text>height:</xsl:text><xsl:value-of select="$height * $scale" /><xsl:text>px;</xsl:text>
		</xsl:attribute>

		<xsl:comment>Scanned image</xsl:comment>
		<img>
			<xsl:attribute name="src">
				<xsl:value-of select="$imageUrl"></xsl:value-of>
			</xsl:attribute> 
			<xsl:attribute name="style">
				<xsl:variable name="height" select="@height" />
				<xsl:variable name="width" select="@width" />
				<xsl:text>margin:0px;padding:0px;</xsl:text>
				<xsl:text>width:</xsl:text><xsl:value-of select="$width * $scale" /><xsl:text>px;</xsl:text>
				<xsl:text>height:</xsl:text><xsl:value-of select="$height * $scale" /><xsl:text>px;</xsl:text>
				<xsl:text>box-shadow:2px 2px 2px #ccc;</xsl:text>
				<xsl:text>border:1px solid #ccc;</xsl:text>
				<!-- prevent user dragging image -->
				<xsl:text>-webkit-user-drag: none;-webkit-user-select: none;</xsl:text>
			</xsl:attribute>
		</img>

		<xsl:apply-templates select="//WORD" />

	</div>
</xsl:template>

<xsl:template match="//WORD">
	<div>
	
		<xsl:attribute name="class">
			<xsl:text>ocrx_word</xsl:text>
		</xsl:attribute>
		
		<xsl:attribute name="id">
			<xsl:value-of select="position()"/>
		</xsl:attribute>

		<xsl:attribute name="style">
			<xsl:text>position:absolute;</xsl:text>
			<!--<xsl:text>border:1px solid rgb(128,128,128);</xsl:text>-->
			<xsl:variable name="coords" select="@coords" />
			<xsl:variable name="minx" select="substring-before($coords,',')" />
			<xsl:variable name="afterminx" select="substring-after($coords,',')" />
			<xsl:variable name="maxy" select="substring-before($afterminx,',')" />
			<xsl:variable name="aftermaxy" select="substring-after($afterminx,',')" />
			<xsl:variable name="maxx" select="substring-before($aftermaxy,',')" />
			<xsl:variable name="aftermaxx" select="substring-after($aftermaxy,',')" />
			<xsl:variable name="miny" select="substring-after($aftermaxy,',')" />
		
			<!-- some DjVu files have five coordinates, some have four (sigh) -->
			<xsl:choose>
				<xsl:when test="contains($miny,',')">
					<!-- five numbers in coords -->
					<xsl:variable name="miny2" select="substring-before($miny,',')" />
					<xsl:text>top:</xsl:text><xsl:value-of select="$miny2 * $scale" /><xsl:text>px;</xsl:text>
					<xsl:text>height:</xsl:text><xsl:value-of select="($maxy - $miny2) * $scale" /><xsl:text>px;</xsl:text>				
				</xsl:when>
				<xsl:otherwise>
					<!-- four -->
					<xsl:text>top:</xsl:text><xsl:value-of select="$miny * $scale" /><xsl:text>px;</xsl:text>
					<xsl:text>height:</xsl:text><xsl:value-of select="($maxy - $miny) * $scale" /><xsl:text>px;</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		
			<xsl:text>left:</xsl:text><xsl:value-of select="$minx * $scale" /><xsl:text>px;</xsl:text>
			<xsl:text>width:</xsl:text><xsl:value-of select="($maxx - $minx) * $scale" /><xsl:text>px;</xsl:text>
			<!--
			<xsl:text>top:</xsl:text><xsl:value-of select="$miny * $scale" /><xsl:text>px;</xsl:text>
			<xsl:text>height:</xsl:text><xsl:value-of select="($maxy - $miny) * $scale" /><xsl:text>px;</xsl:text>
			-->
		
			<!-- http://stackoverflow.com/a/10835846  -->
			<xsl:text>color: rgba(0, 0, 0, 0);</xsl:text> 
	
			<!-- ignore text which flows outside of box -->
			<xsl:text>overflow:hidden;</xsl:text>
		
		</xsl:attribute>
		
		<xsl:value-of select="." />

	</div>
</xsl:template>


</xsl:stylesheet>