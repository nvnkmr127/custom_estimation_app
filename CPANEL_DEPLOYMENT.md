# cPanel Deployment Guide

This guide explains how to deploy the **Custom Estimation App** on a cPanel-hosted environment.

## Initial Setup (Do this once)

1.  **Clone or Initialize the Repository**:
    If starting fresh:
    ```bash
    git clone https://github.com/nvnkmr127/custom_estimation_app.git
    cd custom_estimation_app
    ```
    If files are already there but not a git repo:
    ```bash
    git init
    git remote add origin https://github.com/nvnkmr127/custom_estimation_app.git
    git fetch
    git checkout -t origin/main -f
    ```

2.  **Environment Configuration**:
    - Copy the example `.env` file: `cp .env.example .env`
    - Open `.env` and configure your production database credentials.
    - Generate an app key: `php artisan key:generate`

3.  **Database**:
    - Create a MySQL database and user via cPanel Database Wizard.
    - Ensure the user has all privileges.

4.  **Symbolic Link**:
    If your app is in a subfolder but needs to be served from `public_html`, create a symlink:
    ```bash
    ln -s ~/repositories/custom_estimation_app/public ~/public_html/app
    ```
    *(Adjust paths according to your structure)*

## Automated Deployment

To deploy updates, run the following command in the project root:

```bash
chmod +x cpanel_deploy.sh
./cpanel_deploy.sh
```
*Note: If you get "command not found", ensure you use `./cpanel_deploy.sh` or `bash cpanel_deploy.sh`.*

### What the script does:
- Puts the app in maintenance mode.
- Pulls the latest code from `main`.
- Installs PHP dependencies (`composer install`).
- Runs database migrations.
- Builds frontend assets (Vite/NPM).
- Clears and rebuilds application caches.
- Restarts queue workers.
- Brings the app back online.

## Troubleshooting

- **PHP Version**: Ensure the terminal is using the correct PHP version (`php -v`).
- **Composer Errors**: If composer fails with "prePackageUninstall" errors, try deleting the `vendor` folder and running the script again:
  ```bash
  rm -rf vendor
  bash cpanel_deploy.sh
  ```
- **NPM Not Found**: If the script says NPM is missing, you may need to enable Node.js in cPanel (look for "Setup Node.js App" or "Application Manager"). Alternatively, you can build assets on your local machine and upload the `public/build` folder, then set `BUILD_ASSETS=false` in `cpanel_deploy.sh`.
- **Permissions**: Ensure `storage` and `bootstrap/cache` are writable:
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```
