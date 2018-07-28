html: escpos.doxyfile
	# Compile
	doxygen escpos.doxyfile
	# Show warnings log
	cat warnings.log
	# Return failure if there were doc warnings
	[ ! -s warnings.log ]

latex: html
	# Do nothing

xml: html
	xsltproc xml/combine.xslt xml/index.xml > all.xml

clean:
	rm --preserve-root -Rf html latex xml doxygen_sqlite3.db all.xml warnings.log

