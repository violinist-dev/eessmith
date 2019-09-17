## Installation with composer

Using composer is the preferred way of managing your modules and themes as composer handles dependencies automatically and there is less margin for error. You can find out more about composer and how to install it here: https://getcomposer.org/. It is not recommended to edit your composer.json file manually.

The DX8 module and theme does not currently exist on drupal.org, so you will need to tell composer where to find the repositories and also add the dependencies.

Open up your terminal and navigate to your project root directory.

Next you need to run the following commands to add the DX8 repositories to your composer.json:

```
composer config repositories.dx8 vcs https://bitbucket.org/cohesion_dev/dx8-module.git

composer config repositories.dx8-theme vcs https://bitbucket.org/cohesion_dev/dx8-theme.git
```

Now run the following commands to require the DX8 module and DX8 minimal theme:

```
composer require cohesion/dx8:5.5.0
composer require cohesion/dx8-theme:5.5.0
```

You'll need to change the "5.5.0" above to correspond to the latest release of DX8 which you can find on the repository page here: https://bitbucket.org/cohesion_dev/dx8-module/src/master/ by clicking "Master" and then "Tags" on the popup.

Next run the command `composer update` in your terminal within your project root directory.

DX8 will install along with several module dependencies from drupal.org.

You can now enable the modules via drush with the following commands: 

```
drush cr
drush pm-enable cohesion cohesion_base_styles cohesion_custom_styles cohesion_elements cohesion_style_helpers cohesion_sync cohesion_templates cohesion_website_settings -y
```  

## Upgrading DX8

When upgrading to a newer version of DX8, the following series of commands will need to be run in this order:

```
drush cr 
drush updb -y 
drush dx8:import / drush dx8 import
drush dx8:rebuild / drush dx8 rebuild
``` 

## Drush integration (supports ^8.1)

The `dx8` drush command has the following operations:

### dx8:rebuild

Resave and run pending updates on all DX8 config entities.

Drush 9 format: 

```
drush dx8:rebuild
```

Drush 8 format: 

```
drush dx8 rebuild
```

### dx8:import 

Import assets and rebuild element styles (replacement for the CRON).

Drush 9 format:

```
drush dx8:import
```

Drush 8 format:

```
drush dx8 import
```
 
## Hooks

Several hooks are provided and documented in ./cohesion.api.php.

All hooks are in the `dx8` group, so can be implemented in a 
MODULE.dx8.inc file under your module's root if you wish.


## Global $settings options

Show the JSON fields for debugging:

```
$settings['dx8_json_fields'] = TRUE;    
```

Allow the API URL field on the account settings page to be editable:

```
$settings['dx8_editable_api_url'] = TRUE;
```

Expose a version number field on the account settings page (for development):

```
$settings['dx8_editable_version_number'] = TRUE;
```

Don't show the API key field on the account settings page:

```
$settings['dx8_no_api_keys'] = TRUE;
```

Don't show the Google API key page:

```
$settings['dx8_no_google_keys'] = TRUE;
```

## Using translations and content moderation together

In order for the Layout Canvas field to work with content moderation and 
translation you need:

1. Entity reference revisions >= 1.6
2. Drupal >= 8.6
3. Apply the latest patch from this Entity reference revisions issue #3025709: "Create new revision" option is ignored when updating EntityReferenceRevisionsItem -- https://www.drupal.org/project/entity_reference_revisions/issues/3025709

## Using contextual links with component content

Component content may render the same content multiple times on the same page which makes in context 
editing not working. In order to have in context editing with component content you need to apply this core patch:

https://www.drupal.org/project/drupal/issues/2891603

## Using entity clone module

In order to be able to clone DX8 layouts when cloning a entity you need to apply this entity_clone module patch 

https://www.drupal.org/project/entity_clone/issues/3013286

## Tests

Run something like: `vendor/bin/phpunit -c docroot/core/phpunit.xml.dist --testsuite=unit --group Cohesion`