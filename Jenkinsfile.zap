pipeline {
    agent any  // This pipeline can run on any available agent

    stages {
        stage('Hello') {
            steps {
                // This step echoes 'Hello, world!'
                sh 'echo "Hello, world!"'
            }
        }
    }
}
