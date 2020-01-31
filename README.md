# `Clarity Drupal 8 Starter`
version 0.1
## Purpose
The purpose of this repository is to provide a base Drupal 8 project
in order to improve our ability to rapidly create a Drupal 8 site while
maintaining a consistent development and deployment environment.

This repository contains very few contributed modules and no contributed themes
at this time.

## Technology
### Installed Versions
* Drupal Core  8.8.1
* Layout Builder
* devel (local & dev only)
* twig tweak
* config import, filter, ignore, split

### Local Development
This package contains a [Lando](https://docs.lando.dev/config/drupal8.html) configuration file (.lando.yml)
* Lando [v3.0.0-rc.23](https://github.com/lando/lando/releases/tag/v3.0.0-rc.23)
* Docker Desktop 2.1.0.5
* Docker 19.03.5

Lando is not required for this project.  It can be ran in any local development environment but
Lando configuration is provided and contains several useful tools and allows for a local Drupal site
to be completely setup in 4 commands.

#### Setup Commands
1. `git checkout git@github.com:thafner/d8-starter.git`
2. `composer install --prefer-dist`
3. `lando start`
4. `lando update-local`

This will provide you with a working local development environment.
What you do with it is up to you.

## Workflow
This repository is meant to provide a starting point for a Drupal 8 project. It is not meant
to act as a Forked Upstream or as a method of maintaining a Drupal site.

Therefore, code should not be commited back to this project on a per-project basis.  This project should be cloned then pushed to a different Origin/Upstream repository.

One option for this is to create a new git repository for your new project and change the remote.
1. `git remote -v` (should show )
