# Notes

[![build state](https://travis-ci.org/owncloud/notes.png)](https://travis-ci.org/owncloud/notes)[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/owncloud/notes/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/owncloud/notes/?branch=master)

The Notes app is a distraction free notes taking app. It offers a [RESTful API](https://github.com/owncloud/notes/wiki/API-0.2) for app developers. The source code is [available on GitHub](https://github.com/owncloud/notes)

For further developer and user documentation please visit [the wiki](https://github.com/owncloud/notes/wiki)

# Maintainers
- [Lukas Reschke](https://github.com/LukasReschke)

# Minimum PHP Version
* PHP >= 5.4.0

# Minimum ownCloud Version
* >= 8.0.0 (for newer releases)

# Supported Webservers
* Apache


Bugs
----
Before reporting bugs:

* We do not support Internet Explorer and Safari (Patches accepted though, except for IE < 10)
* get the newest version of the Notes app
* [check if they have already been reported](https://github.com/owncloud/notes/issues?state=open)


# App Store

## Installation

- Go to the ownCloud apps page
- Activate the **Notes** app in the apps menu

## Keep up to date

The **Notes** App can be updated through the ownCloud apps page.


# Git (development version)

## Installation

* Clone the **Notes** app into the **/var/www/owncloud/apps/** directory

    git clone https://github.com/owncloud/notes.git

* Activate the **Notes** app in the apps menu


## Keep up to date

To update the Notes app use::

    cd /var/www/owncloud/apps/notes
    git pull --rebase origin master
