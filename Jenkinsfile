pipeline {
    agent {
       kubernetes {
            label 'my-kubernetes-label'
            containerTemplate {
                name 'docker'
                image 'docker:latest'
                command 'cat'
                ttyEnabled true
                volumeMounts {
                    mountPath '/var/run/docker.sock'
                    name 'docker-sock'
                }
            }
        }
    }
    environment {
        // Define environment variables for Dockerfile and docker-compose file paths
        dockerfilePath = 'build/docker/Dockerfile'
        dockerComposeFilePath = 'build/docker/docker-compose.yml'
        imageName = 'iyedbnaissa/dolibarr_app:latest'
    }

    stages {
        stage('Build Docker Image') {
            steps {
                // Build the Docker image using Dockerfile
                script {
                    docker.build(imageName, "-f ${dockerfilePath} .")
                }
                post {
                    success {
                        echo 'Image Docker construite avec succès!'
                    }
                    failure {
                        echo 'Construction de l\'image Docker a échoué.'
                    }
                }
            }
        }

        stage('Run Tests') {
            steps {
                // Run tests (replace with your actual test commands)
                sh 'echo "Running tests..."'
            }
            post {
                always {
                    echo 'Tests terminés.'
                }
            }
        }

      /*  stage('Deploy') {
            steps {
                // Deploy the application stack using docker-compose
                script {
                    // Pull the latest Docker image
                    docker.image(imageName).pull()

                    // Start the application stack using docker-compose
                    sh "docker-compose -f ${dockerComposeFilePath} up -d"
                }
                post {
                    success {
                        echo 'Application déployée avec succès!'
                    }
                    failure {
                        echo 'Déploiement de l\'application a échoué.'
                    }
                }
            }
        }*/
    }
}
