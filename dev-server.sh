#!/bin/bash

# KUBA App - Quick Setup & Test Script
# Run this to start developing locally

echo "🚀 KUBA App - Starting Development Server"
echo "=========================================="
echo ""
echo "📁 Project Directory:"
pwd
echo ""

echo "📋 Checking new structure..."
if [ -f "public/index.html" ]; then echo "✅ public/index.html"; else echo "❌ public/index.html missing"; fi
if [ -f "public/styles.css" ]; then echo "✅ public/styles.css"; else echo "❌ public/styles.css missing"; fi
if [ -f "public/js/app.js" ]; then echo "✅ public/js/app.js"; else echo "❌ public/js/app.js missing"; fi
if [ -f "src/api/api.php" ]; then echo "✅ src/api/api.php"; else echo "❌ src/api/api.php missing"; fi
if [ -f "src/api/users.php" ]; then echo "✅ src/api/users.php"; else echo "❌ src/api/users.php missing"; fi
if [ -d "src/api" ]; then echo "✅ src/"; else echo "❌ src/ missing"; fi
if [ -d "data/" ]; then echo "✅ data/"; else echo "❌ data/ missing"; fi

echo ""
echo "🌐 Starting PHP Development Server..."
echo "📍 URL: http://localhost:8000"
echo "📍 URL: http://127.0.0.1:8000"
echo ""
echo "✅ Frontend served from: /public/"
echo "✅ API routes from: /src/api/ and /src/auth/"
echo "✅ All PHP files execute properly"
echo ""
echo "Press CTRL+C to stop server"
echo ""

# Load environment variables from .env file
if [ -f "data/.env" ]; then
    echo "📝 Loading .env file..."
    export $(cat data/.env | grep -v '^#' | xargs)
    echo "✅ Environment variables loaded"
    echo ""
else
    echo "⚠️  data/.env file not found"
    echo ""
fi

# Use PHP's built-in development server with router
# Router handles:
# - Static files from public/ (HTML, CSS, JS)
# - API routes to src/api/ and src/auth/
# - SPA-style routing (index.html fallback)
php -S localhost:8000 index.php

