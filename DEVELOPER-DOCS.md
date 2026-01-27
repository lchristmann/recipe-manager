# Developer Docs <!-- omit in toc -->

This project is based on the official [Docker+Laravel Guide](https://docs.docker.com/guides/frameworks/laravel/)'s repository [dockersamples / laravel-docker-examples](https://github.com/dockersamples/laravel-docker-examples),
whose documentation can be found in the [BASE-PROJECT-README.md](docs/BASE-PROJECT-README.md).

## Table of Contents <!-- omit in toc -->

- [Prerequisites](#prerequisites)
- [Commands for Everyday Development](#commands-for-everyday-development)
- [Setting Up the Development Environment](#setting-up-the-development-environment)
- [Usage](#usage)
  - [Accessing the Workspace Container](#accessing-the-workspace-container)
  - [Run Artisan Commands:](#run-artisan-commands)
  - [Rebuild Containers:](#rebuild-containers)
  - [Stop Containers:](#stop-containers)
  - [View Logs:](#view-logs)
- [How to Release](#how-to-release)
  - [Prepare](#prepare)
  - [Build and Tag the Docker Images](#build-and-tag-the-docker-images)
  - [Try it out](#try-it-out)
  - [Publish the Docker Images](#publish-the-docker-images)
  - [Commit, Push and Release](#commit-push-and-release)

## Prerequisites
Ensure you have Docker and Docker Compose installed. You can verify by running:

```bash
docker --version
docker compose version
```

If these commands do not return the versions, install Docker and Docker Compose using the official documentation: [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/).

You also need to activate Flux Pro for this project, entering your license's email and key string.

```bash
php artisan flux:activate
```

> If you don't have a license, but still wish to develop on this project, contact me, and I might write a little guide on
> which Flux Pro Livewire components to comment out in the HTML, after removing the flux-pro repository from the composer.json,
> so that the application runs (with few UI parts like pill boxes and file uploads missing) for you to work with it freely. 

## Commands for Everyday Development

> **For the initial setup of the project visit the [Setting Up the Development Environment](#setting-up-the-development-environment) section first.**

```shell
docker compose -f compose.dev.yaml up -d # Start the setup
```

```shell
docker compose -f compose.dev.yaml exec workspace bash
npm run dev
```

Access the application at [http://localhost](http://localhost).

```shell
docker compose -f compose.dev.yaml down # Shut it down
```

```shell
docker compose -f compose.dev.yaml exec workspace bash
  composer install # run this on first checkout
  npm install
  php artisan key:generate --show # paste this on first checkout to .env and restart the setup
  php artisan migrate # to set up the database structure
  php artisan migrate:fresh --seed
  php artisan tinker
  SomeModel::factory()->count(2)->make()->toJson()
  
docker compose -f compose.dev.yaml exec postgres bash
  psql -d app -U laravel # password: secret
  \dt
  \d users
  SELECT * FROM users;
```

## Helpful SQL Queries

### For the validation of the cookbook.show page

All tags that are available for a given cookbook:

```postgresql
SELECT DISTINCT(t.name)
FROM recipes AS r
    INNER JOIN recipe_tag AS rt ON r.id = rt.recipe_id
    INNER JOIN tags AS t ON rt.tag_id = t.id
WHERE cookbook_id = 1
ORDER BY t.name;
```

All recipes that should be found for a tag filter combination:

```postgresql
SELECT r.id, r.cookbook_id, r.title, r.position
FROM recipes AS r
    INNER JOIN recipe_tag AS rt ON r.id = rt.recipe_id
WHERE r.cookbook_id = 1
  AND rt.tag_id IN (
      SELECT id
      FROM tags
      WHERE name IN ('breakfast', 'french')
    )
GROUP BY r.id, r.cookbook_id, r.title, r.position
HAVING COUNT(DISTINCT rt.tag_id) = 2 -- <- number of tags in list up there
ORDER BY r.position;
```

## Setting Up the Development Environment

For development, we use the `compose.dev.yaml` Docker Compose configuration, which includes an additional workspace container with helpful tools.

1. Copy the .env.example file to .env and adjust any necessary environment variables:

```bash
cp .env.example .env
```

Hint: adjust the `UID` and `GID` variables in the `.env` file to match your user ID and group ID. You can find these by running `id -u` and `id -g` in the terminal.

2. Start the Docker Compose Services:

```bash
docker compose -f compose.dev.yaml up -d
```

3. Install Laravel Dependencies:

```bash
docker compose -f compose.dev.yaml exec workspace bash
composer install
npm install
```

4. Run Migrations:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate
```

5. Generate the application encryption key and restart the setup:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan key:generate
```

```bash
docker compose -f compose.dev.yaml down
docker compose -f compose.dev.yaml up -d
```

```bash
docker compose -f compose.dev.yaml exec workspace bash
npm run dev
```

6. Access the Application:

Open your browser and navigate to [http://localhost](http://localhost).

You can create a first admin user manually like so, but later we will use `php artisan migrate:fresh --seed` instead:

```bash
docker compose -f compose.dev.yaml exec workspace bash
php artisan app:make-user Admin admin@admin.com admin --admin
# Username: Admin, Email: admin@admin.com, Password: admin
```

## Usage

Here are some common commands and tips for using the development environment:

### Accessing the Workspace Container

The workspace sidecar container includes Composer, Node.js, NPM, and other tools necessary for Laravel development (e.g. assets building).

```bash
docker compose -f compose.dev.yaml exec workspace bash
```

### Run Artisan Commands:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate
```

### Rebuild Containers:

```bash
docker compose -f compose.dev.yaml up -d --build
```

### Stop Containers:

```bash
docker compose -f compose.dev.yaml down
```

### View Logs:

```bash
docker compose -f compose.dev.yaml logs -f
```

For specific services, you can use:

```bash
docker compose -f compose.dev.yaml logs -f web
```

## How to Release

### Prepare

Log in to Docker Hub:

```shell
docker login
```

Set a version that you want to release:

```shell
VERSION=1.0.0
```

### Build and Tag the Docker Images

```shell
docker build \
  -f ./docker/deployment/php-fpm/Dockerfile \
  -t leanderchristmann/recipe-manager:${VERSION} \
  -t leanderchristmann/recipe-manager:latest \
  .
```

Only after the `recipe-manager` (php-fpm) Docker Image has been built, can we build the Nginx Image.

```shell
docker build \
  --build-arg VERSION=${VERSION} \
  -f ./docker/deployment/nginx/Dockerfile \
  -t leanderchristmann/recipe-manager-nginx:${VERSION} \
  -t leanderchristmann/recipe-manager-nginx:latest \
  .
````

### Try it out

> ⚠️ Test that whatever you've changed did not break the application: just run the below build command,
> set that new release version in the `docker-compose.yaml` file (for both the `web` and the `php-fpm` service)
> and then do `docker compose -f docker-compose.yaml up -d` here locally.
> 
> In a new setup, you must create the docker network and your admin user, as described in the [Installation Guide > Basic Setup](docs/INSTALLATION-GUIDE.md#basic-setup).

Set that new version in the `docker-compose.yaml`:

```yaml
  web:
      image: leanderchristmann/recipe-manager-nginx:1.0.0 # <--- here!!

  php-fpm:
    # For the php-fpm service, we will create a custom image to install the necessary PHP extensions and setup proper permissions.
    image: leanderchristmann/recipe-manager:1.0.0 # <--- here!!
```

> You can enter and browse the images for debugging as such:
> 
> ```shell
> docker run -it leanderchristmann/recipe-manager:${VERSION} bash
> docker run -it leanderchristmann/recipe-manager-nginx:${VERSION} sh
> ```

### Publish the Docker Images

```shell
docker push leanderchristmann/recipe-manager:${VERSION}
docker push leanderchristmann/recipe-manager:latest
```

```shell
docker push leanderchristmann/recipe-manager-nginx:${VERSION}
docker push leanderchristmann/recipe-manager-nginx:latest
```

### Commit, Push and Release

Now commit your changed code (including the `docker-compose.yaml` changes from above section [Try it out](#try-it-out)!).

Also tag the Git release:

```shell
git tag -a "${VERSION}" -m "Release ${VERSION}"
git push origin "${VERSION}"
```

Finally, [create a GitHub release](https://github.com/lchristmann/recipe-manager/releases) via the GitHub UI -
it's takes the Git tag and lets you add some meta-information to it.
Give a title like `1.0.0`, a heading like `## What's Changed` and put a bullet point list of changes.
