#!/bin/bash

# KUBA App - Quick Setup & Test Script
# Run this to start developing locally

echo "🚀 KUBA App - Starting Development Server"
echo "=========================================="
echo ""
echo "📁 Project Directory:"
pwd
echo ""

echo "📋 Checking files..."
if [ -f "index.html" ]; then echo "✅ index.html"; else echo "❌ index.html missing"; fi
if [ -f "styles.css" ]; then echo "✅ styles.css"; else echo "❌ styles.css missing"; fi
if [ -f "app.js" ]; then echo "✅ app.js"; else echo "❌ app.js missing"; fi
if [ -f "words.json" ]; then echo "✅ words.json"; else echo "❌ words.json missing"; fi
if [ -f "backend.php" ]; then echo "✅ backend.php"; else echo "❌ backend.php missing"; fi

echo ""
echo "🌐 Starting PHP Development Server..."
echo "📍 URL: http://localhost:8000"
echo "📍 URL: http://127.0.0.1:8000"
echo ""
echo "⚠️  This server executes PHP files (login.php, api.php, etc.)"
echo "✅ Sessions and POST requests will work correctly"
echo ""
echo "Press CTRL+C to stop server"
echo ""

# Load environment variables from .env file
if [ -f ".env" ]; then
    echo "📝 Loading .env file..."
    export $(cat .env | grep -v '^#' | xargs)
    echo "✅ Environment variables loaded"
    echo ""
fi

# Use PHP's built-in development server instead of Python
# This allows PHP files to be executed properly
php -S localhost:8000

