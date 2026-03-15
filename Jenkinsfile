pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 20, unit: 'MINUTES')
        timestamps()
    }

    environment {
        APP_ENV = 'test'
        APP_SECRET = 'jenkins-test-secret'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Check environment') {
            steps {
                sh 'php -v'
                sh 'composer -V'
            }
        }

        stage('Composer install') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

        stage('Prepare test database') {
            steps {
                sh 'php bin/console doctrine:database:create --env=test --if-not-exists || true'
                sh 'php bin/console doctrine:migrations:migrate --env=test --no-interaction || true'
                sh 'php bin/console cache:clear --env=test'
            }
        }

        stage('Run tests') {
            steps {
                sh './vendor/bin/phpunit --configuration phpunit.xml.dist'
            }
            post {
                always {
                    junit allowEmptyResults: true, testResults: 'var/phpunit*.xml'
                }
            }
        }
    }

    post {
        failure {
            echo 'Pipeline failed. Check the logs above.'
        }
        success {
            echo 'Pipeline succeeded.'
        }
    }
}
