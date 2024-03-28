pipeline {
    agent any
    
    stages {
        stage('Deploy to Kubernetes') {
            steps {
                // Deploy your Helm chart
                 sh 'helm install dolibarr charts/dolibarr'
            }
        }
    }
}
