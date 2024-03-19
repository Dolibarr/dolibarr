stage('Build Docker Image') {
    steps {
        script {
            def dockerImage = docker.build('build_dolibarr:latest', '.')
            // Tag the Docker image with a version or commit hash
            dockerImage.tag("${env.BUILD_NUMBER}")
        }
    }
}
