# Installation Guide <!-- omit in toc -->

This document guides you through setting up the application, making it secure with HTTPS, having backups and how to upgrade it.

## Table of Contents <!-- omit in toc -->

- [Requirements](#requirements)
- [Basic Setup](#basic-setup)
- [Setting up HTTPS](#setting-up-https)
  - [In Your Domain Provider](#in-your-domain-provider)
  - [On Your Server](#on-your-server)
- [Backup Your Data](#backup-your-data)
  - [Backup Strategy](#backup-strategy)
  - [Backup Script](#backup-script)
  - [Doing a Manual Backup](#doing-a-manual-backup)
  - [Set Up Periodic Backups](#set-up-periodic-backups)
  - [Restore from a Backup](#restore-from-a-backup)
- [Upgrade Guide](#upgrade-guide)

## Requirements

- have a server
- [Docker](https://docs.docker.com/get-started/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/) are installed and operational on your server

Many hosting providers give you both in one go, allowing you to select `Docker CE` instead of a vanilla OS Image in the "Create Server" dialogue. E.g. [Hetzner Cloud](https://www.hetzner.com/de/cloud).

## Basic Setup

SSH into your server.

1. Create a folder
    ```shell
    mkdir /opt/recipe-manager
    cd /opt/recipe-manager
    ```
2. Download the `docker-compose.yaml` and the nginx `default.conf` file:
    ```shell
    curl -L https://raw.githubusercontent.com/lchristmann/recipe-manager/main/docker-compose.yaml -o docker-compose.yaml
    ```
   > You can customize the server port that the recipe-manager will be running on by changing one line in the `docker-compose.yaml`.<br>
   > If you change the `"${NGINX_PORT:-80}:80"` under the `web` service e.g. to `"8080:80"`, it will run on port 8080 on the server.
3. Download the `.env.example` and save it as `env` file
    ```shell
    curl -L https://raw.githubusercontent.com/lchristmann/recipe-manager/main/.env.example -o .env
    ```
4. Edit the `.env` file: `APP_ENV=production`, `APP_DEBUG=false`, and set the `APP_URL` to your server's public IP address (prefixed with `http://`).
    ```shell
    nano .env
    ```
   You may optionally change the below:
    - `APP_TIMEZONE=UTC`: change the application's timezone to another one from the list of [timezones for PHP](https://www.php.net/manual/en/timezones.php), e.g. `Europe/Berlin`
    - `APP_LOCALE=en`: change the application's language by choosing from the list of supported languages (as shown in the array list `->locales([...here...])` in [AppServiceProvider.php](../app/Providers/AppServiceProvider.php))
5. Create the Docker network and start the services
    ```shell
    docker network create recipe-manager-network
    docker compose up -d
    ```
6. Set a newly generated `APP_KEY` in the `.env` file (format `base64:xyz...`)
    ```shell
    docker compose exec php-fpm bash
    php artisan key:generate --show
    exit
    nano .env 
    ```
   ```shell
   # necessary for the application key in the .env to be recognized
   docker compose down
   docker compose up -d 
    ```
7. Visit the app now at http://yourServerPublicIP (specify your port if it's not 80!).

Now you can create your first admin user to log in:

````shell
docker compose exec php-fpm bash
php artisan make:filament-admin
````

## Setting up HTTPS

>  This guide uses the [Nginx Proxy Manager](https://nginxproxymanager.com/) to enable HTTPS. While that is currently my favourite, feel free to use other solutions like [Caddy](https://caddyserver.com/), [Traefik](https://traefik.io/traefik/), or manual [Nginx](https://nginx.org/) setup instead, if you like to.

Requirement: **you must have a sub(domain)** for HTTPS, since [Let's Encrypt](https://letsencrypt.org/) Certificate Authority doesn’t issue certificates for IP addresses and browsers don’t trust them anyway. So get one, if you don't have one yet.

### In Your Domain Provider

1. Point your (sub)domain to your server's public IP, e.g.
   ```text
   A recipes.example.com 192.168.1.1
   ```

### On Your Server

**Recommended Firewall:**

| Source          | Protocol | Port | Purpose |
|-----------------|----------|------|---------|
| Any IPv4 / IPv6 | TCP      | 22   | SSH     |
| Any IPv4 / IPv6 | ICMP     | -    | Ping    |
| Any IPv4 / IPv6 | TCP      | 80   | HTTP    |
| Any IPv4 / IPv6 | TCP      | 443  | HTTPS   |

SSH into your server.

1. Edit the `.env` file and set the `APP_URL` to your (sub)domain prefixed with `https://` and add a variable `ASSET_URL` with the same value.
   ```shell
   cd /opt/recipe-manager
   nano .env
   ```
2. Edit the `docker-compose.yaml` file and comment out the entire ports section. (This ensures the `web` service is only reachable internally in the `recipe-manager-network` Docker network)
   ```shell
   nano docker-compose.yaml
   ```
   ```yaml
   # ports:
     # - "${NGINX_PORT:-80}:80"
   ```
3. Restart the setup with `down` and `up` for the `.env` file change to take effect
   ```shell
   docker compose down
   docker compose up -d
   ```
4. Set up the [Nginx Proxy Manager](https://nginxproxymanager.com/)
    1. Create a folder
       ```yaml
       mkdir /opt/nginx-proxy
       cd /opt/nginx-proxy
       ```
    2. Create a `docker-compose.yaml` file like this (from the [official Quick Setup guide](https://nginxproxymanager.com/guide/#quick-setup))
       ```yaml
       touch docker-compose.yaml
       nano docker-compose.yaml
       ```
       ```yaml
       services:
         app:
           image: 'jc21/nginx-proxy-manager:latest'
           restart: unless-stopped
           ports:
             - '80:80'
             - '81:81'
             - '443:443'
           volumes:
             - ./data:/data
             - ./letsencrypt:/etc/letsencrypt
       ```
       Also paste this, to put the Nginx Proxy Manager into the `recipe-manager-network` Docker network.
       The Proxy Manager is the single point of access to the application now.
       ```yaml
           # This one level nested under the 'app' service
           networks:
             - recipe-manager-network
 
       # This on the root level, not nested at all
       networks:
         recipe-manager-network:
           external: true
       ```
    3. Bring it up by running
       ```yaml
       docker compose up -d
       ```
    4. Temporarily open port 81 in your firewall to use the Nginx Proxy Manager admin interface

       |                    | Protocol | Port | Note                       |
             |--------------------|----------|------|----------------------------|
       | Any IPv4, Any IPv6 | TCP      | 81   | for Nginx Proxy Manager    |
    5. Access the Admin UI at http://yourServersIP:81

       |          | Default credentials |
             |----------|---------------------|
       | Email    | admin@example.com   |
       | Password | changeme            |
       You'll be prompted to change those credentials immediately after logging in.
    6. Find the container name of your app's `web` service, usually `recipe-manager-web-1`.
       ```shell
       cd /opt/recipe-manager
       docker ps --format "{{.Names}} {{.Image}}" | grep "recipe-manager-nginx" | awk '{print $1}'
       ```
    7. In the Nginx Proxy Manager visit Dashboard > Proxy Hosts > Add Proxy Hosts:
        - "Details" Tab

          | Setting               | Value                      | Example                | Explanation                                                                        |
                   |-----------------------|----------------------------|------------------------|------------------------------------------------------------------------------------|
          | Domain Names          | your domain name           | `recipes.example.com`  |                                                                                    |
          | Scheme                | http (yes!!)               |                        | This is only Docker network internal, we'll force HTTPS for internet traffic later |
          | Forward Hostname / IP | the `web` container's name | `recipe-manager-web-1` | See previous step                                                                  |
          | Forward Port          | 80                         |                        |                                                                                    |
          | Block Common Exploits | yes                        |                        |                                                                                    |

        - "SSL" Tab

          | Setting                                       | Value                         | Explanation                                                            |
                  |-----------------------------------------------|-------------------------------|------------------------------------------------------------------------|
          | SSL Certificate                               | Request a new SSL Certificate |                                                                        |
          | Force SSL                                     | yes                           |                                                                        |
          | HTTP/2 Support                                | yes                           | Enables the newer, faster HTTP/2 protocol over TLS                     |
          | HSTS Enabled                                  | yes                           | Adds `Strict-Transport-Security` header to force browsers to use HTTPS |
          | I agree to the Let's Encrypt Terms of Service | yes                           |                                                                        |

        - Click "Save"
    8. Now the application will be available under your domain (prefixed with `https://`).
    9. Remove the temporary firewall rule of allowing port 81.

## Backup Your Data

Application data is stored in two places (with the second one not being used so far, but maybe sometime):

| Data         | Location               | What it contains                                        |
|--------------|------------------------|---------------------------------------------------------|
| **Database** | PostgreSQL             | All data (users, collections, pieces, compilations,...) |
| **Storage**  | Laravel storage volume | file uploads                                            |


For reliable disaster recovery, back up both.

### Backup Strategy

The recommended approach is:

1. **Dump the PostgreSQL database** using `pg_dump`
2. **Archive the Laravel storage volume** using `tar`

### Backup Script

1. Create a backup script file and make it executable:
    ```shell
    cd /opt/recipe-manager
    touch backup.sh
    chmod +x backup.sh
    ```

2. Paste the contents from the [docs/code/backup.sh](code/backup.sh) file there.
    ```shell
    nano backup.sh
    ```
    > Notes on the script:
    > 
    > If you make two backups in the same minute, you'll overwrite the previous one.<br>(This is because the set backup folder name is `$(date +%Y-%m-%d_%H-%M)` with the most fine-grained time unit being the trailing `%M` minutes)
    > 
    > The script also removes backups older than 30 days to reduce disk usage.<br>Feel free to increase the 30 days time limit or comment the line out for infinitely stored backups.

### Doing a Manual Backup

Just execute the backup script:

```shell
/opt/recipe-manager/backup.sh
```

You can now copy the resulting folder `/opt/recipe-manager-backups/<today's date and time>` somewhere safe (another server, your local machine, cloud storage,...).<br>
It contains two backup dump files `postgres.dump` and `storage-volume.tar.gz`

### Set Up Periodic Backups

Once your backup script is in place, you should **run it automatically at regular intervals** to ensure you always have recent copies of your data.

1. Open the root crontab:
    ```shell
    sudo crontab -e
    ```
2. Add the following line to run the backup script every Sunday at 03:30:
    ```text
    30 3 * * 0 /opt/recipe-manager/backup.sh >> /var/log/recipe-manager-backup.log 2>&1
    ```
    > To come up with your own cron expression for a different time interval, check out the [crontab guru](https://crontab.guru/) website and its [cron examples](https://crontab.guru/examples.html).

    Each run creates a new folder `/opt/recipe-manager-backups/<today's date and time>`.<br>
    Output and errors are logged to `/var/log/recipe-manager-backup.log`.

### Restore from a Backup

If something goes wrong, and you need to recover your data, you can restore both the database and the storage volume from a previously created backup folder.

1. Enter the recipe-manager's directory.
    ```shell
    cd /opt/recipe-manager
    ```
2. Choose a backup to restore from.<br>List available backups:
    ```shell
    ls /opt/recipe-manager-backups
    ```
   Pick the folder you need, for example run (**insert your backup's datetime here!**):
    ```shell
    RESTORE_DIR="/opt/recipe-manager-backups/2025-12-29_22-19"
    ```
3. Restore the database (with the whole setup running - or at least the PostgreSQL container)
    ```shell
    cat "$RESTORE_DIR/postgres.dump" | docker compose exec -T postgres \
      pg_restore -d app -U laravel --clean --if-exists
    ```
    > If the database doesn't even exist anymore, also use the `--create` option.
4. Stop the application. This ensures no process writes to the storage while restoring.
    ```shell
    docker compose down
    ```
5. Restore the Laravel storage volume
    ```shell
   docker run --rm \
      -v recipe-manager_laravel-storage-production:/data \
      -v "$RESTORE_DIR:/backup" \
      ubuntu bash -c "rm -rf /data/* /data/.[!.]* /data/..?* ; tar xzf /backup/storage-volume.tar.gz -C /data --strip-components=1"
    ```
   What happens here:
   - the existing storage content is deleted 
   - your archived storage data is extracted back into the volume 
   > If your data set is large, extraction can take a while - that is normal.
6. Start the application again
    ```shell
    docker compose up -d
    ```
7. Rebuild Laravel caches in one step to ensure the app uses fresh, consistent cache files:
    ```shell
    docker compose exec php-fpm php artisan optimize
    ```

Your Recipe Manager application should now be restored to the exact state at the moment of the backup.

## Upgrade Guide

SSH into your server.

1. Update the `leanderchristmann/recipe-manager` docker image version in the `docker.yaml` file to the current (or some other desired) release:
   ```shell
   cd /opt/recipe-manager
   nano docker-compose.yaml
   ```
   Example:
   ```yaml
   php-fpm:
     # For the php-fpm service, we will create a custom image to install the necessary PHP extensions and setup proper permissions.
     image: leanderchristmann/recipe-manager:1.0.0  # <--- here!!
   ```
2. Restart the setup with `down` and `up`. All data will be kept due to Docker volumes' persistence.
   ```shell
   docker compose down
   docker compose up -d
   ```

All done - it's that simple.
