# Clarity Drupal 8 Starter
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

### Installation
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

## Local Development
### Setup
This repository is meant to provide a starting point for a Drupal 8 project. It is not meant
to act as a Forked Upstream or as a method of maintaining a Drupal site.

Therefore, code should not be commited back to this project on a per-project basis.  This project should be cloned then pushed to a different Origin/Upstream repository.

One option for this is to create a new git repository for your new project and change the remote.

`git remote set-url origin https://github.com/USERNAME/REPOSITORY.git`

see [Github](https://help.github.com/en/github/using-git/changing-a-remotes-url) for more info

### Workflow
This repository makes use of configuration, configuration ignores, and configuration splits to provide a functioning `local -> dev -> stg -> prod` environment.
This module assumes that we will use one Configuration Split per environment.

| Configuration |   dev   |   stg   |   prod   |
| ------------- |:--------|--------:| --------:|
| devel modules |    S    |   ---   |    ---   |
|    blocks     |    I    |    I    |     I    |
|    Caching    |   ---   |    S    |     S    |
|Env Indicators |    O    |    O    |     O    |

S = Config Split
I = Config Ignore
O = Config Override (hard-coded into the settings.php files $config array)


### Configuration Management
This repository leverages configuration splits and ignores to improve the configuration workflow.

#### Configuration Ignore
Config Ignore allows for a site to completely ignore whatever configuration is in the codebase.  For example, let's say on the live site we add the 'system.site' configuration to the ignore.  This will ignore any changes to configuration and never update the Site Title from what is in the database, regardless of changes to configuration.

#### Configuration Split
Config Split allows us to arbitrarily define sets of configuration that will get exported to separate directories when exporting, and get merged together when importing.  This site splits out configuration based on the Environment (local, dev, stage, prod).
See [this tutorial](https://docs.acquia.com/blt/developer/config-split/) for examples.
Example:
We want to disable Drupal caching and aggregation locally and on develop, but not stage and production.

1. Navigate to `/admin/config/development/configuration/config-split/` and select the split needed (develop in this case).  We want to use a graylist in this case, so select `system.performance` from the dropdown and save.
2. Navigate to `/admin/config/development/performance` and uncheck caching and aggregation
3. Run `lando drush cex -y` to export the configuration

## Team Workflow
This project follows the gitflow workflow with an additonal stage branch.  See [here](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow) and [here](https://nvie.com/posts/a-successful-git-branching-model/) for additional details.

1. Develop and stage branches are created from master
2. Feature branches are created from develop
3. A developer implements a change to the feature branch
4. When a feature is complete it is merged into the develop branch
5. When a release is ready a release branch is created from develop
6. Minor bugs can be updated in the release branch as-needed.
7. When the release branch is "done" it is merged into develop and master
8. If an issue in master is detected a hotfix branch is created from master
9. Once the hotfix is complete it is merged to both develop and master

#### Code Example
```
git checkout develop
git pull origin develop
git checkout -b feature_branch
# work happens on feature branch
git push -u origin feature_branch
# Create PR for feature_branch into DEVELOP
# delete feature_branch
```

When a release is required, the merge manager will create a release
```
git checkout develop
git pull origin develop
git checkout -b release/0.1.0
# Create a PR for release/0.1.0 into STAGE
# Any bugfixes will be branched from release/0.1.0 and then also merged into DEVELOP
# Create a PR for release/0.1.0 into MASTER
# Create a PR for release/0.1.0 into DEVELOP
git checkout master
git tag -a v0.1.0 -m "Version 0.1.0"
git push origin --tags
```
