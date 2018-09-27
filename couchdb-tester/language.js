function  detect_language(s) {
  var language = null;
  var matched = 0;
  var parts =[];
  
  var regexp = [];
  
  // https://gist.github.com/ryanmcgrath/982242
  regexp['ja'] = /[\u3000-\u303F]|[\u3040-\u309F]|[\u30A0-\u30FF]|[\uFF00-\uFFEF]|[\u4E00-\u9FAF]|[\u2605-\u2606]|[\u2190-\u2195]|\u203B/g; 
  // http://hjzhao.blogspot.co.uk/2015/09/javascript-detect-chinese-character.html
  regexp['zh'] = /[\u4E00-\uFA29]/g; 
  // http://stackoverflow.com/questions/32709687/js-check-if-string-contains-only-cyrillic-symbols-and-spaces
  regexp['ru'] = /[\u0400-\u04FF]/g; 
  
  for (var i in regexp) {
    parts = s.match(regexp[i]);
    
	  if (parts != null) {
		if (parts.length > matched) {
		  language = i;
		  matched = parts.length;
		}
	  }
  }
  
  // require a minimum matching
  if (matched < 2) {
    language = null;
  }
  
  return language;
  
}
