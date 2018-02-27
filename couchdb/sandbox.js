{
    "_id": "_design/sandbox",
    "_rev": "1-3017d2b59f89699e0e1e25180fc55edb",
    "views": {
        "reference_id": {
            "map": "function (doc) {\n  if (doc._id.match(/^biostor/)) {\n    emit(parseInt(doc._id.replace(/biostor\\//, '')), null);\n  }\n}"
        }
    },
    "language": "javascript"
}