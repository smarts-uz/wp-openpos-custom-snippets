'ARPaymentPdf'.output({rowsPerPage: 9})
'RECEIPT'.title()
'Receipt'.datatype({transient: true})

'Header'.section({pageHeading: true})
'creditNumber'.field({caption: "Receipt Number"})
'customerReference'.field()
"date".field({date: true})
'billingNameAndCompany'.field({caption: "Bill To"})
'billingAddress'.field({caption: ""})
'billingEmailAndPhone'.field({caption: ""})
'ourBusinessName'.field({caption: "From"})
'ourFromAddress'.field({caption: ""})
'ourContactDetails'.field({caption: ""})
'ourTaxId'.field({caption: "Tax ID"})
'trackingNumbers'.field({caption: 'Tracking Number'})

'Lines'.manifest()
'ReceiptLine'.datatype({transient: true})
'receipt'.field({refersToParent: 'Receipt', hidden: true})
'productUniqueName'.field({caption: "Product", width: 82})
'productSku'.field({caption: "SKU", width: 15})
'quantity'.field({numeric: true, maxDecimals: 6, width: 18, caption: "Qty"})
'unitPriceExclTaxFX'.field({numeric: true, decimals: 2, caption: "Price", width: 18})
'lineValueFX'.field({numeric: true, decimals: 2, caption: "Amount", width: 20})

'Footer'.section({outputFooter: true, width: 130, rightAlign: true})
'Receipt'.datatype()
'shippingExclTaxFX'.field({numeric: true, decimals: 2, caption: "Shipping", width: 65})
'feesExclTaxFX'.field({numeric: true, decimals: 2, caption: "Fees", width: 65})
'taxFX'.field({numeric: true, decimals: 2, width: 65, caption: "Tax"})
'totalWithCurrency'.field({caption: "Invoice Total", rightAlign: true, width: 65})
'receiptAmount'.field({numeric: true, decimals: 2, caption: "This Payment", rightAlign: true, width: 65})
'amountOutstanding'.field({numeric: true, decimals: 2, rightAlign: true, width: 65})

/* eslint-disable no-cond-assign */

'ARPaymentPdf'.getCasts(async output => {
  'Receipt'.clear()
  'ReceiptLine'.clear()
  let payment = output.cast
  let invoice = await payment.referee('invoiceCredit')
  let rec
  
  let createReceipt = async () => {
    rec = await 'Receipt'.create()
    rec.creditNumber = payment.creditNumber
    rec.customerReference = invoice ? invoice.customerReference : ''
    rec.date = payment.date
    rec.billingNameAndCompany = payment.billingNameAndCompany
    rec.billingAddress = payment.billingAddress
    rec.billingEmailAndPhone = payment.billingEmailAndPhone
    rec.ourBusinessName = payment.ourBusinessName
    rec.ourFromAddress = payment.ourFromAddress
    rec.ourContactDetails = payment.ourContactDetails
    rec.ourTaxId = payment.ourTaxId
    rec.trackingNumbers = invoice ? invoice.trackingNumbers : ''
    rec.shippingExclTaxFX = invoice ? invoice.shippingExclTaxFX : ''
    rec.feesExclTaxFX = invoice ? invoice.feesExclTaxFX : ''
    rec.taxFX = invoice ? invoice.taxFX : payment.taxFX
    rec.totalWithCurrency = invoice ? invoice.totalWithCurrency : payment.totalWithCurrency
    rec.receiptAmount = payment.amount
    rec.amountOutstanding = invoice ? - invoice.amountOutstanding : 0
  }

  let createReceiptLines = async () => {
    if ( ! invoice ) return
    let line; let lines = await invoice.toLines()
    while ( line = lines.__() ) {
      let recLine = await 'ReceiptLine'.create({parentCast: rec}, {receipt: rec.reference()})
      recLine.productUniqueName = line.productUniqueName
      recLine.productSku = line.productSku
      recLine.quantity = line.quantity
      recLine.unitPriceExclTaxFX = line.unitPriceExclTaxFX
      recLine.lineValueFX = line.lineValueFX
    }
  }

  await createReceipt()
  await createReceiptLines()
  return [rec]
})

