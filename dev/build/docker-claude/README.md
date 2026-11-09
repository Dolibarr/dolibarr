# How to run Claude Code into a container.

This is the equivalent of `dev/build/docker-vibe` (Mistral Vibe) for Claude Code.
The image provides the same tools (PHP, phpunit, phpstan, phan, phpcs, hurl, gh, tx, pre-commit, rtk, mariadb client, ...)
and the same protections (no email can be sent from the container). See `dev/build/docker-vibe/README.md` for the details
of the common parts. The scripts `open-on-host`, `xdg-open-shim` and `smtpblock.sh` are shared with docker-vibe: they are
taken from the directory `dev/build/docker-vibe` when the image is built.

## Credentials

The configuration directory `~/.claude` of the host is mounted at the same path into the container, so the container uses
the same credentials (`~/.claude/.credentials.json`, created by a `claude` login on the host), settings, plugins, memory and
sessions as the host.

If you never logged in on the host, run `claude` once on the host to log in, or log in from inside the container (the login
URL is opened in the browser of the host, see "Clipboard and links" into `dev/build/docker-vibe/README.md`).

You can also use an API key or a long lived token: when the environment variable `ANTHROPIC_API_KEY` or `CLAUDE_CODE_OAUTH_TOKEN`
(created with `claude setup-token`) is set on the host, it is passed to the container.

The state file of Claude Code (onboarding, trusted directories, MCP servers, ...) is a single file `~/.claude.json` that is replaced
at each write, so it can not be bind mounted. Into the container, `~/.claude.json` is a link to `~/.claude/.claude.json` (into the
mounted directory), and `claudes.sh` copies the `~/.claude.json` of the host into `~/.claude/.claude.json` at each launch: the
container gets the MCP servers, the trusted directories and the theme of the host, but the changes made into the container
(for example a MCP server added with `claude mcp add`) are lost at the next launch. Add them on the host instead.

## You can add an alias into your /etc/bash.bashrc or ~/.bashrc the line
````
alias claudes='dev/build/docker-claude/claudes.sh'
````

## Run Claude Code into a container
Run from the root of your repository:
````
dev/build/docker-claude/claudes.sh
````

This script will build the docker image, and then run it with the current directory mounted into the container and launch `claude`.

Claude is launched by default without the option `--dangerously-skip-permissions` (mode `--no-yolo`), so permissions are asked.
If you want to run claude with this option (the container being the sandbox), you can run
````
claudes --yolo
````

When you exit claude, the container is stopped too, so you return immediately to your host.
If you want to stay into the container with a bash shell after claude has ended, you can run
````
claudes --no-exit
````

If you want to run claude in Remote Control mode (`claude remote-control`, to drive the session from claude.ai/code
or the Claude mobile app), you can run (add `--yolo` to have the permissions automatically accepted
with `--permission-mode bypassPermissions`)
````
claudes --rc
````

The other arguments are passed to claude, for example to resume the last conversation:
````
claudes --continue
````

Into the container, the links `.claude` (to `.agents`) and `CLAUDE.md` (to `.agents/AGENTS.md`) are created at the root of the
repository when they do not exist, so Claude Code uses the instructions, skills and agents shared by all the AI agents.

### Build (or rebuild) image of the container.
Claude Code is installed into the image when it is built, and its auto-updater is disabled. To get a new version of Claude Code, rebuild the image with:
````
claudes --no-cache
````

### Send emails
The outbound SMTP ports are blocked like for docker-vibe. To start a container that is allowed to send emails, run it with:
````
DOCKER_ALLOW_SMTP=1 claudes
````
