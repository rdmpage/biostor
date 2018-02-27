{
    "_id": "_design/geo",
    "_rev": "1-20a0ea287013909019b8a8229507dc7e",
    "language": "javascript",
    "views": {
        "tile": {
            "map": "function(doc) {\n  var tile_size = 256;\n  var pixels    = 4;\n  var max_zoom  = 11;\n\n  var types = ['article','book','chapter','generic'];\n  var type = types.indexOf(doc.type);\n  if (type != -1) \n  {\n    if (doc.geometry) {\n      for (var i in doc.geometry.coordinates) {\n    \n        var lon = doc.geometry.coordinates[i][0];\n        var lat = doc.geometry.coordinates[i][1];\n\n        // sanity check as some values might be impossible\n        // due to OCR errors, etc.\n        var ok = (\n          (lat >= -90.0 && lat <= 90.0)\n          &&\n          (lon >= -180.0 && lon <= 180.0)\n        );\n        if (ok) {\n          for (var zoom = 0; zoom < max_zoom; zoom++) {\n\n            var x_pos = (parseFloat(lon) + 180)/360 * Math.pow(2, zoom);\n            var x = Math.floor(x_pos);\n    \n            var relative_x = Math.round(tile_size * (x_pos - x));\n  \n            var y_pos = (1-Math.log(Math.tan(parseFloat (lat)*Math.PI/180) + 1/Math.cos(parseFloat(lat)*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom);\n            var y = Math.floor(y_pos);\n            var relative_y = Math.round(tile_size * (y_pos - y));\n  \n            relative_x = Math.floor(relative_x / pixels) * pixels;\n            relative_y = Math.floor(relative_y / pixels) * pixels;\n  \n            var tile = [];\n            tile.push(zoom);\n            tile.push(x);\n            tile.push(y);\n            tile.push(relative_x);\n            tile.push(relative_y);\n     \n            emit(tile, 1);\n          }\n        }\n      }\n    }\n  }\n}\n",
            "reduce": "_sum"
        }
    }
}