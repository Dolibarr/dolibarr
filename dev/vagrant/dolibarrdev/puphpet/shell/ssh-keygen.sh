#!/bin/bash

VAGRANT_CORE_FOLDER=$(cat "/.puphpet-stuff/vagrant-core-folder.txt")

VAGRANT_SSH_USERNAME=$(echo "$1")

if [[ ! -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa" ]]; then
    echo "Creating new SSH key at ${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa"
    ssh-keygen -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa" -P ""
fi

echo "Adding generated key to /root/.ssh/authorized_keys"
mkdir -p /root/.ssh
cat "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" > "/root/.ssh/authorized_keys"
chmod 600 "/root/.ssh/authorized_keys"

if [ "${VAGRANT_SSH_USERNAME}" != 'root' ]; then
    VAGRANT_SSH_FOLDER="/home/${VAGRANT_SSH_USERNAME}/.ssh";

    echo "Adding generated key to ${VAGRANT_SSH_FOLDER}/authorized_keys"
    cat "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" > "${VAGRANT_SSH_FOLDER}/authorized_keys"
    chown "${VAGRANT_SSH_USERNAME}" "${VAGRANT_SSH_FOLDER}/authorized_keys"
    chgrp "${VAGRANT_SSH_USERNAME}" "${VAGRANT_SSH_FOLDER}/authorized_keys"
    chmod 600 "${VAGRANT_SSH_FOLDER}/authorized_keys"
fi

passwd -d vagrant >/dev/null
