# Upload to Elastic

## Bulk download of Elasticsearch documents from CouchDB

```
curl http://direct.biostor.org:5984/biostor/_design/elastic/_list/values/biostor > biostor.jsonl
```

## Chunk and bulk upload to Elasticsearch

