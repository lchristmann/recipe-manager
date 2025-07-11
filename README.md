# Recipe Manager <!-- omit in toc -->

This project is intended to be a massive improvement after my first [recipe-app](https://github.com/lchristmann/recipe-app).

A self-hosted recipe manager.

Based on the [Laravel Vue Starter Kit](https://laravel.com/docs/12.x/starter-kits#vue).

-  [⚡️ Laravel Vue Starter Kit - Deep Dive](https://www.youtube.com/watch?v=phaBzRIioAw) [20min YouTube video]
- [Laravel 12 Starter Kits: What You Need To Know (by Laravel Daily)](https://youtu.be/mjSwVWhgpGc?t=633) [see @10:33 for adding CRUD as example of development on top of the Starter Kit]

## Todos <!-- omit in toc -->

- write one page after the other
- implement that admin users can invite new users to the application (by email)
    - do the mail sending with a queue
- implement testing ([test the mailing](https://mailpit.axllent.org/docs/integration/), test the authentication and authorization,...)

Inspiration for design: [Recipe Manager Notion template](https://mrpugo.notion.site/Recipe-Manager-1be6c91f8850803bb51fee1ba4864e64) by Mr. Pugo

## Table of Contents <!-- omit in toc -->

- [Specification](#specification)
- [Architecture](#architecture)
  - [Database Schema](#database-schema)
- [Development](#development)
  - [Get it up](#get-it-up)
  - [Shut everything down](#shut-everything-down)
  - [Access the database](#access-the-database)
  - [Other helpful commands](#other-helpful-commands)
- [Things I Want to Learn Here](#things-i-want-to-learn-here)
- [Maintenance](#maintenance)

## Specification

This recipe manager shall have

- have a left sidebar layout with folders (private and public)
    - they shall be rearrangeable (draggable in Settings)
- 3 visitor groups: guests, normal users, admins
  - guests shall only be able to view public folders and their recipes
  - normal users shall be able to edit their own recipes and have their private ones
  - admins shall be able to edit all public recipes, but not see others'  private ones
- logged-in users shall have a profile and settings
- there shall be comments under recipes and notifications to people
- recipes shall be able to be liked by others / given a star rating from 1 to 5

## Architecture

The backend is composed of five Docker containers:

- [Nginx](https://nginx.org/): Web Server
- [PHP-FPM](https://www.php.net/manual/de/install.fpm.php): Laravel API runtime
- [PostgreSQL](https://www.postgresql.org/): Database (for main data)
- [Redis](https://redis.io/): In-memory Database (for caching)
- [Mailpit](https://mailpit.axllent.org/): E-Mail Testing Tool (only in `dev` setup included)

### Database Schema

![Database schema](docs/db-schema.drawio.svg)

In addition to the application-specific tables, Laravel adds its standard tables.

## Development

This project adheres to standard [Laravel](https://laravel.com/docs/12.x) conventions.

The below is mostly taken from the [docker/README.md > Setting Up the Development Environment
](docker/README.md#setting-up-the-development-environment).
That's the official [Laravel Development Setup with Docker Compose](https://docs.docker.com/guides/frameworks/laravel/development-setup/) that I used and slightly adapted here.

### Get it up

> Care that there's nothing else on port 80 already - otherwise adapt your `compose.dev.yaml`.

1. ONLY ONCE: Copy the .env.example file to .env and adjust any necessary environment variables:

```bash
cp .env.example .env
```

2. Start the Docker Compose Services:

```bash
docker compose -f compose.dev.yaml up -d
```

3. Install Dependencies (also run whenever they change):

```bash
docker compose -f compose.dev.yaml exec workspace bash
composer install
npm install
exit
```

4. ONLY ONCE or WHEN CHANGED: or when migration/seeding changed Run Migrations:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate:fresh --seed
```

5. ONLY ONCE: Create the symlink from the `storage/app/public` folder to the `public` folder:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan storage:link
```

6. Start the application

```bash
docker compose -f compose.dev.yaml exec workspace composer run dev
```

7. Access the Application:

- the Laravel application is served by the Nginx at port 80: [http://localhost](http://localhost)
  - from the `web` service's container
- the frontend assets are served by the Vite dev server at port 5173: [http://localhost:5173](http://localhost:5173)
  - from the `workspace` service's container

### Shut everything down

```shell
docker compose -f compose.dev.yaml down # Shut it down
```

### Access the database

```shell
docker compose -f compose.dev.yaml exec postgres bash
  psql -d app -U laravel # password: secret
  \dt
```

```shell
\d users
SELECT * FROM users;
```

### Test E-Mail Sending

I included [Mailpit](https://mailpit.axllent.org/docs/install/docker/) in the Docker Compose setup of `compose.dev.yaml`
and configured it in the `.env.example` (see the `MAIL_` prefixed variables).

### Other helpful commands

```shell
docker compose -f compose.dev.yaml exec workspace bash
  php artisan key:generate --show # paste this on first checkout to .env and restart the setup
  php artisan migrate # to set up the database structure
  php artisan migrate:fresh --seed
  php artisan tinker
  Location::factory()->count(2)->make()->toJson()
```

If you've modified the `docker/development/workspace/Dockerfile`, rebuild the image like this:

```shell
docker compose -f compose.dev.yaml build workspace # if you changed something about the Dockerfile
```

## Deployment

Document setting up E-Mail with Amazon SES from AWS.

## Things I Want to Learn Here

In this project, for Laravel learning's sake, I want to implement

- [Mail](https://laravel.com/docs/12.x/mail)
- [Notifications](https://laravel.com/docs/12.x/notifications)
- [Queues](https://laravel.com/docs/12.x/queues)
- [Localization](https://laravel.com/docs/12.x/localization)
- [Authentication](https://laravel.com/docs/12.x/authentication)
  - requiring a personal passcode to register for the application
- [Authorization](https://laravel.com/docs/12.x/authorization), i.e. Policies and Gates
  - three types of users: guests, normal users, admins
- [Testing](https://laravel.com/docs/12.x/testing): Unit Tests, Feature Tests, UI/Acceptance Tests,...
- CI/CD: linting, test execution
- using the Laravel, Inertia, Vue, Tailwind stack

## Maintenance

This project actively maintained by [Leander Christmann](https://github.com/lchristmann).

For questions or support, feel free to [email me](mailto:hello@lchristmann.com).
