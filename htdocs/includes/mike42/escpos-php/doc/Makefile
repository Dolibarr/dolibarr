html: escpos.doxyfile
	# Compile
	doxygen escpos.doxyfile
	# Filter out warnings on README.md- doxygen cannot handle image links that
	# are used there: [![Caption](https://example.com/target)](https://example.com/image)
	sed -i '/README.md\:/d' warnings.log
	# Show warnings log
	cat warnings.log
	# Return failure if there were any doc warnings
	[ ! -s warnings.log ]

latex: html
	# Do nothing

xml: html
	xsltproc xml/combine.xslt xml/index.xml > all.xml

clean:
	rm --preserve-root -Rf html latex xml doxygen_sqlite3.db all.xml warnings.log

