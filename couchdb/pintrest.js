{
   "_id": "_design/pintrest",
   "_rev": "2-348c9d017cbb4dcf54cda3512fdf4a97",
   "language": "javascript",
   "views": {
       "date_pin": {
           "map": "function(doc) {\n  if (doc.type == 'pintrest') {\n    if (doc.biostor) {\n      // ensure date is array of integers\n      var date = [];\n      for (var i in doc.date) {\n        date.push(parseInt(doc.date[i]));\n      }\t\n    }\n    emit(date, doc);\n  }\n}"
       }
   }
}