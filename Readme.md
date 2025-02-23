# TYPO3 Extension `md_notifications`

## Requirements

- TYPO3 >= 12.4

## Screenshots

## Installation
- Install the extension by using composer (`composer req mediadreams/md-notifications`) or using the extension manager
- Include the static TypoScript of the extension
- Configure the extension (see chapter [Configuration](#configuration))

## Configuration
Configuration is done in the sites configuration file. Either add the configuration
directly in `config/sites/site-itentifier/config.yaml`, or import a YAML file
by using this command:

```
imports:
    - { resource: "EXT:my_extension/Configuration/Yaml/MdNotifications.yaml" }
```

Following configuration can be added:

```
md_notifications:
    # The page Id, where the notification records get stored
    # If no `storagePid` is provided, the records will be saved on page Id = 0
    storagePid: 123

    # If this is set, notifications will be saved for users who belong to groupId only.
    feGroup: 3

    # Configure recod types for notifications
    # All record types can be configured. Just add a section for the table name of the record.

    # All pages which have page Id 6 in it's rootline, will get notifications.
    pages:
        - 6

    # All news records which will be saved on a pages within the rootline of page 2 or 16 will receive notofications.
    tx_news_domain_model_news:
        - 2
        - 16
```

Hint:<br>
You can use environment variables in the site configuration like this:

    storagePid: "%env(md_notifications_storagePid)%"

## Usage
As soon as you have installed and activated the extension, it will hook into
the saving process of records. Everytime a backend user adds a
configured record, the unread info for this record and the configured
feUsers will be added.

### List plugin
The extension ships a content element `Notifications`, which shows a list of all
notifications for the logged in user. In the `Plugin`-tab you have the following
configuration options:

- `Record keys`<br>Comma separated list of record keys (table names). Leave empty, if all records shall be shown.
- `Startingpoint`<br>The page on which the notification records are stored.

### Notification counter
This counter will show the number of notifications for a user.

Use following code in your fluid template in order to get number of all notifications:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsCount" />

Use this to get number of notifications for example for `pages`
and `tx_news_domain_model_news` records:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsCount" data="{recordKeys:'pages,tx_news_domain_model_news'}" />

### Show notification info of record
Use the following code in the fluid template to show, whether the current
logged in feUser has read the item.

Example for a page record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsHasSeen" data="{recordKey:'pages', recordUid:'{data.uid}'}" />

Example for a news record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsHasSeen" data="{recordKey:'tx_news_domain_model_news', recordUid:'{newsItem.uid}'}" />

### Remove notification info
Remove the notification info as soon, as the user has read the item. Use
the following code in your fluid template.

Example for a page record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsRemove" data="{recordKey:'pages', recordUid:'{data.uid}'}" />

Example for a news record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsRemove" data="{recordKey:'tx_news_domain_model_news', recordUid:'{newsItem.uid}'}" />

## Bugs and Known Issues
If you find a bug, it would be nice if you add an issue on
[Github](https://github.com/cdaecke/md_notifications/issues).

# THANKS
Thanks a lot to all who make this outstanding TYPO3 project possible!

## Credits
- Extension icon was kindly taken from [Font Awesome](https://fontawesome.com/icons/bell?f=classic&s=solid).
