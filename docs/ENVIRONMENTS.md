# Environment Setup Guide

## 🐳 Docker Deployment Options

The PROSIGHT Salesmen API supports multiple deployment environments with Docker.

### 🚀 **Production Deployment**

**Basic Production:**
```bash
# Standard production deployment
docker-compose up -d --build
```

**Advanced Production with overrides:**
```bash
# Production with custom configuration
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

**Features:**
- ✅ Optimized image (no dev dependencies)
- ✅ Production environment variables
- ✅ Resource limits and security
- ✅ Persistent volumes
- ✅ Auto-restart policies

### 🧪 **Development Environment**

```bash
# Development with testing tools
docker-compose -f docker-compose.dev.yml up -d --build

# Run tests in development container
docker exec -it salesmen-api-app-dev-1 php artisan test

# PHPStan analysis
docker exec -it salesmen-api-app-dev-1 ./vendor/bin/phpstan analyse
```

**Features:**
- ✅ Dev dependencies included (PHPUnit, PHPStan)
- ✅ Volume mounts for live development
- ✅ Debug mode enabled
- ✅ Testing environment

### 📋 **Environment Comparison**

| Feature | Development | Production |
|---------|-------------|------------|
| **Container** | `Dockerfile.dev` | `Dockerfile` |
| **Dependencies** | All (dev + prod) | Production only |
| **Debug Mode** | `APP_DEBUG=true` | `APP_DEBUG=false` |
| **Volume Mounts** | Source code | Storage only |
| **Test Tools** | ✅ PHPUnit, PHPStan | ❌ Excluded |
| **Resource Limits** | None | Memory limits |
| **Restart Policy** | `unless-stopped` | `always` |
| **Image Size** | ~800MB | ~400MB |

### 🔧 **Local Development (Non-Docker)**

```bash
# Traditional Laravel development
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### 🏥 **Health Monitoring**

All environments include health check endpoints:

```bash
# Check application health
curl http://localhost:8000/api/health

# Check container status
docker-compose ps

# View logs
docker-compose logs app
```

### � **Quick Environment Switcher**

```bash
# Switch to Development
docker-compose -f docker-compose.dev.yml up -d --build

# Switch to Production  
docker-compose down && docker-compose up -d --build

# Production with custom settings
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### �🛠 **Environment Files**

- **`.env.docker`** - Production Docker environment
- **`.env.example`** - Local development template
- **`docker-compose.yml`** - Production stack
- **`docker-compose.dev.yml`** - Development stack
- **`docker-compose.prod.yml`** - Production overrides

Choose the appropriate deployment method based on your needs!