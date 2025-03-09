'AllocationList'.list({icon: 'Share'})
'Allocate Inventory to Sales Orders'.title()
'Back'.action({act: 'cancel'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})
'Allocation'.datatype()
'product'.field({showAsLink: true})
'location'.field()
'averageUnitPriceIncTax'.field({caption: 'Average Price'})
'averageUnitPriceExclTax'.field({caption: 'Average Price Excl Tax'})
'quantityPickableLessShipped'.field({caption: 'Quantity Pickable'})
'quantityOnPurchaseOrders'.field({caption: 'Quantity Incoming'})
'nextExpectedDeliveryDate'.field({caption: 'Incoming ETA'})
'quantityRemainingToShip'.field({caption: 'On Sales Orders'})
'allocatedPercent'.field()
'Allocate'.action({place: 'row', act: 'edit', spec: 'AllocationMaint.js'})

'AllocationList'.beforeLoading(async list => {


  let getOrCreateAllocation = async cluster => {
    let product = await cluster.toProduct(); if ( ! product ) return null
    let res = await 'Allocation'.bringSingle({cluster: cluster.reference()})
    if ( res )
      return res
    res = await 'Allocation'.bringOrCreate({cluster: cluster.reference()})
    res.quantityPickableLessShipped = cluster.quantityPickable - cluster.quantityReservedAndShipped
    res.product = product.reference()
    res.location = global.copyObj(cluster.location)
    return res
  }

  let resetAllocations = async () => {
    let allocations = await 'Allocation'.bring()
    for ( var i = 0; i < allocations.length; i++ ) {
      let allocation = allocations[i]
      allocation.quantityRemainingToShip = 0
      let cluster = await allocation.toCluster(); if ( ! cluster ) continue
      allocation.quantityPickableLessShipped = cluster.quantityPickable - cluster.quantityReservedAndShipped
    }
  }

  let trashUnnecessaryAllocations = async () => {
    let allocations = await 'Allocation'.bring()
    for ( var i = 0; i < allocations.length; i++ ) {
      let allocation = allocations[i]
      if ( allocation.quantityRemainingToShip > 0 ) continue
      await allocation.trash()
    }
  }

  let refreshAllocationLines = async allocation => {

    let unflagAllocationLines = async allocation => {
      let lines = await 'AllocationLine'.bring({allocation: allocation.reference()})
      for ( var i = 0; i < lines.length; i++ ) {
        let line = lines[i]
        line._flagged = false
      }
    }

    let trashUnflaggedAllocationLines = async allocation => {
      let lines = await 'AllocationLine'.bring({allocation: allocation.reference()})
      for ( var i = 0; i < lines.length; i++ ) {
        let line = lines[i]
        if ( line._flagged ) continue
        await line.trash()
      }
    }

    await unflagAllocationLines(allocation)
    let soLines = await 'SOLine'.bring({product: allocation.product})
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let so = await soLine.toSO(); if ( ! so ) continue
      if ( so.retired === 'Yes' ) continue
      let soLineRef = soLine.reference()
      let allocationLine = await 'AllocationLine'.bringFirst({allocation: allocation.reference(), soLine: soLineRef})
      if ( ! allocationLine ) {
        allocationLine = await 'AllocationLine'.create({parentCast: allocation},
          {allocation: allocation.reference(), soLine: soLineRef})
      }
      allocationLine.shippingNameAndCompany = so.shippingNameAndCompany
      allocationLine.quantityRemainingToShip = soLine.quantityRemainingToShip
      allocationLine.orderDate = so.orderDate
      allocationLine.unitPriceIncTax = soLine.unitPriceIncTax
      allocationLine._flagged = true
      if ( allocationLine.quantityRemainingToShip <= 0 )
        await allocationLine.trash()
    }
    await trashUnflaggedAllocationLines(allocation)
  }

  let refreshLinesInAllocations = async () => {
    let allocations = await 'Allocation'.bring()
    for ( var i = 0; i < allocations.length; i++ ) {
      let allocation = allocations[i]
      await refreshAllocationLines(allocation)
    }
  }

  await resetAllocations()
  let soLines = await 'SOLine'.bring()
  for ( var i = 0; i < soLines.length; i++ ) {
    let soLine = soLines[i]
    let so = await soLine.toSO(); if ( ! so ) continue
    if ( ! (await so.isActive()) ) continue
    let cluster = await soLine.toCluster(); if ( ! cluster ) continue
    let allocation = await getOrCreateAllocation(cluster)
    allocation.quantityRemainingToShip += soLine.quantityRemainingToShip
  }
  await trashUnnecessaryAllocations()
  await refreshLinesInAllocations()
  await global.foreman.doSave({msgOnException: true})
})

