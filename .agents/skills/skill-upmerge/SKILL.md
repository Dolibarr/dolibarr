---
name: skill-upmerge
description:
  Forward-port (upmerge) Dolibarr maintenance branches in cascade (n into n+1, up to develop) with dev/pullmerge.sh, and resolve merge conflicts without committing.
  Use when asked to do an upmerge, a forward port, a cascade merge, or to run pullmerge.sh.
license: MIT
user-invocable: true
---

# Upmerge / forward porting of Dolibarr branches

## Layout

Each maintenance branch has its own local clone, in the same parent directory:
`dolibarr_14.0`, `dolibarr_15.0`, ..., `dolibarr_24.0` (one directory = one branch `xx.0`),
plus `dolibarr_dev` for the branch `develop`.

The script `dolibarr_dev/dev/pullmerge.sh` must be run from this parent directory
(it uses relative paths `dolibarr_xx.0`).

## Procedure

1. Run the script, from the parent directory, starting from the requested version (default 14):

   ```bash
   ./dolibarr_dev/dev/pullmerge.sh all 14
   ```

   - `all`: pull the last version of each branch (each directory), then merge each version n into n+1, then the last one into `develop`.
   - `pull` or `merge` alone can be used. `merge 16` restarts the cascade from 16 into 17.
   - On a successful merge, the script **pushes automatically**. This is expected behaviour.

2. As soon as the script fails, go into the directory of the branch where the merge failed
   (the one named in `ERROR : Conflict or error on merge into dolibarr_xx.0`) and try to resolve the conflicts:
   - `git status` / `git diff` to list conflicted files.
   - Understand the intent of the change coming from the previous branch (often a fix, sometimes a security fix)
     and keep it, while keeping the changes specific to the current branch (refactoring such as `$user->hasRight()`,
     new hooks, new variables...).
   - Also check files that git merged automatically when the fix touches the same logic
     (for example: no duplicated or misplaced `restrictedArea()` call left before the object fetch).
   - Check there is no conflict marker left and run `php -l` on each resolved file.

3. **Do not commit**, do not `git add`, do not push. Just report:
   - that the conflict has been resolved, with a short summary per file of the choice made,
   - or that the resolution of a conflict is a problem (ambiguous intent, functional choice needed...), and why.

4. Once the user has committed and pushed the resolution, restart the cascade from that version:

   ```bash
   ./dolibarr_dev/dev/pullmerge.sh merge <version where it failed>
   ```

   Before restarting, check with `git status` / `git log` in the directory that the merge commit exists and was pushed.
   Then repeat step 2 on the next failure, until the script ends with `Success !`.
