# Apigee Monetization Drupal module

The Apigee Monetization module enables you to integrate Drupal 8 with Apigee Edge Monetization features. You must have
a [monetization enabled Apigee Edge organization](https://docs.apigee.com/api-platform/monetization/enabling-monetization-organization)
to use this module.

This module includes the following submodules:
* __API product RBAC:__ enables administrators to configure access permissions to API products.
* __Debug__: enables administrators to configure and manage Apigee debug logs.
* __Teams__: (Experimental) enables developers to be organized into teams.

**Note**: The Apigee Monetization module is dependent on the [Apigee Edge](https://www.drupal.org/project/apigee_edge) module.

## Installing

This module must be installed on a Drupal site that is managed by Composer.  Drupal.org has documentation on how to
[use Composer to manage Drupal site dependencies](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies) 
to get you quickly started.
  
1. Install the Apigee Edge Monetization module using [Composer](https://getcomposer.org/).
  Composer will download the Apigee Edge Monetization module and all its dependencies.
  **Note**: Composer must be executed at the root of your Drupal installation.
  For example:
   ```
   cd /path/to/drupal/root
   composer require drupal/apigee_m10n
   ```
   For more information about installing contributed modules using composer, read [how to download contributed modules and themes using Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed).
2. Click **Extend** in the Drupal administration menu.
3. Select the **Apigee Monetization** module.
4. Click **Install**.
5. Configure the [site to connect to Apigee Edge](https://www.drupal.org/docs/8/modules/apigee-edge/configure-the-connection-to-apigee-edge) 
   under **Configuration** > **Apigee Edge** > **General** in the administration toolbar.
   

## Development

Development is happening in our [GitHub repository](https://github.com/apigee/apigee-m10n-drupal). The drupal.org issue queue is disabled, we use the [Github issue queue](https://github.com/apigee/apigee-m10n-drupal/issues) to coordinate development.

## Disclaimer

This is not an officially supported Google product.
