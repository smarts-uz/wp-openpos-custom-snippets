'helper'.emulateVersion('1.5.1.0')
'helper'.trashAll('POReceipt')
'helper'.trashAll('PO')
'helper'.trashAll('Inventory')
'helper'.trashAll('Cluster')
'helper'.trashAll('Transaction')
'helper'.trashAll('Location')
'helper'.trashAll('Configuration')
'helper'.clearWCStockLevels()
'helper'.setWCStockLevel('Belt (#33)', 10)
'helper'.flushCache()
'helper'.trashAll('Morsel')
'helper'.deoptimizeDatabase()
'helper'.flushCache()
'helper'.banExclusives()
'helper'.simulateBrowserRefresh()
'Inventory'.click()
  'mainTable./Belt (#33)/.Quantity On Hand'.shouldEqual('10')
  'Back'.click()
'stocktend_settings'.click()
  'Short Date Format'.enter('dd/mm/yyyy')
  'Database Optimized'.shouldBeReadOnly()
  'Database Optimized'.shouldEqual('Yes')
  'Log Changes To Stock For Problem Diagnosis'.enter('Yes')
  'OK'.click()
'Inventory'.click()
  'searcher'.enter('s')
  'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('0')
  'mainTable./Shórts - Red (SR01)/.Quantity On Hand'.shouldEqual('0')
  'searcher'.enter('c')
  'mainTable./Cap (C01)/.Quantity On Hand'.shouldEqual('0')
  'Back'.click()
'helper'.clearInstalledFlag()
'helper'.clearWCStockLevels()
'helper'.setWCStockLevel('Shórts - Blue (SB01)', 10)
'helper'.setWCStockLevel('Shórts - Red (SR01)', 5)
'helper'.setWCStockLevel('Cap (C01)', 3)
'helper'.checkWCStockLevel('Shórts - Blue (SB01)', 10)
'Inventory'.click()
  'Back'.click()
'helper'.simulateBrowserRefresh()
'helper'.checkWCStockLevel('Shórts - Blue (SB01)', 10)
'helper'.checkWCStockLevel('Shórts - Red (SR01)', 5)
'helper'.checkWCStockLevel('Cap (C01)', 3)
'Inventory'.click()
  'searcher'.enter('s')
  'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('10')
  'mainTable./Shórts - Red (SR01)/.Quantity On Hand'.shouldEqual('5')
  'searcher'.enter('c')
  'mainTable./Cap (C01)/.Quantity On Hand'.shouldEqual('3')
  'searcher'.enter('s')
  'mainTable./Shórts - Blue (SB01)/.View History'.click()
    'mainTable.0.Eff Date'.shouldEqualExpression('global.todayLocal().toLocalMMMDY()')
    'mainTable.0.Quantity'.shouldEqual('10')
    'mainTable.0.Balance'.shouldEqual('10')
    'mainTable.0.Source'.shouldEqual('Sync to WC')
    'mainTable.0.User'.shouldEqual('admin')
    'Back'.click()
  'Back'.click()
'helper'.emulateVersion('')
'helper'.monitorRetrieves({__trb: 'Yes'})
'helper'.simulateBrowserRefresh()
'Inventory'.click()
  'Location Inventory'.click()
    'Location'.shouldEqual('General')
    'searcher'.enter('s')
    'mainTable./Shórts - Blue (SB01)/.On Hand'.shouldEqual('10')
    'mainTable./Shórts - Red (SR01)/.On Hand'.shouldEqual('5')
    'searcher'.enter('c')
    'mainTable./Cap (C01)/.On Hand'.shouldEqual('3')
    'searcher'.enter('s')
    'Back'.click()
  'searcher'.enter('s')
  'mainTable./Shórts - Red (SR01)/.View History'.click()
    'mainTable.0.Quantity'.shouldEqual('5')
    'mainTable.0.Balance'.shouldEqual('5')
    'mainTable.0.Source'.shouldEqual('Sync to WC')
    'Back'.click()
  'searcher'.enter('c')
  'mainTable./Cap (C01)/.View History'.click()
    'mainTable.0.Quantity'.shouldEqual('3')
    'mainTable.0.Balance'.shouldEqual('3')
    'mainTable.0.Source'.shouldEqual('Sync to WC')
    'Back'.click()
  'Back'.click()
'helper'.checkWCStockLevel('Shórts - Blue (SB01)', 10)
'helper'.checkWCStockLevel('Shórts - Red (SR01)', 5)
'helper'.checkWCStockLevel('Cap (C01)', 3)
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Adjust Qty'.click()
'Quantity Change'.enter('-3')
'OK'.click()
'searcher'.enter('s')
'mainTable./Shórts - Red (SR01)/.Adjust Qty'.click()
'Quantity Change'.enter('13')
'OK'.click()
'helper'.checkWCStockLevel('Shórts - Blue (SB01)', 7)
'helper'.checkWCStockLevel('Shórts - Red (SR01)', 18)
'helper'.checkWCStockLevel('Cap (C01)', 3)
'stocktend_purchaseOrders'.click()
'Add'.click()
'Add Line'.click()
'Product'.chooseOption('Cap (C01)')
'Quantity'.enter('9')
'OK'.click()
'OK'.click()
'Receive Purchases'.click()
'mainTable.1.Enter Receipt'.click()
'OK'.click()
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.View History'.click()
'mainTable'.shouldHaveCountGreaterThan(1)
'helper'.flushCache()
'helper'.checkWCStockLevel('Shórts - Blue (SB01)', 7)
'helper'.checkWCStockLevel('Shórts - Red (SR01)', 18)
'helper'.checkWCStockLevel('Cap (C01)', 12)
'helper'.clearWCOrders()
'helper'.addWCOrder({product: 'Shórts - Blue (SB01)', quantity: 2, unitPrice: 17.65, orderDate: '20/9/2019', status: 'wc-completed'})
'helper'.addWCOrder({product: 'Shórts - Red (SR01)', quantity: 1, unitPrice: 10.00, orderDate: '20/9/2019', status: 'wc-completed'})
'helper'.addWCOrder({product: 'Shórts - Red (SR01)', quantity: 2, unitPrice: 11.00, orderDate: '20/10/2019', status: 'wc-completed'})
'helper'.setWCStockLevel('Shórts - Blue (SB01)', 56)
'helper'.setWCStockLevel('Shórts - Red (SR01)', 2)
'helper'.setWCStockLevel('Cap (C01)', 25)
'helper'.flushCache()
'stocktend_purchaseOrders'.click()
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.Quantity On Hand'.shouldEqual('56')
'mainTable./Shórts - Red (SR01)/.Quantity On Hand'.shouldEqual('2')
'searcher'.enter('c')
'mainTable./Cap (C01)/.Quantity On Hand'.shouldEqual('25')
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.View History'.click()
'mainTable.0.Eff Date'.shouldEqualExpression('global.todayLocal().toLocalMMMDY()')
'mainTable.0.Quantity'.shouldEqual('51')
'mainTable.0.Balance'.shouldEqual('56')
'mainTable.0.Source'.shouldEqual('Sync to WC')
'mainTable./Sale/.Quantity'.shouldEqual('-2')
'mainTable.1.Balance'.shouldEqual('5')
'mainTable'.shouldHaveCountGreaterThan(1)
'Back'.click()
'Location Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.On Hand'.shouldEqual('56')
'mainTable./Shórts - Red (SR01)/.On Hand'.shouldEqual('2')
'searcher'.enter('c')
'mainTable./Cap (C01)/.On Hand'.shouldEqual('25')
'Back'.click()
'searcher'.enter('s')
'mainTable./Shórts - Red (SR01)/.View History'.click()
'mainTable.0.Eff Date'.shouldEqualExpression('global.todayLocal().toLocalMMMDY()')
'mainTable.0.Quantity'.shouldEqual('-13')
'mainTable.0.Balance'.shouldEqual('2')
'mainTable.0.Source'.shouldEqual('Sync to WC')
'Back'.click()
'searcher'.enter('')
'mainTable./Cap (C01)/.View History'.click()
'mainTable.0.Eff Date'.shouldEqualExpression('global.todayLocal().toLocalMMMDY()')
'mainTable.0.Quantity'.shouldEqual('13')
'mainTable.0.Balance'.shouldEqual('25')
'mainTable.0.Source'.shouldEqual('Sync to WC')
'Back'.click()
'mainTable./Cap (C01)/.Adjust Qty'.click()
'Quantity Change'.enter('1')
'OK'.click() // includes save of above Syncs
'mainTable./Cap (C01)/.Quantity On Hand'.shouldEqual('26')
'Inventory'.click()
'searcher'.enter('s')
'mainTable./Shórts - Blue (SB01)/.View History'.click()
'mainTable'.shouldHaveCountGreaterThan(1)
