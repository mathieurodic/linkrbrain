SELECT
	p.id
,	p.path
,	p.title
	
FROM
	preset AS p
	
INNER JOIN
	keyword_document AS kd0 ON kd0.document_type = 'preset-$type' AND kd0.keyword_id = p.id
INNER JOIN
	keyword AS k0
	
INNER JOIN
	keyword_document AS kd1 ON kd1.document_type = 'preset-$type' AND kd0.keyword_id = p.id
INNER JOIN
	keyword AS k1
	
WHERE
	k0.word LIKE '$word%'
AND
	k1.word LIKE '$word%'
	
	