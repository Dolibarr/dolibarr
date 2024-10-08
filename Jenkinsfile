pipeline {
  agent {
    kubernetes {
      yaml '''
        apiVersion: v1
        kind: Pod
        spec:
          containers:
          - name: trivy
            image: aquasec/trivy:canary
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

   
  stage('Build Docker Image') {
      steps {
        container('docker') {
          script {
            // Build the Docker image
            docker.build("iyedbnaissa/dolibarr_build:${env.BUILD_NUMBER}", "-f Dockerfile .")
          }
        }
      }
    }
    stage('trivy scan'){
      steps{
          container('trivy'){
            sh "trivy image iyedbnaissa/dolibarr_build:${env.BUILD_NUMBER} --severity HIGH,CRITICAL --format template --template '@sonarqube.tpl' -o trivy_report.json --scanners vuln"
            sh "cat trivy_report.json"
          }
      }
    }

    stage('SonarQube Analysis') {
      environment {
             SONAR_SCANNER_OPTS = " -Xmx1024m"
      }
      steps {
        script {
          def scannerHome = tool 'SonarQube_Scanner';
          // Execute SonarQube analysis
          withSonarQubeEnv('SonarQube_Server') {
           
            sh "${scannerHome}/bin/sonar-scanner -Dproject.settings=sonar-project.properties"
          }
        }
      }
    }
    
    stage('push'){
      steps{
        container('docker'){
          script{
           // Push the Docker image to your Docker registry
              docker.withRegistry('', '30') {
                docker.image("iyedbnaissa/dolibarr_build:${env.BUILD_NUMBER}").push()
              }
          }
        }
      }
    }
  }
}
