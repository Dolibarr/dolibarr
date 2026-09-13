# Docker file to build a docker env to use to run AI agent in a confined env.

FROM ubuntu:26.04

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
		bash-completion \
        vim \
		wget \
		curl \
		phpunit \
		php \
		python3 \
        ca-certificates \
        git \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace

# Prompt
RUN cat >> /etc/bash.bashrc <<'EOF'

git_branch() {
    git branch --show-current 2>/dev/null
}

PS1='\[\e[1;31m\][CONTAINER]\[\e[0m\] \[\e[1;34m\]\w\[\e[0m\] \[\e[1;32m\]$(git_branch)\[\e[0m\] \$ '
EOF

# Add the launcher
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
