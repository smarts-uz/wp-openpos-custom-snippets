'UnpaidARInvoices'.list()
'Unpaid Invoices'.title()
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})

'Credit'.datatype()
'creditNumber'.field({showAsLink: true, caption: 'Invoice Number'})
'date'.field({date: true})
'debtor'.field({showAsLink: true})
'customerReference'.field()
'negAmount'.field({caption: 'Amount', showTotal: true})
'negAmountOutstanding'.field({caption: 'Amount Outstanding', showTotal: true})
'dueDate'.field({date: true})
'daysUntilPaymentDue'.field() //***

'UnpaidARInvoices'.defaultSort({field: 'dueDate'})

'UnpaidARInvoices'.filter(async credit => {
  return (credit.creditType === 'Invoice') && (credit.paid !== 'Yes')
})



