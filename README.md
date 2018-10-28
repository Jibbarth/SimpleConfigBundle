# SimpleConfigBundle

This bundle provide an UI to configure other bundles by override a configuration file.

This should be used to allow administrators of your Application to easily change some simple configuration.
References this [documentation example](http://symfony.com/doc/current/bundles/configuration.html#using-the-bundle-extension), they could can change the _Twitter Client Id_ and _Twitter Client secret_.

The mechanism behind is to retrieve all available configuration for a bundle, display it in a form, and dump the submitted data in a new _config file_ that will override the default configuration.

## Installation

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute:

```console
$ composer require barth/simple-config-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundle.php` file of your project:

```php
<?php
// config/bundles.php

return [
    // ...
    Barth\SimpleConfigBundle\BarthSimpleConfigBundle::class => ['all' => true],
];
```

### Step 3: Import routes

In your `config/routes.yaml`, add the following route definition :

```yaml
#config/routes.yaml
barth_simpleconfig:
    resource: "@BarthSimpleConfigBundle/Controller/"
    type:     annotation
    prefix:   /admin
```

:warning: You should provide a `prefix` where only **ROLE_ADMIN** can access.

### Optionnal Steps

You should add the `config/packages/override` path to your gitignore.
If you deploy your app with awesome tools like [Capistrano](https://capistranorb.com/) or [Deployer](https://deployer.org/), don't forget to make this path as **shared** to avoid lose custom override between each deployment.

### Blacklist bundles

By default, all bundles that come with [symfony/website-skeleton](https://github.com/symfony/website-skeleton) are blacklisted.
You cannot override them so easily.

You can extend this list by adding the _bundle alias_ in your configuration :

```yaml
#config/packages/barth_simple_config
barth_simple_config:
    blacklisted_bundles:
        - nelmio_api_doc # for example
```

## How use it

When installation is completed, you have two new routes :

* http://yourdomain.org/admin/config That exposes all available configuration routes
* http://yourdomain.org/admin/config/{package} That display your form configuration

## Customization and Integration

### Custom Backend

By default, pages don't look very pretty. To integrate it in your template, don't hesitate to override the `base.html.twig` template by creating a new one in `templates/bundles/BarthSimpleConfigBundle/` and make it extend your base template.

### Third-party bundles

SimpleConfigBundle can easily be integrated in [EasyAdminBundle](https://github.com/EasyCorp/EasyAdminBundle). 
Just require it.

## Contribute

First of all, thank you for contributing :heart:

If you find any typo/misconfiguration/... please send me a PR or open an issue.

Also, while creating your PR, please write a description which gives the context and/or explains why you are creating it.

## TODOs

- [x] Make installation as simple as a `composer require barth/simple-config-bundle`, so submit it to packagist
- [x] Process configuration when form is submitted to validate it immediatly.
- [ ] Write Tests Suite
- [ ] Add translations
- [x] Integration with [EasyAdminBundle](https://github.com/EasyCorp/EasyAdminBundle)
- [ ] Integration with [Sonata](https://sonata-project.org/)
