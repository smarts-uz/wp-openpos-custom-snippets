'EntryLine'.datatype({exportable: true})
'entry'.field({refersToParent: 'Entry', parentIsHeader: true})
'account'.field({refersTo: 'Account'})
'amount'.field({numeric: true, minDecimals: 2, maxDecimals: 2})
'drAmount'.field({numeric: true, minDecimals: 2, maxDecimals: 2, caption: 'DR Amount'})
'crAmount'.field({numeric: true, minDecimals: 2, maxDecimals: 2, caption: 'CR Amount'})
'notes'.field({multiLine: true})
'fullNotes'.field({caption: 'Notes'})
'effectiveDate'.field({date: true})
'accountType'.field()
'effectOnLedger'.field()
'entryNumber'.field()
'posted'.field({yesOrNo: true})

'posted'.calculate(async line => {
  let entry = await line.parent()
  return entry.posted
})

'fullNotes'.calculate(async line => {
  let entry = await line.parent()
  let res = global.appendWithSep(entry.notes, line.notes, ' ')
  return res
})

'EntryLine'.method('isPosted', async function() {
  let entry = await this.parent(); if ( ! entry ) return null
  return (entry.posted === 'Yes')
})

'drAmount'.calculate(async line => {
  let res = await line.toDebitAmount()
  if ( res < 0 )
    res = 0
  return res
})

'crAmount'.calculate(async line => {
  let res = await line.toDebitAmount()
  if ( res > 0 )
    res = 0
  return - res
})

'EntryLine'.method('toAccount', async function() {
  return await this.referee('account')
})

'EntryLine'.method('toDebitAmount', async function() {
  let res = this.amount
  let account = await this.referee('account'); if ( ! account ) return res
  if ( account.drcr === 'CR' )
    res = - res
  return global.roundTo2Decimals(res)
})

'EntryLine'.beforeSaving(async function() {
  let config = await 'Configuration'.bringFirst()
  if ( config.glEnabled !== 'Yes' ) return
  let entry = await this.parent()
  await entry.refreshBalance()
})

'effectOnLedger'.calculate(async line => {
  let account = await line.referee('account'); if ( ! account ) return null
  let amt = line.amount
  let drcr = account.drcr
  if ( amt < 0 ) {
    amt = - amt
    drcr = (drcr === 'DR') ? 'CR' : 'DR'
  }
  let amtStr = global.numToStringWithXDecimals(amt, 2)
  return amtStr + ' ' + drcr
})

'effectiveDate'.calculate(async line => {
  let entry = await line.parent(); if ( ! entry ) return null
  return entry.effectiveDate
})

'accountType'.calculate(async line => {
  let account = await line.referee('account'); if ( ! account ) return null
  return account.type
})

'entryNumber'.calculate(async line => {
  let entry = await line.parent(); if ( ! entry ) return null
  return entry.entryNumber
})

