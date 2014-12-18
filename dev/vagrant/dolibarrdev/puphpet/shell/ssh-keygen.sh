#!/bin/bash

VAGRANT_CORE_FOLDER=$(cat '/.puphpet-stuff/vagrant-core-folder.txt')

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
VAGRANT_SSH_USERNAME=$(echo "$1")

function create_key()
{
    BASE_KEY_NAME=$(echo "$1")

    if [[ ! -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/${BASE_KEY_NAME}" ]]; then
        ssh-keygen -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/${BASE_KEY_NAME}" -P ""

        if [[ ! -f "${VAGRANT_CORE_FOLDER}/files/dot/ssh/${BASE_KEY_NAME}.ppk" ]]; then
            if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
                apt-get install -y putty-tools >/dev/null
            elif [ "${OS}" == 'centos' ]; then
                yum -y install putty >/dev/null
            fi

            puttygen "${VAGRANT_CORE_FOLDER}/files/dot/ssh/${BASE_KEY_NAME}" -O private -o "${VAGRANT_CORE_FOLDER}/files/dot/ssh/${BASE_KEY_NAME}.ppk"
        fi

        echo "Your private key for SSH-based authentication has been saved to 'puphpet/files/dot/ssh/${BASE_KEY_NAME}'!"
    else
        echo "Pre-existing private key found at 'puphpet/files/dot/ssh/${BASE_KEY_NAME}'"
    fi
}

create_key 'root_id_rsa'
create_key 'id_rsa'

PUBLIC_SSH_KEY=$(cat "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub")

echo 'Adding generated key to /root/.ssh/id_rsa'
echo 'Adding generated key to /root/.ssh/id_rsa.pub'
echo 'Adding generated key to /root/.ssh/authorized_keys'

mkdir -p /root/.ssh

cp "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa" '/root/.ssh/'
cp "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" '/root/.ssh/'

if [[ ! -f '/root/.ssh/authorized_keys' ]] || ! grep -q "${PUBLIC_SSH_KEY}" '/root/.ssh/authorized_keys'; then
    cat "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" >> '/root/.ssh/authorized_keys'
fi

chown -R root '/root/.ssh'
chgrp -R root '/root/.ssh'
chmod 700 '/root/.ssh'
chmod 644 '/root/.ssh/id_rsa.pub'
chmod 600 '/root/.ssh/id_rsa'
chmod 600 '/root/.ssh/authorized_keys'

if [ "${VAGRANT_SSH_USERNAME}" != 'root' ]; then
    VAGRANT_SSH_FOLDER="/home/${VAGRANT_SSH_USERNAME}/.ssh";

    mkdir -p "${VAGRANT_SSH_FOLDER}"

    echo "Adding generated key to ${VAGRANT_SSH_FOLDER}/id_rsa"
    echo "Adding generated key to ${VAGRANT_SSH_FOLDER}/id_rsa.pub"
    echo "Adding generated key to ${VAGRANT_SSH_FOLDER}/authorized_keys"

    cp "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa" "${VAGRANT_SSH_FOLDER}/id_rsa"
    cp "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" "${VAGRANT_SSH_FOLDER}/id_rsa.pub"

    if [[ ! -f "${VAGRANT_SSH_FOLDER}/authorized_keys" ]] || ! grep -q "${PUBLIC_SSH_KEY}" "${VAGRANT_SSH_FOLDER}/authorized_keys"; then
        cat "${VAGRANT_CORE_FOLDER}/files/dot/ssh/id_rsa.pub" >> "${VAGRANT_SSH_FOLDER}/authorized_keys"
    fi

    chown -R "${VAGRANT_SSH_USERNAME}" "${VAGRANT_SSH_FOLDER}"
    chgrp -R "${VAGRANT_SSH_USERNAME}" "${VAGRANT_SSH_FOLDER}"
    chmod 700 "${VAGRANT_SSH_FOLDER}"
    chmod 644 "${VAGRANT_SSH_FOLDER}/id_rsa.pub"
    chmod 600 "${VAGRANT_SSH_FOLDER}/id_rsa"
    chmod 600 "${VAGRANT_SSH_FOLDER}/authorized_keys"

    passwd -d "${VAGRANT_SSH_USERNAME}" >/dev/null
fi
