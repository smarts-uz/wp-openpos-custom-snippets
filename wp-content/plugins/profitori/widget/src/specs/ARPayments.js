'ARPayments'.list({withHeader: true, icon: 'Money'})
'Payments'.title()
'Back'.action({act: 'cancel'})
'Add'.action({act: 'add'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})

'negAmountOutstanding'.field({numeric: true, decimals: 2, readOnly: true, caption: 'Amount Outstanding'})
'dueDate'.field({date: true, allowEmpty: true, readOnly: true})
'daysUntilPaymentDue'.field({numeric: true, readOnly: true})
'CreditMaint.js'.maintSpecname()

'ThePayments'.manifest()
'Credit'.datatype()
'creditNumber'.field()
'date'.field({caption: 'Date'})
'debtor'.field({showAsLink: true, caption: 'Customer'})
'customerReference'.field()
'amount'.field({numeric: true, decimals: 2, showTotal: true})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'CreditMaint.js'.maintSpecname()

'ARPayments'.beforeLoading(async list => {
  let credit = list.callerCast(); if ( ! credit ) return
  list.setFieldValue('negAmountOutstanding', credit.negAmountOutstanding)
  list.setFieldValue('dueDate', credit.dueDate)
  list.setFieldValue('daysUntilPaymentDue', credit.daysUntilPaymentDue)
})

'ThePayments'.defaultSort({field: "date"})

'ARPayments'.dynamicTitle(function() {
  let credit = this.callerCast(); if ( ! credit ) return
  if ( credit.datatype() !== "Credit" ) return 
  let res = "Payments for Invoice " + credit.creditNumber
  return res
})

'ThePayments'.criteria(async function () {
  let invoice = this.callerCast(); if ( ! invoice ) return null
  if ( invoice.datatype() !== "Credit" ) return null
  let res = {invoiceCredit: invoice.reference()}
  return res
})

