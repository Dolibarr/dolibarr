pipeline {
    agent {
        kubernetes {
            defaultContainer 'jnlp'
            yaml """
                apiVersion: v1
                kind: Pod
                spec:
                  containers:
                  - name: docker
                    image: docker:25
                    tty: true
                  - name: git
                    image: alpine/git
                    tty: true
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

     /*   stage('Build Docker Image') {
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
        }*/
    }
}
