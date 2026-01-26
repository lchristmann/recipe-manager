# Recipe Manager <!-- omit in toc -->

A beautiful self-hosted recipe manager for your family / friend group.

The setup is quick and easy: just follow the **[Installation Guide](docs/INSTALLATION-GUIDE.md)**.

## Feature Description

Each user can manage any number of cookbooks for himself, which may contain many recipes.

Recipes can take shape as...

- a link (leading to some recipe website's page)
- ingredients + instructions
- recipe images (photos taken from a physical cookbook)

... and have food pictures uploaded for it.

There can also be multiple tags associated with a recipe (like "vegetarian" or "gluten-free").

A searchbar and a tag filter in the header enable quick and easy browsing of recipes by specific criteria.

There is a planner, where a user can switch between calendar weeks and manage recipes that he wants to prepare on specific days (for breakfast, lunch, dinner or as a snack in between).

Sorting is implemented for almost all entities, to give the user the freedom of (re)arranging his data.

By default, the app is "multi-tenant" (each user only sees their own data) and the only special privilege of admins is to manage users.<br>
However, users can make their cookbooks editable by others ("community") or just viewable ("public").

## Technical Architecture

The app uses:

- a [Docker Compose](https://docs.docker.com/compose/) setup based on the [official Docker+Laravel setup example](https://docs.docker.com/guides/frameworks/laravel/)
  - with [PHP](https://www.php.net/) 8.4 
- [Laravel](https://laravel.com/) 12
- [Livewire](https://livewire.laravel.com/) 4
  - using the [Laravel Livewire Starter Kit](https://laravel.com/docs/12.x/starter-kits#livewire)
- [Flux UI](https://fluxui.dev/) 2 incl. [Flux Pro Components](https://fluxui.dev/pricing)
- [Tailwind CSS](https://tailwindcss.com/) 4

## Data Model

The below database diagram shows the data model of the application.

![Entity Relationship Diagram](docs/diagrams/db-schema.drawio.svg)

## Maintenance

This project is actively maintained by [me](https://github.com/lchristmann).

For questions or support, just [email me](mailto:hello@lchristmann.com).

## Contribution

See the [Contribution Guide](docs/CONTRIBUTION-GUIDE.md) and the [Developer Docs](DEVELOPER-DOCS.md).
