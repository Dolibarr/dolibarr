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
                    image: owasp/zap2docker-stable
                    command: ["sleep", "infinity"]  # Keep container running indefinitely
                    tty: true
                    volumeMounts:
                    - name: zap-workdir
                      mountPath: /zap/wrk
                  volumes:
                  - name: zap-workdir
                    emptyDir: {}  # Use an emptyDir volume (ephemeral storage)
            '''
        }
  }
  stages {
 stage('Security Testing with ZAP') {
            steps {
              container('zap'){
                script {
                        def targetUrl = "https://dolibarr.hbenaissa.uk"
                        
                        // Execute ZAP scan inside Kubernetes pod
                        sh "zap-baseline.py -t ${targetUrl} -x /zap/wrk/report.xml -I"
                        
                        // Copy ZAP report from pod to Jenkins workspace
                        sh "kubectl cp zap:/zap/wrk/report.xml ./report.xml"
                    }
              }
            }
        }
  }
}
