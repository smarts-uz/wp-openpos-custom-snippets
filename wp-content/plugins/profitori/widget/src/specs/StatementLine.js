'StatementLine'.datatype({exportable: true, plex: true})
'statement'.field({refersToParent: 'Statement', parentIsHeader: true})
'sequence'.field({numeric: true})
'caption'.field()
'ranges'.field()
'drcr'.field({caption: 'DR/CR'})
'bold'.field({yesOrNo: true})

'StatementLine'.cruxFields(['statement', 'sequence'])

'drcr'.options(['DR', 'CR'])
