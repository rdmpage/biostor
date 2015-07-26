# biostor


## SSH keys

Use github SSH keys (see https://pagodabox.io/docs/setting_up_ssh-osx-linux). The following command puts the public key into the clipboard:

    pbcopy < ~/.ssh/github_rsa.pub

Can then paste this key into Pagodabox site.

### Multiple keys

I found that sometimes Pagodabox would expect the github SSH key, authorities the one I’d generated for Pagodabox, so I pasted both keys into the Pagodabox admin panel.

## Monitoring

Added New Relic key, after a while New Relic shows data for the app https://rpm.newrelic.com/accounts/691868/applications/8332767