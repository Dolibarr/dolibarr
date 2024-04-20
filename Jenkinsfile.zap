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
                  - name: zap
                    image: softwaresecurityproject/zap-stable
                    command:
                    - cat
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
    stage('Checkout') {
      steps {
        // Checkout your source code
        checkout scm
     }
  }
        stage('Pipeline Info') {
            steps {
                script {
                    echo "<--Parameter Initialization-->"
                    echo """
                    The current parameters are:
                        Scan Type: ${params.SCAN_TYPE}
                        Target: ${params.TARGET}
                        Generate report: ${params.GENERATE_REPORT}
                    """
                }
            }
        }

        stage('Prepare wrk directory') {
            when {
                expression {
                    params.GENERATE_REPORT == true
                }
            }
            steps {
                container('zap') {
                    script {
                        sh """
                            mkdir -p /zap/wrk
                        """
                    }
                }
            }
        }

        stage('Scanning target on owasp container') {
            steps {
                container('zap') {
                    script {
                        def scan_type = "${params.SCAN_TYPE}"
                        def target = "${params.TARGET}"
                        echo "----> Scan Type: ${scan_type}, Target: ${target}"

                        // Validate target URL protocol
                        if (!(target.startsWith("http://") || target.startsWith("https://"))) {
                            error "Invalid target URL format. Target URL must start with 'http://' or 'https://'."
                        }

                        // Execute ZAP scan based on scan type
                        switch (scan_type) {
                            case 'Baseline':
                                sh """
                                    zap-baseline.py -t ${target} -r report.html -I
                                """
                                break
                            case 'APIS':
                                sh """
                                    zap-api-scan.py -t ${target} -f openapi -r report.html -I
                                """
                                break
                            case 'Full':
                                sh """
                                    zap-full-scan.py -t ${target} -r report.html -I
                                """
                                break
                            default:
                                error "Invalid scan type"
                        }
                    }
                }
            }
        }

        stage('Copy Report to Workspace') {
            steps {
                    container('zap') {
                        script {
                        sh """
                            cp /zap/wrk/report.html  -v ${WORKSPACE}/report.html
                        """
                        archiveArtifacts artifacts: '*.html'
                    }
                }
            }
        }      
    }
}

