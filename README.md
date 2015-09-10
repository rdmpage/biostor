[![Stories in Ready](https://badge.waffle.io/rdmpage/biostor.png?label=ready&title=Ready)](https://waffle.io/rdmpage/biostor)
# biostor


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
