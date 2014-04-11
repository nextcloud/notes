# Notes

[![build state](https://travis-ci.org/owncloud/notes.png)](https://travis-ci.org/owncloud/notes)

The Notes app is a distraction free notes taking app. It offers a [RESTful API](https://github.com/owncloud/notes/wiki/API-0.2) for app developers. The source code is [available on GitHub](https://github.com/owncloud/notes)

For further developer and user documentation please visit [the wiki](https://github.com/owncloud/notes/wiki)

# Maintainers

Maintainers wanted for additional features!

* [Bernhard Posselt](https://github.com/Raydiation)
* [Jan-Christoph Borchardt](https://github.com/jancborchardt) (Design)

# Minimum PHP Version
* PHP >= 5.3.6

# Minimum ownCloud Version
* >= 6.0.3

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