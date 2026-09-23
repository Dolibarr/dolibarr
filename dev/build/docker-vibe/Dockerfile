# Docker file to build a docker env to use to run AI agent in a confined env.

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
		git \
        graphviz \
        gzip \
		hurl \
        memcached \
		mysql-client \
		openssh-client \
		phpunit \
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
		python3 \
		python3-pip \
		shellcheck \
		sshpass \
		time \
		unzip \
		w3m \
		zip \
        zstd \
    && rm -rf /var/lib/apt/lists/*

RUN curl -LsSf https://astral.sh/uv/install.sh | UV_INSTALL_DIR=/usr/local/bin sh

RUN mkdir -p /opt/vibe \
    && uv venv /opt/vibe \
    && uv pip install --python /opt/vibe/bin/python mistral-vibe

ENV PATH="/opt/vibe/bin:$PATH"


# Install RTK - Rust Token Killer
RUN curl -fsSL https://raw.githubusercontent.com/rtk-ai/rtk/master/install.sh | sh \
    && cp /root/.local/bin/rtk /usr/local/bin/rtk \
    && chmod +x /usr/local/bin/rtk \
    && rtk --version \
    && rtk gain



RUN mkdir -p /opt/php-tools \
	&& composer require \
    --working-dir=/opt/php-tools \
    phan/phan \
    phpstan/phpstan:^2.2 \
	squizlabs/php_codesniffer \
    --ignore-platform-reqs \
	&& ln -s /opt/php-tools/vendor/bin/phan /usr/local/bin/phan \
	&& ln -s /opt/php-tools/vendor/bin/phpstan /usr/local/bin/phpstan \
	&& ln -s /opt/php-tools/vendor/bin/phpcs /usr/local/bin/phpcs \
	&& ln -s /opt/php-tools/vendor/bin/phpcbf /usr/local/bin/phpc


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
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
