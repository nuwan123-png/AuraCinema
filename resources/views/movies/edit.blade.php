name: Build, Test, and Push to ECR

on:
  push:
    branches: [ "main" ]

jobs:
  build-and-push:
    runs-on: ubuntu-latest

    steps:
    - name: Checkout code
      uses: actions/checkout@v4

    - name: Gate - no third-party script CDNs
      run: |
        echo "Checking for third-party script CDNs..."
        grep -rnEi "<script[^>]+src=[\"']https?://" resources/views/ || true

    - name: Set up PHP 8.4 (same as production)
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: mbstring, xml, ctype, iconv, mysql, pdo_mysql, bcmath, zip, gd
        coverage: none

    - name: Gate - PHP syntax
      run: |
        find app bootstrap config routes -type f -name "*.php" -exec php -l {} \; > /dev/null
        echo "PHP syntax check passed."

    - name: Gate - runner PHP is 8.4 like production
      run: |
        PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
        if [ "$PHP_VER" != "8.4" ]; then
          echo "PHP version mismatch: expected 8.4, got $PHP_VER"
          exit 1
        fi
        echo "PHP version confirmed: $PHP_VER"

    - name: Install PHP dependencies (with dev tools for the tests)
      run: composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-req=php+

    - name: Gate - automated tests
      env:
        APP_ENV: testing
        APP_KEY: base64:tX9YlG5zQ0r3E4W1o8P7s2D6u1A0f5H8j3K6l9O2v4M=
        DB_CONNECTION: sqlite
        DB_DATABASE: ':memory:'
      run: php artisan test

    - name: Report - npm audit (production deps)
      run: |
        npm audit --omit=dev || true

    - name: Configure AWS credentials
      uses: aws-actions/configure-aws-credentials@v4
      with:
        aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
        aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
        aws-region: ${{ secrets.AWS_REGION }}

    - name: Login to Amazon ECR
      id: login-ecr
      uses: aws-actions/amazon-ecr-login@v2

    - name: Build Docker image
      env:
        ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
        ECR_REPOSITORY: auracinema
        IMAGE_TAG: latest
      run: |
        docker build -t $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG .

    - name: Push to ECR
      env:
        ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
        ECR_REPOSITORY: auracinema
        IMAGE_TAG: latest
      run: |
        docker push $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG