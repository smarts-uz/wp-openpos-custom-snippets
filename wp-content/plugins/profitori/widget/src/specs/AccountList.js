'AccountList'.list({expose: true, icon: 'BalanceScale'})
'General Ledger'.title()
'Back'.action({act: 'cancel'})
'Enable General Ledger'.action({spec: 'GLSettings.js'})
'GL Settings'.action({spec: 'GLSettings.js'})
'Add Account'.action({act: 'add'})
'Journal Entries'.action({spec: 'EntryLineList.js'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})
'Profit and Loss'.action({spec: 'StatementReport.js', parms: {statementName: 'Profit and Loss'}})
'Balance Sheet'.action({spec: 'StatementReport.js', parms: {statementName: 'Balance Sheet'}})
'Account'.datatype()
'accountCode'.field()
'name'.field()
'type'.field()
'ptdBalance'.field()
'ytdBalance'.field()
'balance'.field()
'drcr'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'View Journals'.action({place: 'row', spec: 'AccountJournalList.js'})
'AccountMaint.js'.maintSpecname()

'Enable General Ledger'.availableWhen((cast, list) => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  return c.glEnabled !== 'Yes'
})

'GL Settings'.availableWhen((cast, list) => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  return c.glEnabled === 'Yes'
})

'Add Account'.availableWhen((cast, list) => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  return c.glEnabled === 'Yes'
})

'Journal Entries'.availableWhen((cast, list) => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  return c.glEnabled === 'Yes'
})

'Profit and Loss'.availableWhen((cast, list) => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  return c.glEnabled === 'Yes'
})

'Balance Sheet'.availableWhen((cast, list) => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  return c.glEnabled === 'Yes'
})

'AccountList'.beforeLoading(async list => {
  await list.harmonize()
})

'ptdBalance'.modifyRenderValue((val, account) => {
  if ( ! account.ptdBalance ) return ''
  if ( (account.type !== 'Income') && (account.type !== 'Expenditure') )
    return ''
  return val
})

'ytdBalance'.modifyRenderValue((val, account) => {
  if ( ! account.ytdBalance ) return ''
  if ( (account.type !== 'Income') && (account.type !== 'Expenditure') )
    return ''
  return val
})

'balance'.modifyRenderValue((val, account) => {
  if ( ! account.balance ) return ''
  if ( (account.type === 'Income') || (account.type === 'Expenditure') )
    return ''
  return val
})
