'CommissionReport'.list({withHeader: true})
'Commission Based on Shipments'.title()
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})

'agent'.field({refersTo: 'Agent'})
'fromDate'.field({date: true})
'toDate'.field({date: true})

'agent'.readOnlyWhen(list => {
  return ! global.app.userHasAdminRights
})

'Lines'.manifest()
'Go'.action({place: 'header', spinner: true})
'CommissionLine'.datatype({transient: true})
'agent'.field({refersTo: 'Agent', showAsLink: true})
'product'.field({refersTo: 'products', showAsLink: true})
'shipmentDate'.field({date: true})
'quantityShipped'.field({numeric: true})
'purchasePrice'.field({numeric: true, decimals: 2})
'soldPrice'.field({numeric: true, decimals: 2, hidden: true})
'soldValue'.field({numeric: true, hidden: true})
'adjustedSoldPrice'.field({numeric: true, decimals: 2, caption: 'Sold Price'})
'adjustedSoldValue'.field({numeric: true, hidden: true})
'commission'.field({numeric: true, decimals: 2, showTotal: true})
'order'.field({refersTo: 'orders', showAsLink: true})
'customerId'.field({hidden: true})
'productSku'.field({hidden: true})
'productSupplierCode'.field({hidden: true})
'wcUserName'.field({hidden: true})

'purchasePrice'.columnVisibleWhen((list, cast) => {
  return global.confVal('cty') !== 'Gross Sales'
})

'CommissionReport'.beforeLoading(async list => {
  let agent = await global.getLoggedInAgent(); if ( ! agent ) return
  list.setFieldValue('agent', agent.reference())
})

'Go'.act(async list => {
  let basedOnGrossSales = global.confVal('cty') === 'Gross Sales'
  
  let processAgent = async (shipment, agent) => {
    
    let refreshCommission = line => {
      line.adjustedSoldPrice = line.adjustedSoldValue / line.quantityShipped
      if ( basedOnGrossSales ) {
        line.commission = (line.adjustedSoldValue * pct) / 100
        return
      }
      let margin = line.adjustedSoldPrice - line.purchasePrice
      if ( margin < 0 )
        margin = 0
      if ( line.purchasePrice === global.unknownNumber() )
        line.commission = global.unknownNumber()
      else
        line.commission = (line.quantityShipped * margin * pct) / 100
    }

    let deductionAppliesToLine = async (line, deduction) => {
      if ( deduction.customerIds ) {
        if ( ! deduction.customerIds.split(',').contains(line.customerId + '') )
          return false
      }
      if ( deduction.productSkus ) {
        if ( ! deduction.productSkus.split(',').contains(line.productSku) )
          return false
      }
      if ( deduction.productSupplierCodes ) {
        if ( ! deduction.productSupplierCodes.split(',').contains(line.productSupplierCode) )
          return false
      }
      if ( deduction.agentWCUserNames ) {
        if ( ! deduction.agentWCUserNames.split(',').contains(line.wcUserName) )
          return false
      }
      return true
    }

    let getAffectedLines = async (lines, deduction) => {
      let res = []
      for ( var i = 0; i < lines.length; i++ ) {
        let line = lines[i]
        if ( ! await deductionAppliesToLine(line, deduction) ) continue
        res.push(line)
      }
      return res
    }

    let linesToTotalQuantity = async lines => {
      let res = 0
      for ( var i = 0; i < lines.length; i++ ) {
        let line = lines[i]
        res += line.quantityShipped
      }
      return res
    }

    let doAmountDeduction = async (lines, deduction) => {
      let order = await shipment.toOrder(); if ( ! order ) return
      let affectedLines = await getAffectedLines(lines, deduction)
      let totalQuantity = await linesToTotalQuantity(affectedLines)
      if ( totalQuantity === 0 ) return
      for ( var i = 0; i < affectedLines.length; i++ ) {
        let affectedLine = affectedLines[i]
        if ( affectedLine.quantityShipped === 0 ) continue
        let ratio = affectedLine.quantityShipped / totalQuantity
        let deductionAmount = ratio * deduction.deductionAmount
        if ( affectedLine.quantityShipped < 0 )
          deductionAmount = - deductionAmount
        affectedLine.adjustedSoldValue = affectedLine.adjustedSoldValue - deductionAmount
        if ( affectedLine.quantityShipped > 0 ) {
          if ( affectedLine.adjustedSoldValue < 0 )
            affectedLine.adjustedSoldValue = 0
        } else {
          if ( affectedLine.adjustedSoldValue > 0 )
            affectedLine.adjustedSoldValue = 0
        }
        refreshCommission(affectedLine)
      }
    }

    let doPercentageDeduction = async (lines, deduction) => {
      for ( var i = 0; i < lines.length; i++ ) {
        let line = lines[i]
        if ( line.quantityShipped === 0 ) continue
        if ( ! await deductionAppliesToLine(line, deduction) ) continue
        line.adjustedSoldValue -= (line.soldValue * deduction.deductionPercent) / 100
        refreshCommission(line)
      }
    }

    let doDeduction = async (lines, deduction) => {
      if ( deduction.type === 'Percentage' )
        await doPercentageDeduction(lines, deduction)
      else
        await doAmountDeduction(lines, deduction)
    }

    let doDeductions = async lines => {
      let deductions = await 'Deduction'.bring({active: 'Yes'})
      for ( var i = 0; i < deductions.length; i++ ) {
        let deduction = deductions[i]
        await doDeduction(lines, deduction)
      }
    }

    let shipmentLines = await shipment.toLines()
    let pct = agent.commissionPercent
    let lines = []
    for ( var i = 0; i < shipmentLines.length; i++ ) {
      let shipmentLine = shipmentLines[i]
      if ( shipmentLine.quantityShipped === 0 ) continue
      let product = await shipmentLine.referee('product'); if ( ! product ) continue
      let inventory = await product.toInventory(); if ( ! inventory ) continue
      let orderItem = await shipmentLine.toOrderItem(); if ( ! orderItem ) continue
      let order = await orderItem.toOrder(); if ( ! order ) continue
      let supplier = await inventory.toMainSupplier()
      let line = await 'CommissionLine'.create()
      line.agent = agent.reference()
      line.product = product.reference()
      line.shipmentDate = shipment.shipmentDate
      line.quantityShipped = shipmentLine.quantityShipped
      line.purchasePrice = await inventory.avgUnitCostExclTax
      line.soldPrice = await orderItem.toUnitPriceExclTax()
      line.adjustedSoldPrice = line.soldPrice
      line.soldValue = line.soldPrice * line.quantityShipped
      line.adjustedSoldValue = line.soldValue
      line.order = order.reference()
      line.customerId = order._customer_user
      line.productSku = product._sku
      line.productSupplierCode = supplier && supplier.code
      line.wcUserName = agent.wcUserName
      refreshCommission(line)
      lines.push(line)
    }
    await doDeductions(lines)
  }

  let piecesToAgents = async pieces => {
    let res = []
    for ( var i = 0; i < pieces.length; i++ ) {
      let piece = pieces[i]
      let agent = await piece.referee('agent'); if ( ! agent ) continue
      res.push(agent)
    }
    return res
  }

  let shipmentToAgents = async shipment => {
    let res = []
    let order = await shipment.toOrder(); if ( ! order ) return res
    let pieces = await 'Piece'.bring({order: order})
    if ( pieces.length > 0 )
      return await piecesToAgents(pieces)
    let customer = await order.toCustomer(); if ( ! customer ) return res
    let ties = await 'Tie'.bring({customer: customer})
    for ( var i = 0; i < ties.length; i++ ) {
      let tie = ties[i]
      let agent = await tie.referee('agent'); if ( ! agent ) continue
      res.push(agent)
    }
    return res
  }

  let processShipment = async (shipment, agentRef)  => {
    let agents = await shipmentToAgents(shipment)
    for ( var i = 0; i < agents.length; i++ ) {
      let agent = agents[i]
      if ( agentRef && (agent.id !== agentRef.id) ) continue
      await processAgent(shipment, agent)
    }
  }

  let fromDate = list.getFieldValue('fromDate')
  if ( (! fromDate) || fromDate.isEmptyYMD() ) fromDate = "0000-00-00"
  let toDate = list.getFieldValue('toDate')
  if ( (! toDate) || toDate.isEmptyYMD() ) toDate = "9999-99-99"

  let agentRef = list.getFieldValue('agent')
  if ( (! agentRef) && (! global.app.userHasAdminRights) )
    return
  'CommissionLine'.clear()
  let shipments = await 'SOShipment'.bring()
  for ( var i = 0; i < shipments.length; i++ ) {
    let shipment = shipments[i]
    if ( shipment.shipmentDate < fromDate ) continue
    if ( shipment.shipmentDate > toDate ) continue
    await processShipment(shipment, agentRef)
  }

})

