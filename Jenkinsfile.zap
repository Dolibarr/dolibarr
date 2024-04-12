pipeline {
    agent {
        kubernetes {
            yaml '''
                apiVersion: v1
                kind: Pod
                metadata:
                  name: zap-pod
                spec:
                  containers:
                  - name: owasp
                    image: owasp/zap2docker-stable
                    command:
                    - sleep
                    - infinity
                    tty: true
                    volumeMounts:
                    - name: zap-workdir
                      mountPath: /zap/wrk
                  volumes:
                  - name: zap-workdir
                    emptyDir: {}
            '''
        }
    }
    
    parameters {
        choice(
            choices: ["Baseline", "APIS", "Full"],
            description: 'Type of scan that is going to be performed',
            name: 'SCAN_TYPE'
        )
        string(
            defaultValue: "http://dolibarr.dolibarr.svc.hbenaissa.local",
            description: 'Target URL to scan (must start with http:// or https://)',
            name: 'TARGET'
        )
        booleanParam(
            defaultValue: true,
            description: 'Generate report?',
            name: 'GENERATE_REPORT'
        )
    }

    stages {
        

    post {
        always {
            container('owasp') {
                script {
                    echo "Removing container"
                    sh """
                    kubectl delete pod zap-pod
                """
                }
            }
        }
    }
}
}
