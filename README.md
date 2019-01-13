# Notes

<!--
[![build state](https://travis-ci.org/nextcloud/notes.png)](https://travis-ci.org/nextcloud/notes) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/notes/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/notes/?branch=master)
-->

<!-- The following paragraph should be kept synchronized with the description in appinfo/info.xml -->
The Notes app is a distraction free notes taking app for [Nextcloud](https://www.nextcloud.com/). It provides categories for better organization and supports formatting using [Markdown](https://en.wikipedia.org/wiki/Markdown) syntax. Notes are saved as files in your Nextcloud, so you can view and edit them with every Nextcloud client. Furthermore, a separate [RESTful API](https://github.com/nextcloud/notes/wiki/API-0.2) allows for an easy integration into third-party apps (currently, there are notes apps for [Android](https://github.com/stefan-niedermann/nextcloud-notes), [iOS](https://github.com/owncloud/notes-iOS-App) and the [console](https://git.danielmoch.com/nncli/about) which allow convenient access to your Nextcloud notes). Further features include marking notes as favorites.

![Screenshot of Nextcloud Notes](https://raw.githubusercontent.com/nextcloud/screenshots/master/apps/Notes/notes.png)


## :rocket: Installation
In your Nextcloud, simply navigate to »Apps«, choose the category »Office«, find the Notes app and enable it. Then open the Notes app from the app menu.


## :exclamation: Bugs
Before reporting bugs:

* get the newest version of the Notes app
* please consider also installing the [latest development version](https://github.com/nextcloud/notes/archive/master.zip)
* [check if they have already been reported](https://github.com/nextcloud/notes/issues)


## :busts_in_silhouette: Maintainers
- [Kristof Hamann](https://github.com/korelstar)
- [Hendrik Leppelsack](https://github.com/Henni)
- [Lukas Reschke](https://github.com/LukasReschke)


## :warning: Git (development version)

**Installation**

* Clone the **Notes** app into the `/var/www/nextcloud/apps/` directory

    `git clone https://github.com/nextcloud/notes.git`

* Activate the **Notes** app in the apps menu


**Keep up to date**

To update the Notes app use::

    cd /var/www/nextcloud/apps/notes
    git pull --rebase origin master


**Building JavaScript**

If you want to change some JavaScript code, you have to consolidate the files in a build. Please follow the instructions in the [JavaScript directory](js/README.md).


**Third-party apps**

The notes app provides a JSON-API for third-party apps. You can find the documentation in [the wiki](https://github.com/nextcloud/notes/wiki).

