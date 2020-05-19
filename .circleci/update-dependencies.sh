#!/bin/bash -ex

# Make sure the robofile is in the correct location.
cp modules/apigee_m10n/.circleci/RoboFile.php ./

# Update dependencies if necessary.
if [[ ! -f dependencies_updated ]]; then
  robo setup:skeleton
  robo add:modules $1
  robo configure:m10n-dependencies
  robo update:dependencies
fi

# Touch a flag so we know dependencies have been set. Otherwise, there is no
# easy way to know this step needs to be done when running circleci locally since
# it does not support workflows.
touch dependencies_updated
