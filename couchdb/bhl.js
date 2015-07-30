{
   "_id": "_design/bhl",
   "_rev": "4-69adea769da4ffcce91810183806cbea",
   "language": "javascript",
   "views": {
       "page_to_pageid": {
           "map": "function(doc) {\n  if (doc.bhl_pages) {\n    for (var page in doc.bhl_pages) {\n      var parts = page.match(/Page\\s+(\\d+)/);\n      if (parts) {\n        emit([doc._id, parseInt(parts[1])], doc.bhl_pages[page]);\n      }\n    }\n  }\n}"
       },
       "page_id": {
           "map": "// List BHL PageIDs\nfunction(doc) {\n  if (doc.bhl_pages) {\n    for (var page in doc.bhl_pages) {\n      emit(doc.bhl_pages[page], 1);\n    }\n  }\n}",
           "reduce": "_sum"
       }
   }
}