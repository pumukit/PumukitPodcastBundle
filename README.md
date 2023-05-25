PuMuKIT Podcast Bundle
======================

This bundle provides auto expired video feature.

```bash
composer require teltek/pumukit-podcast-bundle
```

if not, add this to config/bundles.php

```
Pumukit\PodcastBundle\PumukitPodcastBundle::class => ['all' => true]
```

Then execute the following commands

```bash
php bin/console cache:clear
php bin/console cache:clear --env=prod
php bin/console assets:install
```
