# InfoFinland Drupal 9 site

Drupal 9 website for the InfoFinland project.

## Environments

Env | Branch | Drush alias | URL | Notes
--- | ------ | ----------- | --- | -----
development | * | - | https://drupal-infofinland.docker.so/ | Local development environment
production | main | @main | TBD | Not implemented yet

## Requirements

You need to have these applications installed to operate on all environments:

- [Docker](https://github.com/druidfi/guidelines/blob/master/docs/docker.md)
- [Stonehenge](https://github.com/druidfi/stonehenge)
- For theme development: [node >=16.6](https://nodejs.org/en/) is required. [NVM](https://nodejs.org/en/) is recomennended for node version management. .nvmrc is included in the theme
- For the new person: Your SSH public key needs to be added to servers

## Create and start the environment

For the first time (new project):

``
$ make new
``

Stop project:

``
$ make stop
``

Start project after stopping it:

``
$ make up
``

Stop project and remove containers:

``
$ make down
``

Start project, rebuild and update configuration:

``
$ make up; make build; make post-install
``

Install fresh Drupal site from existing configuration:

``
$ make build; make drush-si; make post-install
``

Start project, update all packages and sync db from local sql dump:

``
$ make fresh
``

## Update Drupal and composer modules

Install all modules and composer packages:

``
$ make build
``

Update all modules and composer packages:

``
$ make composer-update
``

Update only Drupal core:

``
$ make drupal-update
``

**Note:** After updates, clear caches, run database updates and export possibly changed configuration:

``
$ make drush-cr; make drush-updb; make drush-cex
``

Update Composer.lock if outdated (after merges, etc):

```
# Login into app container first:
$ make shell

# Update lock file:
$ composer update --lock
```

## Configuration management and workflow

When checking out new branch run `make drush-cim` to import new config changes to the database.

Remember always run `make drush-cex` after you have made configuration changes and before you pull or checkout new code. Remember also to commit your config changes.

Export settings:

``
$ make drush-cex
``

Import settings:

``
$ make drush-cim
``

## Other useful commands

# After pulling latest changes, run all the updates:
$ make drush-deploy

```
# Login to app container
$ make shell

# Login with Drush
$ make drush-uli

# Create sql dump from local site
$ make drush-create-dump

# Check Drupal coding style
$ make lint-drupal

# Automatically fix Drupal coding style errors
$ make fix-drupal
```

### Coding standards
Follow Drupal's coding standards: https://www.drupal.org/docs/develop/standards

City of Helsinki's coding standars and best practices: https://dev.hel.fi/

Check for coding style violantions by running `$ make lint-drupal`

### Gitflow workflow
The Gitflow workflow is followed, with the following conventions:

**Main branch**: `develop`. All feature branches are created from `develop` and merged back with pull requests. All new code must be added with pull requests, not committed directly.

**Production branch:** `main`. Code running in production. Code is merged to `main` with release and hotfix branches.

**Feature branches**: For example, `feature/IFU-000-add-content-type`, Use Jira ticket number in the branch name. Always created from and merged back to `develop` with pull requests after code review and testing.

**Release branches**: Code for future and currently developed releases. Should include the version number, for example: `1.1.0`

**Hotfix branches**: Branches for small fixes to production code. Should include the word hotfix, for example: `IFU-hotfix-drupal-updates`. Remember to also merge these back to `develop`.
