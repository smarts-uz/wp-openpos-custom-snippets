'YearBalance'.datatype({exportable: true})
'year'.field({refersToParent: 'Year'})
'account'.field({refersTo: 'Account', key: true})
'balance'.field({numeric: true, decimals: 2})

'YearBalance'.cruxFields(['year', 'account'])
