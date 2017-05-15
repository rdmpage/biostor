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

    git push pagoda --all

## Monitoring

Added New Relic key, after a while New Relic shows data for the app https://rpm.newrelic.com/accounts/691868/applications/8332767

## Replication

Launch this from local machine to replicate CouchDB with Cloudant.
````
curl http://localhost:5984/_replicate -H ‘Content-Type: application/json’ -d ‘{ “source”: “biostor”, “target”: “https://<username>:<password>@rdmpage.cloudant.com/biostor”, “continuous”:true }’
````

## Image proxy

BioStor uses CloudFlare http://cloudflare.com to provide caching, and by default CloudFlare doesn’t cache images that with dynamic URLs (i.e., it expects a URL to have a file extension). I’ve borrowed heavily from https://github.com/andrieslouw/imagesweserv to create an image proxy that fetches images from BHL, then outputs them such that CloudFlare will treat them as static images and cache them.

## Future ideas

### Interfaces

For a very different interface to historical texts see the [UK Medical Heritage Library](https://ukmhl.historicaltexts.jisc.ac.uk/home).



