QODANA TUTO
-----------
This README explains how to use qodana to generate static analytics reports on the code
 
Install docker
 apt install docker
 
Install qodana into ~/.loca/bin/qodana
 curl -fsSL https://jb.gg/qodana-cli/install | bash
 
To run inspection on CLI
 cd ~/git/dirtoscan
 sudo qodana scan --show-report
 
