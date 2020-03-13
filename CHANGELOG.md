# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and follows the requirements of the [Nextcloud Appstore Metadata specification](https://nextcloudappstore.readthedocs.io/en/latest/developer.html#changelog).

Types of changes:
- *Added* for new features.
- *Changed* for changes in existing functionality.
- *Deprecated* for soon-to-be removed features.
- *Removed* for now removed features.
- *Fixed* for any bug fixes.
- *Security* in case of vulnerabilities. 

## [Unreleased]


## 3.2.0 - 2020-03-14

### Changed
- new undo design using notifications (#431)
- new app navigation design (`@nextcloud/vue` #481)

### Fixed
- dependency updates (#463, #475, #480 [security], #481 [security])
- maintenance (#451, #462, #465, #469, #472)


## 3.1.5 - 2020-02-12

### Fixed
- fix regression for PHP 7.0


## 3.1.4 - 2020-02-06

### Fixed
- fix regression for v3.1.3 (#459)


## 3.1.3 - 2020-02-05

### Changed
- API: send HTTP 404 if note is not found (#457)

### Fixed
- fix InsufficientStorageException on unknown free space (#456)


## 3.1.2 - 2020-02-03

### Added
- support for Nextcloud 19 (#439)
- API: send HTTP 507 if storage is insufficient (#438/#449)

### Fixed
- fix wrapping in a note's code elements (#447)
- fix link-color in preview mode (#448)
- improved error handling (#449)
- improve error handling if notes path fails (#450)
- dependency updates (#441, #452)


## 3.1.1 - 2020-01-03

### Fixed
- fix sidebar behaviour in mobile mode (#425; thanks to @gko)
- fix checkmark in editor (#419, #427)
- speed-up switching categories (#424)


## 3.1.0 - 2019-12-23

### Added
- undo for deleted notes (#54, #398)

### Changed
- App navigation was confusing (#364, #373)

### Fixed
- set font color to black for printing (#401; thanks to @bovender)
- fix search result in app-navigation (#399)
- update favorite only if different to current state (#407, #409)
- warning if JavaScript was not built (#408)
- improve code quality (#380, #381, #394, #396, #409)
- dependency updates (#387, #373, #397, #400, #405, #412, #414)


## 3.0.3 - 2019-09-17

### Fixed
- fix null error in Sidebar subtitle (#374)
- fix fullscreen wording (#375)
- disable editor if note is deleting (#377)
- dependency updates (#372)


## 3.0.2 - 2019-08-31

### Fixed
- code block style (revert) (#362, #349)
- table layout in preview mode (#362, #355)
- dependency updates (#348, #354, #361, #363)


## 3.0.1 - 2019-07-22

### Added
- media query for printing style (#323)
- tooltip with subcategory documentation (#341)

### Fixed
- code block style on Chrome (#340)


## 3.0.0 - 2019-06-14
### Added
- preview mode (#315, #23)
- group list of notes by timeslots (#313, #319)
- sidebar for metadata details (instead of status-bar) (#290)
- welcome screen (#290, #14)
- show placeholder if note is empty (#290)
- action: show all notes in the category of a note (#290)

### Changed
- moved to Vue.js from AngularJS (complete rewrite of JavaScript) (#290, #241)
- moved editor to EasyMDE from SimpleMDE (#290, #204)
- fit text horizontally (#182)

### Removed
- removed support for Nextcloud 14 (require at least NC15)

### Fixed
- first run experience (#14)
- put star on the left side of the note (#2)
- new list item makes scrollbar appear until text is put in (#119)
- last line of note partially obscured by status bar (#296)
- full-screen mode: scrolling not possible (#279)
- copy & paste on text in Markdown syntax (#199)
- when selecting text, the App navigation bar opens (#282)
- title underlined with "=" doesn't get rendered in edit view (#259)
- code quality (#314, #290)
- editor code style (#305, #321)


## 2.6.0 - 2019-04-16
- Checkbox functionality (#303)
- require at least NC14 (#283)

## 2.5.1 - 2018-11-29
- improve theme compability (#272)
- don't change modified when updating category (#276)
- use minified version of JS-libs (#275)
- fix design issues in some browsers (#277)

## 2.5.0 - 2018-11-17
- new: front-end for categories (#8, #228, #210, #265)
- show more details about current search (#264)
- fix: monospace font for code-block (#258)
- fix: test if file is a note is now case insensitive (#262)
- refactor PHP files (#266)

## 2.4.2 - 2018-09-16
- fix: favorite tooltip stayed open when clicked (#251)
- fix: error handling and work-around for "missing signature" error when using server-side encryption (#254)
- fix: 3rd-party API returned wrong HTTP code when user credentials are wrong (#255)

## 2.4.1 - 2018-08-30
- fix: note ID in URL param is ignored (#239)
- some fixes (#240, #243)
- update/remove obsolete JS dependencies (#222, #246)

## 2.4.0 - 2018-08-02
- add setting for notes path (#207)
- add setting for default file suffx (#223)
- make app compatible with Nextcloud 14 (#185, #234)
- use new search API for Nextcloud >= 14 (#227)
- speed-up loading list of notes (#233)
- better error handling (faulty files) (#188)
- design optimizations/fixes (#187, #208, #213, #226, #234)
- more fixes (#215, #216, #219)

## 2.3.2 - 2017-12-04
### Added
- manual save with Ctrl + S (#137)

### Fixed
- proper handling of multibyte characters (#125)
- better error handling (#134, #137, #148 )

## 2.3.1 - 2017-07-03
### Fixed
- fixes database xml schema for the app store

## 2.3.0 - 2017-07-03
### Added
- category backend (frontend coming soon)
- distraction free mode

### Changed
- switch editor from mdedit to simplemde
- cleaner note titles
- design improvements
- API speed up through ETags

## 2.2.0 - 2017-01-13
- new feature: show notes from sub-directories
- fix issues with windows line-breaks
- design improvements
- improve presentation in Nextcloud app-store
- updated translations

## 2.1.0 - 2016-12-13
- new feature: set a note as favorite (star/unstar)
- new feature: simple search functionality
- design improvements, Nextcloud makeover
- API: let the modified time be changeable
- updated translations

## 2.0.2 - 2016-08-01

## 2.0.1 - 2016-03-23

## 2.0 - 2016-01-11

## 1.1.0 - 2015-05-22

## 1.0.0 - 2015-02-20

## 0.9 - 2014-05-18

## 0.8 - 2014-05-09

## 0.7 - 2014-04-29
