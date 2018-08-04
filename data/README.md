# Data

Dumps of data

## DOIs and JSTOR ids

For now MySQL from old BioStor, but should make CouchDB view.

```
SELECT doi, reference_id INTO OUTFILE ‘/tmp/doi.tsv'
  FIELDS TERMINATED BY '\t' 
  LINES TERMINATED BY '\n'
  FROM  rdmp_reference WHERE (doi IS NOT NULL AND doi <> '') AND (PageID <> 0 AND PageID IS NOT NULL);
  
```

```
SELECT jstor, reference_id INTO OUTFILE ‘/tmp/jstor.tsv'
  FIELDS TERMINATED BY '\t' 
  LINES TERMINATED BY '\n'
  FROM  rdmp_reference WHERE (jstor IS NOT NULL AND jstor <> '') AND (PageID <> 0 AND PageID IS NOT NULL);  
```

The files doi.tsv and jstor.tsv consist of two columns, the first with the external identifier (DOI or JSTOR id), the second is the BioStor reference id.
