Clases de documentos existentes:

* ModelePDFFactures - Únicamente tiene una función para listar los modelos de documentos. Extiende la CommonDocGenerator
* ModelePDFAskPriceSupplier
* ModeleChequeReceipts
* ModelePDFCommandes
* ModelePDFContract
* ModeleDon
* ModelePdfExpedition
* ModeleExpenseReport
* ModelePDFFicheinter
* ModelePDFDeliveryOrder
* ModelePDFProjects
* ModelePDFTask
* ModelePDFPropales
* ModeleThirdPartyDoc
* ModelePDFSuppliersInvoices
* ModelePDFSUppliersOrders


CommonDocGenerator - Contiene funciones sobre la substitución de parámetros (probablemente usado para las plantillas ODT) y la función printRect ¿?



Modelos de documento PDF y ODT <--- ModelePDFXX <---- CommonDocGenerator

Funciones comunes:

* pdf_getFormat
* pdf_getInstance
* pdf_getPDFFont
* pdf_getPDFFontSize
* pdf_getHeightForLogo
* pdfBuildThirdpartyName = Construye el nombre del tercero (va separado de la dirección)
* pdf_build_address = Construye la dirección
* pdf_pagehead
* pdf_watermark
* pdf_bank
* pdf_pagefoot
* pdf_writeLinkedObjects
* pdf_writelinedesc
* pdf_getlinedesc
* pdf_getlinenum
* pdf_getlineref
* pdf_getlineref_supplier
* pdf_getlinevatrate
* pdf_getlineupexcltax
* pdf_getlineupwithtax
* pdf_getlineqty
* pdf_getlineqty_asked
* pdf_getlineqty_shipped
* pdf_getlineqty_keeptoship
* pdf_getlineunit
* pdf_getlineremisepercent
* pdf_getlineprogress
* pdf_getlinetotalexcltax
* pdf_getlinetotalwithtax
* pdf_getTotalQty
* pdf_getLinkedObjects
* pdf_getSizeForImage

Propuesta:

* DocumentModel = contendrá la definición de todos los parámetros comunes a ambos tipos de documento
* PdfDocumentModel = contendrá las funciones de parseo específicas para Pdf
* TextDocumentModel = contendrá las funciones de parseo específicas para formatos de texto (HTML, TXT y ODT)

Modelos ODT <--- TextDocumentModel <--- DocumentModel

Modelos PDF <--- PdfDocumentModel <--- DocumentModel