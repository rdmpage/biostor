{
    "_id": "_design/geodd",
    "_rev": "2-c21e1a4e317267f2e1e7dee2321237c2",
    "st_indexes": {
        "geoidx": {
            "index": "function(doc) {\n  var types = ['article','book','chapter','generic'];\n  var type = types.indexOf(doc.type);\n  if (type != -1) {\n    if (doc.geometry && doc.geometry.coordinates) {\n        st_index(doc.geometry);\n    }\n  }\n}"
        },
        "points": {
            "index": "function(doc) {\n  var types = ['article','book','chapter','generic'];\n  var type = types.indexOf(doc.type);\n  if (type != -1) {\n    if (doc.geometry && doc.geometry.coordinates) {\n        if (doc.geometry.type == \"MultiPoint\") {\n        for (var i in doc.geometry.coordinates) {\n          var pt = {\n            \"type\": \"Point\",\n            \"coordinates\": doc.geometry.coordinates[i]\n          };\n          st_index(pt);\n        }\n      }\n    }\n  }\n}"
        }
    }
}