'AccountJournalList'.list({withHeader: true, icon: 'History'})
'Account Journal Entries'.title()
'account'.field({readOnly: true})
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})

'Lines'.manifest()
'EntryLine'.datatype()
'entryNumber'.field()
'effectiveDate'.field()
'fullNotes'.field()
'posted'.field()
'drAmount'.field()
'crAmount'.field()
'Edit'.action({place: 'row', act: 'edit'})
'EntryMaint.js'.maintSpecname()

'drAmount'.modifyRenderValue((val, line) => {
  if ( line.drAmount ) return val
  return ''
})

'crAmount'.modifyRenderValue((val, line) => {
  if ( line.crAmount ) return val
  return ''
})

'Lines'.defaultSort({field: "effectiveDate", descending: true})

'Lines'.criteria(async function () {
  let c = this.callerCast()
  let account
  if ( c.datatype() !== "Account" ) return null
  account = c
  let res = {account: account}
  return res
})

'account'.default((list) => {
  let cc = list.callerCast()
  if ( ! cc )
    return null
  return cc.name
})
