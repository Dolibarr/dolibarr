pipeline {
    agent any

    environment {
        // Define environment variables for Dockerfile and docker-compose file paths
        dockerfilePath = 'build/docker/Dockerfile'
        dockerComposeFilePath = 'build/docker/docker-compose.yml'
    }

    stages {
    /*    stage('Set GitHub Credentials') {
            steps {
                // Configure GitHub credentials with your Personal Access Token
                withCredentials([usernamePassword(credentialsId: 'YOUR_CREDENTIALS_ID', usernameVariable: 'GIT_USERNAME', passwordVariable: 'GIT_PASSWORD')]) {
                    // Set git config with token
                    sh 'git config --global credential.helper "store --file ~/.git-credentials"'
                    sh 'echo "https://${GIT_USERNAME}:${GIT_PASSWORD}@github.com" > ~/.git-credentials'
                }
            }
        }
        stage('Clone Repository') {
            steps {
                // Clone the Dolibarr repository from GitHub
                git url: 'https://github.com/iyedben/Dolibarr.git'
            }
        }*/
        stage('Build Docker Image') {
            steps {
                // Build the Docker image using Dockerfile
                script {
                    docker.build('-f ${dockerfilePath} -t dolibarr-app .')
                }
            }
        }
        stage('Run Tests') {
            steps {
                // Run tests (replace with your actual test commands)
                sh 'echo "Running tests..."'
            }
        }
        stage('Deploy') {
            steps {
                // Deploy the application stack using docker-compose
                script {
                    // Pull the latest Docker image
                    docker.image('dolibarr-app').pull()

                    // Start the application stack using docker-compose
                    sh "docker-compose -f ${dockerComposeFilePath} up -d"
                }
            }
        }
    }
}
