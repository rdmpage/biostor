{
    "_id": "_design/bhl",
    "_rev": "4-f2f3a9e3d26c5f967e34d0381e1991cf",
    "language": "javascript",
    "views": {
        "page_to_pageid": {
            "map": "function(doc) {\n  if (doc.bhl_pages) {\n    for (var page in doc.bhl_pages) {\n      var parts = page.match(/Page\\s+(\\d+)/);\n      if (parts) {\n        emit([doc._id, parseInt(parts[1])], doc.bhl_pages[page]);\n      }\n    }\n  }\n}"
        },
        "page_id": {
            "map": "// List BHL PageIDs\nfunction(doc) {\n  if (doc.bhl_pages) {\n    for (var page in doc.bhl_pages) {\n      emit(doc.bhl_pages[page], 1);\n    }\n  }\n}",
            "reduce": "_sum"
        },
        "types": {
            "map": "function(doc) {\n  if (doc.TitleID) {\n    emit('title', 1);\n   }\n  if (doc.PageID) {\n    emit('page', 1);\n  }\n  if (doc.ItemID && doc.PrimaryTitleID) {\n    emit('item', 1);\n  }\n}",
            "reduce": "_sum"
        },
        "pageid_to_biostor": {
            "map": "function(doc) {\n  if (doc.bhl_pages) {\n    for (var page in doc.bhl_pages) {\n      var id = doc._id.replace(/biostor\\//, '');\n      emit(doc.bhl_pages[page], parseInt(id));\n    }\n  }\n}"
        }
    }
}