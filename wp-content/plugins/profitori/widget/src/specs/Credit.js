'Credit'.datatype({caption: 'AR Entry'})
'creditNumber'.field({key: true, caption: 'AR Entry Number'})
'debtor'.field({refersTo: 'Debtor', caption: 'Customer', indexed: true})
'customerName'.field({caption: 'Customer'})
'date'.field({date: true})
'dueDate'.field({date: true, allowEmpty: true})
'creditType'.field({caption: 'Entry Type', allowEmpty: false})
'amount'.field({numeric: true, decimals: 2})
'notes'.field()
'shipment'.field({refersTo: 'SOShipment', indexed: true})
'order'.field({refersTo: 'orders', indexed: true})
'emailed'.field({yesOrNo: true})
'emailDate'.field({date: true, allowEmpty: true})
'emailAddress'.field()
'billingNameAndCompany'.field()
'billingAddress'.field()
'billingEmailAndPhone'.field()
'ourBusinessName'.field()
'ourFromAddress'.field()
'ourContactDetails'.field()
'ourPhoneNumber'.field()
'ourEmail'.field()
'ourTaxId'.field()
'ourCompanyRegistrationNumber'.field()
'ourIBAN'.field()
'shippingExclTaxFX'.field({numeric: true, decimals: 2})
'feesExclTaxFX'.field({numeric: true, decimals: 2})
'taxFX'.field({numeric: true, decimals: 2})
'totalWithCurrency'.field()
'terms'.field()
'paymentInstructions'.field()
'customerReference'.field()
'trackingNumbers'.field()
'shipmentNumber'.field({storedCalc: true})
'paymentCredit'.field({refersTo: 'Credit'})
'paymentReference'.field()
'paymentLink'.field()
'absoluteAmount'.field({numeric: true, decimals: 2, caption: 'Amount'})
'taxNumber'.field()
'companyRegistrationNumber'.field()
'billingState'.field()
'billingCountry'.field()
'negAmount'.field({numeric: true, decimals: 2})
'negAmountExclTax'.field({numeric: true, decimals: 2})
'daysUntilPaymentDue'.field({numeric: true})
'amountOutstanding'.field({numeric: true, decimals: 2})
'absoluteAmountOutstanding'.field({numeric: true, decimals: 2})
'negAmountOutstanding'.field({numeric: true, decimals: 2})
'invoiceCredit'.field({refersTo: 'Credit'})
'journaledAmount'.field({numeric: true, preserveOnTrash: true})
'journaledDate'.field({date: true, allowEmpty: true})
'journaledLocation'.field({refersTo: 'Location'})

'Credit'.beforeSaving(async function() {

  let getOrCreateEntry = async () => {
    let sourceAbbrev = 'AR'
    let entryNumber = sourceAbbrev + '-' + this.journaledDate
    let res = await 'Entry'.bringOrCreate({entryNumber: entryNumber})
    res.effectiveDate = this.journaledDate
    res.sourceEffectiveDate = res.effectiveDate
    res.notes = 'AR ' + res.effectiveDate.toLocalMMMDY()
    res.enteredDate = global.todayYMD()
    return res
  }

  let maybeReverseOldEntry = async () => {
    if ( ! this.journaledAmount ) return
    let loc = await 'Location'.bringSingle({locationName: 'General'})
    await updateJournalEntry({location: loc, amount: - this.journaledAmount})
  }

  let updateJournalEntry = async (options) => {
    if ( ! options.amount ) return
    let entry = await getOrCreateEntry()
    await entry.updateCredit(options.location, options.amount, this.creditType)
  }

  let doUpdateJournalEntries = async () => {
    await maybeReverseOldEntry()
    let amount = this.amount
    let loc = await 'Location'.bringSingle({locationName: 'General'})
    await updateJournalEntry({location: loc, amount: amount})
    this.journaledAmount = amount
    this.journaledLocation = loc.reference()
  }

  let updateJournalEntries = async () => {
    let config = await 'Configuration'.bringOrCreate()
    if ( config.glEnabled !== 'Yes' ) return
    this.journaledDate = this.date
    await doUpdateJournalEntries()
  }

  let so = await this.toSO()
  if ( so )
    await so.refreshValueRemainingToBeInvoiced()
  await this.maybeRefreshPaidInvoice()
  await this.refreshAmountOutstanding()
  let debtor = await this.toDebtor()
  if ( debtor ) {
    await debtor.refreshBalance()
  }
  await updateJournalEntries()
})

'Credit'.method('toLines', async function() {
  let lines = await 'CreditLine'.bringChildrenOf(this)
  return lines
})

'Credit'.method('toCustomer', async function() {
  let debtor = await this.toDebtor(); if ( ! debtor ) return null
  return await debtor.referee('user')
})

'billingNameAndCompany'.calculate(async credit => {
  let customer = await credit.toCustomer(); if ( ! customer ) return ''
  return customer.billingNameAndCompany
})

'billingAddress'.calculate(async credit => {
  let customer = await credit.toCustomer(); if ( ! customer ) return ''
  return await customer.toBillingAddress()
})

'billingEmailAndPhone'.calculate(async credit => {
  let customer = await credit.toCustomer(); if ( ! customer ) return ''
  return customer.billingEmailAndPhone
})

'invoiceCredit'.afterUserChange(async (oldInputValue, newInputValue, credit) => {
  let inv = await credit.referee('invoiceCredit'); if ( ! inv ) return
  credit.amount = - inv.amountOutstanding
  credit.absoluteAmount = credit.amount
})

'Credit'.validate(async function() {
  if ( global.confVal('cph') !== 'Multiple Payments Per Invoice' ) return
  if ( this.creditType !== 'Payment' ) return
  if ( ! this.invoiceCredit )
    throw(new Error('Invoice is required'.translate()))
})

'Credit'.method('refreshAmountOutstanding', async function() {
  if ( this.creditType !== 'Invoice' ) return
  await this.refreshCalculations({force: true})
  this.amountOutstanding = this.amount
  let payments = await 'Credit'.bring({invoiceCredit: this.reference()})
  for ( var i = 0; i < payments.length; i++ ) {
    let payment = payments[i]
    this.amountOutstanding += payment.amount
  }
  if ( global.confVal('cph') !== 'Multiple Payments Per Invoice' ) return
  if ( this.amountOutstanding === this.amount ) 
    this.paid = 'No'
  else if ( this.amountOutstanding >= 0 ) // note: amountOustanding for invoices is negative when there's an outstanding amount
    this.paid = 'Yes'
  else
    this.paid = 'No'
})

'absoluteAmountOutstanding'.calculate(credit => {
  if ( credit.effectIsNormallyPositive() ) 
    return credit.amountOutstanding
  return - credit.amountOutstanding
})

'daysUntilPaymentDue'.calculate(credit => {
  if ( (! credit.dueDate) || (credit.dueDate === global.emptyYMD()) )
    return 0
  return - global.todayYMD().dateSubtract(credit.dueDate)
})

'ourIBAN'.calculate(credit => {
  return global.confVal('ibn')
})

'ourCompanyRegistrationNumber'.calculate(credit => {
  return global.confVal('crn')
})

'ourBusinessName'.calculate(credit => {
  return global.confVal('salesInvoiceBusinessName')
})

'ourPhoneNumber'.calculate(credit => {
  return global.confVal('salesInvoicePhoneNumber')
})

'ourEmail'.calculate(credit => {
  return global.confVal('salesInvoiceEmail')
})

'ourTaxId'.calculate(credit => {
  return global.confVal('salesInvoiceTaxId')
})

'negAmount'.calculate(credit => {
  return - credit.amount
})

'negAmountOutstanding'.calculate(credit => {
  return - credit.amountOutstanding
})

'negAmountExclTax'.calculate(credit => {
  return credit.negAmount - credit.taxFX
})

'billingState'.calculate(async credit => {
  let order = await credit.toOrder(); if ( ! order ) return ''
  return order._billing_state
})

'billingCountry'.calculate(async credit => {
  let order = await credit.toOrder(); if ( ! order ) return ''
  return global.codeToCountry(order._billing_country)
})

'companyRegistrationNumber'.calculate(async credit => {
  let debtor = await credit.toDebtor(); if ( ! debtor ) return ''
  return debtor.companyRegistrationNumber
})

'taxNumber'.calculate(async credit => {
  let debtor = await credit.toDebtor(); if ( ! debtor ) return ''
  return debtor.taxNumber
})

'Credit'.afterRetrieving(async function() {
  if ( this.propChanged('absoluteAmount') ) return
  if ( this.effectIsNormallyPositive() ) 
    this.absoluteAmount = this.amount
  else
    this.absoluteAmount = - this.amount
})

'Credit'.method('effectIsNormallyPositive', function() {
  return this.creditType !== 'Invoice'
})

'absoluteAmount'.afterUserChange(async (oldInputValue, newInputValue, credit) => {
  if ( credit.effectIsNormallyPositive() ) 
    credit.amount = credit.absoluteAmount
  else
    credit.amount = - credit.absoluteAmount
})

'paymentLink'.calculate(credit => {
  let loc = window.location
  let res = loc.protocol + '//' + loc.host.stripRight('/') + '/shipments/prfi_pay_invoice/' + credit.id
  res += '/'
  return res
})

'Credit'.method('maybeCreatePayment', async function(parms) {
  let invoice = this
  if ( invoice.creditType !== 'Invoice' ) return
  if ( invoice.paymentCredit ) return
  let paymentNumber = invoice.creditNumber + '-PAY'
  let payment = await 'Credit'.create(null, {creditNumber: paymentNumber, debtor: invoice.debtor})
  payment.date = parms.date
  payment.creditType = 'Payment'
  payment.amount = - invoice.amount
  payment.paymentReference = invoice.paymentReference // NOTE: populated on the invoice server-side 
  payment.invoiceCredit = invoice.reference()
  invoice.paymentCredit = payment.reference()
})

'shipmentNumber'.calculate(async credit => {
  if ( ! credit.shipment ) return null
  let shipment = await credit.referee('shipment'); if ( ! shipment ) return null
  if ( shipment.invoiced !== 'Yes' ) return null
  return shipment.shipmentNumber
})

'totalWithCurrency'.calculate(async credit => {
  let res = (await credit.toCurrencyCode()) + ' ' + global.numToStringWithXDecimals(- credit.amount, 2)
  return res
})

'Credit'.method('toCurrencyCode', async function() {
  let order = await this.toOrder(); if ( ! order ) return ''
  return order._order_currency
})

'trackingNumbers'.calculate(async credit => {
  let order = await credit.toOrder(); if ( ! order ) return ''
  return order.trackingNumbers
})

'Credit'.method('toOrder', async function() {
  if ( ! this.order ) 
    return null
  let res = await 'orders.RecentOrActive'.bringSingle({id: this.order.id})
  return res
})

'Credit'.method('getPDFSpecName', function() {
  if ( this.creditType === 'Invoice' )
    return 'ARInvoicePdf.js'
  if ( this.creditType === 'Payment' )
    return 'ARPaymentPdf.js'
})

'Credit'.method('createPDFAttachment', async function() {
  let pdf = await global.app.generatePDF(this, {spec: this.getPDFSpecName()}); if ( ! pdf ) return
  let attachment = await 'Attachment'.create()
  attachment.datatype = 'Credit'
  attachment.theParentId = this.id
  await attachment.refreshEntityName()
  attachment.parentReference = this.creditNumber
  attachment.attachmentType = 'Invoice'
  attachment.description = 'Invoice PDF ' + this.creditNumber
  attachment.attachedDate = global.todayYMD()
  attachment.documentType = 'PDF'
  attachment.contents = btoa(pdf.output())
  let fileName = attachment.description + '.PDF'
  attachment.__fileName = fileName
  attachment.fileName = fileName
  await global.foreman.doSave()
  return attachment
})

'Credit'.method('sendViaEmail', async function() {
  await global.foreman.doSave()
  if ( this.creditType !== 'Invoice' ) return
  let attachment = await this.createPDFAttachment(); if ( ! attachment ) return
  await this.refreshEmailAddress(); if ( ! this.emailAddress ) return
  await global.foreman.doSave()
  let salesInvoiceBusinessName = global.confVal('salesInvoiceBusinessName')
  await global.server.emailAttachment(
    {
      attachment: attachment, 
      emailAddress: this.emailAddress,
      subject: salesInvoiceBusinessName + ' ' + 'Invoice'.translate() + ' ' + this.creditNumber,
      message: 'Please find attached Invoice'.translate() + ' ' + this.creditNumber
    }
  )
  this.emailed = 'Yes'
  this.emailDate = global.todayYMD()
  await global.foreman.doSave()
})

'Credit'.method('maybeEmail', async function() {
  if ( global.confVal('autoAREmail') !== 'Yes' ) return
  await this.sendViaEmail()
})

'Credit'.method('refreshEmailAddress', async function() {
  this.emailAddress = null
  let order = await this.toOrder(); if( ! order ) return
  this.emailAddress = order._billing_email
  if ( this.emailAddress )
    return
  let customer = await order.toCustomer(); if ( ! customer ) return
  this.emailAddress = customer.billing_email
})

'creditType'.options(['Payment', 'Prepayment', 'Credit Note', 'Invoice', 'Adjustment'])

'customerName'.calculate(async credit => {
  let debtor = await credit.referee('debtor'); if ( ! debtor ) return null
  return debtor.customerName
})

'Credit'.method('maybeRefreshPaidInvoice', async function() {
  if ( this.getOld() && this.getOld().invoiceCredit ) {
    let oldInvoice = await 'Credit'.bringFirst({id: this.getOld().invoiceCredit.id})
    if ( oldInvoice ) 
      await oldInvoice.refreshAmountOutstanding()
  }
  if ( this.invoiceCredit ) {
    let invoice = await this.referee('invoiceCredit')
    if ( invoice ) 
      await invoice.refreshAmountOutstanding()
  }
})

/*
'Credit'.beforeSaving(async function() {
  let so = await this.toSO()
  if ( so )
    await so.refreshValueRemainingToBeInvoiced()
  await this.maybeRefreshPaidInvoice()
  await this.refreshAmountOutstanding()
  let debtor = await this.toDebtor()
  if ( debtor ) {
    await debtor.refreshBalance()
  }
})
*/

'Credit'.method('toSO', async function() {
  if ( ! this.order ) return null
  let order = await this.toOrder(); if( ! order ) return
  return await order.toSO()
})

'Credit'.method('toDebtor', async function() {
  return await this.referee('debtor')
})
