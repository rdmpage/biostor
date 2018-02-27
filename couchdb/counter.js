{
    "_id": "_design/counter",
    "_rev": "7-129e416b72f38448474db9c7e8ea938e",
    "views": {
        "ip": {
            "reduce": "_sum",
            "map": "function(doc) {\n  if (doc.SERVER_ADDR) {\n    var id = doc.REQUEST_URI;\n    id = id.replace(/\\/reference\\//, '');\n    emit([id, doc.HTTP_CF_CONNECTING_IP], 1);\n  }\n}"
        },
        "user_agent": {
            "reduce": "_sum",
            "map": "function(doc) {\n  if (doc.HTTP_USER_AGENT) {\n    emit(doc.HTTP_USER_AGENT, 1);\n  }\n}"
        },
        "views": {
            "reduce": "_sum",
            "map": "function notabot(user_agent) {\n  var isbot = false;\n\n  if (user_agent.match(/biadu|bot|crawl|slurp|spider/i)) {\n    isbot = true;\n  }\n\n  return !isbot;\n}\n\n\nfunction(doc) {\n  if (doc.HTTP_USER_AGENT) {\n    if (notabot(doc.HTTP_USER_AGENT)) {\n      var id = doc.REQUEST_URI;\n      id = id.replace(/\\/reference\\//, '');\n      id = id.replace(/biostor\\//, '');\n      emit([id, doc.HTTP_CF_CONNECTING_IP], 1);\n    }\n  }\n}"
        }
    },
    "language": "javascript"
}