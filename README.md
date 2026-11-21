# PodcastBundle

Bundle for the [PuMuKIT Video Platform 6.0](https://github.com/pumukit/PuMuKIT/tree/6.0.x) or higher. It publishes the
videos of the `PUCHPODCAST` publication channel as RSS podcast feeds for Apple Podcasts, iVoox, Spotify and the other
podcast apps and directories.

## Installation

```bash
composer require teltek/pumukit-podcast-bundle
```

If Symfony Flex does not do it, add the bundle to `config/bundles.php`:

```php
Pumukit\PodcastBundle\PumukitPodcastBundle::class => ['all' => true],
```

Import its routes, for example in `config/routes/pumukit_podcast.yaml`:

```yaml
pumukit_podcast:
    resource: "@PumukitPodcastBundle/Resources/config/routing.yml"
    prefix:   /
```

Create the `PUCHPODCAST` publication channel with `php bin/console pumukit:setup` of PuMuKIT, which runs the setup of
every enabled bundle, or only with:

```bash
php bin/console podcast:init:tags
```

## Feeds

| Route | Episodes |
|-------|----------|
| `/podcast/video.xml` | The video episodes |
| `/podcast/audio.xml` | The audio episodes |
| `/podcast/series/{id}/video.xml` | The video episodes of a series |
| `/podcast/series/{id}/audio.xml` | The audio episodes of a series |
| `/podcast/series/{id}/collection.xml` | The episodes of a series, with the video track or else the audio one |
| `/podcast/{tag}/audio.xml` | The audio episodes of a tag code or of a group of `tag_feeds.groups` |
| `/podcast/list.xml` | The `collection.xml` feeds of the series with episodes |

An episode is a video that is published, has the `PUCHPODCAST` channel (or the `tag_feeds.channel` one in the tag
feeds), has a public broadcast and has a visible track with the `podcast` tag. The audio episodes use the track with
the `podcast` and `audio` tags, and the video episodes use a track with `podcast` and without `audio`. The series must
not be hidden. The episodes are sorted by publication date, newest first, and each feed only has the newest ones:
`feed_limit` in the global and tag feeds and `series_feed_limit` in the series feeds.

Each episode links to `/podcast/media/{track}.{ext}`, a public URL that does not expire. It checks the same rules and,
like `/trackfile/`, answers a `302` to a signed URL of the download server of PuMuKIT (`PUMUKIT_DOWNLOAD_SERVER_URL`),
or to the URL of an external track; PHP never sends the file. The podcast apps must follow the redirect and reach the
host of the download server. It answers `404` when the file is missing and `503` when the download server cannot serve
it (no download server configured, a file outside the storage or an HLS track in a shared directory). A track whose file
is missing is left out of the feeds.

### Cache

A generated feed is kept for `cache_ttl` seconds in the `pumukit_podcast.cache` pool, which uses `cache.app` (the
filesystem by default, or Redis if `cache.app` uses it), so the episodes published in the meantime show up when it
expires. On each server, only one request generates an expired feed and the others wait for it. The responses are public, with the
remaining time as `max-age`, an `ETag` and a `Last-Modified` date, so a podcast app that already has the feed gets a
`304 Not Modified` without content, and a proxy cache in front of PuMuKIT can serve them.

To empty the cache, for example after changing the configuration:

```bash
php bin/console cache:pool:clear pumukit_podcast.cache
```

## Configuration

```yaml
pumukit_podcast:
    enable: true
    feed_limit: 100
    series_feed_limit: 500
    cache_ttl: 1800
    image: '/images/podcast-cover.jpg'
    language: 'es'
    tag_feeds:
        channel: 'PUCHPODCAST'
        groups:
            sciences: ['U210000', 'U250000', 'U220000']
            health: ['U310000', 'U240000', 'U320000', 'U610000']
    channel_title: 'PuMuKIT Channel'
    channel_description: 'PuMuKIT description of the channel'
    channel_copyright: 'PuMuKIT Team 2015'
    itunes_category: 'Education'
    itunes_summary: 'Education channel'
    itunes_subtitle: 'Itunes subtitle'
    itunes_author: 'PuMuKIT-TV'
    itunes_explicit: false
```

| Option | Default | Description |
|--------|---------|-------------|
| `enable` | `true` | If false, the feeds, the episodes and the back-office modal return 404 and nothing else of the bundle is loaded: no commands, services or cache pool |
| `feed_limit` | `100` | Maximum number of episodes of the global and tag feeds |
| `series_feed_limit` | `500` | Maximum number of episodes of the series feeds |
| `cache_ttl` | `1800` | Seconds a generated feed is served from the cache; `0` generates it on every request |
| `image` | `pumukit.info.logo` | Absolute or relative URL of the channel cover. Apple Podcasts asks for a square JPEG or PNG of 1400 to 3000 pixels |
| `language` | `kernel.default_locale` | Language of the feeds and of the texts of the episodes |
| `tag_feeds.channel` | `PUCHPODCAST` | Publication channel the episodes of `/podcast/{tag}/audio.xml` must have |
| `tag_feeds.groups` | `[]` | Feeds `/podcast/{name}/audio.xml` with the episodes of any of their tag codes |
| `channel_title` | `PuMuKIT Channel` | Title of the global and tag feeds; if empty, `pumukit.info.title` |
| `channel_description` | `PuMuKIT description of the channel` | Description of the global and tag feeds; if empty, `pumukit.info.description` |
| `channel_copyright` | `PuMuKIT Team 2015` | Copyright of the feeds, and of the series without the `copyright` property |
| `itunes_category` | `Education` | Category of the feeds, and of the series without the `itunescategory` property. It must be one of the [Apple Podcasts categories](https://podcasters.apple.com/support/1691-apple-podcasts-categories), in English |
| `itunes_summary` | `Education channel` | Summary of the global and tag feeds |
| `itunes_subtitle` | `Itunes subtitle` | Subtitle of the feeds, and of the series without subtitle |
| `itunes_author` | `PuMuKIT-TV` | Author of the feeds |
| `itunes_explicit` | `false` | Whether the episodes have explicit content |

The series feeds take their title, description and subtitle from the series. The owner email of the feeds is
`pumukit.info.email`.

## Back-office

The info icon of the **Podcast** channel in the **Publication** tab of a video opens a modal, for the users with
`ROLE_ACCESS_MULTIMEDIA_SERIES`, with the podcast status of the video and the URLs of the feeds of its series.

## Upgrade from 5.x

- The bundle requires PHP 8.5 and PuMuKIT 6.0.
- **The feeds only have the newest episodes** (`feed_limit` and `series_feed_limit`), sorted by publication date, and
  they are cached for `cache_ttl` seconds. Before, every feed had all the episodes, in no order, and was generated on
  each request, which could take down an installation with many videos. `pubDate` is now the publication date of the
  video instead of the recording date.
- **The URL of the episodes changes** to `/podcast/media/{track}.{ext}`: the files are no longer served from
  `/storage`, which PuMuKIT 6.0 blocks, and the `/trackfile/` URLs expire. The `guid` keeps its previous value, with
  `isPermaLink="false"`, so the podcast apps do not download the episodes again. The `guid` is the URL of the track:
  `pumukit:hls:move-to-own-directory` of the core changes the URL of the HLS tracks it moves, so the podcast apps may
  show those episodes again.
- The iTunes U categories are removed: the `itunesu` namespace of the feeds, the `ITUNESU` tags and the
  `pumukit:podcast:init:itunesu` command. iTunes U closed in 2021. The `ITUNESU` tags already in the database are not
  deleted.
- The namespaces of the feeds are the ones Apple Podcasts and the Atom specification define, with `http`;
  `itunes:explicit` is `true` or `false`, `itunes:duration` is in seconds, `itunes:image` of the episodes has the
  `href` attribute, and the texts are escaped and cleaned of characters that are not valid in XML.
- The language of the feeds and of the texts is `language`, or the default locale, instead of the language of the
  request, so that the cached feeds are the same for everybody. The channel cover is `image`, or the logo of
  `pumukit.info`: the default images of the bundle were removed long ago.
- The `/podcast/{tag}/audio.xml` feed returns 404 if the tag does not exist. The projects that override
  `FeedController` to group tags, such as the UNESCO areas, can drop it and use `tag_feeds.groups`, with
  `tag_feeds.channel: PUCHWEBTV` if their episodes are the published videos of the portal. The controller is rewritten,
  so those overrides no longer work.
- The `frontend` filter of the bundle is removed: the feeds and the episodes check the rules in their own queries.
- The modal of the **Publication** tab uses Bootstrap 5 and needs `ROLE_ACCESS_MULTIMEDIA_SERIES`. `podcast:init:tags`
  names the channel *Podcast* instead of *PodcastEDU/iTunesU*; the existing tag keeps its title.
- `podcast:init:tags` no longer needs `--force` and does nothing if the channel exists, instead of failing, so
  `pumukit:setup` runs it on every deployment.
