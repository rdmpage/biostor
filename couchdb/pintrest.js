{
    "_id": "_design/pintrest",
    "_rev": "4-c1082d37e100abde6484b356210ca859",
    "language": "javascript",
    "views": {
        "date_pin": {
            "map": "function(doc) {\n  if (doc.type == 'pintrest') {\n    if (doc.biostor) {\n      // ensure date is array of integers\n      var date = [];\n      for (var i in doc.date) {\n        date.push(parseInt(doc.date[i]));\n      }\t\n    }\n    emit(date, doc);\n  }\n}"
        },
        "biostor": {
            "map": "function(doc) {\n  if (doc.type == 'pintrest') {\n    if (doc.biostor) {\n     emit(parseInt(doc.biostor), 1);\n    }\n  }\n}",
            "reduce": "_sum"
        }
    }
}