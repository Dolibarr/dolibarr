#!/bin/bash

VAGRANT_CORE_FOLDER=$(cat '/.puphpet-stuff/vagrant-core-folder.txt')

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
VAGRANT_SSH_USERNAME=$(echo "$1")

if [[ ! -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa" ]]; then
    ssh-keygen -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa" -P ""

    if [[ ! -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.ppk" ]]; then
        if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
            apt-get install -y putty-tools >/dev/null
        elif [ "${OS}" == 'centos' ]; then
            yum -y install putty >/dev/null
        fi

        puttygen "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa" -O private -o "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.ppk"
    fi

    echo 'Your private key for SSH-based authentication have been saved to "puphpet/files/dot/ssh/"!'
else
    echo 'Using pre-existing private key at "puphpet/files/dot/ssh/id_rsa"'
fi

echo 'Adding generated key to /root/.ssh/authorized_keys'
mkdir -p /root/.ssh
cat "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" > '/root/.ssh/authorized_keys'
chmod 600 '/root/.ssh/authorized_keys'

if [ "${VAGRANT_SSH_USERNAME}" != 'root' ]; then
    VAGRANT_SSH_FOLDER="/home/${VAGRANT_SSH_USERNAME}/.ssh";

    echo "Adding generated key to ${VAGRANT_SSH_FOLDER}/authorized_keys"
    cat "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" > "${VAGRANT_SSH_FOLDER}/authorized_keys"
    chown "${VAGRANT_SSH_USERNAME}" "${VAGRANT_SSH_FOLDER}/authorized_keys"
    chgrp "${VAGRANT_SSH_USERNAME}" "${VAGRANT_SSH_FOLDER}/authorized_keys"
    chmod 600 "${VAGRANT_SSH_FOLDER}/authorized_keys"
fi

passwd -d vagrant >/dev/null
