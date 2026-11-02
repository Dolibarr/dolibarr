# Docker file to build a docker env to use to run the AI agent Claude Code in a confined env.

FROM ubuntu:26.04

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
		bash \
		bash-completion \
		ca-certificates \
		composer \
        vim \
		wget \
		curl \
        dialog \
		gh \
		ghostscript \
		git \
		golang-go \
        graphviz \
        gzip \
		hurl \
		iptables \
		jq \
		libglib2.0-bin \
        memcached \
		mariadb-client \
		mariadb-server \
		openssh-client \
		php \
		php-ast \
		php-curl \
		php-mysql \
		php-mbstring \
		php-xml \
		php-gd \
		php-zip \
		php-intl \
		php-soap \
		php-ldap \
		php-imagick \
		php-cli \
		pgloader \
		python3 \
		python3-pip \
		shellcheck \
		sshpass \
		sudo \
		time \
		unzip \
		w3m \
		wl-clipboard \
		xclip \
		zip \
        zstd \
    && rm -rf /var/lib/apt/lists/*

RUN curl -LsSf https://astral.sh/uv/install.sh | UV_INSTALL_DIR=/usr/local/bin sh

# Install Claude Code with the native installer. The installer puts the binary
# into the home of the user running it (root here), so it is copied into
# /usr/local/bin to be available for the user created by the entrypoint.
# The auto-updater is disabled: the binary is not writable by the user. To get
# a new version of Claude Code, rebuild the image with --no-cache.
# libglib2.0-bin provides gdbus, used by open-on-host to reach the
# xdg-desktop-portal of the host session. xclip and wl-clipboard are used by
# Claude Code to read the clipboard of the host (paste of images). See README.md.
RUN curl -fsSL https://claude.ai/install.sh | bash \
    && install -m 755 "$(readlink -f /root/.local/bin/claude)" /usr/local/bin/claude \
    && rm -rf /root/.local/bin/claude /root/.local/share/claude \
    && claude --version

ENV DISABLE_AUTOUPDATER=1

# Claude Code opens the links (login, links of the chat) with the program
# declared in BROWSER, or with xdg-open. open-on-host forwards it to the
# xdg-desktop-portal of the host session over the D-Bus session bus, so the
# host opens the URL in its default browser (see open-on-host and README.md).
ENV BROWSER="/usr/local/bin/open-on-host"


# Install RTK - Rust Token Killer
RUN curl -fsSL https://raw.githubusercontent.com/rtk-ai/rtk/master/install.sh | sh \
    && cp /root/.local/bin/rtk /usr/local/bin/rtk \
    && chmod +x /usr/local/bin/rtk \
    && rtk --version \
    && rtk gain


# Install tx - Transifex CLI, used to push/pull the language files (configuration is the file .tx/config at repository root)
RUN ARCH="$(dpkg --print-architecture)" \
    && curl -fsSL "https://github.com/transifex/cli/releases/latest/download/tx-linux-${ARCH}.tar.gz" | tar xz -C /usr/local/bin tx \
    && chmod 755 /usr/local/bin/tx \
    && tx --version



RUN mkdir -p /opt/php-tools \
	&& composer require \
    --working-dir=/opt/php-tools \
	phpunit/phpunit ^9 \
    phan/phan \
    phpstan/phpstan:^2.2 \
	squizlabs/php_codesniffer \
    --ignore-platform-reqs \
	&& ln -s /opt/php-tools/vendor/bin/phpunit /usr/local/bin/phpunit \
	&& ln -s /opt/php-tools/vendor/bin/phan /usr/local/bin/phan \
	&& ln -s /opt/php-tools/vendor/bin/phpstan /usr/local/bin/phpstan \
	&& ln -s /opt/php-tools/vendor/bin/phpcs /usr/local/bin/phpcs \
	&& ln -s /opt/php-tools/vendor/bin/phpcbf /usr/local/bin/phpcbf


# Install pre-commit - tool to manage git hooks (see https://pre-commit.com and dev/setup/pre-commit/README.md)
# Hooks configuration for Dolibarr is the file .pre-commit-config.yaml at repository root.
RUN uv venv /opt/pre-commit \
    && uv pip install --python /opt/pre-commit/bin/python pre-commit \
    && ln -s /opt/pre-commit/bin/pre-commit /usr/local/bin/pre-commit \
    && pre-commit --version


# Preload the pre-commit hooks environments of the repository into the image,
# so the first run into the container does not have to download them.
# PRE_COMMIT_HOME must be a stable path: the environments contain absolute paths and are not relocatable.
# The .pre-commit-config.yaml is added into the build context by claudes.sh (COPY is skipped if absent).
ENV PRE_COMMIT_HOME=/var/cache/pre-commit

COPY .pre-commit-config.yaml* /tmp/pre-commit-warmup/
RUN if [ -f /tmp/pre-commit-warmup/.pre-commit-config.yaml ]; then \
        git init -q /tmp/pre-commit-warmup \
        && cd /tmp/pre-commit-warmup \
        && for i in 1 2 3; do pre-commit install-hooks && break; [ "$i" = 3 ] && exit 1; echo "pre-commit install-hooks failed, retry $i..."; sleep 10; done \
        && cd / \
        && rm -rf /tmp/pre-commit-warmup \
        && chmod -R a+rwX /var/cache/pre-commit; \
    fi


# Guarantee that no email can be delivered from inside the container (see README.md):
# no MTA (sendmail/postfix/exim/...) is installed. The binary /usr/sbin/sendmail is a stub
# that stores the emails it receives into /var/mail/outbox instead of delivering them, so
# the code and the tests that send emails (the Dolibarr send mode "PHP mail function") run
# without error but no email ever leaves the container.
RUN cat > /usr/sbin/sendmail <<'EOF'
#!/bin/sh
# Sendmail stub installed by the docker-claude image (see dev/build/docker-vibe/README.md).
# No MTA is installed in this image: emails received here are stored into /var/mail/outbox
# and are never delivered anywhere.

umask 000

outbox=/var/mail/outbox
mkdir -p "$outbox" 2>/dev/null || exit 0

file="$outbox/$(date +%Y%m%d_%H%M%S%N)_$$.eml"
{
    echo "X-SendmailStub-Args: $*"
    echo
    cat
} > "$file" 2>/dev/null

exit 0
EOF

RUN chmod 755 /usr/sbin/sendmail \
    && mkdir -p /var/mail/outbox \
    && chmod 1777 /var/mail/outbox \
    && for dir in /etc/php/*/cli/conf.d /etc/php/*/apache2/conf.d /etc/php/*/fpm/conf.d; do \
        if [ -d "$dir" ]; then \
            printf '%s\n' \
                '; Emails sent with mail() are stored by the sendmail stub into /var/mail/outbox, never delivered (see dev/build/docker-vibe/README.md)' \
                'sendmail_path = /usr/sbin/sendmail -t -i' \
                > "$dir/99-sendmail-stub.ini"; \
        fi; \
    done \
    && test ! -e /usr/lib/sendmail \
    && grep -q "X-SendmailStub-Args" /usr/sbin/sendmail \
    && echo "Subject: build test of the docker-claude image" | /usr/sbin/sendmail -t \
    && php -r 'if (!mail("buildtest@localhost", "Build test", "Body of the build test")) { fwrite(STDERR, "mail() failed.\n"); exit(1); }' \
    && test "$(ls -1 /var/mail/outbox | wc -l)" -eq 2 \
    && rm -f /var/mail/outbox/*.eml


# Prompt
RUN cat >> /etc/bash.bashrc <<'EOF'

git_branch() {
    git branch --show-current 2>/dev/null
}

alias egrep='egrep --color=auto'
alias fgrep='fgrep --color=auto'
alias grep='grep --color=auto'
alias l='ls -CF'
alias la='ls -A'
alias ll='ls -alF'
alias ls='ls --color=auto'

export HISTSIZE=10000
export HISTFILESIZE=20000

shopt -s histappend
PROMPT_COMMAND="history -a; history -n${PROMPT_COMMAND:+;$PROMPT_COMMAND}"

PS1='\[\e[1;31m\][CONTAINER]\[\e[0m\] \[\e[1;34m\]\w\[\e[0m\] \[\e[1;32m\]$(git_branch)\[\e[0m\] \$ '
EOF

WORKDIR /workspace

# Add the launcher
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
# Add the URL opener used by the BROWSER environment variable (see above)
# (shared with docker-vibe, added into the build context by claudes.sh)
COPY open-on-host /usr/local/bin/open-on-host
# Add the xdg-open shim: xdg-utils is not installed into the image, the shim
# forwards the URL to open-on-host, so a link opened with xdg-open is opened on the host.
COPY xdg-open-shim /usr/local/bin/xdg-open
# Add the tool used by the launcher to block the outbound SMTP ports on the network stack
# shared with the VM (see docker-entrypoint.sh and README.md)
COPY smtpblock.sh /usr/local/bin/smtpblock.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/open-on-host /usr/local/bin/xdg-open /usr/local/bin/smtpblock.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
