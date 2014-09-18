#!bash
#
# bash completion support for Git subtree.
#
# To use this routine:
#
#    1) Make sure you have installed and configured the core Git completion script, which is required to make this script work;
#    2) Copy this file to somewhere (e.g. ~/.git-subtree-completion.sh);
#    3) Added the following line to your .bashrc:
#        source ~/.git-subtree-completion.sh
#

_git_subtree ()
{
        local cur="${COMP_WORDS[COMP_CWORD]}"

        if [ $COMP_CWORD -eq 2 ]; then
                __gitcomp "add merge pull push split"
                return
        elif [ $COMP_CWORD -eq 3 ]; then
                __gitcomp "--prefix="
                return
        fi
        __gitcomp "$(__git_remotes)"
}
