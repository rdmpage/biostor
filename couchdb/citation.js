{
   "_id": "_design/citation",
   "_rev": "1-159529fe6d1f0f7ef4876a3688699f50",
   "language": "javascript",
   "indexes": {
       "all": {
           "index": "function(doc) { if (doc.citation) {  index(\"default\", doc.citation, {\"store\": \"yes\"}); } if (doc.journal) { index(\"publication\", doc.journal.name, {\"facet\": true}); } if (doc.type) { index(\"type\", doc.type, {\"facet\": true}); } if (doc.year) { index(\"year\", doc.year, {\"facet\": true}); } if (doc.author) { for (i in doc.author) { index(\"author\", doc.author[i].name, {\"facet\": true}); }} }"
       }
   }
}