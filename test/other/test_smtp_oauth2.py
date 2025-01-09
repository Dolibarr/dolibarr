#!/usr/bin/env python3
import smtplib
import base64

# Information connection
smtp_server = "smtp.office365.com"
smtp_port = 587
access_token = "tokenhere"
email_from = "emailfrom@domain.com"
email_to = "test@example.com"
subject = "Test Email"
body = "This is a test email sent using OAuth2 and Office365."

# Prepare the token for authentication
auth_string = f"user={email_from}\1auth=Bearer {access_token}\1\1"
auth_string = base64.b64encode(auth_string.encode()).decode()

# Create connection SMTP
server = smtplib.SMTP(smtp_server, smtp_port)
server.ehlo()
server.starttls()
server.ehlo()

try:
	print (auth_string)
    response = server.docmd("AUTH", "XOAUTH2 " + auth_string)

    # Check authentication
    if response[0] != 235:
        raise Exception(f"Authentication fails : {response[1].decode()}")

except Exception as e:
    print (f"Error : {e}")

finally:
    server.quit()
