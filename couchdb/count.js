{
    "_id": "_design/count",
    "_rev": "1-44d9f672fbaacf4de99f60dbb68bf381",
    "language": "javascript",
    "views": {
        "identifier": {
            "map": "function(doc) {\n  if (doc.identifier) {\n    for (var i in doc.identifier) {\n      emit(doc.identifier[i].type, 1);\n    }\n  }\n}",
            "reduce": "_sum"
        }
    }
}