#! /usr/bin/python
# -*- coding: utf-8 -*-
# © 2017 Alexis de Lattre <alexis.delattre@akretion.com>

from optparse import OptionParser
import sys
from facturx import get_facturx_xml_from_pdf
from facturx.facturx import logger
import logging
from os.path import isfile, isdir

__author__ = "Alexis de Lattre <alexis.delattre@akretion.com>"
__date__ = "August 2017"
__version__ = "0.1"

options = [
    {'names': ('-l', '--log-level'), 'dest': 'log_level',
        'action': 'store', 'default': 'info',
        'help': "Set log level. Possible values: debug, info, warn, error. "
        "Default value: info."},
    {'names': ('-d', '--disable-xsd-check'), 'dest': 'disable_xsd_check',
        'action': 'store_true', 'default': False,
        'help': "De-activate XML Schema Definition check on Factur-X XML file "
        "(the check is enabled by default)"},
    ]


def main(options, arguments):
    if options.log_level:
        log_level = options.log_level.lower()
        log_map = {
            'debug': logging.DEBUG,
            'info': logging.INFO,
            'warn': logging.WARN,
            'error': logging.ERROR,
        }
        if log_level in log_map:
            logger.setLevel(log_map[log_level])
        else:
            logger.error(
                'Wrong value for log level (%s). Possible values: %s',
                log_level, ', '.join(log_map.keys()))
            sys.exit(1)

    if len(arguments) != 2:
        logger.error(
            'This command requires 2 arguments (%d used). '
            'Use --help to get the details.', len(arguments))
        sys.exit(1)
    pdf_filename = arguments[0]
    out_xml_filename = arguments[1]
    if not isfile(pdf_filename):
        logger.error('Argument %s is not a filename', pdf_filename)
        sys.exit(1)
    if isdir(out_xml_filename):
        logger.error(
            '2nd argument %s is a directory name (should be a the '
            'output XML filename)', out_xml_filename)
        sys.exit(1)
    pdf_file = open(pdf_filename)
    check_xsd = True
    if options.disable_xsd_check:
        check_xsd = False
    # The important line of code is below !
    try:
        (xml_filename, xml_string) = get_facturx_xml_from_pdf(
            pdf_file, check_xsd=check_xsd)
    except Exception as e:
        logger.error(e)
        sys.exit(1)
    if xml_filename and xml_string:
        if isfile(out_xml_filename):
            logger.warn(
                'File %s already exists. Overwriting it!', out_xml_filename)
        xml_file = open(out_xml_filename, 'w')
        xml_file.write(xml_string)
        xml_file.close()
        logger.info('File %s generated', out_xml_filename)
    else:
        logger.warn('File %s has not been created', out_xml_filename)
        sys.exit(1)


if __name__ == '__main__':
    usage = "Usage: facturx-pdfextractxml <invoice.pdf> <factur-x_xml.xml>"
    epilog = "Author: %s\n\nVersion: %s" % (__author__, __version__)
    description = "This extracts the XML file from a Factur-X invoice."
    parser = OptionParser(usage=usage, epilog=epilog, description=description)
    for option in options:
        param = option['names']
        del option['names']
        parser.add_option(*param, **option)
    options, arguments = parser.parse_args()
    sys.argv[:] = arguments
    main(options, arguments)
    
    