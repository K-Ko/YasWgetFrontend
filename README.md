YasWgetFrontend
===============

#### Yet another simple Wget frontend

Simple frontend for GNU Wget utility for downloading network data.

http://www.gnu.org/software/wget/manual/wget.html

### Features

* Downlaod **single** files to your server
* **List** downloaded files / downloads in progress
* **Download** finished files from server
* **Delete** downloaded files from server
* **Stop** downloads in progress

### Installation

Just clone the repo into document root of your server

    # git clone https://github.com/K-Ko/YasWgetFrontend.git

#### Recommended
Protect your installation, at least with a basic authentication!

#### Optional
Copy default settings file if needed and adjust them for your needs.

    # cp config.default.php config.local.php
    # editor config.local.php

#### File storage

YasWgetFrontend will create on 1st start a subfolder for file and logs storage.

It will start with **.f** and is unique for each installation.

If you want to have the files stored anywhere else, e.g. somewhere in a directory shared with Samba
you can replace the **.f...** directory with a link to the other directory.

**Please** make sure, your web server is able to write into this directory!

