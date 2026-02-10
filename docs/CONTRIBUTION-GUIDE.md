# Contribution Guide

The Recipe Manager is open to contributions

- you can open issues in GitHub and
- pose pull requests (fork the repository first)

## Editing the Diagrams

Edit them easily with [draw.io](https://www.drawio.com/).

## Add a proper Logo

One could make a logo using the [RealFaviconGenerator's Logo maker](https://realfavicongenerator.net/logo-maker)
e.g. with the below settings:

- Text: R
- Size: slider at about 75%
- Color & Stroke: #ffffff (white) (dark: #000000)
- Background:
  - Color #1: #2e3f4e (dark: #ffffff)
  - Color #2: #111111 (dark: #d2d2be)

> For the normal (light) version, download the complete favicon package, for the dark version just the single SVG.

Adding a serious application logo would be even better though - if you got one, you could run it through the RealFaviconGenerator,
replace the current logo files and submit the pull request.

## Adding another Language

At the time of writing, the there's two languages available: English and German.<br>
The technical admin can set the default language via the `APP_LOCALE` environment variable, and users can switch between all available ones in the settings.

If you want to add another language (which I’d be happy to see), follow these steps.

### 1. Add the Laravel language files

> Laravel itself only ships with English translations.<br>
> To add another language, you must install the language files.

Choose a locale from the [list of available locales](https://laravel-lang.com/available-locales-list.html) of the `laravel-lang/lang` package.<br>
Then run:

```shell
php artisan lang:add <locale>
```

This installs framework-level translations such as validation messages, dates, and pagination.

### 2. Register the locale in the language switch

The language must be added to the language switch in the settings (so users can choose it).

Add the new locale code as select option in the [⚡language.blade.php](../resources/views/pages/settings/⚡language.blade.php):

```html
<flux:select variant="listbox" wire:model.live="locale" placeholder="{{ __('Select language') }}">
    <flux:select.option value="en">English</flux:select.option>
    <flux:select.option value="de">Deutsch</flux:select.option>
    <!-- add your new language locale here -->
</flux:select>
```

### 3. Add application-specific translations

All custom application strings live in the JSON translation files `lang/<locale>.json`.

To add a new language, you can simply copy all application-specific translations below the blank line e.g. from `lang/de.json` into your new language file and translate the values.

The English text is always used as the key (don't touch that - just replace the values!).

### 4. Verify

Run the application (see [Developer Docs](../DEVELOPER-DOCS.md)), switch to the new language in your settings, and confirm that everything looks correct.
