pipeline {
    agent {
        docker {
            image 'php:8.2-cli'
            args '-u root:root --entrypoint=""'
            reuseNode true
        }
    }

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
        stage('Install PHP extensions and Composer') {
            steps {
                sh '''
                    apt-get update -qq && apt-get install -y -qq git unzip libzip-dev libicu-dev > /dev/null
                    docker-php-ext-install -j$(nproc) zip intl pdo_mysql pdo_sqlite 2>/dev/null || true
                    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                '''
            }
        }

        stage('Checkout') {
            steps {
                checkout scm
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
