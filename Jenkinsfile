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

        stage('Prepare .env') {
            steps {
                sh 'cp .env.test .env 2>/dev/null || cp .env.example .env'
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
                    if grep -q "sqlite" .env 2>/dev/null; then
                      php bin/console doctrine:schema:create --env=test
                    else
                      php bin/console doctrine:database:create --env=test --if-not-exists || true
                      php bin/console doctrine:migrations:migrate --env=test --no-interaction
                    fi
                '''
                sh 'php bin/console cache:clear --env=test'
            }
        }

        stage('Run tests') {
            steps {
                sh 'mkdir -p var && ./vendor/bin/phpunit --configuration phpunit.xml.dist'
            }
            post {
                always {
                    junit allowEmptyResults: true, testResults: 'var/phpunit*.xml'
                }
            }
        }

        stage('SonarQube') {
            when {
                expression { return env.SONAR_SKIP != 'true' }
            }
            steps {
                catchError(buildResult: 'SUCCESS', message: 'SonarQube step skipped or failed (e.g. sonar-scanner not installed)') {
                    withSonarQubeEnv('SonarQube') {
                        sh 'sonar-scanner'
                    }
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
