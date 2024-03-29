pipeline {
  agent {
    kubernetes {
      yaml '''
        apiVersion: v1
        kind: Pod
        spec:
          containers:
          - name: git
            image: alpine/git:latest
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
    stage('Checkout') {
      steps {
        // Checkout your source code
        checkout scm
     }
  }
  stage('Build Docker Image') {
      steps {
        container('docker') {
          script {
            // Build the Docker image
            def appImage = docker.build("iyedbnaissa/dolibarr_build:${env.BUILD_NUMBER}", "--no-cache", "-f Dockerfile .")
            // Push the Docker image to your Docker registry
            docker.withRegistry('', '30') {
              appImage.push()
            }
          }
        }
      }
    }
  }
}
