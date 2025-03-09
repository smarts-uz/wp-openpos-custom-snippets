'MasterSchedule'.page({readOnly: false})
'Schedule Master'.title()
'Back'.action()
'Save'.action()
'Blocks'.action({spec: 'BlockList.js'})
'Processes'.action({spec: 'ProcessList.js'})
'Work Stations'.action({spec: 'StationList.js'})
'Daily Worksheet'.action({spec: 'DailyWorksheet.js'})
'Copy Day'.action({spec: 'CopyDay.js'})

'Copy Day'.act(async page => {
  let schedule = page._schedule
  let date = await schedule.lastClickedDate
  if ( ! date )
    date = global.todayYMD()
  await page.segue('view', 'CopyDay.js', null, date)
})

'Daily Worksheet'.act(async page => {
  let schedule = page._schedule
  let date = await schedule.lastClickedDate
  if ( ! date )
    date = global.todayYMD()
  await page.segue('view', 'DailyWorksheet.js', null, date)
})

'Back'.act(async page => {
  global.app.backToFirstDifferentSpec({promptIfUnsaved: true})
})

'Save'.act(async page => {
  global.showSpinner()
  try {
    let errMsg = await global.foreman.doAtomicSave()
    if ( errMsg ) {
      console.log("Error on saving: " + errMsg)
      global.gApp.showMessage(errMsg)
      return
    }
  } finally {
    global.hideSpinner()
  }
})

'schedule'.schedule({fixedColCaptions: ['Work Station', 'Group', 'Name'], fixedColWidths: [70, 150, 150], colWidth: 175})

/* eslint-disable no-cond-assign */

'schedule'.onHeadingClick(async (i, schedule) => {
  let zoomedInWidth = schedule.colScrollerWidth || 1000
  let zoomedOutWidth = 175
  if ( schedule.colWidth === zoomedInWidth ) 
    schedule.colWidth = zoomedOutWidth
  else
    schedule.colWidth = zoomedInWidth
  schedule.leftmostColIdx = i
  schedule.lastClickedDate = schedule.dates[i]
})

'schedule'.colCaptions(schedule => {
  schedule.dates = []
  let date = global.todayLocal()
  let ymd = global.todayYMD()
  let colCount = global.maxScheduleX + 1
  let res = []
  for ( var i = 0; i < colCount; i++ ) {
    let caption = date.toShortDayName() + ' ' + date.stripAfterLast('/')
    res.push(caption)
    schedule.dates.push(ymd)
    date = date.incDays(1)
    ymd = ymd.incDays(1)
  }
  return res
})

'schedule'.moveDweller(async (id, x, y, tableau, schedule) => {

  let coordsToDateAndHHMM = async (x, y) => {
    let startHHMM = global.decimalHoursToHHMM(global.workDayStartDecimalHours)
    let res = {ymd: global.todayYMD(), hhmm: startHHMM}
    if ( x < 0 )
      return res
    let wholeDays = Math.floor(x)
    res.ymd = res.ymd.incDays(wholeDays)
    let dFrac = x - wholeDays
    let hoursDec = (dFrac * global.workDayHours) + global.workDayStartDecimalHours
    res.hhmm = global.decimalHoursToHHMM(hoursDec)
    return res
  }

  let yToStation = y => {
    let rows = tableau.rows; if ( ! rows ) return
    for ( var i = 0; i < rows.length; i++ ) {
      let row = rows[i]
      if ( row.y === y )
        return row.object
    }
  }

  let shuntWop = async (wop, prevWop) => {
    let station = await wop.referee('station'); if ( ! station ) return;
    let dtObj = await schedule.stationToNextAvailableStartDateAndHHMM(station, prevWop, {ignoreWop: wop}); if ( ! dtObj ) return
    if ( global.dateAndHHMMGreaterThan({ymd: wop.scheduledStartDate, hhmm: wop.scheduledStartHHMM}, dtObj) )
      return false
    wop.scheduledStartDate = dtObj.ymd
    wop.scheduledStartHHMM = dtObj.hhmm
    return true
  }

  let shuntLaterProcesses = async thisWop => {
    let wo = await thisWop.referee('workOrder'); if ( ! wo ) return
    let wop; let wops = await 'WOProcess'.bringChildrenOf(wo)
    wops.sort((wop1, wop2) => {
      return global.sortCompare(wop1.sequence, wop2.sequence)
    })
    let foundThis = false
    let prevWop = thisWop
    while ( wop = wops.__() ) {
      if ( ! foundThis ) {
        if ( wop === thisWop )
          foundThis = true
        continue
      }
      let didShunt = await shuntWop(wop, prevWop)
      if ( ! didShunt )
        break
      prevWop = wop
    }
  }

  let woProcess = await 'WOProcess'.bringSingle({id: id}); if ( ! woProcess ) return 
  let process = await woProcess.referee('process'); if ( ! process ) return
  let station = yToStation(y)
  if ( station && (! await station.supportsProcess(process)) )
    station = null
  let dt = await coordsToDateAndHHMM(x, y)
  woProcess.scheduledStartDate = dt.ymd
  woProcess.scheduledStartHHMM = dt.hhmm
  if ( station )
    woProcess.station = station.reference()
  await shuntLaterProcesses(woProcess)
})

'schedule'.method('wopToX', async function(wop) {
  let dateHHMM = {ymd: wop.scheduledStartDate, hhmm: wop.scheduledStartHHMM}
  if ( ! dateHHMM.ymd ) return 0
  let daysInt = dateHHMM.ymd.dateSubtract(global.todayYMD())
  let slotHoursInDay = global.workDayHours
  let hoursNum = this.hhMMToHHNum(dateHHMM.hhmm)
  let res = daysInt + (hoursNum / slotHoursInDay)
  return res
})

'schedule'.method('hhMMToHHNum', function(hhMM) {
  if ( ! hhMM )
    hhMM = '00:00'
  let arr = hhMM.split(':')
  let hhInt = parseInt(arr[0])
  let mmInt = parseInt(arr[1])
  let hours = hhInt + (mmInt / 60)
  let res = hours - global.workDayStartDecimalHours
  return res
})
    
'schedule'.method('slotIsFullyBeforeWopStart', function(wop, slot) {
  if ( slot.slotDate < wop.scheduledStartDate )
    return true
  if ( slot.slotDate > wop.scheduledStartDate )
    return false
  if ( slot.finishHHMM < wop.scheduledStartHHMM )
    return true
  return false
})

'schedule'.method('wopToFinishX', async function(wop) {
  let station = await wop.referee('station'); 
  if ( ! station ) 
    return (await this.wopToX(wop)) + 1
  let slot; let slots = await station.getSlotsInChronOrder()
  // consume slots including and after the start slot
  let hoursRemaining = await wop.getHours()
  let finishSlot
  let slotHours
  let hoursIntoFinishSlot = 0
  let lastSlot
  let isFirstSlot = true
  let waitHours = 0
  while ( slot = slots.__() ) {
    if ( ! (hoursRemaining > 0) )
      break;
    let slotIsBeforeWop = this.slotIsFullyBeforeWopStart(wop, slot)
    if ( slotIsBeforeWop )
      continue
    lastSlot = slot
    waitHours = 0
    if ( isFirstSlot && (wop.scheduledStartDate === slot.slotDate) ) {
      waitHours = global.subtractHHMM(wop.scheduledStartHHMM, slot.startHHMM)
      if ( waitHours < 0 )
        waitHours = 0
    }
    isFirstSlot = false
    slotHours = await slot.getHours() - waitHours
    if ( slotHours >= hoursRemaining ) {
      finishSlot = slot
      hoursIntoFinishSlot = hoursRemaining + waitHours
      hoursRemaining = 0
      break
    }
    hoursRemaining -= slotHours
  }
  if ( hoursRemaining > 0 ) {
    finishSlot = lastSlot
    hoursIntoFinishSlot = slotHours + waitHours
  }
  if ( ! finishSlot )
    return (await this.wopToX(wop)) + 1
  let ymd = finishSlot.slotDate
  let daysInt = ymd.dateSubtract(global.todayYMD())
  let finishSlotStartDecimalHours = global.subtractHHMM(finishSlot.startHHMM, '00:00')
  let hoursIntoWorkDay = hoursIntoFinishSlot + finishSlotStartDecimalHours - global.workDayStartDecimalHours
  return daysInt + (hoursIntoWorkDay / global.workDayHours)
})

'schedule'.method('xToDateAndHMMM', function(x) {

  let fractionOfWorkDayToHHMM = dFrac => {
    let hBase = global.workDayStartDecimalHours
    let hours = dFrac * global.workDayHours
    let h = hBase + hours
    let intHH = Math.floor(h)
    let hFrac = h - intHH
    let intMM = Math.floor(hFrac * 60)
    let hh = global.padWithZeroes(intHH, 2)
    let mm = global.padWithZeroes(intMM, 2)
    let res = hh + ':' + mm
    return res
  }

  let intX = Math.floor(x)
  let decX = x - intX
  let ymd = global.todayYMD().incDays(intX)
  let hhmm = fractionOfWorkDayToHHMM(decX)
  return {ymd: ymd, hhmm: hhmm}
})

'schedule'.method('wopToFinishDateAndHHMM', async function(wop) {
  let x = await this.wopToFinishX(wop)
  let res = this.xToDateAndHMMM(x)
  return res
})

'schedule'.method('stationToNextAvailableStartDateAndHHMM', async function(station, prevWop, opt) {
  let ignoreWop = opt && opt.ignoreWop
  let minStartDateAndHHMM
  if ( prevWop )
    minStartDateAndHHMM = await this.wopToFinishDateAndHHMM(prevWop)
  let firstSlot = await station.toToFirstSlot({finishingAfterDateAndHHMM: minStartDateAndHHMM}); if ( ! firstSlot ) return null
  let res = {ymd: firstSlot.slotDate, hhmm: firstSlot.startHHMM} 
  if ( global.dateAndHHMMGreaterThanOrEqual(minStartDateAndHHMM, res) ) {
    res = minStartDateAndHHMM
  }
  let latestWopForStation = await station.toLatestWOProcess({ignoreWop: ignoreWop}); if ( ! latestWopForStation ) return res
  let pres = await this.wopToFinishDateAndHHMM(latestWopForStation)
  if ( pres ) {
    if ( global.dateAndHHMMGreaterThanOrEqual(pres, res) ) {
      res = pres
    }
  }
  return res
})

'schedule'.refreshTableau(async schedule => {

  let calcWorkDayHours = async () => {
    if ( global.confVal('s24') === 'Yes' ) {
      global.workDayHours = 24
      global.workDayStartDecimalHours = 0
      return
    }
    let slot; let slots = await 'Slot'.bring()
    let minStartHHMM = '12:00'
    let maxFinishHHMM = '13:00'
    while ( slot = slots.__() ) {
      if ( slot.slotDate < global.todayYMD() ) continue
      if ( slot.startHHMM < minStartHHMM )
        minStartHHMM = slot.startHHMM
      if ( slot.finishHHMM > maxFinishHHMM )
        maxFinishHHMM = slot.finishHHMM
    }
    let start = global.subtractHHMM(minStartHHMM, '00:00')
    let finish = global.subtractHHMM(maxFinishHHMM, '00:00')
    global.workDayHours = finish - start
    global.workDayStartDecimalHours = start
  }

  let tableau = {}
  await calcWorkDayHours()
  global.maxScheduleX = 27; // 28 days on schedule

  let allocateWOProcessToStation = async (wop, prevWop) => {
    let process = await wop.referee('process'); if ( ! process ) return
    let stationProcess = await 'StationProcess'.bringFirst({process: process}); if ( ! stationProcess ) return
    let station = await stationProcess.referee('station'); if ( ! station ) return;
    let dtObj = await schedule.stationToNextAvailableStartDateAndHHMM(station, prevWop); if ( ! dtObj ) return
    wop.scheduledStartDate = dtObj.ymd
    wop.scheduledStartHHMM = dtObj.hhmm
    wop.station = station.reference()
  }

  let slotToX = async slot => {
    let dateHHMM = {ymd: slot.slotDate, hhmm: slot.startHHMM}
    if ( ! dateHHMM.ymd ) return 0
    let daysInt = dateHHMM.ymd.dateSubtract(global.todayYMD())
    let slotHoursInDay = global.workDayHours
    let hoursNum = schedule.hhMMToHHNum(dateHHMM.hhmm)
    return daysInt + (hoursNum / slotHoursInDay)
  }

  let slotToFinishX = async slot => {
    let dateHHMM = {ymd: slot.slotDate, hhmm: slot.finishHHMM}
    if ( ! dateHHMM.ymd ) return 0
    let daysInt = dateHHMM.ymd.dateSubtract(global.todayYMD())
    let slotHoursInDay = global.workDayHours
    let hoursNum = schedule.hhMMToHHNum(dateHHMM.hhmm)
    return daysInt + (hoursNum / slotHoursInDay)
  }

  let blockToX = async block => {
    let dateHHMM = {ymd: block.blockDate, hhmm: block.startHHMM}
    if ( ! dateHHMM.ymd ) return 0
    let daysInt = dateHHMM.ymd.dateSubtract(global.todayYMD())
    let blockHoursInDay = global.workDayHours
    let hoursNum = schedule.hhMMToHHNum(dateHHMM.hhmm)
    return daysInt + (hoursNum / blockHoursInDay)
  } 
    
  let blockToFinishX = async block => {
    let dateHHMM = {ymd: block.blockDate, hhmm: block.finishHHMM}
    if ( ! dateHHMM.ymd ) return 0
    let daysInt = dateHHMM.ymd.dateSubtract(global.todayYMD())
    let blockHoursInDay = global.workDayHours
    let hoursNum = schedule.hhMMToHHNum(dateHHMM.hhmm)
    return daysInt + (hoursNum / blockHoursInDay)
  } 

  let stationToY = station => {
    for ( var i = 0; i < rows.length; i++ ) {
      let row = rows[i]
      if ( row.object === station ) {
        return i
      }
    }
    return 0
  }

  let addProcesses = async (dwellers, wo) => {
    let allocsOccurred
    let wop; let wops = await 'WOProcess'.bring({workOrder: wo})
    wops.sort((wop1, wop2) => {
      return global.sortCompare(wop1.sequence, wop2.sequence)
    })
    let prevWop
    while ( wop = wops.__() ) {
      let process = await wop.referee('process'); if ( ! process ) continue
      let dweller = {}
      dweller.caption = wo.workOrderNumber
      dweller.object = wop
      let station = await wop.referee('station')
      if ( ! station ) {
        await allocateWOProcessToStation(wop, prevWop)
        station = await wop.referee('station'); if ( ! station ) continue
        allocsOccurred = true
      }
      prevWop = wop
      //dweller.x = await dateAndHHMMToX({ymd: wop.scheduledStartDate, hhmm: wop.scheduledStartHHMM})
      dweller.x = await schedule.wopToX(wop)
      dweller.y = stationToY(station)
      let finishX = await schedule.wopToFinishX(wop)
      dweller.w = finishX - dweller.x
      if ( ! dweller.w )
        dweller.w = 1
      dweller.caption = wo.workOrderNumber + ' - ' + process.processName
      let product = await wo.referee('product')
      dweller.toolTipText = wo.workOrderNumber + ' - ' + process.processName + ': ' + 
        product.uniqueName
      dweller.toolTipText2 = wop.scheduledStartHHMM +
        ' (Duration: ' + global.decimalHoursToHHMM(await wop.getHours()) + ')'
      dwellers.push(dweller)
    }
    if ( allocsOccurred )
      await global.foreman.doSave()
  }

  let addSlots = async (station, y, condos) => {
    let slot; let slots = await station.getSlotsInChronOrder()
    while ( slot = slots.__() ) {
      let condo = {}
      condo.object = slot
      condo.x = await slotToX(slot)
      condo.y = y
      let finishX = await slotToFinishX(slot)
      condo.w = finishX - condo.x
      if ( ! condo.w )
        condo.w = 1
      if ( condo.x < 0 )
        continue
      if ( (condo.x + condo.w) > global.maxScheduleX )
        continue
      condo.toolTipText = slot.startHHMM + ' - ' + slot.finishHHMM
      condos.push(condo)
    }
  }

  let addBlocks = async (station, y, exclusions) => {
    let block; let blocks = await station.getBlocksInChronOrder()
    while ( block = blocks.__() ) {
      let exclusion = {}
      exclusion.object = block
      exclusion.x = await blockToX(block)
      exclusion.y = y
      let finishX = await blockToFinishX(block)
      exclusion.w = finishX - exclusion.x
      if ( ! exclusion.w )
        continue
      if ( exclusion.x < 0 )
        continue
      if ( (exclusion.x + exclusion.w) > global.maxScheduleX )
        continue
      exclusion.toolTipText = block.startHHMM + ' - ' + block.finishHHMM
      exclusions.push(exclusion)
    }
  }

  let addCondos = async () => {
    let condos = []
    let station; let stations = await 'Station'.bring()
    let y = 0
    while ( station = stations.__() ) {
      await addSlots(station, y, condos)
      y++
    }
    tableau.condos = condos
  }

  let addExclusions = async () => {
    let exclusions = []
    let station; let stations = await 'Station'.bring()
    let y = 0
    while ( station = stations.__() ) {
      await addBlocks(station, y, exclusions)
      y++
    }
    tableau.exclusions = exclusions
  }

  let addDwellers = async () => {
    let dwellers = []
    let wo; let wos = await 'WO'.bring({status: 'Awaiting Manufacture'})
    while ( wo = wos.__() ) {
      await addProcesses(dwellers, wo)
    }
    tableau.dwellers = dwellers
  }

  let rows = []
  let station; let stations = await 'Station'.bring()
  let y = 0
  while ( station = stations.__() ) {
    let group = await station.referee('stationGroup')
    let groupName = group ? group.groupName : ''
    let row = {}
    row.captions = []
    row.captions.push(station.stationCode)
    row.captions.push(groupName)
    row.captions.push(station.stationName)
    row.object = station
    row.y = y
    y++
    rows.push(row)
  }
  tableau.rows = rows
  
  await addCondos()
  await addExclusions()
  await addDwellers()

  return tableau
})
