The hook "rtk-rewrite" declared into ~/.vibe/hooks.toml rewrites the bash commands of the agent
through the rtk proxy to save tokens (rtk is installed into the image, see
dev/build/docker-vibe/Dockerfile).

rtk only rewrites the commands when the field tool_name of the hook payload is "bash", but the
Unified Harness exposes the bash tool as "file_system.bash": the hook must call the wrapper
~/.vibe/hooks/rtk-rewrite.sh instead of "rtk hook vibe" directly. Into ~/.vibe/hooks.toml, replace
the line
command = "rtk hook vibe"
with
command = "/home/mylogin/.vibe/hooks/rtk-rewrite.sh"

Content of the wrapper ~/.vibe/hooks/rtk-rewrite.sh:

#!/usr/bin/env bash
# Wrapper for "rtk hook vibe": rtk only rewrites commands when
# tool_name == "bash". The Unified Harness exposes the bash tool as
# "file_system.bash", so normalize the tool name before forwarding
# the hook payload.
# rtk absent: pass through (exit 0, no output). hooks.toml uses strict = true,
# so a failing wrapper would deny every bash call.

if ! command -v rtk >/dev/null 2>&1; then
    exit 0
fi

python3 -c '
import json, sys
d = json.load(sys.stdin)
if d.get("tool_name") == "file_system.bash":
    d["tool_name"] = "bash"
sys.stdout.write(json.dumps(d))
' | rtk hook vibe
