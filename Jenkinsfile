pipeline {
    agent any
    
    stages {
        stage('Clone') {
            steps {
                git branch: 'main', changelog: false, poll: false, url: 'https://github.com/iyedben/Dolibarr.git'
            }
        }
        
        stage('Build-Docker-Image') {
            agent {
                docker {
                    image 'docker:latest'
                    args '-v /var/run/docker.sock:/var/run/docker.sock'
                }
            }
            steps {
                sh 'docker build -t iyedbnaissa/dolibarr-image:latest -f build/docker/Dockerfile .'
            }
        }
        
        stage('Push-Images-Docker-to-DockerHub') {
            environment {
                DOCKER_HUB_CREDENTIALS = credentials('20')
            }
            steps {
                script {
                    docker.withRegistry('https://index.docker.io/v1/', DOCKER_HUB_CREDENTIALS) {
                        sh 'docker push iyedbnaissa/dolibarr-image:latest'
                    }
                }
            }
        }
    }
    
    post {
        always {
            cleanup()
        }
    }
    
}
