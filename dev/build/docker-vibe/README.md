# How to run an IA Agent into a container.

## Prepare api key
With the payment mode of Mistral, the credential is stored into the OS system and not into the .vibe/.env directory so is not available into another docker container.
So you must first get it from your OS system keystore with:
````
secret-tool search --all xdg:schema org.freedesktop.Secret.Generic
````
And then copy the value in entry "secret" for section "MISTRAL_API_KEY" into the .vibe/.env file
````
MISTRAL_API_KEY='<your_api_key>'
````
and/or for Eurouter 

````
EUROUTER_API_KEY='<your_api_key>'
````


So now when running the container, the .vibe/.env file has your paid key that will be used to set the environment variable MISTRAL_API_KEY.

## You can add an alias into your /etc/bash.bashrc or ~/.bashrc the line
````
alias vibes='dev/build/docker-vibe/vibes.sh'
````
 

## Run vibe into a container
````
alias vibes='dev/build/docker-vibe/vibes.sh'
or
dev/build/docker-vibe/vibes.sh
````


This script will build the docker image, and then run it with the current directory mounted into the container and launch vibe.

Vibe is launched with the option `--yolo` by default. If you want to run vibe without this option, you can run
````
vibes --no-yolo
````

When you exit vibe (for example with CTRL+C), the container is stopped too, so you return immediately to your host.
If you want to stay into the container with a bash shell after vibe has ended, you can run
````
vibes --no-exit
````

### Build (or rebuild) image of the container.
The build context is created on the fly to include the `.pre-commit-config.yaml` of the repository (see the pre-commit section), so build with the same command as `vibes.sh`:
````
tar -cf - -C dev/build/docker-vibe . -C "$PWD" .pre-commit-config.yaml | sudo docker build -t docker-vibe --no-cache -
````

### Run image
GIT_DIR=`basename $PWD` sudo docker run --rm -it -e HOST_UID="$(id -u)" -e HOST_GID="$(id -g)" -e HOST_USER="$(id -un)" --network=host --cap-add=NET_ADMIN -v "$HOME/git/test:/test" -v "$HOME/.vibe:/home/$(id -un)/.vibe" --mount type=bind,src="$HOME/git/$GIT_DIR",dst=/$GIT_DIR -w /$GIT_DIR dockervibe bash

## Clipboard and links work with the desktop of the host

Two features of Vibe need to reach the desktop of the host, and a container cannot do it alone. The image and `vibes.sh` provide what is needed:

- **Copy to clipboard**: Vibe copies first through `pyperclip` (installed into the image), that uses `xclip` (installed into the image) to talk to the X server of the host, reached through the socket `/tmp/.X11-unix` mounted by `vibes.sh`. When this native copy works, Vibe verifies it by reading the clipboard back (the message "Selection copied to clipboard" without a hint is then displayed). `wl-clipboard` is also installed for the Wayland native equivalent (`wl-copy`, `wl-paste`).
- **Open links**: the links of the chat are opened by the program declared in the `BROWSER` environment variable of the image: `open-on-host`. It forwards the URL to the xdg-desktop-portal of the host session over the D-Bus session bus (socket into `XDG_RUNTIME_DIR`, mounted by `vibes.sh`), and the host opens the URL in its default browser. The container itself contains no browser.

It works out of the box when the container is started with `vibes.sh` from a terminal of the graphical session of the host (GNOME on Wayland or X11): `vibes.sh` passes the environment (`DISPLAY`, `WAYLAND_DISPLAY`, `XDG_SESSION_TYPE`, `XDG_RUNTIME_DIR`, `XAUTHORITY`) and mounts the host sockets (`/tmp/.X11-unix`, `XDG_RUNTIME_DIR`). Outside a graphical session (SSH, console), nothing is added and Vibe falls back to its own mechanisms: the OSC 52 escape sequence for the clipboard, that the terminals based on VTE (GNOME Terminal, Tilix) do not support, and the selection of the terminal itself (hold Shift while selecting, paste with the mouse wheel).

Security note: mounting `XDG_RUNTIME_DIR` gives the container an access to the session bus of the host, so to the same services as the applications of your session. It is consistent with the rest of this setup (the container already runs with `--network=host` and your repository mounted), but keep it in mind.

If the clipboard still does not work:
- Check that `DISPLAY` is set on the host (`echo $DISPLAY`). On a Wayland session, GNOME runs XWayland, so `DISPLAY` is normally defined and `xclip` works through it.
- If the copy fails with an authorization error, allow your own user to connect to the X server from the container with `xhost +si:localuser:$(id -un)` on the host (the connections from the container come with your UID).
- The links can also be opened with Shift+clic: it is then the terminal itself that opens them (OSC 8 hyperlinks), which bypasses the container.

## pre-commit
The tool `pre-commit` is installed into the image (see https://pre-commit.com).
Hooks for the Dolibarr repository are defined into the file `.pre-commit-config.yaml` at the repository root.
To activate the hooks on your commits, run into the container, once:
````
pre-commit install
````

The hooks environments are preloaded into the image when it is built (they are the downloads you see at the first use of pre-commit elsewhere).
They are stored into the directory `/var/cache/pre-commit`, declared with `PRE_COMMIT_HOME` in the Dockerfile, and are already present at the first run into the container.
If the `.pre-commit-config.yaml` of the repository changes after the image was built (new revision of a hook), only the changed hooks are downloaded at the first run.

## No email can be delivered

No MTA is installed into the image (no postfix, no exim, no sendmail daemon, ...). The binary `/usr/sbin/sendmail` is a stub that stores the emails it receives into the directory `/var/mail/outbox/` instead of delivering them:
- Emails sent with the PHP function `mail()` (the Dolibarr send mode "PHP mail function") succeed from the point of view of the code, but are only stored: inspect them with `ls /var/mail/outbox/`.
- The build fails if a package installs a real MTA (it checks that `/usr/sbin/sendmail` is still the stub).

Known limits (this is a protection against accidental emails, not a security sandbox):
- Only the sendmail path is intercepted into the image itself. The Dolibarr send modes "SMTP socket" and "SwiftMailer" (see `htdocs/core/class/smtps.class.php`) use the network: they are closed by the block of the outbound SMTP ports of the VM, installed at each container start (see next section).
- The emails sent through HTTP APIs (SendGrid, Mailjet, ...) on the port 443 are not concerned: they are indistinguishable from any other HTTPS call.

### Block the outbound SMTP ports of the VM

The containers are run with `--network=host`, so their network connections are the ones of the machine that runs them: the image can not block them itself, it must be done at the level of the machine (the VM) with iptables. The script `smtpblock.sh` does it. It is installed into the image (`/usr/local/bin/smtpblock.sh`) and called by `docker-entrypoint.sh` at the start of each container, so the protection is always active and nothing has to be remembered:

````
sudo dev/build/docker-vibe/smtpblock.sh            Install the block (also done automatically at each container start)
sudo dev/build/docker-vibe/smtpblock.sh --status   Show the state
sudo dev/build/docker-vibe/smtpblock.sh --remove   Remove the block
````

The script rejects the connections to the ports 25, 465 and 587 for the whole VM (IPv4 and IPv6), inserted at the top of the OUTPUT chain so they take precedence over any existing ACCEPT rule. The connections fail immediately (REJECT) instead of hanging.

Requirements and notes:
- The container must be run with the capability `--cap-add=NET_ADMIN` (done by `vibes.sh`), otherwise the block can not be installed and the container refuses to start. To start a container that is allowed to send emails (for example to test the SMTP features of Dolibarr), run it with `-e VIBE_ALLOW_SMTP=1`.
- The block stays on the VM when the container stops (it is reinstalled at the next start anyway). Use `--remove` to remove it.
- The rules are not persistent: they are lost at the next reboot of the VM (and reinstalled at the next container start). To have them permanent, save them with `iptables-persistent` (`sudo apt-get install iptables-persistent` then `sudo netfilter-persistent save`).
- The block applies to all the processes of the VM, not only to the containers: an MTA running on the VM can no longer deliver its queue either, and the local submission to a MTA of the VM through the port 25 is also blocked (this is one of the paths to close).
