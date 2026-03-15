pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 15, unit: 'MINUTES')
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
                sh 'php -v && composer -V'
            }
        }

        stage('Composer install') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

        stage('Prepare test database') {
            steps {
                sh '''
                    mkdir -p var
                    export DATABASE_URL="sqlite:///${WORKSPACE}/var/data_test.db"
                    php bin/console doctrine:database:create --env=test --if-not-exists 2>/dev/null || true
                    php bin/console doctrine:migrations:migrate --env=test --no-interaction 2>/dev/null || true
                    php bin/console cache:clear --env=test
                '''
            }
        }

        stage('Run tests') {
            steps {
                sh '''
                    export DATABASE_URL="sqlite:///${WORKSPACE}/var/data_test.db"
                    ./vendor/bin/phpunit --configuration phpunit.xml.dist
                '''
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
