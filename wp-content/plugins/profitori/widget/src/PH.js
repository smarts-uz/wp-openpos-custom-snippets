import React from 'react'
require('./Globals.js')

var gCellHeight = 36
var gStartClientX
var gStartClientY
var gDragDweller
var gColScrollerLeft
var gPH

/* eslint-disable eqeqeq */

class PHHeading extends React.Component {

  caption() {
    let extColNo = this.props.extColNo
    let headingCaptions = this.props.headingCaptions
    return headingCaptions[extColNo]
  }

  render() {
    return (
      <span 
        className='prfiPHHeading'
        style={{float: 'left', width: this.props.width + 'px', height: gCellHeight + 'px'}}
        onClick={this.props.onClick}
      >
        {this.caption()}
      </span>
    )
  }

}

class PHHeadings extends React.Component {

  headings() {
    let res = []
    let headingCaptions = this.props.headingCaptions
    let p = this.props
    for ( var i = 0; i < headingCaptions.length; i++ ) {
      let width = p.colWidth
      res.push(
        <PHHeading
          key={i}
          extColNo={i}
          headingCaptions={headingCaptions}
          width={width}
          onClick={this.onHeadingClick.bind(this, i)}
        />
      )
    }
    return res
  }

  async onHeadingClick(i) {
    let p = this.props
    let schedule = p.schedule
    await schedule.onHeadingClick(i, schedule)
    gPH.forceUpdate()
  }

  render() {
    let headings = this.headings()
    return (
      <div>{headings}</div>
    )
  }

}

class PHCell extends React.Component {

  render() {
    let p = this.props
    let style = {float: 'left', width: p.width + 'px', height: gCellHeight + 'px'}
    return (
      <div 
        style={style}
        className="prfiPHCell"
      >
        {this.props.caption}
      </div>
    )
  }

}

class PHRowCaptionSet extends React.Component {

  render() {
    let res = []
    let p = this.props
    let captions = p.row.captions
    let w = 0
    let idx = 0
    for ( var i = 0; i < captions.length; i++ ) {
      let caption = captions[i]
      w += p.fixedColWidths[i]
      res.push(
        <PHCell
          index={i}
          key={i}
          caption={caption}
          width={p.fixedColWidths[i]}
        />
      )
      idx++
    }
    res.push(
      <div 
        style={{clear:'both'}}
        key={idx + 1}
      >
      </div>
    )
    let top = (p.row.y + 1) * gCellHeight
    res = (
      <div
        style={{position: 'absolute', top: top + 'px', width: w + 'px', height: gCellHeight + 'px'}}
        className="prfiPHRowCaptionSet"
      >
        {res}
      </div>
    )
    return res
  }
}

class PHRow extends React.Component {

  render() {
    let res = []
    let p = this.props
    let w = p.width
    let top = (p.row.y + 1) * gCellHeight
    let knt = p.colCount
    let idx = 0
    for ( var i = 0; i < knt; i++ ) {
      let caption = ''
      res.push(
        <PHCell
          index={i}
          key={i}
          caption={caption}
          width={p.colWidth}
        />
      )
      idx++
    }
    res.push(
      <div
        style={{clear:'both'}}
        key={idx + 1}
      >
      </div>
    )
    res = (
      <div
        style={{position: 'absolute', top: top + 'px', width: w + 'px', height: gCellHeight + 'px'}}
        className="prfiPHRow"
      >
        {res}
      </div>
    )
    return res
  }
}

class PHRowCaptions extends React.Component {

  render() {
    let res = []
    let p = this.props
    let rows = p.rows
    if ( ! rows )
      return null
    for ( var i = 0; i < rows.length; i++ ) {
      let row = rows[i]
      let key = 'PHRowCaptionSet' + i
      res.push(
        <PHRowCaptionSet
          key={key}
          row={row}
          fixedColWidths={p.fixedColWidths}
        />
      )
    }
    return res
  }

}

class PHColScroller extends React.Component {

  getViewportWidth() {
    return global.elementWidth('stLevel5')
/*
    let res
    if ( global.runningInsideWordpress ) {
      let wpbodyWidth = global.elementWidth("wpbody")
      if ( wpbodyWidth ) { 
        res = wpbodyWidth
        return res
      }
    } 
    let viewportWidth = global.getViewportDimensions().width
    let wpMenuWidth = global.elementWidth('adminmenuwrap') 
    let scrollbarWidth = global.getScrollbarWidth()
    res = viewportWidth - wpMenuWidth - scrollbarWidth
    return res
*/
  }     

  restoreScroll() {
    let el = document.getElementById('prfiPHColScroller'); if ( ! el ) return
    el.scrollLeft = gColScrollerLeft
    gColScrollerLeft = 0
  }

  scrollToLeftmost() {
    let p = this.props
    let schedule = p.schedule
    let left = this.colIdxToLeft(schedule.leftmostColIdx)
    let el = document.getElementById('prfiPHColScroller'); if ( ! el ) return
    el.scrollLeft = left
    schedule.leftmostColIdx = 0
  }

  colIdxToLeft(colIdx) {
    let p = this.props
    let schedule = p.schedule
    return colIdx * schedule.colWidth
  }

  render() {
    let p = this.props
    let schedule = p.schedule
    let rows = p.rows
    if ( ! rows )
      return null
    let bottomBuffer = gCellHeight * 1.5 // needs to be enough for work order hover tip to show
    let hsHeight = ((rows.length + 1) * gCellHeight) + bottomBuffer
    let top = 0
    let left = p.totalFixedColWidth
    let width = this.getViewportWidth() - left
    schedule.colScrollerWidth = width
    if ( gColScrollerLeft )
      global.prfiExecDeferred(this.restoreScroll, {noSpinner: true, ms: 50})
    if ( schedule.leftmostColIdx )
      global.prfiExecDeferred(this.scrollToLeftmost.bind(this), {noSpinner: true, ms: 50})
    let res = (
      <div
        className="prfiPHColScroller"
        id="prfiPHColScroller"
        style={{
          position: 'absolute', 
          top: top + 'px', 
          left: left + 'px', 
          width: width + 'px', 
          height: hsHeight + 'px',
          overflowX: 'scroll',
          overflowY: 'hidden'
        }}
      >
        <PHBody
          key = 'PHBody'
          schedule={p.schedule}
          colWidth={p.colWidth}
          colCount={p.colCount}
          excludeFirstNCols={p.excludeFirstNCols}
          colCaptions = {p.colCaptions}
          rows={rows}
          condos={p.condos}
          exclusions={p.exclusions}
          dwellers={p.dwellers}
          width={p.bodyWidth}
        />
      </div>
    )
    return res
  }

}

class PHBody extends React.Component {

  render() {
    let p = this.props
    let schedule = p.schedule
    let height = gCellHeight * (p.rows.length + 1)
    let res = (
      <div
        className="prfiPHBody"
        style={{
          position: 'absolute',
          width: p.width + 'px',
          height: height
        }}
      > 
        <PHHeadings
          key='PHHeadings'
          headingCaptions={p.colCaptions}
          fixedColWidths={schedule.fixedColWidths}
          colWidth={schedule.colWidth}
          schedule={schedule}
        />
        <PHRows
          key='PHRows'
          colWidth={schedule.colWidth}
          fixedColWidths={schedule.fixedColWidths}
          excludeFirstNCols={p.excludeFirstNCols}
          colCaptions = {p.colCaptions}
          colCount = {p.colCount}
          rows={p.rows}
          width={p.width}
        />
        <PHCondos
          key={'PHCondos'}
          fixedColWidths={schedule.fixedColWidths}
          colWidth={schedule.colWidth}
          condos={p.condos}
          rows={p.rows}
        />
        <PHExclusions
          key={'PHExclusions'}
          fixedColWidths={schedule.fixedColWidths}
          colWidth={schedule.colWidth}
          exclusions={p.exclusions}
          rows={p.rows}
        />
        <PHDwellers
          key={'PHDwellers'}
          fixedColWidths={schedule.fixedColWidths}
          colWidth={schedule.colWidth}
          dwellers={p.dwellers}
          rows={p.rows}
        />
      </div>
    ) 
    return res
  }       

}

class PHRows extends React.Component {

  render() {
    let res = []
    let p = this.props
    let rows = p.rows
    if ( ! rows )
      return null
    for ( var i = 0; i < rows.length; i++ ) {
      let row = rows[i]
      let key = 'PHRow' + i
      res.push(
        <PHRow
          key={key}
          row={row}
          fixedColWidths={p.fixedColWidths}
          width={p.width}
          colWidth={p.colWidth}
          colCount={p.colCount}
        />
      )
    }
/*
    let hsHeight = rows.length * gCellHeight
    let top = gCellHeight // below header cells
    let left = p.totalFixedColWidth
    res = (
      <div
        style={{position: 'absolute', top: top + 'px', left: left + 'px', width: '300px', height: hsHeight + 'px'}}
        className="prfiPHRowHorizontalScroller"
      >
        {res}
      </div>
    )
*/
    return res
  }

}

class PHCondo extends React.Component {

  wToWidth(w) {
    let p = this.props
    let borderWidth = 1
    return w * (p.colWidth - (2 * borderWidth))
  }

  xToLeft(x) {
    let p = this.props
    let leftBorderWidth = 1
    return (x * p.colWidth) + leftBorderWidth
  }

  yToTop(y) {
    return gCellHeight * (y + 1)
  }

  render() {
    let p = this.props
    let condo = p.condo
    let borderWidth = 2
    let width = this.wToWidth(condo.w) //- (borderWidth * 2)
    let left = this.xToLeft(condo.x) //+ borderWidth
    let top = this.yToTop(condo.y) + borderWidth
    let height = gCellHeight - (borderWidth * 2)
    let style = {position: 'absolute', top: top + 'px', left: left + 'px', width: width + 'px', height: height + 'px'}
    return (
      <div 
        id={condo.object.id}
        className="prfiPHCondo"
        style={style}
      >
      </div>
    )
  }

}

class PHExclusion extends React.Component {

  wToWidth(w) {
    let p = this.props
    let borderWidth = 1
    return w * (p.colWidth - (2 * borderWidth))
  }

  xToLeft(x) {
    let p = this.props
    let leftBorderWidth = 1
    return (x * p.colWidth) + leftBorderWidth
    //return this.totalFixedColWidth() + (x * p.colWidth)
  }

  yToTop(y) {
    return gCellHeight * (y + 1)
  }

  render() {
    let p = this.props
    let exclusion = p.exclusion
    let borderWidth = 2
    let width = this.wToWidth(exclusion.w) //- (borderWidth * 2)
    let left = this.xToLeft(exclusion.x) //+ borderWidth
    let top = this.yToTop(exclusion.y) + borderWidth
    let height = gCellHeight - (borderWidth * 2)
    let style = {position: 'absolute', top: top + 'px', left: left + 'px', width: width + 'px', height: height + 'px'}
    return (
      <div 
        id={exclusion.object.id}
        className="prfiPHExclusion"
        style={style}
      >
      </div>
    )
  }

}


class PHCondos extends React.Component {

  render() {
    let res = []
    let p = this.props
    let condos = p.condos
    if ( ! condos )
      return null
    for ( var i = 0; i < condos.length; i++ ) {
      let condo = condos[i]
      let key = 'PHCondo' + i
      res.push(
        <PHCondo
          key={key}
          condo={condo}
          fixedColWidths={p.fixedColWidths}
          colWidth={p.colWidth}
        />
      )
    }
    return res
  }

}

class PHExclusions extends React.Component {
        
  render() {
    let res = []
    let p = this.props
    let exclusions = p.exclusions
    if ( ! exclusions )
      return null
    for ( var i = 0; i < exclusions.length; i++ ) {
      let exclusion = exclusions[i]
      let key = 'PHExclusion' + i
      res.push(
        <PHExclusion
          key={key}
          exclusion={exclusion}
          fixedColWidths={p.fixedColWidths}
          colWidth={p.colWidth}
        />
      )
    }
    return res
  } 

}   

class PHDweller extends React.Component {

  wToWidth(w) {
    let p = this.props
    return w * p.colWidth 
  }

/*
  totalFixedColWidth() {
    let p = this.props
    let fcws = p.fixedColWidths
    let res = 0
    for ( var i = 0; i < fcws.length; i++ ) {
      res += fcws[i]
    }
    return res
  }
*/

  xToLeft(x) {
    let p = this.props
    let cookedX = x
    if ( cookedX < 0 )
      cookedX = 0
    return cookedX * p.colWidth
    //return this.totalFixedColWidth() + (cookedX * p.colWidth)
  }

  yToTop(y) {
    return gCellHeight * (y + 1)
  }

  render() {
    let p = this.props
    let dweller = p.dweller
    let width = this.wToWidth(dweller.w)
    let left = this.xToLeft(dweller.x)
    let top = this.yToTop(dweller.y)
    let style = {position: 'absolute', top: top + 'px', left: left + 'px', width: width + 'px', height: gCellHeight + 'px'}
    return (
      <div 
        className="prfiPHDwellerAndTooltip"
        style={style}
      >
        <div
          id={dweller.object.id}
          className="prfiPHDweller"
          draggable="true"
          style={{height: gCellHeight + 'px'}}
        >
          {dweller.caption}
        </div>
        <div className="prfiTooltiptext">
          {dweller.toolTipText}
          <br/>
          {dweller.toolTipText2}
        </div>
      </div>
    )
  }

}

class PHDwellers extends React.Component {

  render() {
    let res = []
    let p = this.props
    let dwellers = p.dwellers
    if ( ! dwellers )
      return null
    for ( var i = 0; i < dwellers.length; i++ ) {
      let dweller = dwellers[i]
      let key = 'PHDweller' + i
      res.push(
        <PHDweller
          key={key}
          dweller={dweller}
          fixedColWidths={p.fixedColWidths}
          colWidth={p.colWidth}
        />
      )
    }
    return res
  }

}

export default class PH extends React.Component {

  rememberScroll() {
    let el = document.getElementById('prfiPHColScroller'); if ( ! el ) return
    gColScrollerLeft = el.scrollLeft
  }

  async refreshData() {
    if ( global.usingDifferentForeman ) return
    if ( this.refreshingData ) return
    this.refreshingData = true
    global.showSpinner({delayOnMaints: true})
    global.prfiExecDeferred(
      async () => {
        try {
          let p = this.props
          let schedule = p.schedule
          this.rememberScroll()
          this.tableau = await schedule.refreshTableau(schedule)
          global.prfiExecDeferred(() => this.forceUpdate(), {noSpinner: true})
        } finally {
          global.hideSpinner()
          this.refreshingData = false
        }
      }, {showSpinner: false, ms: 10}
    )
  }

  render() {
    gPH = this
    if ( ! this.initialised ) {
      if ( this.initialising )
        return (
          <div>   
            {"Loading...".translate()}
          </div>
        )
      this.initialising = true
      global.prfiExecDeferred(this.initialise.bind(this), {noSpinner: true})
      return (
        <div>   
          {"Loading...".translate()}
        </div>
      )
    }
    let p = this.props
    let newGrid = (p.specname !== this.specname) || (p.manifestName !== this.manifestName) ||
      (p.containerCastId !== this.containerCastId) || (this.forwardNo !== global.forwardCount) || (this.lastVersionNo !== this.versionNo)
    if ( newGrid ) {
      this.lastVersionNo = this.versionNo
      this.specname = p.specname
      this.manifestName = p.manifestName
      this.containerCastId = p.containerCastId
      this.forwardNo = global.forwardCount
      if ( newGrid && (! this.refreshingData) ) {
        this.casts = []
      }
      global.prfiExecDeferred(this.refreshData.bind(this), {noSpinner: true})
    }
    else if ( p.containerVersion && (p.containerVersion !== this.containerVersion) ) {
      this.containerVersion = p.containerVersion
      global.prfiExecDeferred(this.refreshData.bind(this), {noSpinner: true})
    }
    else if ( p.refreshNo && (p.refreshNo !== this.lastRefreshNo) ) {
      this.lastRefreshNo = p.refreshNo
      global.prfiExecDeferred(this.refreshData.bind(this), {noSpinner: true})
    }
    let res = this.createReactElement()
    return res
  }

  async initialise() {
    this.initialised = true
    this.initialising = false
    this.versionNo = 0
    this.forceUpdate()
  }

  getKey() {
    return 'PH' + global.gApp.currentSituation.hash
  }

  getHeadingCaptions() {
    return this.props.schedule.fixedColCaptions
  }

  addDragAndDropHandlers() {
    this.addDragHandlers()
    this.addDropHandlers()
  }

  idToDweller(id) {
    let t = this.tableau; if ( ! t ) return
    let dwellers = t.dwellers; if ( ! dwellers ) return
    for ( var i = 0; i < dwellers.length; i++ ) {
      let dweller = dwellers[i]
      if ( dweller.object && (dweller.object.id == id) )
        return dweller
    }
  }

  addDragHandlers() {
    let self = this

    let dragStart = function(e) {
      e.dataTransfer.setData('text/plain', e.target.id);
      setTimeout(() => {
        e.target.classList.add('prfiHide');
      }, 0);
      gDragDweller = self.idToDweller(e.target.id)
      gStartClientX = e.clientX
      gStartClientY = e.clientY
    }

    let dragEnd = function(e) {
      e.preventDefault();
      e.stopPropagation();
      if ( ! gDragDweller ) return
      let p = self.props
      let schedule = p.schedule
      let x = self.clientXtoNewDwellerX(e.clientX)
      let y = self.clientYtoNewDwellerY(e.clientY)
      schedule.moveDweller(gDragDweller.object.id, x, y, self.tableau, schedule)
      gDragDweller = null
      self.rememberScroll()
      self.tableau = schedule.refreshTableau(schedule).then(() => {
        self.versionNo++
        self.forceUpdate()
      })
    }

    let nonTargetDragEnd = function(e) {
      if ( ! gDragDweller ) return
      gDragDweller = null
      e.preventDefault();
      e.stopPropagation();
      let p = self.props
      let schedule = p.schedule
      this.rememberScroll()
      self.tableau = schedule.refreshTableau(schedule).then(() => {
        self.versionNo++
        self.forceUpdate()
      })
    }

    let els = document.querySelectorAll('.prfiPHDweller');
    els.forEach(el => {
      el.addEventListener('dragstart', dragStart)
      el.addEventListener('dragend', dragEnd)
    })
    window.addEventListener('dragend', nonTargetDragEnd); 
  }

  pixelsToColWidths(pixels) {
    let p = this.props
    let schedule = p.schedule
    let res = pixels / schedule.colWidth
    return res
  }

  clientXtoNewDwellerX(clientX) {
    let deltaClientX = (clientX - gStartClientX) / global.scheduleZoom
    let deltaX = this.pixelsToColWidths(deltaClientX)
    if ( ! gDragDweller ) 
      return deltaX
    return gDragDweller.x + deltaX
  }

  pixelsToDwellerRowCount(pixels) {
    if ( pixels < 0 ) {
      pixels = - pixels
      return - Math.round(pixels / gCellHeight)
    }
    return Math.round(pixels / gCellHeight)
  }

  clientYtoNewDwellerY(clientY) {
    let deltaClientY = clientY - gStartClientY
    let deltaY = this.pixelsToDwellerRowCount(deltaClientY)
    if ( ! gDragDweller ) 
      return deltaY
    return gDragDweller.y + deltaY
  }

  addDropHandlers() {
    //let self = this

    let dragEnter = function(e) {
      e.preventDefault();
      e.stopPropagation();
      e.target.classList.add('drag-over');
    }

    let dragOver = function(e) {
      e.preventDefault();
      e.stopPropagation();
      e.target.classList.add('drag-over');
    }

    let dragLeave = function(e) {
      e.preventDefault();
      e.stopPropagation();
      e.target.classList.remove('drag-over');
    }

    let cells = document.querySelectorAll('.prfiPHRow');
    cells.forEach(cell => {
      cell.addEventListener('dragenter', dragEnter)
      cell.addEventListener('dragover', dragOver);
      cell.addEventListener('dragleave', dragLeave);
      //cell.addEventListener('drop', drop);
    });
  }

  totalFixedColWidth() {
    let p = this.props
    let schedule = p.schedule
    let fcws = schedule.fixedColWidths
    let res = 0
    for ( var i = 0; i < fcws.length; i++ ) {
      res += fcws[i]
    }
    return res
  }

  colCount() {
    let p = this.props
    let schedule = p.schedule
    let colCaptions = schedule.colCaptions(schedule)
    return colCaptions.length
  }

  createReactElement() {
    let p = this.props
    let schedule = p.schedule
    let key = this.getKey()
    let headingCaptions = global.copyObj(schedule.fixedColCaptions)
    let colCaptions = schedule.colCaptions(schedule)
    let fixedColWidths = schedule.fixedColWidths
    let bodyWidth = this.colCount() * schedule.colWidth
    headingCaptions.appendArray(colCaptions)
    let rows = []
    let dwellers = []
    let condos = []
    let exclusions = []
    if ( this.tableau ) {
      rows = this.tableau.rows
      dwellers = this.tableau.dwellers
      condos = this.tableau.condos
      exclusions = this.tableau.exclusions
    }
    global.prfiExecDeferred(this.addDragAndDropHandlers.bind(this), {showSpinner: false, ms: 500})
    return (
      <div>
        <PHRowCaptions
          key={key + 'RowCaptions'}
          fixedColWidths={fixedColWidths}
          rows={rows}
        />
        <PHColScroller
          schedule={schedule}
          key={key + 'ColScroller'}
          colWidth={schedule.colWidth}
          bodyWidth={bodyWidth}
          excludeFirstNCols={fixedColWidths.length}
          colCaptions = {colCaptions}
          rows={rows}
          condos={condos}
          exclusions={exclusions}
          dwellers={dwellers}
          totalFixedColWidth={this.totalFixedColWidth()}
          colCount={this.colCount()}
        />
      </div>
    )
  }

}

