'DebtorMaint'.maint({panelStyle: 'titled', icon: 'UserFriends'})
'Edit Customer AR Details'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Account'.action({spec: 'CreditList.js'})
'Attachments'.action({act: 'attachments'})

'Debtor'.datatype()

'Customer AR Information'.panel()
'user'.field({refersTo: 'users', indexed: true, caption: 'Customer', hidden: true})
'customerName'.field({essence: true, readOnly: true, caption: 'Name', showAsLink: true})
'code'.field({caption: "AR Customer Code"})
'taxNumber'.field()
'companyRegistrationNumber'.field()
'mainContactPerson'.field()
'notes'.field({multiLine: true})
'customerId'.field({numeric: true, hidden: true, key: true})

'Financials'.panel()
'paymentTerms'.field()
'balance'.field({numeric: true, decimals: 2, readOnly: true})
'creditLimit'.field({numeric: true, decimals: 2})

'Debtor'.method('dateToDueDate', async function(date) {
  let paymentDays = await this.toPaymentDays()
  return date.incDays(paymentDays)
})

'Debtor'.method('toPaymentDays', async function() {
  if ( ! this.paymentTerms ) return 0
  let parts = this.paymentTerms.split(' ')
  if ( parts.length === 0 ) return 0
  return parseFloat(parts[0], 10)
})

'paymentTerms'.options(['0 Days', '1 Day', '7 Days', '14 Days', '30 Days', '45 Days', '60 Days', '90 Days'])

'paymentTerms'.inception('30 Days')

'DebtorMaint'.makeDestinationFor('Debtor')

'Debtor'.method('refreshBalance', async function() {
  let balance = 0
  let credits = await 'Credit'.bring({debtor: this})
  for ( var i = 0; i < credits.length; i++ ) {
    let credit = credits[i]
    balance += credit.amount
  }
  this.balance = balance
})

'DebtorMaint'.whenAdding(async function(debtor, maint) {
  let user = maint.callerCast(); if ( ! user ) return
  if ( user.datatype() !== 'users' )
    return
  debtor.user = user.reference()
})

'customerName'.destination(async debtor => {
  return await debtor.referee('user')
})

'customerName'.calculate(async debtor => {
  let user = await debtor.referee('user'); if ( ! user ) return null
  return user.toUniqueName()
})
