# Production Deployment Guide

This document explains how to set up and use the automated deployment pipeline for the Custom Estimation App.

## Prerequisites

1.  **Production Server**: A Linux server (e.g., Ubuntu) with the following installed:
    - PHP 8.2+
    - Composer
    - Node.js & NPM
    - Git
    - Web Server (Nginx or Apache)
    - Database (MySQL/PostgreSQL/SQLite)
2.  **GitHub Repository**: Your code must be hosted on GitHub.

## Step 1: Prepare the Production Server

1.  Clone the repository to your production server:
    ```bash
    git clone https://github.com/your-username/custom_estimation_app.git /var/www/html/custom_estimation_app
    ```
2.  Set up the `.env` file on the server and configure the database, app key, etc.
3.  Ensure the web server user (e.g., `www-data`) has write permissions to `storage` and `bootstrap/cache`.

## Step 2: Configure GitHub Secrets

To allow GitHub Actions to securely log in to your server and run the deployment script, you need to add the following secrets to your GitHub repository (**Settings > Secrets and variables > Actions > New repository secret**):

| Secret Name | Description | Example |
| :--- | :--- | :--- |
| `SERVER_HOST` | The IP address or domain of your server | `1.2.3.4` |
| `SERVER_USER` | The SSH username | `ubuntu` or `deploy` |
| `SSH_PRIVATE_KEY` | The contents of your private SSH key | `-----BEGIN RSA PRIVATE KEY-----...` |
| `REMOTE_DIR` | The full path to the app on the server | `/var/www/html/custom_estimation_app` |
| `SERVER_PORT` | (Optional) SSH port if not 22 | `2222` |

### Generating SSH Keys
If you don't have an SSH key pair, generate one on your local machine:
```bash
ssh-keygen -t rsa -b 4096 -C "deployment-key"
```
1.  Add the **public key** (`~/.ssh/id_rsa.pub`) to the `~/.ssh/authorized_keys` file on your **production server**.
2.  Add the **private key** (`~/.ssh/id_rsa`) as the `SSH_PRIVATE_KEY` secret in **GitHub**.

## Step 3: Deployment Workflow

1.  Every time you push code to the `main` branch, GitHub Actions will automatically:
    - Log in to your server via SSH.
    - Navigate to the `REMOTE_DIR`.
    - Run `./deploy.sh`.
2.  The `deploy.sh` script handles:
    - Maintenance mode (`php artisan down`).
    - Pulling the latest code.
    - Installing Composer & NPM dependencies.
    - Running database migrations.
    - Clearing/Caching configuration and routes.
    - Building frontend assets (`npm run build`).
    - Exiting maintenance mode (`php artisan up`).

## Manual Deployment

You can still run the deployment manually on the server:
```bash
cd /path/to/app
chmod +x deploy.sh
./deploy.sh
```
