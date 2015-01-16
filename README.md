Webhook PHP doc generator
=========================

This script generate php doc using phpDocumentor on github webhook

Installation
------------

- Copy conf/config.php.dist to conf/config.php
- adapt conf/config.php to your needs
- fill allowed repositories in `conf/repository.json`
- config your webserver to create a virtualhost / host which will point to public dir

NOTE : your webserver user (`www-data`, ...) should have write permissions to this dir and subfolders, and have permissions to run `git` and `phpDocumentor`.

**On github:**

- Create a webhooks which will point to http://path.to.your.host/generate.php
- add the secret if you need it

TODO
----

- add a license (GPLv3 ?) 
- test the creation of tags
- what if we push multiple branches ?
- check bug about log file (log file is not created nor filled)
