'helper'.trashAll('*')
'helper'.clearWCStockLevels()
'helper'.clearWCOrders()
'helper'.simulateBrowserRefresh()
'helper'.setWCStockLevel('Hoodie (H01)', 10)
'helper'.flushCache()
'helper'.simulateBrowserRefresh()
'Inventory'.click()
  'mainTable./Hoodie (H01)/.Quantity On Hand'.shouldEqual('10')
  'mainTable./Hoodie (H01)/.Adjust Value'.click()
    'Avg Unit Cost'.enter('20.00')
    'OK'.click()
  'mainTable./Hoodie (H01)/.Inventory by Location'.click()
    'mainTable'.shouldHaveCount(2) // 1 for total, one for General
    'Back'.click()
  'Back'.click()
'helper'.trashAll('Cluster')
'helper'.trashAll('Inventory')
'helper'.trashAll('Transaction')
'helper'.trashAll('Location')
'helper'.trashAll('Facet')
'helper'.trashAll('Template')
'helper'.clearWCStockLevels()
'stocktend_settings'.click()
  'Short Date Format'.enter('dd/mm/yyyy')
  'Average Costing Algorithm'.shouldEqual('Dynamic Refresh')
  'Use Database Subselects Instead of Joins'.enter('Yes')
  'OK'.click()
'Inventory'.click()
  'mainTable./Bóónie (BOO100)/.Adjust Qty'.click()
    'Quantity Change'.enter('5')
    'Unit Price (Inc Tax)'.enter('12.3457')
    'OK'.click()
  'Back'.click()
'helper'.simulateBrowserRefresh()
'Inventory'.click()
  'mainTable./Bóónie (BOO100)/.Adjust Qty'.click()
    'Quantity Change'.enter('5')
    'Unit Price (Inc Tax)'.enter('12.3455')
    'OK'.click()
  'mainTable./Bóónie (BOO100)/.Quantity On Hand'.shouldEqual('10')
  'mainTable./Bóónie (BOO100)/.Avg Unit Cost'.shouldEqual('12.3456')
  'Back'.click()
'stocktend_settings'.click()
'Utilities'.click()
'Utilities'.shouldBeCurrentPage()
'Import Unit Costs'.click()
'Import Unit Costs'.shouldBeCurrentPage()
'Go'.click()
'messageDialogText'.shouldExist('doc')
'messageDialogText'.shouldEqual("Please select an attribute to import from", 'doc')
'messageDialogOK'.click('doc')
'WC Product Attribute to import into Profitori Avg Unit Costs'.clickDropdown()
'WC Product Attribute to import into Profitori Avg Unit Costs'.shouldHaveOption('WC Product Attribute.my-unit-cost')
'WC Product Attribute to import into Profitori Avg Unit Costs'.chooseOption('WC Product Attribute.my-unit-cost')
'Go'.click()
'messageDialogText'.shouldExist('doc')
'messageDialogText'.shouldEqual("WARNING: This will update all Profitori average unit costs. Are you sure?", 'doc')
'messageDialogYes'.click('doc')
'messageDialogText'.shouldExist('doc') //'messageDialogText'.shouldEqual("Process complete - average unit costs have been updated")
'messageDialogOK'.click('doc')
'Back'.click()
'Back'.click()
'Back'.click()
'Inventory'.click()
'mainTable./Bóónie (BOO100)/.Quantity On Hand'.shouldEqual('10')
'mainTable./Bóónie (BOO100)/.Avg Unit Cost'.shouldEqual('3.212121')
'Back'.click()
'helper'.trashAll('Inventory')
'helper'.trashAll('Transaction')
'helper'.simulateBrowserRefresh()
'stocktend_settings'.click()
'Default Tax %'.enter('10')
'OK'.click()
'stocktend_settings'.click()
'Default Tax %'.shouldEqual('10.00')
'Back'.click()
'Inventory'.click()
  'mainTable./Belt (#33)/.Quantity On Hand'.shouldEqual('0')
  'mainTable./Belt (#33)/.Avg Unit Cost'.shouldEqual('0.00')
  'mainTable./Belt (#33)/.Inventory Value'.shouldEqual('0.00')
  'Customize'.click()
    'Add Field'.click()
      'Get Value From'.enter('Inventory.inventoryValueExclTax')
      'Caption'.enter('Inventory Value Excl Tax')
      'Display As'.enter('Number')
      'OK'.click()
    'OK'.click()
  'mainTable./Belt (#33)/.Inventory Value Excl Tax'.shouldEqual('0.00')
  'Back'.click()
'stocktend_purchaseOrders'.click()
'Add'.click()
'Purchase Order Number'.enter('PO00001')
'Add Line'.click()
'Product'.chooseOption('Shórts - Blue (SB01)')
'Unit Price (Inc Tax)'.enter('23.471379')
'Unit Price (Inc Tax)'.shouldEqual('23.471379')
'Quantity'.enter('3')
'Line Total (Inc Tax)'.shouldEqual('70.41')
'Tax %'.enter('10.00')
'Line Tax'.shouldEqual('6.40')
'OK'.click()
'Order Total (Inc Tax)'.shouldEqual('70.41')
'Tax'.shouldEqual('6.40')
'Add Line'.click()
'Product'.chooseOption('Cap (C01)')
'Unit Price (Inc Tax)'.enter('10')
'Quantity'.enter('60')
'Unit Price (Inc Tax)'.shouldEqual('10.00')
'Line Total (Inc Tax)'.shouldEqual('600.00')
'Tax %'.shouldEqual('10.00')
'Tax %'.enter('5')
'Line Tax'.shouldEqual('28.57')
'OK'.click()
'Order Total (Inc Tax)'.shouldEqual('670.41')
'Tax'.shouldEqual('34.97')
'stocktend_purchaseOrders'.click()
'Add'.click()
'Order Total (Inc Tax)'.shouldEqual('0.00')
'Tax'.shouldEqual('0.00')
'Receive Purchases'.click()
  'mainTable./PO00001/.Enter Receipt'.click()
    'Lines./Shórts - Blue (SB01)/.Edit'.click()
    'Received'.enter('2')
    'OK'.click()
  'OK'.click()
'Inventory'.click()
  'searcher'.enter('s')
  'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('23.471379')
  'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('46.94')
  'searcher'.enter('c')
    'mainTable./Cap (C01)/.Avg Unit Cost'.shouldEqual('10.00')
    'mainTable./Cap (C01)/.Inventory Value'.shouldEqual('600.00')
    'mainTable./Cap (C01)/.Inventory Value Excl Tax'.shouldEqual('545.45')
    'Receive Purchases'.click()
      'mainTable./PO00001/.View Receipts'.click()
      'mainTable.0.Edit'.click()
        'Lines./Shórts - Blue (SB01)/.Edit'.click()
        'Received'.enter('3')
        'OK'.click()
      'OK'.click()
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('23.471379')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('70.41')
'stocktend_purchaseOrders'.click()
'Add'.click()
'Purchase Order Number'.enter('PO00002')
'Add Line'.click()
'Product'.chooseOption('Shórts - Blue (SB01)')
'Unit Price (Inc Tax)'.enter('25.917562')
'Quantity'.enter('7')
'OK'.click()
'Receive Purchases'.click()
'mainTable./PO00002/.Enter Receipt'.click()
'OK'.click()
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('25.183707')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('251.84')
'stocktend_purchaseOrders'.click()
'mainTable./PO00002/.Edit'.click()
  'View Receipts'.click()
    'mainTable.0.Trash'.click()
    'mainTable.0.Really Trash?'.click()
    'Back'.click()
  'Back'.click()
'mainTable./PO00002/.Trash'.click()
'mainTable./PO00002/.Really Trash?'.click()
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('23.471379')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('70.41')
'mainTable./Shórts - Blue (SB01)/.Adjust Qty'.click()
'Unit Price (Inc Tax)'.shouldEqual('23.471379')
'Unit Price (Inc Tax)'.enter('110')
'Quantity Change'.enter('2')
'Line Total (Inc Tax)'.shouldEqual('220.00')
'Tax %'.shouldEqual('10.00')
'Line Tax'.shouldEqual('20.00')
'OK'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('58.082827')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('290.41')
'mainTable./Shórts - Blue (SB01)/.Adjust Qty'.click()
'Unit Price (Inc Tax)'.shouldEqual('58.082827')
'Unit Price (Inc Tax)'.enter('57')
'Quantity Change'.enter('-3')
'OK'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('59.707067')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('119.41')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('2')
'helper'.setWCStockLevel('Shórts - Blue (SB01)', 10)
'helper'.flushCache()
'stocktend_inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('10')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('59.707067')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('597.07')
'mainTable./Shórts - Blue (SB01)/.View History'.click()
'mainTable.0.Quantity'.shouldEqual('8')
'mainTable.0.Balance'.shouldEqual('10')
'mainTable.0.Unit Cost'.shouldEqual('59.707067')
'mainTable.0.Value'.shouldEqual('477.66')
'helper'.trashAll('Inventory')
'stocktend_purchaseOrders'.click()
'helper'.setWCStockLevel('Shórts - Blue (SB01)', 0)
'helper'.flushCache()
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('0')
'stocktend_purchaseOrders'.click()
'helper'.clearInstalledFlag()
'helper'.clearLastPurchaseCosts()
'helper'.clearWCStockLevels()
'helper'.setWCStockLevel('Shórts - Blue (SB01)', 10)
'helper'.flushCache()
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('10')
'mainTable./Shórts - Blue (SB01)/.View History'.click()
'mainTable.0.Quantity'.shouldEqual('10')
'mainTable.0.Balance'.shouldEqual('10')
'mainTable.0.Source'.shouldEqual('Sync to WC')
'mainTable.0.Value'.shouldEqual('Unknown')
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('Unknown')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('Unknown')
'mainTable./Shórts - Blue (SB01)/.Adjust Qty'.click()
'Quantity Change'.enter('1')
'Unit Price (Inc Tax)'.enter('23.45')
'OK'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('11')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('23.45')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('257.95')
'mainTable./Shórts - Blue (SB01)/.Adjust Qty'.click()
'Quantity Change'.enter('-2')
'OK'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('9')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('23.45')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('211.05')
'Location Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.On Hand'.shouldEqual('9')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('23.45')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('211.05')
'Back'.click()
'mainTable./Shórts - Blue (SB01)/.Product'.click()
'Edit Product'.shouldBeCurrentPage()
'Adjust Value'.click()
'Adjust Inventory Value'.shouldBeCurrentPage()
'Product'.shouldEqual('Shórts - Blue (SB01)')
'Inventory Value'.shouldEqual('211.05')
'Quantity On Hand'.shouldEqual('9')
'Avg Unit Cost'.shouldEqual('23.45')
'Avg Unit Cost'.enter('33.00')
'Inventory Value'.shouldEqual('297.00')
'OK'.click()
'Edit Product'.shouldBeCurrentPage()
'Avg Unit Cost'.shouldEqual('33.00')
'Inventory Value'.shouldEqual('297.00')
'OK'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('9')
'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('33.00')
'mainTable./Shórts - Blue (SB01)/.Inventory Value'.shouldEqual('297.00')
'mainTable./Shórts - Blue (SB01)/.View History'.click()
'mainTable.0.Source'.shouldEqual('Value Adjustment')
'mainTable.0.Unit Cost'.shouldEqual('33.00')
'mainTable.0.Quantity'.shouldEqual('9')
'mainTable.0.Balance'.shouldEqual('9')
'mainTable.1.Source'.shouldEqual('Value Adjustment')
'mainTable.1.Unit Cost'.shouldEqual('23.45')
'mainTable.1.Quantity'.shouldEqual('-9')
'mainTable.1.Balance'.shouldEqual('0')
'searcher'.key('v')
'searcher'.key('<Backspace>')
'Inventory'.click()
'mainTable./Belt (#33)/.Avg Unit Cost'.shouldEqual('0.00')
'mainTable./Belt (#33)/.Adjust Qty'.click()
'Quantity Change'.enter('10')
'Unit Price (Inc Tax)'.shouldEqual('0.00')
'Line Total (Inc Tax)'.shouldEqual('0.00')
'Back'.click()
'Yes - Cancel my changes'.click('doc')
'Inventory'.shouldBeCurrentPage()
'mainTable./Belt (#33)/.Product'.click()
'Adjust Value'.click()
'Avg Unit Cost'.enter('12.34')
'OK'.click()
'OK'.click()
'mainTable./Belt (#33)/.Avg Unit Cost'.shouldEqual('12.34')
'mainTable./Belt (#33)/.Adjust Qty'.click()
'Quantity Change'.enter('-1')
'OK'.click()
'mainTable./Belt (#33)/.Avg Unit Cost'.shouldEqual('12.34')
'mainTable./Belt (#33)/.Inventory Value'.shouldEqual('-12.34')
'Extended'.chapter()
'helper'.trashAll('*')
'helper'.simulateBrowserRefresh()
'stocktend_settings'.click()
  'Add PO Shipping Costs To Product Average Costs'.shouldEqual('No')
  'Add PO Shipping Costs To Product Average Costs'.enter('Yes')
  'OK'.click()
'stocktend_purchaseOrders'.click()
  'Add'.click()
    'Purchase Order Number'.enter('PO00001')
    'Add Line'.click()
      'Product'.chooseOption('Shórts - Blue (SB01)')
      'Unit Price (Inc Tax)'.enter('23.471379')
      'Quantity'.enter('3')
      'Tax %'.enter('10.00')
      'OK'.click()
    'Add Line'.click()
      'Product'.chooseOption('Cap (C01)')
      'Unit Price (Inc Tax)'.enter('10')
      'Quantity'.enter('60')
      'Tax %'.enter('5')
      'OK'.click()
    'Add Line'.click()
      'Line Type'.enter('Shipping')
      'Unit Price (Inc Tax)'.enter('100')
      'OK'.click()
    'Add Line'.click()
      'Line Type'.enter('Shipping')
      'Description'.enter('Shipping 2')
      'Unit Price (Inc Tax)'.enter('50')
      'OK'.click()
    'OK'.click()
  'Back'.click()
'Receive Purchases'.click()
  'mainTable./PO00001/.Enter Receipt'.click()
    'Lines./Shórts - Blue (SB01)/.Edit'.click()
    'Received'.enter('2')
    'OK'.click()
  'OK'.click()
'Inventory'.click()
  'searcher'.enter('s')
  'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('25.890734') // shipping = 150 / 62 = 2.419355
  'searcher'.enter('c')
  'mainTable./Cap (C01)/.Avg Unit Cost'.shouldEqual('12.419355')
  'Back'.click()
'Receive Purchases'.click()
  'View All Receipts'.click()
    'mainTable.0.Edit'.click()
      'Lines./Shórts - Blue (SB01)/.Edit'.click()
      'Received'.enter('3')
      'OK'.click()
    'OK'.click()
  'Back'.click()
'Inventory'.click()
  'searcher'.enter('s')
  'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('25.852331') // shipping = 150 / 63 = 2.380952
  'Back'.click()
'Receive Purchases'.click()
  'View All Receipts'.click()
    'mainTable.0.Edit'.click()
      'Lines./Shipping 2/.Edit'.click()
      'Unit Price (Inc Tax)'.enter('1000')
      'OK'.click()
    'OK'.click()
  'Back'.click()
'Inventory'.click()
  'searcher'.enter('s')
  'mainTable./Shórts - Blue (SB01)/.Avg Unit Cost'.shouldEqual('40.931696') // shipping = 1100 / 63 = 17.460318
  'Back'.click()
