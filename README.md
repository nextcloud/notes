<!--
  - SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Notes
[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/notes)](https://api.reuse.software/info/github.com/nextcloud/notes)

<!-- The following paragraph should be kept synchronized with the description in appinfo/info.xml -->
The Notes app is a distraction free notes taking app for [Nextcloud](https://www.nextcloud.com/). It provides categories for better organization and supports formatting using [Markdown](https://en.wikipedia.org/wiki/Markdown) syntax. Notes are saved as files in your Nextcloud, so you can view and edit them with every Nextcloud client. Furthermore, a separate [REST API](https://github.com/nextcloud/notes/blob/master/docs/api/README.md) allows for an easy integration into apps ([Android](https://github.com/nextcloud/notes-android), [iOS](https://github.com/nextcloud/notes-ios), as well as [3rd-party apps](https://github.com/nextcloud/notes/wiki#3rd-party-clients) which allow convenient access to your Nextcloud notes). Further features include marking notes as favorites.

![Screenshot of Nextcloud Notes](https://raw.githubusercontent.com/nextcloud/screenshots/master/apps/Notes/notes.png)


## :rocket: Installation
In your Nextcloud, simply navigate to »Apps«, choose the category »Office«, find the Notes app and enable it. Then open the Notes app from the app menu.

Nextcloud will notify you about possible updates. Please have a look at [CHANGELOG.md](CHANGELOG.md) for details about changes.


## :exclamation: Bugs
Before reporting bugs:

* get the newest version of the Notes app
* please consider also installing the [latest development version](https://github.com/nextcloud/notes/archive/master.zip)
* [check if they have already been reported](https://github.com/nextcloud/notes/issues)


## :busts_in_silhouette: Maintainers
- [Kristof Hamann](https://github.com/korelstar)
- [Hendrik Leppelsack](https://github.com/Henni) (formerly)
- [Lukas Reschke](https://github.com/LukasReschke) (formerly)


## :warning: Developer Info

[![Lint](https://github.com/nextcloud/notes/workflows/Lint/badge.svg?branch=master&event=push)](https://github.com/nextcloud/notes/actions?query=workflow%3ALint+event%3Apush+branch%3Amaster)
[![Test](https://github.com/nextcloud/notes/workflows/Test/badge.svg?branch=master&event=push)](https://github.com/nextcloud/notes/actions?query=workflow%3ATest+event%3Apush+branch%3Amaster)

### Building the app

1. Clone this into your `apps` folder of your Nextcloud
2. In a terminal, run the command `make dev-setup` to install the dependencies
3. Then to build the Javascript run `make build-js` or `make watch-js` to
   rebuild it when you make changes
4. Enable the app through the app management of your Nextcloud


### REST API for third-party apps

The notes app provides a JSON-API for third-party apps. Please have a look at our **[API documentation](docs/api/README.md)**.


### Admin configuration

It is possible to specify different defaults for the notes settings of new users using `occ` commands like these:

```
occ config:app:set notes noteMode --value="preview"
occ config:app:set notes fileSuffix --value=".md"
occ config:app:set notes defaultFolder --value="Shared notes"
```

| Setting | Property name | Default | Other available option(s) |
|---------|---------------|---------|---------------------------|
| Display mode for notes | noteMode | edit | preview |
| File extension for new notes | fileSuffix | .txt | .md |
| Folder to store your notes | defaultFolder | Notes | _Custom_ |
