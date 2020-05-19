# Apigee Monetization Drupal module

The Apigee Monetization module enables you to integrate Drupal 8 with Apigee Edge Monetization features. You must have
an Apigee Edge organization with [monetization enabled](https://docs.apigee.com/api-platform/monetization/enabling-monetization-organization)
to use this module.

The Drupal documentation pages for this module give an [overview of the features of the Apigee monetization module](https://www.drupal.org/docs/8/modules/apigee-monetization/understand-how-app-developers-interact-with-apigee-monetization#explore-the-apigee-monetization-features-in-the-developer-portal).

This module includes the following submodules:
* **Apigee Monetization Add Credit:** adds the ability to create a Drupal Commerce "Add Credit" product to add money to 
  a developer's prepaid balance.

## Module status

The core functionality of this module is complete and we are conducting final testing and critical
issue fixes. We encourage you to download and evaluate the module, and to use our 
[GitHub issue queue](https://github.com/apigee/apigee-m10n-drupal/issues) to give feedback, ask questions, 
or log issues.

## Planned features
* **Monetization Teams:** Adds the ability to share app credentials with other developers and to manage billing under one
  account. 

## Installing

This module must be installed on a Drupal site that is managed by Composer.  Drupal.org has documentation on how to
[use Composer to manage Drupal site dependencies](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies) 
to get you started quickly.
  
**Note**: The Apigee Monetization module is dependent on the [Apigee Edge](https://www.drupal.org/project/apigee_edge) module.
  
1. Install the Apigee Edge Monetization module using [Composer](https://getcomposer.org/).
  Composer will download the Apigee Edge Monetization module and all its dependencies.
  **Note**: Composer must be executed at the root of your Drupal installation.
  For example:
   ```
   cd /path/to/drupal/root
   composer require drupal/apigee_m10n
   ```
   For more information about installing contributed modules using Composer, read [how to download contributed modules and themes using Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed).
2. Choose **Extend** in the Drupal administration menu.
3. Select the **Apigee monetization** module.
4. Choose **Install**.
5. Configure the [site to connect to Apigee Edge](https://www.drupal.org/docs/8/modules/apigee-edge/configure-the-connection-to-apigee-edge) 
   under **Configuration** > **Apigee Edge** > **General** in the administration toolbar.
   
## Known Issues

1. Rate plans can be purchased with insufficient funds when 'start date' is a future date. See [#158](https://github.com/apigee/apigee-m10n-drupal/issues/158) for details.
2. Early termination fees are not reflected in current balance when cancelling future plans. See [#163](https://github.com/apigee/apigee-m10n-drupal/issues/163) for details.

## Development

Development is happening in our [GitHub repository](https://github.com/apigee/apigee-m10n-drupal). The Drupal.org issue queue is disabled; we use the [Github issue queue](https://github.com/apigee/apigee-m10n-drupal/issues) to coordinate development.

## Support

This project, which integrates Drupal 8 with Apigee Edge, is supported by Google.
