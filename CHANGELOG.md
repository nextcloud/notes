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

## 4.12.3

### Added

* feat(ui): switch to outline icons by @JuliaKirschenheuter [#1596](https://github.com/nextcloud/notes/pull/1596)
* Prevent exception for a share file by inviting a new guest account by @JuliaKirschenheuter [#1595](https://github.com/nextcloud/notes/pull/1595)
* feat(files): Expose image api for other clients as api v1.4 including routes fix by @oli-ver [#1600](https://github.com/nextcloud/notes/pull/1600)

### Fixed

* fix(files): replace outline plus icon with normal one by @JuliaKirschenheuter [#1613](https://github.com/nextcloud/notes/pull/1613)

### Dependencies

* [main] Fix npm audit by @nextcloud-command [#1597](https://github.com/nextcloud/notes/pull/1597)
* Chore(deps-dev): Bump php-cs-fixer/shim from 3.76.0 to 3.85.1 by @dependabot[bot] [#1603](https://github.com/nextcloud/notes/pull/1603)
* Chore(deps): Bump actions/checkout from 4.2.2 to 4.3.0 by @dependabot[bot] [#1612](https://github.com/nextcloud/notes/pull/1612)


## 4.12.2

### Fixed

* Fix: Update internalPath after updating title by @enjeck in [#1577](https://github.com/nextcloud/notes/pull/1577)
* Fixed Title heading sync broken - Failed to push steps by @theroch in [#1580](https://github.com/nextcloud/notes/pull/1580)

### Dependencies

* Update nextcloud/ocp dependencies by @nextcloud-command in [#1562](https://github.com/nextcloud/notes/pull/1562)
* Fix npm audit by @nextcloud-command in [#1563](https://github.com/nextcloud/notes/pull/1563)
* Chore(deps-dev): Bump squizlabs/php_codesniffer from 3.12.2 to 3.13.0 by @dependabot in [#1564](https://github.com/nextcloud/notes/pull/1564)
* Ci: update node.yml workflow from template by @nextcloud-command in [#1565](https://github.com/nextcloud/notes/pull/1565)
* Chore(deps): Bump @nextcloud/dialogs from 6.3.0 to 6.3.1 by @dependabot in [#1569](https://github.com/nextcloud/notes/pull/1569)
* Fix npm audit by @nextcloud-command in [#1571](https://github.com/nextcloud/notes/pull/1571)
* Chore(deps): Bump svenstaro/upload-release-action from 2.9.0 to 2.10.0 by @dependabot in [#1574](https://github.com/nextcloud/notes/pull/1574)
* Chore(deps-dev): Bump pbkdf2 from 3.1.2 to 3.1.3 by @dependabot in [#1575](https://github.com/nextcloud/notes/pull/1575)
* Ci: update reuse.yml workflow from template by @nextcloud-command in [#1582](https://github.com/nextcloud/notes/pull/1582)
* Chore(deps-dev): Bump phan/phan from 5.4.5 to 5.5.0 by @dependabot in [#1584](https://github.com/nextcloud/notes/pull/1584)
* Chore(deps-dev): Bump squizlabs/php_codesniffer from 3.13.0 to 3.13.2 by @dependabot in [#1585](https://github.com/nextcloud/notes/pull/1585)
* Chore(deps-dev): Bump php-cs-fixer/shim from 3.75.0 to 3.76.0 by @dependabot in [#1586](https://github.com/nextcloud/notes/pull/1586)
* Update nextcloud/ocp dependencies by @nextcloud-command in [#1588](https://github.com/nextcloud/notes/pull/1588)
* Chore(deps): Bump svenstaro/upload-release-action from 2.10.0 to 2.11.2 by @dependabot in [#1590](https://github.com/nextcloud/notes/pull/1590)
* Chore(deps): Bump skjnldsv/xpath-action from 7e6a7c379d0e9abc8acaef43df403ab4fc4f770c to f5b036e9d973f42c86324833fd00be90665fbf77 by @dependabot in [#1591](https://github.com/nextcloud/notes/pull/1591)


## 4.12.1

### Fixed

* Fix slow capabilities by @enjeck in [#1543](https://github.com/nextcloud/notes/pull/1543)
* Fix: Use info instead of warning to avoid filling logs by @enjeck in [#1530](https://github.com/nextcloud/notes/pull/1530)

### Dependencies

* Chore(deps): Bump peter-evans/create-pull-request from 7.0.7 to 7.0.8 by @dependabot in [#1500](https://github.com/nextcloud/notes/pull/1500)
* Chore(deps-dev): Bump guzzlehttp/guzzle from 7.9.2 to 7.9.3 by @dependabot in [#1512](https://github.com/nextcloud/notes/pull/1512)
* Chore(deps): Bump @nextcloud/vue from 8.24.0 to 8.26.1 by @dependabot in [#1540](https://github.com/nextcloud/notes/pull/1540)
* Chore(deps-dev): Bump http-proxy-middleware from 2.0.7 to 2.0.9 by @dependabot in [#1528](https://github.com/nextcloud/notes/pull/1528)
* Chore(deps): Bump @nextcloud/dialogs from 6.1.1 to 6.3.0 by @dependabot in [#1542](https://github.com/nextcloud/notes/pull/1542)
* Chore(deps-dev): Bump squizlabs/php_codesniffer from 3.12.0 to 3.12.2 by @dependabot in [#1537](https://github.com/nextcloud/notes/pull/1537)
* Chore(deps): Bump @nextcloud/moment from 1.3.2 to 1.3.4 by @dependabot in [#1539](https://github.com/nextcloud/notes/pull/1539)
* Chore(deps): Bump diff from 7.0.0 to 8.0.1 by @dependabot in [#1545](https://github.com/nextcloud/notes/pull/1545)
* Chore(deps-dev): Bump @nextcloud/stylelint-config from 3.0.1 to 3.1.0 by @dependabot in [#1554](https://github.com/nextcloud/notes/pull/1554)
* Update nextcloud/ocp dependencies by @nextcloud-command in [#1531](https://github.com/nextcloud/notes/pull/1531)
* Fix npm audit by @nextcloud-command in [#1550](https://github.com/nextcloud/notes/pull/1550)


## 4.12.0

### Added

* add SPDX headers by @luka-nextcloud in [#1360](https://github.com/nextcloud/notes/pull/1360)
* add notes path to capabilities by @tobiasKaminsky in [#1468](https://github.com/nextcloud/notes/pull/1468)

### Fixed

* fix: Adjust note item size to be one line by @juliusknorr in [#1387](https://github.com/nextcloud/notes/pull/1387)
* feat: migrate to files:node:updated by @luka-nextcloud in [#1377](https://github.com/nextcloud/notes/pull/1377)
* Adjust the welcome message paddings for mobile view by @falghamdi125 in [#1425](https://github.com/nextcloud/notes/pull/1425)
* Micro text change: Remove reference to https://github.com/djmoch/nncli by @per-oestergaard in [#1423](https://github.com/nextcloud/notes/pull/1423)
* Fix note caption colour for custom themes by @looowizz in [#1460](https://github.com/nextcloud/notes/pull/1460)
* Migrate notes search to NcTextField with clear button by @korelstar in [#1410](https://github.com/nextcloud/notes/pull/1410)
* Fix: Updated links to app repositories by @timosmit [1519](https://github.com/nextcloud/notes/pull/1519)


### Dependencies

* Chore(deps-dev): Bump @nextcloud/eslint-config from 8.4.1 to 8.4.2 by @dependabot in [#1483](https://github.com/nextcloud/notes/pull/1483)
* Chore(deps): Bump peter-evans/create-pull-request from 7.0.5 to 7.0.6 by @dependabot in [#1465](https://github.com/nextcloud/notes/pull/1465)
* Chore(deps): Bump @nextcloud/vue from 8.22.0 to 8.23.1 by @dependabot in [#1478](https://github.com/nextcloud/notes/pull/1478)
* Chore(deps-dev): Bump elliptic from 6.6.0 to 6.6.1 by @dependabot in [#1479](https://github.com/nextcloud/notes/pull/1479)
* Chore(deps): Bump actions/setup-node from 4.1.0 to 4.2.0 by @dependabot in [#1469](https://github.com/nextcloud/notes/pull/1469)
* Chore(deps): Bump @nextcloud/dialogs from 6.0.1 to 6.1.1 by @dependabot in [#1466](https://github.com/nextcloud/notes/pull/1466)
* Chore(deps-dev): Bump php-cs-fixer/shim from 3.64.0 to 3.66.0 by @dependabot in [#1457](https://github.com/nextcloud/notes/pull/1457)
* Chore(deps-dev): Bump squizlabs/php_codesniffer from 3.11.2 to 3.11.3 by @dependabot in [#1473](https://github.com/nextcloud/notes/pull/1473)
* chore(CI): Updating reuse.yml workflow from template by @nextcloud-command in [#1456](https://github.com/nextcloud/notes/pull/1456)
* Chore(deps-dev): Bump squizlabs/php_codesniffer from 3.11.1 to 3.11.2 by @dependabot in [#1458](https://github.com/nextcloud/notes/pull/1458)
* Chore(deps): Bump @nextcloud/dialogs from 5.3.7 to 6.0.1 by @dependabot in [#1414](https://github.com/nextcloud/notes/pull/1414)
* Chore(deps): Bump @nextcloud/vue from 8.19.0 to 8.21.0 by @dependabot in [#1421](https://github.com/nextcloud/notes/pull/1421)
* Chore(deps-dev): Update php-cs-fixer/shim requirement from 3.61.1 to 3.64.0 by @dependabot in [#1355](https://github.com/nextcloud/notes/pull/1355)
* Chore(deps): Bump fsfe/reuse-action from 4.0.0 to 5.0.0 by @dependabot in [#1419](https://github.com/nextcloud/notes/pull/1419)
* Chore(deps): Bump actions/setup-node from 4.0.3 to 4.1.0 by @dependabot in [#1404](https://github.com/nextcloud/notes/pull/1404)
* chore: Bump max-version of PHP to 8.4 by @enjeck in [#1427](https://github.com/nextcloud/notes/pull/1427)
* Chore(deps): Bump @nextcloud/vue from 8.21.0 to 8.22.0 by @dependabot in [#1455](https://github.com/nextcloud/notes/pull/1455)
* Chore(deps): Bump @nextcloud/moment from 1.3.1 to 1.3.2 by @dependabot in [#1451](https://github.com/nextcloud/notes/pull/1451)
* Chore(deps): Bump nanoid from 3.3.7 to 3.3.8 by @dependabot in [#1452](https://github.com/nextcloud/notes/pull/1452)
* chore: Add code owners according to maintenance responsibilities by @juliusknorr in [#1388](https://github.com/nextcloud/notes/pull/1388)
* Chore(deps-dev): Bump elliptic from 6.5.7 to 6.6.0 by @dependabot in [#1409](https://github.com/nextcloud/notes/pull/1409)
* Chore(deps-dev): Bump nextcloud/coding-standard from 1.3.1 to 1.3.2 by @dependabot in [#1411](https://github.com/nextcloud/notes/pull/1411)
* Chore(deps-dev): Bump squizlabs/php_codesniffer from 3.10.3 to 3.11.1 by @dependabot in [#1433](https://github.com/nextcloud/notes/pull/1433)
* Chore(deps): Bump peter-evans/create-pull-request from 7.0.3 to 7.0.5 by @dependabot in [#1393](https://github.com/nextcloud/notes/pull/1393)
* Chore(deps): Bump actions/checkout from 4.1.7 to 4.2.2 by @dependabot in [#1401](https://github.com/nextcloud/notes/pull/1401)
* Chore(deps): Bump vue-material-design-icons from 5.3.0 to 5.3.1 by @dependabot in [#1386](https://github.com/nextcloud/notes/pull/1386)
* Chore(deps): Bump @nextcloud/axios from 2.5.0 to 2.5.1 by @dependabot in [#1379](https://github.com/nextcloud/notes/pull/1379)
* Chore(deps): Bump cookie and express by @dependabot in [#1390](https://github.com/nextcloud/notes/pull/1390)
* Chore(deps-dev): Bump @nextcloud/webpack-vue-config from 6.1.0 to 6.2.0 by @dependabot in [#1391](https://github.com/nextcloud/notes/pull/1391)
* Chore(deps): Bump @nextcloud/vue from 8.18.0 to 8.19.0 by @dependabot in [#1374](https://github.com/nextcloud/notes/pull/1374)
* Chore(deps-dev): Bump @nextcloud/webpack-vue-config from 6.0.1 to 6.1.0 by @dependabot in [#1376](https://github.com/nextcloud/notes/pull/1376)
* Update nextcloud/ocp dependencies
* Fix npm audit by @nextcloud-command in [#1415](https://github.com/nextcloud/notes/pull/1415)
* Move to more standard CI pipelines by @juliusknorr in [#1389](https://github.com/nextcloud/notes/pull/1389)
## 4.11.0

### Fixed

- fix: Switch to vue-frag instead of vue-fragment to avoid errors during delete @juliushaertl [#1322](https://github.com/nextcloud/notes/pull/1322)
- fix: apply css variable --default-clickable-area @luka-nextcloud [#1323](https://github.com/nextcloud/notes/pull/1323)
- fix/shared notes @juliushaertl [#1320](https://github.com/nextcloud/notes/pull/1320)
- Fix CI @juliushaertl[#1364](https://github.com/nextcloud/notes/pull/1364)
- ci: Update workflows @nickvergessen [#1359](https://github.com/nextcloud/notes/pull/1359)

### Dependencies

- Chore(deps): Bump @nextcloud/vue from 7.12.7 to 8.14.0 @dependabot [#1318](https://github.com/nextcloud/notes/pull/1318)
- Chore(deps): Bump skjnldsv/read-package-engines-version-actions from 2.2 to 3 @dependabot [#1272](https://github.com/nextcloud/notes/pull/1272)
- Chore(deps): Bump skjnldsv/block-fixup-merge-action from 1 to 2 @dependabot [#1292](https://github.com/nextcloud/notes/pull/1292)
- Chore(deps-dev): Bump @nextcloud/stylelint-config from 2.4.0 to 3.0.1 @dependabot [#1294](https://github.com/nextcloud/notes/pull/1294)
- Updating dependabot-approve-merge.yml workflow from template @nextcloud-command [#1281](https://github.com/nextcloud/notes/pull/1281)
- chore: Bump minimum supported versions to 28 @juliushaertl [#1321](https://github.com/nextcloud/notes/pull/1321)
- chore: update workflows from templates @skjnldsv [#1324](https://github.com/nextcloud/notes/pull/1324)
- Chore(deps): Bump markdown-it from 13.0.2 to 14.1.0 @dependabot [#1252](https://github.com/nextcloud/notes/pull/1252)
- Chore(deps-dev): Update php-cs-fixer/shim requirement from 3.54.0 to 3.59.3 @dependabot [#1316](https://github.com/nextcloud/notes/pull/1316)
- Chore(deps): Bump @nextcloud/vue from 8.14.0 to 8.15.0 @dependabot [#1329](https://github.com/nextcloud/notes/pull/1329)
- Chore(deps): Bump fast-xml-parser from 4.2.5 to 4.4.1 @dependabot [#1331](https://github.com/nextcloud/notes/pull/1331)
- Chore(deps): Bump @nextcloud/vue from 8.15.0 to 8.15.1 @dependabot [#1332](https://github.com/nextcloud/notes/pull/1332)
- Chore(deps-dev): Update php-cs-fixer/shim requirement from 3.59.3 to 3.61.1 @dependabot [#1334](https://github.com/nextcloud/notes/pull/1334)
- Chore(deps): Bump @nextcloud/vue from 8.15.1 to 8.16.0 @dependabot [#1338](https://github.com/nextcloud/notes/pull/1338)
- chore: update workflows from templates @nextcloud-command [#1348](https://github.com/nextcloud/notes/pull/1348)
- Chore(deps-dev): Bump webpack from 5.88.2 to 5.94.0 @dependabot [#1354](https://github.com/nextcloud/notes/pull/1354)
- Chore(deps): Bump markdown-it-bidi from 0.1.0 to 0.2.0 @dependabot [#1352](https://github.com/nextcloud/notes/pull/1352)
- Chore(deps): Bump axios from 1.6.8 to 1.7.7 @dependabot [#1369](https://github.com/nextcloud/notes/pull/1369)
- Chore(deps): Bump @nextcloud/dialogs from 5.3.5 to 5.3.7 @dependabot [#1347](https://github.com/nextcloud/notes/pull/1347)
- Chore(deps): Bump diff from 5.2.0 to 7.0.0 @dependabot [#1358](https://github.com/nextcloud/notes/pull/1358)
- Chore(deps-dev): Bump express from 4.19.2 to 4.21.0 @dependabot [#1368](https://github.com/nextcloud/notes/pull/1368)
- Chore(deps-dev): Bump elliptic from 6.5.4 to 6.5.7 @dependabot [#1351](https://github.com/nextcloud/notes/pull/1351)
- Chore(deps): Bump @nextcloud/vue from 8.16.0 to 8.18.0 @JuliaKirschenheuter [#1370](https://github.com/nextcloud/notes/pull/1370)

## 4.10.0

### Added

- Compatibility with Nextcloud 29

### Fixed

- Fix "TypeError: t is undefined" @HolgerHees [#1264](https://github.com/nextcloud/notes/pull/1264)

## 4.9.4

### Fixed

- fix: Shared folder check @provokateurin [#1263](https://github.com/nextcloud/notes/pull/1263)

## 4.9.3

### Fixed

- fix: Avoid conflicts on notes folder creation @juliushaertl [#1260](https://github.com/nextcloud/notes/pull/1260)

### Other

- chore: Fix php-cs-fixer @juliushaertl [#1261](https://github.com/nextcloud/notes/pull/1261)

## 4.9.2

### Added

- Add bidi support @ahangarha [#1191](https://github.com/nextcloud/notes/pull/1191)

### Fixed

- chore: Bump max-version of PHP to 8.3 @juliushaertl [#1194](https://github.com/nextcloud/notes/pull/1194)


## 4.9.1

### Fixed

- fix: Resolve file list dependency of the sidebar on Nextcloud <= 27 @juliushaertl [#1174](https://github.com/nextcloud/notes/pull/1174)
- fix: Avoid using constant that is not available on 25 @juliushaertl [#1182](https://github.com/nextcloud/notes/pull/1182)

## 4.9.0

### Added

- Note sharing and file sidebar integration @luka-nextcloud [#1146](https://github.com/nextcloud/notes/pull/1146)
- Replace sidebar with rename and category options in the note list @JonnyTischbein [#1004](https://github.com/nextcloud/notes/pull/1004)

### Fixed

- fix: Scrolling on mobile and proper alignment of the back button/menubar @juliushaertl [#1164](https://github.com/nextcloud/notes/pull/1164)
- Avoid throwing on other share types than user @juliushaertl [#1153](https://github.com/nextcloud/notes/pull/1153)
- fix: fix note controller user id param @juliushaertl [#1106](https://github.com/nextcloud/notes/pull/1106)
- Fix dashboard icons @provokateurin [#1124](https://github.com/nextcloud/notes/pull/1124)
- fixing typos @modernNeo [#1129](https://github.com/nextcloud/notes/pull/1129)

## 4.9.0-beta.3

### Fixed

- fix: Scrolling on mobile and proper alignment of the back button/menubar @juliushaertl [#1164](https://github.com/nextcloud/notes/pull/1164)

### Dependencies

- Chore(deps): Bump axios from 1.4.0 to 1.6.1 @dependabot[bot] [#1156](https://github.com/nextcloud/notes/pull/1156)

## 4.9.0-beta.2

### Fixed

- Avoid throwing on other share types than user @juliushaertl [#1153](https://github.com/nextcloud/notes/pull/1153)

### Dependencies

- Chore(deps): Bump @nextcloud/dialogs from 4.2.1 to 4.2.2 @dependabot[bot] [#1151](https://github.com/nextcloud/notes/pull/1151)

## 4.9.0-beta.1

### Added

- Note sharing and file sidebar integration @luka-nextcloud [#1146](https://github.com/nextcloud/notes/pull/1146)
- Replace sidebar with rename and category options in the note list @JonnyTischbein [#1004](https://github.com/nextcloud/notes/pull/1004)

### Fixed

- fix: fix note controller user id param @juliushaertl [#1106](https://github.com/nextcloud/notes/pull/1106)
- Fix dashboard icons @provokateurin [#1124](https://github.com/nextcloud/notes/pull/1124)
- fixing typos @modernNeo [#1129](https://github.com/nextcloud/notes/pull/1129)

## 4.8.1

### Fixed

- fix: Check for the notes app version for editor hint @juliushaertl [#1077](https://github.com/nextcloud/notes/pull/1077)
- Fix autotitle and save status @juliushaertl [#1078](https://github.com/nextcloud/notes/pull/1078)
- Update depenencies


## 4.8.0

### Added

- Three column layout @joachimeichborn [#1021](https://github.com/nextcloud/notes/pull/1021)
- Settings: Move Settings to NcAppSettingsDialog, NotePath FilePicker and merge AppHelp @JonnyTischbein [#1003](https://github.com/nextcloud/notes/pull/1003)

### Fixed

- Use the color-primary-element* variables @szaimen [#1043](https://github.com/nextcloud/notes/pull/1043)
- fix: setting button spacing @luka-nextcloud [#1048](https://github.com/nextcloud/notes/pull/1048)
- fix: Wrap renaming of notes through autotile in locking context @juliushaertl [#1047](https://github.com/nextcloud/notes/pull/1047)
- Fix help table on dark mode @eckelon [#1000](https://github.com/nextcloud/notes/pull/1000)
- fix: Proper response for attachment endpoint @juliushaertl [#1031](https://github.com/nextcloud/notes/pull/1031)

### Other

- Dependency updates

## 4.7.2 - 2023-03-22

### Fixed

- fix: Allow migration endpoint to be called by non-admins @juliushaertl [#986](https://github.com/nextcloud/notes/pull/986)

## 4.7.1 - 2023-03-22

### Fixed

- fix: Ensure to fallback to old editor properly on 25 [#984](https://github.com/nextcloud/notes/pull/984)

## 4.7.0 - 2023-03-20

- Compatiblity with Nextcloud 26
- Integration Nextcloud Text editor
- Support dashboard API for mobile clients


## 4.6.0 - 2022-10-14

- Compatibility with Nextcloud 25 (#927, #928)
- make code-blocks nicer (#892 by @newhinton)
- maintenance (#926, #929)


## 4.5.1 - 2022-09-04

- always use edit mode when creating a new note (#912)
- maintenance (#911, #913, #914)


## 4.5.0 - 2022-08-13

- Allow Checkbox to be toggled in viewmode (#861 by @newhinton)
- Update table style (#878 by @newhinton)
- Add a button to create a new note to the dashboard (#873 by @salixor)
- rename settings to Notes settings (#891)
- auto create notes folder only if app is opened (#890)
- fix minor API version (#898)
- fix loading (should fix some error situations) (#897)
- maintenance (#888, #893, #899)


## 4.4.0 - 2022-07-10

### Added
- Add support for images/attachments (by @newhinton, #785, #823, #834)
- Allow custom file extensions (#795 by @vincowl)
- external API for custom file extension (#850)

### Fixed
- break long words in preview mode (#817)
- allow empty settings (required for root directory) (#819)
- don't ignore newlines in preview mode (#820)
- fix noteservice not returning the final filename (#821 by @newhinton)
- fix undo deletes note (#825)
- fix conflict solution (#849)
- disable some shortcuts in editor mode (#865 by @newhinton)
- fix checked list item with backslash (#867)
- fix checkbox editable in read-only mode (#876)

- maintenance (#803, #808, #813, #827, #829, #830, #833, #835, #838, #840, #844, #846, #847, #848, #851, #853, #874, #877)


## 4.3.1 - 2022-02-27

- add support for PHP 8.1, maintenance (#824)


## 4.3.0 - 2022-01-09

- checkbox support in preview mode (#787 by @newhinton)
- maintenance (#789, #800)


## 4.2.0 - 2021-11-13

- add setting for view mode (#747)
- keep Sidebar open when open other note (#775)
- maintenance (#748, #773, #774, #776)


## 4.1.1 - 2021-07-31

- fix error handling if loading notes fails (#740)
- rewrite save queue (#742)
- maintenance (#743)


## 4.1.0 - 2021-06-27

- API: new minor API version 1.2 (#701)
- API: new settings API (#694)
- API: allow requesting notes list in chunks (#720)
- API and frontend: Prevent lost updates by using ETags for notes (#692)
- API and frontend: new attribute "readonly" for read-only notes (#711)
- slow-down refresh interval if app is hidden (#710)
- EasyMDE: Always use contenteditable and native spellcheck (#712)
- fix action buttons running out of window (#717)
- change EasyMDE tab size to 4 (#718)
- maintenance (#693, #699, #705, #709, #719, #723, #727, #731)


## 4.0.4 - 2021-03-06

- fix note creation on encrypted s3 storage (#690)
- maintenance (#691)


## 4.0.3 - 2021-03-02

- add PHP8 support (#685)
- fix long duplicate title generation (#665, thanks to @skazi0)
- maintenance (#663, #673, #678, #686, #688)


## 4.0.2 - 2020-12-29

- fix actions popover (#653)
- fix deletion of multiple notes (#657)
- remove "more" entry from dashboard (#656)
- exclude dev files from build (#644)
- maintenance (#645, #649, #654, #658)


## 4.0.1 - 2020-11-23

- fix multi-byte handling in excerpt for dashboard (#630)
- improve handling of files with special chars (#632)
- maintenance (#625, #631, #633)


## 4.0.0 - 2020-10-03

### Added

- dashboard widget (#614)
- unified search (#600)

### Changed

- migrate to Nextcloud 20 / require at least Nextcloud 20 (#599)
- maintenance (#609, #617)


## 3.6.4 - 2020-08-26

### Fixed

- fix Nextcloud 20 compatibility (#597)


## 3.6.3 - 2020-08-25

### Fixed

- fix toasts (#591)
- improved error handling (#593)
- maintenance (#594)


## 3.6.2 - 2020-08-22

### Fixed

- fix cannot create notes when using object storage (#566)
- maintenance (#577, #578, #582, #585, #588)


## 3.6.1 - 2020-07-17

### Fixed

- fix note refresh for unsaved changes (#576)
- improved error handling (#573)
- maintenance (#563, #567, #569, #571, #572, #575)


## 3.6.0 - 2020-06-28

### Added
- auto refresh current note (#553)
- add CTRL+/ as shortcut for preview toggle (#556)
- API: add app version to capabilities (#545)

### Changed
- localize default notes folder (#557)

### Fixed
- debounce autotitle (#555)
- maintenance (#548, #550, #554, #558)


## 3.5.1 - 2020-06-11

### Fixed
- editor: jump to end on click below (#540)
- editor: fix Home/End key behavior (#543)
- improve error handling (#541)
- maintenance (#542, #544)


## 3.5.0 - 2020-06-06

### Added
- auto-refresh notes list (#519)
- allow scrolling past the note end (#529)
- show number of characters (#530)

### Changed
- speed-up synchronization (#525)
- new app icon (#522)

### Fixed
- retry on LockedException (should fix #350)
- maintenance (#526, #527, #531)


## 3.4.0 - 2020-05-24

### Added
- API: filter notes by category (API v1.1) (#518)

### Changed
- show empty categories in Sidebar (#515)

### Fixed
- Category list: fix notes count (#517)
- maintenance (#516, #520)


## 3.3.1 - 2020-05-17

### Fixed
- fix autotitle (#511)
- relax autosave (#513)
- maintenance (#514)


## 3.3.0 - 2020-05-05

### Added
- API v1 (#474, #491)

### Changed
- manuel edit of a note's title (#474)

### Deprecated
- API v0.2 (#491)

### Fixed
- editor: colors in dark-mode (#485)
- preview: show empty table rows (#500)
- maintenance (#479, #483, #488, #490, #499)


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
