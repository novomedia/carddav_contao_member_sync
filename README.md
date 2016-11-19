CardDAV Contao Member Sync
==========================

A small php-script for importing contacts from carddav (e.g. apple addressbook server) in contao
------------------------------------------------------------------------------------------------

The script imports contacts from a carddav-server in the contao member table.

In our example contact groups are assigned by smart groups and tags in the note field.

Installation
------------

- Copy files to /_tools/sync_contacts/ at your webserver.
- Configure the settings at the top of the script
- Run by browser
- OR by cronjob with the following parameters: ?action=run&mode=cron

License
-------

CardDAV Contao Member Sync is licensed under the terms of the LGPLv3.
The script was developed by [Contao Agentur novo.media](http://www.novo-online.de).

Credits
-------

CardDAV-PHP: Christian Putzke
https://github.com/graviox/CardDAV-PHP?source=c

vCard-parser: Martins Pilsetnieks, Roberts Bruveris
https://github.com/nuovo/vCard-parser

