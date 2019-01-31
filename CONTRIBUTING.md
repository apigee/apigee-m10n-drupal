# How to Contribute

We'd love to accept your patches and contributions to this project. There are
just a few small guidelines you need to follow.

## Contributor License Agreement

Contributions to this project must be accompanied by a Contributor License
Agreement. You (or your employer) retain the copyright to your contribution,
this simply gives us permission to use and redistribute your contributions as
part of the project. Head over to <https://cla.developers.google.com/> to see
your current agreements on file or to sign a new one.

You generally only need to submit a CLA once, so if you've already submitted one
(even if it was for a different project), you probably don't need to do it
again.

## Code reviews

All submissions, including submissions by project members, require review. We
use GitHub pull requests for this purpose. Consult
[GitHub Help](https://help.github.com/articles/about-pull-requests/) for more
information on using pull requests.

## Community Guidelines

This project follows [Google's Open Source Community Guidelines](https://opensource.google.com/conduct/).

# Suggested contributing workflow

## To start

* Fork this project on Github.
* Install the module from for your fork instead of Drupal.org on your local. (See below.)
* If you want to run this module against an actual Apigee org, you can [create an Apigee Edge trial organization](https://login.apigee.com/login).
  However, the automated tests use mock Apigee Edge server response and no Apigee org or account is needed.
* Install the module from for your fork instead of Drupal.org on your local. (See below.)

## For daily work

* Create a new branch in your fork repository, ex.: patch-1.
* Add changes to the code. If you implement new features, add new
tests to cover the implemented functionality. If you modify existing features, update related tests.
* Push your changes to your repo's patch-1 branch
* Create a pull request for the project

## Installing module from your fork instead of Drupal.org

Create a new branch on Github.com in your fork for your fix, ex.: patch-1.

Update your composer.json and install the module from your fork:
```bash
cd [DRUPAL_ROOT]
composer config repositories.forked-apigee_m10n vcs https://github.com/[YOUR-GITHUB-USERNAME]/apigee-edge-drupal
```

It is important to require a branch/tag here that does not exist in the Drupal.org repo otherwise code
gets pulled from that repo. For example, dev-8.x-1.x condition would pull the code from Drupal.org repo
instead of your fork. The command below will clone the `patch-1` branch:

```bash
composer require drupal/apigee_m10n:dev-patch-1
```

If you would like to keep your fork always up-to-date with recent changes in
upstream then add Apigee repo as a remote (one time only):

```bash
cd [DRUPAL_ROOT]/modules/contrib/apigee_m10n
git remote add upstream https://github.com/apigee/apigee-edge-drupal.git
git fetch upstream
```

For daily bases, rebase your current working branch to get latest changes from
upstream:

```bash
cd [DRUPAL_ROOT]/modules/contrib/apigee_m10n
git fetch upstream
git rebase upstream/8.x-1.x
```

After you have installed the module from your fork you can easily create new
branches for new fixes on your local:

```bash
cd [DRUPAL_ROOT]/modules/contrib/apigee_m10n
git fetch upstream
git checkout -b patch-2 upstream/8.x-1.x
## Add your awesome changes.
git push -u origin patch-2:patch-2 # Push changes to your repo.
## Create PR on Github.
```

## Running tests

This module has [Drupal kernel and functional tests](https://www.drupal.org/docs/8/testing/types-of-tests-in-drupal-8). These
tests are ran against pull requests using [CircleCi](https://circleci.com/). To learn more about the CircleCi setup, see
the README.md in the [.circleci directory](.circleci).

You can execute tests of this module with the following command (note the location
of the `phpunit` executable may vary):

```sh
./vendor/bin/phpunit -c core --verbose --color --group apigee_m10n
```

You can read more about running Drupal 8 PHPUnit tests [here](https://www.drupal.org/docs/8/phpunit/running-phpunit-tests).

For setting up PHPStorm to run tests with a click of a button, see:
<https://www.drupal.org/docs/8/phpunit/running-phpunit-tests-within-phpstorm>.

### The API response mocking system

The `apigee_mock_client` module included under `./tests` is responsible for faking API
responses from the Apigee SDK. Tests are written to queue API responses for all API calls
that will be made by the SDK connector. The response mocking system avoids the need for
an actual Apigee organization credentials to contribute to this module. There are many
examples in the test suite on how to write tests and queue mock responses. Responses are
returned in the chronological order they are queued.

In order to run tests against an actual Apigee organization, you must first set up
credentials according to the instructions in the instructions in the [Apigee Edge Drupal module](https://github.com/apigee/apigee-edge-drupal/blob/8.x-1.x/CONTRIBUTING.md#running-tests).
Then integration can be enabled by setting `APIGEE_INTEGRATION_ENABLE` environment variable
to "1" i.e. by setting `<env name="APIGEE_INTEGRATION_ENABLE" value='1' />` in phpunit.xml.

If needed, you can set environment variables multiple ways, either by defining them with
`export` or `set` in the terminal or creating a copy of the `core/phpunit.xml.dist`
file as `phpunit.xml` and specifying them in that file.

## Best practices

### Pulling in un-merged dependencies:

If your pull request relies on changes that are not yet available in Apigee Edge
Client Library for PHP's or the Apigee Edge Drupal module's latest stable release,
It is still possible to. pull in changes from an existing pull request.

Please *temporarily* add required changes as patches to module's `composer.json` file.
This way this module's tests could pass on Travis CI.

#### Example:

You can easily get a patch file from any Github pull requests by adding `.diff`
to end of the URL.

- Pull request: <https://github.com/apigee/apigee-client-php/pull/1>
- Patch file: <https://github.com/apigee/apigee-client-php/pull/1.diff>

composer.json:

```js
        "patches": {
            "apigee/apigee-client-php": {
                "Fix for a bug": "https://patch-diff.githubusercontent.com/raw/apigee/apigee-client-php/pull/1.diff"
            }
        }
```

**Note:** Apigee Client Library for PHP patches should be removed from the
module's composer.json before the next stable release. Code changes cannot be
merged until the related PR(s) have not been released in a new stable version of
the Apigee Client Library for PHP.
