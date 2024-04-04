pipeline {
  agent {
    kubernetes {
      yaml '''
        apiVersion: v1
        kind: Pod
        spec:
          containers:
          - name: sonar-scanner
            image: sonarsource/sonar-scanner-cli:latest
            command:
            - cat
            tty: true
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
  stage ('SonarQube scan'){
     steps {
        container('sonar-scanner') {
           sh 'sonar-scanner'
        }
    }
  }
  stage('Build Docker Image') {
      steps {
        container('docker') {
          script {
            // Build the Docker image
            def appImage = docker.build("iyedbnaissa/dolibarr_build:${env.BUILD_NUMBER}", "-f Dockerfile .")
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
