# Docker file to build a docker env to use to run AI agent in a confined env.

FROM ubuntu:26.04

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
		bash \
		bash-completion \
        vim \
		wget \
		curl \
        dialog \
		hurl \
		phpunit \
		php \
		python3 \
		python3-pip \
        ca-certificates \
        git \
    && rm -rf /var/lib/apt/lists/*

# Install RTK - Rust Token Killer
RUN curl -fsSL https://raw.githubusercontent.com/rtk-ai/rtk/master/install.sh | sh \
    && cp /root/.local/bin/rtk /usr/local/bin/rtk \
    && chmod +x /usr/local/bin/rtk \
    && rtk --version \
    && rtk gain

WORKDIR /workspace

RUN curl -LsSf https://astral.sh/uv/install.sh | UV_INSTALL_DIR=/usr/local/bin sh

RUN mkdir -p /opt/vibe \
    && uv venv /opt/vibe \
    && uv pip install --python /opt/vibe/bin/python mistral-vibe

ENV PATH="/opt/vibe/bin:$PATH"


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

PS1='\[\e[1;31m\][CONTAINER]\[\e[0m\] \[\e[1;34m\]\w\[\e[0m\] \[\e[1;32m\]$(git_branch)\[\e[0m\] \$ '
EOF

# Add the launcher
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
