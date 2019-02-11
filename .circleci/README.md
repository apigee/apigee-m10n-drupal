# Continous Integration

Continuous integration has been set up using the GitHub project
[deviantintegral/drupal_tests](https://github.com/deviantintegral/drupal_tests).

# Custom changes

* `RoboFile.php`: Added configureM10nDependencies() to pull in needed dependencies
* `RoboFile.php`: Disable xdebug for most processes for performance
* `config.yml`: Use our phpcs.xml.dist codesniffer config
* `config.yml`: Apply 2951487_15_no-tests.patch in update_dependencies

