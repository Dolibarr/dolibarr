pipeline {
    agent any

    environment {
        // Define environment variables for Dockerfile and docker-compose file paths
        dockerfilePath = 'build/docker/Dockerfile'
        dockerComposeFilePath = 'build/docker/docker-compose.yml'
    }

    stages {
        stage('Checkout Source Code') {
            steps {
                // Clone the Dolibarr repository from GitHub
                git credentialsId: '10', url: 'https://github.com/iyedben/Dolibarr.git'
            }
        }
        stage('Build Docker Image') {
            steps {
                // Build the Docker image using Dockerfile
                script {
                    docker.build("-f ${dockerfilePath} -t dolibarr-app .")
                }
            }
        } 
    }
}
