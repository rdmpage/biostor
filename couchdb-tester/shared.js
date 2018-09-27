/*

Shared code


*/

var triples = {};


//----------------------------------------------------------------------------------------
// http://stackoverflow.com/a/25715455
function isObject (item) {
  return (typeof item === "object" && !Array.isArray(item) && item !== null);
}

//----------------------------------------------------------------------------------------
// http://stackoverflow.com/a/21445415
function uniques(arr) {
  var a = [];
  for (var i = 0, l = arr.length; i < l; i++)
    if (a.indexOf(arr[i]) === -1 && arr[i] !== '')
      a.push(arr[i]);
  return a;
}

		
//----------------------------------------------------------------------------------------
// Create a triple with optional language code
function triple(subject, predicate, object, language) {
  var triple = [];
  triple[0] = subject;
  triple[1] = predicate;
  triple[2] = object
  
  // clean
  console.log(triple[2]);
  triple[2] = triple[2].replace(/\n/g, '');
  triple[2] = triple[2].replace(/\n/g, '');
  triple[2] = triple[2].replace(/^\s+/g, '');
  console.log(triple[2]);

  if (typeof language === 'undefined') {
  } else {
    triple[3] = language;
  }
  
  return triple;
}

//----------------------------------------------------------------------------------------
// Store triple in the relevant graph
function store_triple(triple, graph) {
	var graph_uri;
	if (typeof graph === 'undefined') {
		graph_uri = '_:g';
	} else {
		graph_uri = graph;
	}
	
	
	if (!triples[graph_uri]) {
		triples[graph_uri] = [];
	}

	triples[graph_uri].push(triple);
}

//----------------------------------------------------------------------------------------
// Enclose triple in suitable wrapping for HTML display or triplet output
function wrap(s, html) {
if (s) {

  if (s.match(/^(http|urn|_:)/)) {
    //s = s.replace(/\\_/g, '_');

    // handle < > in URIs such as SICI-based DOIs
    s = s.replace(/</g, '%3C');
    s = s.replace(/>/g, '%3E');
  
    if (s.match(/_:/)) {
     	s = s;
    } else {
  
	    if (html) {
    	  s = '&lt;' + s + '&gt;';
	    } else {
    	  s = '<' + s + '>';
	    }
	}
  } else {
    s = '"' + s.replace(/"/g, '\\"') + '"';
  }}
  return s;
}

//----------------------------------------------------------------------------------------
// https://css-tricks.com/snippets/javascript/htmlentities-for-javascript/
function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

//----------------------------------------------------------------------------------------
// create basic triples for a nanopub
function nanopub_skelton(doc, uri) {
	var nanopub = {};

	nanopub.base_uri       = uri + '/nanopub';
    nanopub.head_uri       = nanopub.base_uri + '#head';
	nanopub.assertion_uri  = nanopub.base_uri + '#assertion';
	nanopub.provenance_uri = nanopub.base_uri + '#provenance';
	nanopub.pubInfo_uri    = nanopub.base_uri + '#pubInfo'; 
	
	store_triple(triple(nanopub.base_uri,
    'http://www.nanopub.org/nschema#hasAssertion',
    nanopub.assertion_uri), nanopub.head_uri);
    
	store_triple(triple(nanopub.base_uri,
    'http://www.nanopub.org/nschema#hasProvenance',
    nanopub.provenance_uri), nanopub.head_uri);

	store_triple(triple(nanopub.base_uri,
    'http://www.nanopub.org/nschema#hasPublicationInfo',
    nanopub.pubInfo_uri), nanopub.head_uri);
    
	store_triple(triple(nanopub.base_uri,
    'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
    'http://www.nanopub.org/nschema#Nanopublication'), nanopub.head_uri);
  
  // pubInfo for this nanopub
	store_triple(triple(nanopub.base_uri,
    'http://www.w3.org/ns/prov#generatedAtTime',
   new Date().toISOString()), nanopub.pubInfo_uri);
 
  // provenance for this nanopub
	store_triple(triple(nanopub.assertion_uri,
    'http://www.w3.org/ns/prov#generatedAtTime',
   new Date(doc['message-timestamp']).toISOString()), nanopub.provenance_uri);
   
   return nanopub;

}

//----------------------------------------------------------------------------------------
function show_graph() {

  var dot = 'digraph G { rankdir = LR;';

  var nodes = [];

  for (var graph in triples) {
    for (var i in triples[graph]) {
      var triple = triples[graph][i];
      var s = 0;
      var p = 1;
      var o = 2;
      var lang = 3;

      var subject = triple[s];
      if (nodes.indexOf(subject) == -1) {
        nodes.push(subject);
      }
      var index = nodes.indexOf(subject);

      if (index > 0) {
        index = subject;
      }

      var predicate = triple[p];
      var lastBit = predicate.substring(predicate.lastIndexOf("/") + 1);
      lastBit = lastBit.substring(lastBit.lastIndexOf("#") + 1);

      var object = triple[o];
      if (object.length > 50) {
        if (object.match(/^http/)) {} else {
          object = object.substring(0, 50) + '...';
        }
      }

      //dot += '"' + triples[i][s] + '" -- "' + triples[i][o] + '" [label="' + lastBit + '"];' + "\n";
      dot += '"' + index + '" -> "' + object + '" [label="' + lastBit + '"];' + "\n";
    }
  }
  dot += '}';

  var graph = Viz(dot, "svg", "dot");
  $('#graph').html(graph);
}

//----------------------------------------------------------------------------------------
function output(doc, triples, html_format) {
  if (html_format) {
    // Output triples

    var nquads = '';

    var html = '<table width="100%">';

    for (var graph in triples) {
      for (var i in triples[graph]) {
        var triple = triples[graph][i];
        var s = 0;
        var p = 1;
        var o = 2;
        var lang = 3;

        nquads += wrap(triple[s], false) +
          ' ' + wrap(triple[p], false) +
          ' ' + wrap(triple[o], false);

        if (triple[lang]) {
          nquads += '@' + triple[lang];
        }

        if (graph != '_:g') {
          nquads += ' ' + wrap(graph, false);
        }

        nquads += ' .' + "\n";
      }
    }
    html += '</table>';

    html += '<pre>' + htmlEntities(nquads) + '</pre>';

    $('#output').html(html);

    // graph
    if (1) {
      show_graph();
    }

    // convert RDF to JSON-LD
    jsonld.fromRDF(nquads, {
      format: 'application/nquads'
    }, function(err, j) {

      //  $('#jsonld').html(JSON.stringify(j, null, 2));

      // make nice
      var context = {
        "@vocab": "http://schema.org/",

        // RDF syntax
        "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
        "type": "rdf:type",

        "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
        "type": "rdf:type",

        "rdfs": "http://www.w3.org/2000/01/rdf-schema#",


        // Dublin Core
        "dc": "http://purl.org/dc/terms/",


        //  "identifier" :"http://purl.org/dc/terms/identifier",
        //  "title" : "http://purl.org/dc/terms/title",

        // Bibio
        /* "volume" : "http://purl.org/ontology/bibo/volume",
         "issue" : "http://purl.org/ontology/bibo/issue",
         "pages" : "http://purl.org/ontology/bibo/pages",*/

        // Prism
        "publicationName": "http://prismstandard.org/namespaces/basic/2.1/publicationName",
        "doi": "http://prismstandard.org/namespaces/basic/2.1/doi",

        // Open Annotation
        "oa": "http://www.w3.org/ns/oa#",

        // Darwin Core
        "dwc": "http://rs.tdwg.org/dwc/terms/",

        // LOCN
        "locn": "http://www.w3.org/ns/locn#",

        // id.loc.gov
        "sha1": "http://id.loc.gov/vocabulary/preservation/cryptographicHashFunctions/sha1",

        // tdwg
        "tc": "http://rs.tdwg.org/ontology/voc/Common#",
        "tn": "http://rs.tdwg.org/ontology/voc/TaxonName#",
        "tpub": "http://rs.tdwg.org/ontology/voc/PublicationCitation#",
        
        // PROV
        "prov": "http://www.w3.org/ns/prov#",
        
        // Nanopub
        "nano": "http://www.nanopub.org/nschema#",

        // Identifiers
        "DOI": "http://identifiers.org/doi/",
        "HANDLE": "http://hdl.handle.net/",
        "ISBN": "http://identifiers.org/isbn/",
        "ISSN": "http://identifiers.org/issn/",
        "ORCID": "https://orcid.org/",
        "PMID": "http://identifiers.org/pmid/",
        "PMC": "http://identifiers.org/pmc/"

      };

      jsonld.compact(j, context, function(err, compacted) {
        $('#jsonld').html('<pre>' + JSON.stringify(compacted, null, 2) + '</pre>');
      });


    });

  } else {
    // CouchDB
    for (var graph in triples) {
      for (var i in triples[graph]) {
        var triple = triples[graph][i];
        var s = 0;
        var p = 1;
        var o = 2;
        //emit([wrap(triples[i][s], false), wrap(triples[i][p], false), wrap(triples[i][o], false)], 1);

        var lang = 3;

        var nquads = wrap(triple[s], false) +
          ' ' + wrap(triple[p], false) +
          ' ' + wrap(triple[o], false);
        if (triple[lang]) {
          nquads += '@' + triple[lang];
        }

        if (graph != '_:g') {
          nquads += ' ' + wrap(graph, false);
        }

        nquads += ' .' + "\n";

        //emit(doc._id, nquads);		
      }
    }
  }
}


//----------------------------------------------------------------------------------------
// https://github.com/darkskyapp/string-hash
function hash(str) {
  var hash = 5381,
      i    = str.length;

  while(i) {
    hash = (hash * 33) ^ str.charCodeAt(--i);
  }

  /* JavaScript does bitwise operations (like XOR, above) on 32-bit signed
   * integers. Since we want the results to be always positive, convert the
   * signed int to an unsigned by doing an unsigned bitshift. */
  return hash >>> 0;
}