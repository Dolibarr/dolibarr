pipeline {
    agent {
        kubernetes {
            // Define the pod template with containers and volumes
            yaml """
apiVersion: v1
kind: Pod
metadata:
  labels:
    app: docker-build
spec:
  containers:
  - name: docker
    image: docker:25
    command:
    - cat
    tty: true
    volumeMounts:
    - mountPath: /var/run/docker.sock
      name: docker-socket
  - name: git
    image: alpine/git
    command:
    - cat
    tty: true
  volumes:
  - name: docker-socket
    hostPath:
      path: /var/run/docker.sock
"""
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
                script {
                    // Build the Docker image
                    def appImage = docker.build("iyedbnaissa/dolibarr_app:${env.BUILD_NUMBER}", "-f build/docker/Dockerfile .")

                    // Push the Docker image to your Docker registry
                    docker.withRegistry('', '20') {
                        appImage.push()
                    }
                }
            }
        }
    }
}
