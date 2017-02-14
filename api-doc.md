# BioStor API

BioStor has a simple API which underpins the site. By default results are returned in JSON format, with an HTTP status code in the field **status**. A value of 200 means the call returns a result, 404 means there are no results that match the query. Some calls may return plain text, TSV, or other formats.

## Individual records

Individual articles can be retrieved in JSON format by appending   **biostor/** and the article id to http://biostor.org/api.php, e.g. http://biostor.org/api.php?id=biostor/197602.

The result is a record in [BibJSON](http://okfnlabs.org/bibjson/) format, which is the native formatted stored by BioStor.

## Search

To undertake a full text search of article metadata append the search string to http://biostor.org/api.php?q=, e.g. http://biostor.org/api.php?q=The+Lepidoptera+collected+during+the+recent+expedition+of+H.M.S.+‘Challenger.’

The result is zero or more records from the CouchDB database used by BioStor.

## URL match

There are many ways to link to articles in BHL, including:
- copying the URL displayed in the web browser
- a direct link to the page using the pattern http://biodiversitylibrary.org/page/
- copy the Internet Archive book viewer URL for a page for an item that is also in BHL

Examples of these URLs appear in Wikispecies, and published article such as [On Hypolycaena from Maluku, Indonesia, including the first description of male Hypolycaena asahi (Lepidoptera, Lycaenidae)](https://doi.org/10.3897/zookeys.115.1406).

Given a **url** parameter the service takes the URL and returns the corresponding the BHL PageID and BioStor article(s), if they exist. There may be more than one BioStor article for a given page if an article starts on the same page as the preceding article ends.

If the URL contains a fragment identifier (**#**) the hash symbol must be URL encoded by replacing it with **%23**.

If the URL comes from a viewer display two pages side by side, then the page number in the URL refers to the left page, whereas the target article may start on the right page. Add the target page number as the **page** parameter to retrieve the correct PageID. 

For example, 

Butler AG (1882) Description of New Species of Lepidoptera chiefly from Duke of York Island and New Britain. Annals and Magazine of Natural History (5)10: 149–160. http://www.archive.org/stream/annalsmagazineof5101882lond#page/148/mode/2up

has a link to a two-page display where the left page is 148, and the right page is 149. To get the PageID of page 149 we use the following API call: 

http://biostor.org/api_url.php?url=
http://www.archive.org/stream/annalsmagazineof5101882lond%23page/148/mode/2up&page=149

which returns:

```
{
  “status”: 200,
  “results”: [
    {
      “PageID”: 29869861,
      “biostor”: [
        93555
      ]
    }
  ]
}
```

Each matching record has a PageID as an integer, and an array of zero to more BioStor article ids.

## OpenURL

BioStor supports [OpenURL](https://en.wikipedia.org/wiki/OpenURL). If the parameter **redirect** is appended to an OpenURL request BioStor will redirect the user to the page displaying the corresponding article. If the article is not found an error message will be displayed. Without the **redirect** parameter the API returns JSON.

## Reconciliation API

BioStor supports the [Reconciliation Service API](https://github.com/OpenRefine/OpenRefine/wiki/Reconciliation-Service-API) used by tools such as [OpenRefine](http://openrefine.org). The service is available at [/reconcile](http://biostor.org/reconcile)
```
{
  “name”: “BioStor”,
  “identifierSpace”: “http:\/\/biostor.org\/“,
  “schemaSpace”: “http:\/\/rdf.freebase.com\/ns\/type.object.id”,
  “view”: {
    “url”: “http:\/\/biostor.org\/reference\/{{id}}”
  },
  “defaultTypes”: [
    {
      “id”: “https:\/\/schema.org\/CreativeWork”,
      “name”: “CreativeWork”
    }
  ]
}
```
A web interface to this service is available at http://biostor.org/match.html.

## Article boundaries for BHL item

Given an **ItemID** in BHL this API analyses the page numbering and suggests article boundaries. It returns a list of starting page, ending page, and BHL **PageID** for each article.

The web interface is http://biostor.org/api_bhl.php?item=.
