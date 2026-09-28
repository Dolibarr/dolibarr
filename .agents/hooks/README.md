Example of file ~/.vibe/hooks/rtk-rewrite.sh to replace into the ~/.vibe/hooks.toml the line
command = "rtk hook vibe" 
with
command = "/home/mylogin/.vibe/hooks/rtk-rewrite.sh"

#!/usr/bin/env bash 
# Wrapper for "rtk hook vibe": rtk only rewrites commands when 
# tool_name == "bash". The Unified Harness exposes the bash tool as 
# "file_system.bash", so normalize the tool name before forwarding 
# the hook payload. 
python3 -c ' 
import json, sys 
d = json.load(sys.stdin) 
if d.get("tool_name") == "file_system.bash": 
    d["tool_name"] = "bash" 
sys.stdout.write(json.dumps(d)) 
' | rtk hook vibe
