#!/bin/sh

echo 'biostor-20000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-20000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-40000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-40000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-60000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-60000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-80000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-80000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-100000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-100000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-120000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-120000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-140000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-140000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-160000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-160000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-180000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-180000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-200000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-200000.json'  --progress-bar | tee /dev/null
echo ''
echo 'biostor-208973.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/biostor/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@biostor-208973.json'  --progress-bar | tee /dev/null
echo ''
