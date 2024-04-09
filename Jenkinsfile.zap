pipeline {
  agent {
    kubernetes {
      yaml '''
        apiVersion: v1
        kind: Pod
        spec:
          containers:
          - name: zap
            image: owasp/zap2docker-stable
            command:
            - cat
            tty: true
          - name: docker
            image: docker:latest
            command:
            - cat
            tty: true
            volumeMounts:
             - mountPath: /var/run/docker.sock
               name: docker-sock
          volumes:
          - name: docker-sock
            hostPath:
              path: /var/run/docker.sock    
        '''
    }
  }
  stages {
 stage('Security Testing with ZAP') {
            steps {
              container('zap'){
                script {
                        def targetUrl = "https://dolibarr.hbenaissa.uk/"
                        
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
