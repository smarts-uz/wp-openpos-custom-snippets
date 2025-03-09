'ConsolidatedProfits'.list({withHeader: true, icon: 'DollarSign'})
'View Consolidated Profits'.title()
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})
'Consolidation Settings'.action()
'fromDate'.field({date: true})
'toDate'.field({date: true})
'includeTax'.field({yesOrNo: true})
'sites'.field({readOnly: true})

'sites'.calculate(async cast => {
  let res = ''
  let constituents = await 'Constituent'.bring()
  for ( var i = 0; i < constituents.length; i++ ) {
    let constituent = constituents[i]
    if ( res )
      res += ' + '
    res += constituent.siteName
  }
  if ( ! res )
    res = 'There are no constituent sites configured - go to Consolidation Settings to set them up'.translate()
  else
    res = 'This site'.translate() + ' + ' + res
  return res
})

'Lines'.manifest()
'Go'.action({place: 'header', spinner: true})
'Profit'.datatype({transient: true})
'product'.field({refersTo: 'products', key: true, showAsLink: true})
'salesQuantity'.field({numeric: true, showTotal: true})
'salesValue'.field({numeric: true, decimals: 2, showTotal: true})
'avgUnitPrice'.field({numeric: true, decimals: 2, caption: "Avg Unit Sale Price"})
'costOfGoodsSold'.field({numeric: true, decimals: 2, caption: "Cost of Goods Sold", showTotal: true})
'avgUnitCost'.field({numeric: true, decimals: 2})
'grossProfitValue'.field({numeric: true, decimals: 2, showTotal: true})
'grossProfitPercent'.field({numeric: true, decimals: 2, caption: "Gross Profit %", showTotal: true})
'taxValue'.field({numeric: true, decimals: 2, showTotal: true})


'Consolidation Settings'.act(async list => {
  await list.segue("edit", 'ConsolidationSettings.js', null)
})

'includeTax'.default(async list => {
  let c = await 'Configuration'.bringSingle(); if ( ! c ) return 'No'
  return c.enterPurchasePricesInclusiveOfTax
})

'ConsolidatedProfits'.beforeLoading(async list => {
  'Profit'.clear()
  await list.harmonize()
})

'grossProfitPercent'.calculateTotal(profits => {
  let grossProfitValue = 0
  let salesValue = 0
  profits.forAll(profit => {
    if ( profit.grossProfitValue === global.unknownNumber() ) return "continue"
    grossProfitValue = global.sum(grossProfitValue, profit.grossProfitValue)
    salesValue += profit.salesValue
  })
  let res = global.percent(grossProfitValue, salesValue)
  return res
})

'fromDate'.default(async (list) => {
  let c = await 'Configuration'.bringSingle(); if ( ! c ) return
  return c.viewFromDate
})

'toDate'.default(async (list) => {
  let c = await 'Configuration'.bringSingle(); if ( ! c ) return
  return c.viewToDate
})

'Go'.act(async (list) => {

  let app = list.app()
  let includeTax
  let doingSiteName

  let incorporateConstituentProfits = async (constituentProfitCasts, constituent) => {
    for ( var i = 0; i < constituentProfitCasts.length; i++ ) {
      let constituentProfit = constituentProfitCasts[i]
      if ( (! constituentProfit.salesQuantity) && (! constituentProfit.salesValue) && (! constituentProfit.costOfGoodsSold) ) continue
      if ( ! constituentProfit.sku ) {
        throw(new Error('Product'.translate() + ' ' + constituentProfit.product.keyval + ' ' + 'from constituent site'.translate() + ' ' +
          constituent.siteName + 'has no SKU - please correct then try again'.translate()))
      }
      let mainProfit = await 'Profit'.bringSingle({sku: constituentProfit.sku})
      if ( ! mainProfit ) {
        throw(new Error('SKU'.translate() + ' ' + constituentProfit.sku + ' ' + 'not found in main system'.translate()))
      }
      mainProfit.salesQuantity += constituentProfit.salesQuantity
      mainProfit.salesValue += constituentProfit.salesValue
      mainProfit.costOfGoodsSold = global.sum(mainProfit.costOfGoodsSold, constituentProfit.costOfGoodsSold)
      mainProfit.grossProfitValue = global.sum(mainProfit.grossProfitValue, constituentProfit.grossProfitValue)
      mainProfit.grossProfitPercent = global.percent(mainProfit.grossProfitValue, mainProfit.salesValue)
      mainProfit.taxValue += constituentProfit.taxValue
      if ( mainProfit.salesQuantity ) {
        mainProfit.avgUnitPrice = mainProfit.salesValue / mainProfit.salesQuantity
        let cogs = mainProfit.costOfGoodsSold
        let avg = global.unknownNumber()
        if ( cogs !== global.unknownNumber() )
          avg = cogs / mainProfit.salesQuantity
        mainProfit.avgUnitCost = avg
      }
    }
  }

  let addConstituentProfits = async constituent => {
    doingSiteName = constituent.siteName
    let constituentProfitCasts
    try {
      await app.switchToConstituentForeman(constituent)
      let products = await 'products'.bring()
      await products.forAllAsync(async product => {
        await product.createProfitCast(fromDate, toDate, (includeTax === 'Yes'))
      })
      constituentProfitCasts = await 'Profit'.bring()
    } finally {
      await app.revertToMainForeman()
    }
    await incorporateConstituentProfits(constituentProfitCasts, constituent)
  }

  let addConstituentsProfits = async () => {
    let constituents = await 'Constituent'.bring()
    for ( var i = 0; i < constituents.length; i++ ) {
      let constituent = constituents[i]
      await addConstituentProfits(constituent)
      global.updateProgress(0.2 + 0.8 * ((i + 1) / constituents.length))
    }
  }

  await app.harmonize()
  let saveSetting = async (fieldName, settingFieldName) => {
    let c = await 'Configuration'.bringOrCreate()
    let v = list.getFieldValue(fieldName)
    c[settingFieldName] = v
  }

  await saveSetting('fromDate', 'viewFromDate')
  await saveSetting('toDate', 'viewToDate')
  await app.save()
  let fromDate = list.getFieldValue('fromDate')
  if ( (! fromDate) || fromDate.isEmptyYMD() ) fromDate = "0000-00-00"
  let toDate = list.getFieldValue('toDate')
  if ( (! toDate) || toDate.isEmptyYMD() ) toDate = "9999-99-99"
  includeTax = list.getFieldValue('includeTax')

  let products = await 'products'.bring()
  'Profit'.clear()
  doingSiteName = 'Main'
  global.startProgress({message: "Collating data..."})
  try {
    await products.forAllAsync(async product => {
      await product.createProfitCast(fromDate, toDate, (includeTax === 'Yes'))
    })
    global.updateProgress(0.2)
    await addConstituentsProfits()
  } catch(e) {
    let msg = 'There was a problem fetching data from ' + doingSiteName + ' - please check settings. (' + e.message + ')'
    global.gApp.showMessage(msg)
    'Profit'.clear()
  } finally {
    global.stopProgress()
  }

})

'product'.destination(async (profit, productRef) => {
  let id = productRef.id; if ( ! id ) return null
  let p = await 'products'.bringFirst({id: id})
  return p
})

