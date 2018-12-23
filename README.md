Apigee Monetization Drupal module
---

The Apigee Monetization module enables you to integrate Drupal 8 with Apigee Edge Monetization features.

**Note**: The Apigee Monetization module is dependent on the [Apigee Edge](https://www.drupal.org/project/apigee_edge) module.

### Installing

> The Apigee Monetization module depends on the Apigee Edge module being installed and configured to connect to the user's Apigee Edge account. 

1. Install and enable the Apigee Edge module (see that module's README.md file for details).
2. Go to `/admin/config/apigee-edge/settings` and create an authentication key.
3. Install the Apigee Edge Monetization module using [Composer](https://getcomposer.org/).
  Composer will download the Apigee Edge Monetization module and all its dependencies.
  **Note**: Composer must be executed at the root of your Drupal installation.
  For example:
   ```
   cd /path/to/drupal/root
   composer require drupal/apigee_m10n
   ```

  For more information about installing contributed modules using composer, see [the official documentation] (https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed)
4. Click **Extend** in the Drupal administration menu.
5. Select the **Apigee Monetization** module.
6. Click **Install**.

### Requirements

* Apigee Monetization module depends on the Apigee Edge module and a number of drupal commerce modules (see apigee_m10n.info.yml)
* **Please check [composer.json](https://github.com/apigee/apigee-edge-drupal/blob/8.x-1.x/composer.json) for required patches.** Patches prefixed with "(For testing)" are only required for running tests. Those are not necessary for using this module. Patches can be applied with the [cweagans/composer-patches](https://packagist.org/packages/cweagans/composer-patches) the plugin automatically or manually.

### Development

Development is happening in our [GitHub repository](https://github.com/apigee/apigee-m10n-drupal). The drupal.org issue queue is disabled, we use the [Github issue queue](https://github.com/apigee/apigee-m10n-drupal/issues) to coordinate development.

### Disclaimer

This is not an officially supported Google product.
