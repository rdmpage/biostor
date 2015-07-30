{
   "_id": "_design/author",
   "_rev": "1-157c7ea016bbc505c00cef5953292052",
   "language": "javascript",
   "views": {
       "lastname_firstname": {
           "map": "function isUpperCase(str) {\n    return str === str.toUpperCase();\n}\n\nfunction(doc) {\n       if (doc.author)\n       {\n          for (var i in doc.author)\n          {\n            if (doc.author[i].lastname)\n            {\n  \t      if (doc.author[i].firstname) {\n                // Handle case where we have names around the wrong way (e.g., from CiNii)        \n                if ((doc.author[i].firstname.length > 1) && doc.author[i].firstname.match(/^[A-Z]+$/)) {\n                 var firstname = doc.author[i].firstname;\n                 firstname = firstname.charAt(0).toUpperCase() + firstname.substr(1).toLowerCase();\n                 emit([firstname, doc.author[i].lastname,], 1);\n                } else {\n                 emit([doc.author[i].lastname,doc.author[i].firstname], 1);\n                }\n              }\n            }\n          }\n       }\n\n\n}",
           "reduce": "function (key, values, rereduce) {\n    return sum(values);\n}"
       },
       "name": {
           "map": "function(doc) {\n       if (doc.author)\n       {\n          for (var i in doc.author)\n          {\n            if (doc.author[i].name)\n            {\n              var name = doc.author[i].name;\n              name = name.replace(/\\./g, '');\n              name = name.replace(/^\\s+/, '');\n              emit(name, doc._id);\n            }\n          }\n       }\n\n\n}"
       },
       "coauthor": {
           "map": "function(doc) {\n       if (doc.author)\n       {\n          var n = doc.author.length;\n          for (var i = 0; i < n-1; i++) {\n            for (var j = i+1; j<n; j++) {\n              if (doc.author[i].name && doc.author[j].name) {\n                var name_i = doc.author[i].name;\n                name_i = name_i.replace(/\\./g, '');\n                name_i = name_i.replace(/^\\s+/, '');\n                var name_j = doc.author[j].name;\n                name_j = name_j.replace(/\\./g, '');\n                name_j = name_j.replace(/^\\s+/, '');\n                emit([name_i, name_j], 1);\n                emit([name_j, name_i], 1);\n              }\n            }\n          }\n       }\n\n\n}",
           "reduce": "function (key, values, rereduce) {\n    return sum(values);\n}"
       }
   }
}