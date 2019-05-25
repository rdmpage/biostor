# Backup

## CouchDB views

```
cd backup
php fetch-design.php
```

## CouchDB data

Grab JSONL format dump (big!)

curl http://admin:3h0kylo8ljfp@34.90.120.208:5984/biostor/_design/export/_view/jsonl  > data.jsonl

## MySQL

Go to local server and dump MySQL database.



