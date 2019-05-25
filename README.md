# biostor

[![Stories in Ready](https://badge.waffle.io/rdmpage/biostor.png?label=ready&title=Ready)](https://waffle.io/rdmpage/biostor)

[![Throughput Graph](https://graphs.waffle.io/rdmpage/biostor/throughput.svg)](https://waffle.io/rdmpage/biostor/metrics/throughput)

[![Join the chat at https://gitter.im/biostor/Lobby](https://badges.gitter.im/biostor/Lobby.svg)](https://gitter.im/biostor/Lobby?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


## SSH keys

Use github SSH keys (see https://pagodabox.io/docs/setting_up_ssh-osx-linux). The following command puts the public key into the clipboard:

    pbcopy < ~/.ssh/github_rsa.pub

Can then paste this key into Pagodabox site.

### Multiple keys

I found that sometimes Pagodabox would expect the github SSH key, authorities the one I’d generated for Pagodabox, so I pasted both keys into the Pagodabox admin panel.

## Pushing to Pagodabox

You need to add Pagoda as a remote repository:

```
git remote add pagoda git@git.pagodabox.io:apps/biostor.git
```

Then push the changes

```
git push pagoda --all
```

## Nanobox

[Nanobox](https://nanobox.io) is the replacement to Pagodabox. It requires a different boxfile. Note that this file needs specify some additional PHP extensions that others depend upon, for example, just adding xsl by itself generated linking errors until dom was also added.

```
run.config:
  # install php and associated runtimes
  engine: php
  # php engine configuration (php version, extensions, etc)
  engine.config:
    # sets the php version to 5.6
    runtime: php-5.6
    extensions:
      - curl
      - exif
      - gd
      - mbstring
      - dom # need this for xsl to work
      - xml # need this for utf8_decode
      - xsl
      
web.main:

  start:
    php: start-php
    apache: start-apache
    
  # the path to a logfile you want streamed to the nanobox dashboard
  log_watch:
    apache[access]: /data/var/log/apache/access.log
    apache[error]: /data/var/log/apache/error.log
    php[error]: /data/var/log/php/php_error.log
    php[fpm]: /data/var/log/php/php_fpm.log
        
```

To make a local version of BioStor, type:

```
nanobox deploy dry-run
```

To deploy to nanobox add a remote, e.g.:

```
nanobox remote add happy-hog
```

Then deploy:

```
nanobox deploy
```

### Things to remember

- Make sure that your DNS for your website points to the IP address (A-Record) of the nanobox app (find the A-Record in the “Network” tab).

- Add New Relic key to the “CONFIG” tab.

### Nanobox resources required

Initially ran on Google Cloud using f1-micro (1 vCPU, 0.6 GB memory), which Google Cloud reported was overused. Can add more resources via nanobox “scale” which sets up a new server. Need to explore why we need more resources.

## Cloudflare

### nanobox

Type | Name | Value
---|---|---
A | 	biostor.org | IP address from nanobox

### heroku

Type | Name | Value
---|---|---
CNAME | 	biostor.org | biostor.org.herokudns.com
CNAME | www | biostor.org.herokudns.com

Cloudflare applies [CNAME flattening](https://support.cloudflare.com/hc/en-us/articles/200169056-CNAME-Flattening-RFC-compliant-support-for-CNAME-at-the-root) to 


## Heroku

Deploy to Heroku.



## CouchDB on Bitnami

Create an instance at https://google.bitnami.com/vms

Note that you need to follow the steps here https://docs.bitnami.com/google/infrastructure/couchdb/#how-to-connect-to-couchdb-from-a-different-machine in order to be able to connect. Click on “Launch ssh console” and edit the local.ini file:

```
sudo nano /opt/bitnami/couchdb/etc/local.ini
```

 Change the bind_address from 127.0.0.1 to 0.0.0.0:
```
[chttpd]
port = 5984
bind_address = 0.0.0.0
...

[httpd]
bind_address = 0.0.0.0
...
```

Reboot the VM.

#### Firewall

Note that now we also need to add firewall access, see https://docs.bitnami.com/google/faq/administration/use-firewall/

### Replicate

```
curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '{ "source": "biostor", "target": "http://admin:<password>@IP-SERVER:5984/biostor"}'
```

## Monitoring

Added New Relic key, after a while New Relic shows data for the app https://rpm.newrelic.com/accounts/691868/applications/8332767

## Replication

Launch this from local machine to replicate CouchDB with Cloudant.
```
curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '{ "source": "biostor", "target": "https://<username>:<password>@rdmpage.cloudant.com/biostor"}'
```

IBM hosted Cloudant

```
curl http://localhost:5984/_replicate -H 'Content-Type: application/json' -d '{ "source": "biostor", "target": "https://<username>:<password>@4c577ff8-0f3d-4292-9624-41c1693c433b-bluemix.cloudant.com/biostor" }'
```



## Image proxy

BioStor uses CloudFlare http://cloudflare.com to provide caching, and by default CloudFlare doesn’t cache images that with dynamic URLs (i.e., it expects a URL to have a file extension). I’ve borrowed heavily from https://github.com/andrieslouw/imagesweserv to create an image proxy that fetches images from BHL, then outputs them such that CloudFlare will treat them as static images and cache them.

## Future ideas

### Interfaces

For a very different interface to historical texts see the [UK Medical Heritage Library](https://ukmhl.historicaltexts.jisc.ac.uk/home).

## Backup

See details in “backup” folder.



