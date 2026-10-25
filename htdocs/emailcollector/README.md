EMailCollector
==============

This module provides a scheduled job that scan regularly one or several IMAP email boxes, with filtering rules, to automatically record data in your application, like
* recording the email in the history of events (event is automatically linked to its related objects if possible, for example when a customer reply to an email sent from the application, the answer is automatically linked to the good objects)
* and/or creating a lead
