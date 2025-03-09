'SettingsMaint'.maint({expose: true, panelStyle: "titled", icon: 'Cog'})
'Settings'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Security'.action({spec: "ManageSecurity.js", icon: 'Lock'})
'Manage Account'.action({spec: "ManageAccount.js"})
'Currencies'.action({spec: "CurrencyList.js"})
'Units of Measure'.action({spec: "UOMList.js"})
'Imposts'.action({spec: "ImpostList.js"})
'Auto Numbering'.action({spec: 'NextNumberList.js'})
'Consolidation'.action({spec: 'ConsolidationSettings.js'})
'Transport Settings'.action({spec: 'TransportConfigMaint.js'})
'Label Layouts'.action({spec: 'TemplateList.js'})
'Utilities'.action({spec: "Utilities.js"})
'Modify Profitori'.action({spec: "Modify.js", caption: 'Modify ' + global.prfiSoftwareName})
'Configuration'.datatype()

'Manage Account'.availableWhen(config => {
  return ! global.isWhiteLabel()
})

//'Licensing and Security'.panel()
'Licensing'.panel()
'licenseKey'.field()
//'usersWithAccess'.field()

'Regional Settings'.panel()
'shortDateFormat'.field()
'displayDatesUsingShortDateFormat'.field({yesOrNo: true})
'lun'.field({caption: 'Length Unit for Label Layout'})

'lun'.inception('mm')
'lun'.options(['mm', 'in'])

'Reporting'.panel()
'salesProjectionPriorWeeks'.field()
'salesAnalysisPriorWeeks'.field()
'transactionRecentWeeks'.field()
'poPdfLogoUrl'.field({caption: 'PDF Logo URL'})

'System Settings'.panel()
'trb'.field()
'tbs'.field({readOnly: true})
'databaseOptimized'.field({readOnly: true})
'cdr'.field()
'classicDatabaseUpdates'.field({yesOrNo: true})
'subselects'.field({yesOrNo: true, caption: 'Use Database Subselects Instead of Joins'})
'highConcurrency'.field({yesOrNo: true})
'optimisticLocking'.field({yesOrNo: true})
'includeCorsHeaders'.field({yesOrNo: true, caption: 'Include CORS headers with ' + global.prfiSoftwareName + ' API responses'})
'logChangesToStockForProblemDiagnosis'.field({yesOrNo: true})
'treatOldIncompleteOrdersAsInactive'.field({yesOrNo: true, caption: 'Treat Old Incomplete WC Orders As Inactive'})
'exc'.field({yesOrNo: true, caption: 'Include Exclusive Customizations'})
'noAutoSunder'.field()
'storeAttachmentsInSecureLocation'.field()
'attachmentsPathOnServer'.field()
'useAttachmentSubfolders'.field()
'mih'.field()

'exc'.inception('Yes')

'exc'.visibleWhen((maint, config) => {
  let sess = 'session'.bringSingleFast(); if ( ! sess ) return true
  return sess.softwareName === 'Profitori'
})

'subselects'.inception('Yes')

'Inventory'.panel()
'defaultStockingUOM'.field({refersTo: 'UOM', caption: 'Default Stocking Unit of Measure'})
'showUOMOnPOLines'.field({yesOrNo: true, caption: 'Show Unit of Measure on PO Lines'})
'suppressPOQuantityOnHandUpdates'.field({caption: 'Suppress PO Receipt Updates to Quantity On Hand'})
'alwaysCreateInventoryRecord'.field({caption: 'Create ' + global.prfiSoftwareName + ' Inventory Records For All WC Products'})
'avgAlg'.field({caption: 'Average Costing Algorithm'})
'updCostHist'.field({caption: 'Update Inventory Historical Unit Costs When Recalculating Average Cost', yesOrNo: true})
'hideDisc'.field({yesOrNo: true, caption: 'Hide Discontinued Products From Customers'})
'tlo'.field({caption: 'Default Lot Tracking'})
'tex'.field({caption: 'Default Expiry Date Tracking', yesOrNo: true})
'pns'.field()

'tlo'.options(['None', 'Serial', 'Lot'])

'tlo'.inception('None')

'tex'.visibleWhen((maint, config) => {
  return (config.tlo === 'Serial') || (config.tlo === 'Lot')
})

'avgAlg'.options(['Dynamic Refresh', 'Simple Weighted'])

'avgAlg'.inception('Dynamic Refresh')

'updCostHist'.inception('Yes')

'Purchasing'.panel()
'businessName'.field()
'deliveryAddress'.field({caption: 'Address for Deliveries'})
'deliveryCity'.field({caption: 'City/Suburb/Town'})
'deliveryState'.field({caption: 'State/Province'})
'deliveryPostcode'.field({caption: 'Postal/Zip Code'})
'deliveryCountry'.field({caption: 'Country'})
'email'.field()
'phoneNumber'.field({caption: 'Phone'})
'taxPct'.field({numeric: true, decimals: 2, caption: "Default Tax %"}) 
'enterPurchasePricesInclusiveOfTax'.field({yesOrNo: true})
'excludeTaxFromPOPdf'.field()
'sortPOPdfBySupplierSku'.field()
'viewSalesHistoryInPurchasing'.field()
'allowDeliveryDatesOnPOLines'.field({yesOrNo: true, caption: 'Allow Separate Delivery Dates On PO Lines'})
'startPOReceiptsAsZero'.field()
'autoLoadSupplierProductData'.field({yesOrNo: true})
'ptr'.field()
'supplierPaymentHandling'.field()
'spt'.field()
'pte'.field({multiLine: true})
'eub'.field()
'asc'.field({caption: 'Add PO Shipping Costs To Product Average Costs', yesOrNo: true})

'pte'.visibleWhen((maint, config) => {
  return config.spt === 'Yes'
})

'Stocktake'.panel()
'showDiscrepanciesInCountPage'.field({yesOrNo: true})

'Manufacturing'.panel()
'mfgIncTax'.field({yesOrNo: true, caption: 'Show WO and Bundle Costs Including Tax'})
'acp'.field()

'Preorders'.panel()
'preIncTax'.field({yesOrNo: true, caption: 'Enter Preorder Prices Inclusive Of Tax'})
'deductPre'.field({yesOrNo: true, caption: 'Deduct Firm Preorders From WC Stock Level'})
'showPre'.field({yesOrNo: true, caption: 'Show Preorders To Customer'})

'Sales Order Management'.panel()
'stImp'.field()
'asd'.field({caption: "Automatically Show Discounts In Store", yesOrNo: true})
'ump'.field({caption: "Use Markup Pricing", yesOrNo: true})
'emb'.field()
'eaa'.field()
'pnm'.field()
'sti'.field()

'Fulfillment'.panel()
'nfa'.field()
'prioritizeOrderCompletion'.field()
'autoCompleteWCOrders'.field()
'preventOverpick'.field()
'enterIncrementalShipmentQuantity'.field()
'annotatePartiallyDeliveredWCOrders'.field()
'showShipmentsToCustomer'.field({yesOrNo: true})
'shc'.field()
'hsnc'.field()
'sbp'.field({yesOrNo: true, caption: 'Show Shipment Billing And Payment Status To Customer'})
'showFinanceInfoToCustomer'.field({yesOrNo: true})
'hfc'.field()
'excludeTaxFromShipmentInvoices'.field({yesOrNo: true})
'swo'.field()
'scs'.field()
'ais'.field()

'tbs'.visibleWhen((maint, config) => {
  return config.trb && (config.trb !== 'Off')
})

'trb'.afterUserChange(async (oldInputValue, newInputValue, config, maint) => {
  if ( (! config.trb) || (config.trb === 'Off') ) {
    config.tbs = '';
    return
  }
  config.tbs = 'Pending'
})

'hfc'.visibleWhen((maint, config) => {
  return config.showFinanceInfoToCustomer === 'Yes'
})

'shc'.visibleWhen((maint, config) => {
  return config.showShipmentsToCustomer === 'Yes'
})

'hsnc'.visibleWhen((maint, config) => {
  return config.showShipmentsToCustomer === 'Yes'
})

'scs'.visibleWhen((maint, config) => {
  return config.swo === 'Yes'
})

'ais'.visibleWhen((maint, config) => {
  return config.swo === 'Yes'
})

'sbp'.visibleWhen((maint, config) => {
  return config.showShipmentsToCustomer === 'Yes'
})

'Sales Invoicing'.panel()
'salesInvoiceBusinessName'.field({caption: "Business Name"})
'salesInvoiceAddress'.field({caption: 'Address for Sales Invoices'})
'salesInvoiceCity'.field({caption: 'City/Suburb/Town'})
'salesInvoiceState'.field({caption: 'State/Province'})
'salesInvoicePostcode'.field({caption: 'Postal/Zip Code'})
'salesInvoiceCountry'.field({caption: 'Country'})
'salesInvoiceEmail'.field({caption: 'Email'})
'salesInvoicePhoneNumber'.field({caption: 'Phone'})
'salesInvoiceTaxId'.field({caption: 'Tax ID'})
'crn'.field({caption: 'Company Registration Number'})
'ibn'.field({caption: 'IBAN'})
'salesInvoicePaymentInstructions'.field({caption: 'Payment Instructions'})
'salesInvoiceTerms'.field({caption: 'Terms'})
'salesInvoiceDaysToPay'.field({caption: 'Days to Pay', numeric: true})

'Accounts Receivable'.panel()
'cph'.field()
'creditPayMeths'.field({caption: 'Payment Methods Limited to Customers With Credit'})
'autoAREmail'.field({yesOrNo: true, caption: 'Automatically Email Invoices'})
'sin'.field()

'Profit Reporting'.panel()
'prDateType'.field({caption: 'Date To Use For Profit Reporting'})
'prHistCost'.field({caption: 'Use Inventory Historical Unit Costs when Viewing Profits', yesOrNo: true})

'Commissions'.panel()
'ccf'.field()
'cty'.field()

'prDateType'.options(['Order Payment Date', 'Order Date'])

'prHistCost'.inception('Yes')

'useAttachmentSubfolders'.visibleWhen((maint, configuration) => {
  return configuration.storeAttachmentsInSecureLocation === 'Yes'
})

'attachmentsPathOnServer'.visibleWhen((maint, configuration) => {
  return configuration.storeAttachmentsInSecureLocation === 'Yes'
})

'supplierPaymentHandling'.options(['Manually Mark POs as Paid', 'Multiple Payments Per PO'])

'supplierPaymentHandling'.inception('Manually Mark POs as Paid')

'cph'.options(['Manually Mark Invoices as Paid', 'Multiple Payments Per Invoice'])

'cph'.inception('Multiple Payments Per Invoice')

'SettingsMaint'.onSave(async (maint, config) => {
  
  let condense = async () => {
    config.tbs = 'Conversion Started'
    await global.app.save()
    config.condense()
  }

  let cancel = async () => {
    config.trb = 'Off'
    config.tbs = ''
    await global.app.save()
  }

  if ( config.trb && (config.trb !== 'Off') && (config.tbs === 'Pending') ) {
    maint.showMessage(("You’re turning on Turbo mode.  Please backup your system before proceeding. " + 
      " This will take a while to complete and may place extra load on your system while it's processing.  Do you wish to proceed?").translate(),
      {yesNo: true, onYes: condense, onNo: cancel}
    )
  }
})
