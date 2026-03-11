#!/bin/bash

# DrawingFlow Start and Test Script
# This script sets up the environment, starts services, and runs tests

set -e  # Exit on any error

echo "🔧 Setting up DrawingFlow environment..."

# Check if we're on Linux (WSL will be detected as Linux)
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Use Docker Compose on Linux
    DOCKER_CMD="docker compose"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    # Use Docker Compose on macOS
    DOCKER_CMD="docker compose"
else
    echo "🚨 Unsupported OS: $OSTYPE"
    exit 1
fi

echo "🐳 Using Docker command: $DOCKER_CMD"

# Stop any existing containers
echo "🛑 Stopping existing containers..."
$DOCKER_CMD down

# Start containers in detached mode
echo "🚀 Starting Docker containers..."
$DOCKER_CMD up -d

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 10

# Check MySQL is ready
echo "🔍 Checking MySQL status..."
$DOCKER_CMD exec mysql mysqladmin ping --silent --user=root --password=secret
if [ $? -ne 0 ]; then
    echo "🚨 MySQL is not ready"
    exit 1
fi

# Install PHP dependencies if vendor folder doesn't exist
if [ ! -d "vendor" ]; then
    echo "📦 Installing PHP dependencies..."
    $DOCKER_CMD exec app composer install
fi

# Generate application key if not exists
if [ ! -f ".env" ]; then
    echo "📄 Setting up .env file..."
    $DOCKER_CMD exec app cp .env.example .env
fi

echo "🔐 Generating application key..."
$DOCKER_CMD exec app php artisan key:generate

# Run migrations
echo "🏗️  Running database migrations..."
$DOCKER_CMD exec app php artisan migrate --force

# Run unit tests
echo "🔬 Running unit tests..."
$DOCKER_CMD exec app php artisan test --testsuite=Unit

# Check if any tests failed
if [ $? -ne 0 ]; then
    echo "🚨 Unit tests failed"
    exit 1
fi

echo "✅ All tests passed!"

# Show service status
echo "📊 Service status:"
$DOCKER_CMD ps

echo "✅ Setup complete!"
echo "🌐 Web interface available at: http://localhost:8080"
echo "📧 MailHog available at: http://localhost:8025"