pipeline {
    agent any

    environment {
        dockerfilePath = 'build/docker/Dockerfile'
        dockerComposeFilePath = 'build/docker/docker-compose.yml'
        IMAGE_NAME = 'iyedbnaissa/dolibarr_app' // Remplacez `monusername` par votre nom d'utilisateur Docker Hub
        IMAGE_TAG = 'latest' // Vous pouvez dynamiser ce tag si nécessaire, par exemple, en utilisant une variable d'environnement ou un numéro de build
    }

    stages {
        stage('Build Docker Image') {
            steps {
                script {
                    def dockerImage = docker.build("${IMAGE_NAME}:${IMAGE_TAG}", "-f ${dockerfilePath} .")
                    echo 'Image Docker construite avec succès.'
                }
            }
        }
      /*  stage('Push Docker Image') {
            steps {
                script {
                    // Authentification à Docker Hub
                    withCredentials([usernamePassword(credentialsId: 'docker-hub-credentials', usernameVariable: 'DOCKERHUB_USERNAME', passwordVariable: 'DOCKERHUB_PASSWORD')]) {
                        sh 'echo $DOCKERHUB_PASSWORD | docker login -u $DOCKERHUB_USERNAME --password-stdin'
                    }
                    // Pousser l'image sur Docker Hub
                    docker.withRegistry('https://index.docker.io/v1/', 'docker-hub-credentials') {
                        dockerImage.push("${IMAGE_TAG}")
                        dockerImage.push("latest") // Pousser également comme latest si désiré
                    }
                }
            }
        }*/
        stage('Run Tests') {
            steps {
                sh 'echo "Running tests..."'
            }
        }
        stage('Deploy') {
            steps {
                script {
                    docker.image("${IMAGE_NAME}:${IMAGE_TAG}").pull()
                    sh "docker-compose -f ${dockerComposeFilePath} up -d"
                }
            }
        }
    }
}
