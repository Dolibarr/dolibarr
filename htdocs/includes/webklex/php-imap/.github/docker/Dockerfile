FROM ubuntu:latest
LABEL maintainer="Webklex <github@webklex.com>"

RUN apt-get update
RUN apt-get upgrade -y
RUN apt-get install -y sudo dovecot-imapd

ENV LIVE_MAILBOX=true
ENV LIVE_MAILBOX_HOST=mail.example.local
ENV LIVE_MAILBOX_PORT=993
ENV LIVE_MAILBOX_ENCRYPTION=ssl
ENV LIVE_MAILBOX_VALIDATE_CERT=true
ENV LIVE_MAILBOX_USERNAME=root@example.local
ENV LIVE_MAILBOX_PASSWORD=foobar
ENV LIVE_MAILBOX_QUOTA_SUPPORT=true

EXPOSE 993

ADD dovecot_setup.sh /root/dovecot_setup.sh
RUN chmod +x /root/dovecot_setup.sh

CMD ["/bin/bash", "-c", "/root/dovecot_setup.sh && tail -f /dev/null"]

