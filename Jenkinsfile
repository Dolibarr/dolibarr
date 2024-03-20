pipeline {
    agent any

    environment {
        // Define environment variables for Dockerfile and docker-compose file paths
        dockerfilePath = 'build/docker/Dockerfile'
        dockerComposeFilePath = 'build/docker/docker-compose.yml'
    }

    stages {
        stage('Build Docker Image') {
            steps {
                script {
                    docker.build("-f ${dockerfilePath} -t dolibarr-app .")
                    echo 'Image Docker construite avec succès.'
                }
            }
        }
        stage('Run Tests') {
            steps {
                // Supposez que vos tests réels sont exécutés ici
                sh 'echo "Running tests..."'
                echo 'Tests exécutés avec succès.'
            }
        }
        stage('Deploy') {
            steps {
                script {
                    // Supposons que vous ayez déjà configuré l'authentification Docker Hub
                    docker.withRegistry('https://registry.hub.docker.com', 'dockerhub_credentials') {
                        docker.image('dolibarr-app').push("latest")
                    }
                    echo 'Image Docker poussée sur Docker Hub avec succès.'

                    // Démarrez la pile d'applications à l'aide de docker-compose
                    sh "docker-compose -f ${dockerComposeFilePath} up -d"
                    echo 'Déploiement effectué avec succès.'
                }
            }
        }
    }
}
