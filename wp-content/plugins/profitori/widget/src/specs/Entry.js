'Entry'.datatype({exportable: true})
'entryNumber'.field({key: true})
'sourceEffectiveDate'.field({date: true})
'effectiveDate'.field({date: true})
'enteredDate'.field({date: true})
'notes'.field({multiLine: true})
'balance'.field({numeric: true, minDecimals: 2, maxDecimals: 2, caption: 'Entry Balance'})
'posted'.field({yesOrNo: true})

'Entry'.method('updateCredit', async function(location, creditValue, domain) {
  if ( ! location ) return

  let getAccount = async drcr => {
    let res = await location.sourceAndDrCrToAccount('Credit', drcr, domain)
    return res
  }

  let updateLine = async (drcr, sign) => {
    let account = await getAccount(drcr)
    let line = await this.accountToLine(account)
    let amount = creditValue * sign
    if ( account.drcr === 'CR' )
      amount = - amount
    line.amount += amount
    if ( ! line.amount )
      await line.trash()
  }

  if ( domain === 'Invoice' ) {
    await updateLine('DR', -1)
    await updateLine('CR', +1)
  } else {
    await updateLine('DR', +1)
    await updateLine('CR', -1)
  }
})

'Entry'.method('refreshEffectiveDate', async function() {
  this.effectiveDate = this.sourceEffectiveDate
  let config = await 'Configuration'.bringFirst()
  let preventDate = config.preventPostingBefore
  if ( preventDate && (! preventDate.isEmptyYMD()) ) {
    if ( this.effectiveDate <= preventDate )
      this.effectiveDate = preventDate.incDays(1)
  }
})

'Entry'.beforeSaving(async function() {
  let config = await 'Configuration'.bringFirst()
  if ( ! global.ymdIsSet(this.effectiveDate) )
    this.effectiveDate = this.sourceEffectiveDate
  if ( config.glEnabled !== 'Yes' ) return
  let preventDate = config.preventPostingBefore
  if ( preventDate && (! preventDate.isEmptyYMD()) ) {
    if ( ! (this.effectiveDate > preventDate) )
      throw(new Error('General Ledger is closed for dates on or before ' + preventDate))
    if ( global.ymdIsSet(this.getOld().effectiveDate) && ! (this.getOld().effectiveDate > preventDate) )
      throw(new Error('General Ledger is closed for dates on or before ' + preventDate))
  }
})

'Entry'.method('updatePurchase', async function(location, purchaseValue, domain, supplier) {
  if ( ! location ) return

  let getAccount = async drcr => {
    let res = await location.sourceAndDrCrToAccount('Purchase', drcr, domain, supplier)
    return res
  }

  let updateLine = async (drcr, sign) => {
    let account = await getAccount(drcr)
    let line = await this.accountToLine(account)
    let amount = purchaseValue * sign
    if ( account.drcr === 'CR' )
      amount = - amount
    line.amount += amount
    if ( ! line.amount )
      await line.trash()
  }

  await updateLine('DR', +1)
  await updateLine('CR', -1)
})

'Entry'.method('updateSale', async function(location, saleValue, domain) {
  if ( ! location ) return

  let getAccount = async drcr => {
    let res = await location.sourceAndDrCrToAccount('Sale', drcr, domain)
    return res
  }

  let updateLine = async (drcr, sign) => {
    let account = await getAccount(drcr)
    let line = await this.accountToLine(account)
    let amount = saleValue * sign
    if ( account.drcr === 'CR' )
      amount = - amount
    line.amount += amount
    if ( ! line.amount )
      await line.trash()
  }

  await updateLine('DR', +1)
  await updateLine('CR', -1)
})

'Entry'.method('updateFromSOLine', async function(location, saleValue) {
  await this.updateSale(location, saleValue, 'Price')
})

'Entry'.method('updateFromPOLine', async function(location, purchaseValue, supplier) {
  await this.updatePurchase(location, purchaseValue, 'Price', supplier)
})

'Entry'.method('accountToLine', async function(account) {
  let lines = await this.toLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( account.id === line.account.id )
      return line
  }
  let res = await 'EntryLine'.create({parentCast: this}, {entry: this, account: account})
  return res
})

'Entry'.method('updateFromTransaction', async function(tran, qty, supplier) {

  let getAccount = async drcr => {
    let location = await tran.toLocation(); if ( ! location ) return null
    let inventory = await tran.toInventory()
    let res = await location.sourceAndDrCrToAccount(tran.source, drcr, null, supplier, inventory)
    return res
  }

  let updateLine = async (drcr, sign) => {
    let unitCost = (tran.unitCost === global.unknownNumber()) ? 0 : tran.unitCost
    let amount = qty * unitCost * sign
    let account = await getAccount(drcr); if ( ! account ) return
    let line = await this.accountToLine(account)
    if ( account.drcr === 'CR' )
      amount = - amount
    line.amount += amount
    if ( ! line.amount )
      await line.trash()
  }

  await updateLine('DR', +1)
  await updateLine('CR', -1)
  if ( tran.taxPct > 0 )
    await this.updateTaxFromTransaction(tran, qty)
})

'Entry'.method('updateTaxFromTransaction', async function(tran, qty) {

  let getAccount = async drcr => {
    let location = await tran.toLocation(); if ( ! location ) return null
    let res = await location.sourceAndDrCrToAccount(tran.source, drcr, 'Tax')
    return res
  }

  let updateLine = async (drcr, sign) => {
    let unitCost = (tran.unitCost === global.unknownNumber()) ? 0 : tran.unitCost
    let amountIncTax = qty * unitCost * sign
    let amountExclTax = amountIncTax / (1 + (tran.taxPct / 100))
    let amount = amountIncTax - amountExclTax
    let account = await getAccount(drcr); if ( ! account ) return
    let line = await this.accountToLine(account)
    if ( account.drcr === 'CR' )
      amount = - amount
    line.amount += amount
    if ( ! line.amount )
      await line.trash()
  }

  await updateLine('DR', +1)
  await updateLine('CR', -1)
})

'Entry'.method('refreshAccountBalances', async function() {
  let Bringer = global.stBringerClass
  let b = new Bringer()
  let lines = await b.bring('EntryLine', null, {parent: this, includeMarkedForDeletion: true})
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let account = await line.toAccount(); if ( ! account ) continue
    await account.refreshBalances()
  }
})

'Entry'.method('refreshBalance', async function() {
  let lines = await this.toLines()
  this.balance = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let amount = await line.toDebitAmount()
    this.balance += amount
  }
  this.balance = global.roundTo2Decimals(this.balance)
  this.posted = (this.balance === 0) ? 'Yes' : 'No'
  await this.refreshAccountBalances()
})

'Entry'.method('toLines', async function() {
  let lines = await 'EntryLine'.bringChildrenOf(this)
  return lines
})
