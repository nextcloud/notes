# Notes

<!--
[![build state](https://travis-ci.org/nextcloud/notes.png)](https://travis-ci.org/nextcloud/notes) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/notes/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/notes/?branch=master)
-->

<!-- The following paragraph should be kept synchronized with the description in appinfo/info.xml -->
The Notes app is a distraction free notes taking app. It supports formatting using [Markdown](https://en.wikipedia.org/wiki/Markdown) syntax. Notes are saved as files in your Nextcloud, so you can view and edit them with every Nextcloud client. Furthermore, a separate [RESTful API](https://github.com/nextcloud/notes/wiki/API-0.2) allows for an easy integration into third-party apps (currently, there are notes apps for [Android](https://github.com/stefan-niedermann/nextcloud-notes) and [iOS](https://github.com/owncloud/notes-iOS-App) which allow convenient access to your Nextcloud notes). Further features include marking notes as favorites and future versions will provide categories for better organization.

![Screenshot of Nextcloud Notes](https://cloud.githubusercontent.com/assets/4741199/21027342/b70a6be2-bd90-11e6-9f12-eca46d6c505a.png)

For further developer and user documentation please visit [the wiki](https://github.com/nextcloud/notes/wiki)

## :busts_in_silhouette: Maintainers
- [Hendrik Leppelsack](https://github.com/Henni)
- [Lukas Reschke](https://github.com/LukasReschke)

## :link: Requirements
**Minimum PHP Version**
* PHP >= 5.4

**Minimum Nextcloud / ownCloud Version**
* Nextcloud >= 9.0
* ownCloud >= 8.1

**Supported Webservers**
* Apache

**Supported Browsers**
* latest 3 versions of Chrome, Firefox and Edge 
* we *do not* officially support Internet Explorer and Safari (Patches are accepted though)

## :exclamation: Bugs
Before reporting bugs:

* check the requirements above
* get the newest version of the Notes app
* [check if they have already been reported](https://github.com/nextcloud/notes/issues?state=open)


## :warning: Git (development version)

**Installation**

* Clone the **Notes** app into the `/var/www/nextcloud/apps/` directory

    `git clone https://github.com/nextcloud/notes.git`

* Activate the **Notes** app in the apps menu


**Keep up to date**

To update the Notes app use::

    cd /var/www/nextcloud/apps/notes
    git pull --rebase origin master
    

