pipeline {
    agent {
        kubernetes {
            // Define the pod template with containers and volumes
            podTemplate(
                label: 'docker-build',
                containers: [
                    containerTemplate(
                        name: 'docker',
                        image: 'docker:25',
                        ttyEnabled: true,
                        command: 'cat'
                    ),
                    containerTemplate(
                        name: 'git',
                        image: 'alpine/git',
                        ttyEnabled: true,
                        command: 'cat'
                    )
                ],
                volumes: [hostPathVolume(hostPath: '/var/run/docker.sock', mountPath: '/var/run/docker.sock')]
            )
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
