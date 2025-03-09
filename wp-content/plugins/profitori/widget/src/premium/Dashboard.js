import React from 'react'
import { Button, FormGroup, Spinner, Row, Label } from 'reactstrap'
import logo from '../specs/images/profitori_logo_29x34.png'
import Feature from '../Feature.js'
import Link from '../Link.js'
require('../Globals.js')

let gCurrentDashboard
let gDefaultZoom = 0.8

global.willSunder = () => {
  if ( global.prfiSunder ) return false // already sundered
  let m = global.foreman.doNameToMold('Configuration'); if ( ! m ) return true
  let config = m && m.fastRetrieveSingle()
  return global.no(config.noAutoSunder)
}

export default class Dashboard extends React.Component {

  constructor(aProps) {
    super(aProps)
    this.version = 1
    this.refreshNo = 1
    this.stLevel3Height = 60
    this.customDashboardTitles = []
    this.state = {}
  }

  operation() {
    return this.props.operation
  }

  async refreshCustomDashboardTitles() {
    let res = ['Main Dashboard'.translate()]
    let customDashboards = await 'CustomDashboard'.bring()
    for ( var i = 0; i < customDashboards.length; i++ ) {
      let customDashboard = customDashboards[i]
      res.push(customDashboard.title)
    }
    this.customDashboardTitles = res
  }

  async getAutoRefreshMs() {
    let config = await 'Configuration'.bringFirst(); if ( ! config ) return 60000
    let res = config.drSecs * 1000
    if ( res < 10000 )
      res = 10000
    return res
  }

  async initialise() { 
    try {
      gCurrentDashboard = this
      global.stCurrentDashboard = this
      this.lastOperation = this.operation()
      let p = this.props
      this.forward = p.forward
      this.showMessage = p.showMessage
      this.behaviour = this.operation().behaviour
      this.autoRefreshMs = await this.getAutoRefreshMs()
      this.version++
      if ( global.willSunder() ) {
        if ( ! global.gApp.currentSituation._didSunder ) {
          global.gApp.currentSituation._didSunder = true
          this.sunder()
        }
      } else {
        await global.gApp.harmonize()
        await this.bulkGetServerData()
        await this.refreshCustomDashboardTitles()
      }
      global.prfiExecDeferred(() => this.refreshState(), {noSpinner: true})
      setTimeout(this.refresh.bind(this), this.autoRefreshMs)
    } finally {
      this.initialising = false
    }
  }

  async bulkGetServerData() {
    if ( global.prfiGotDashboardBulkServerData )
      return
    global.startProgress({message: "Initialising Dashboard"})
    try {
      let dts = [
        'CustomDashboard',
        'tax_rates',
        'products',
        'Supplier',
        'orders.RecentOrActive',
        'order_items.RecentOrActive',
        'PO',
        'POLine',
        'POReceiptLine',
        'POReceipt',
        'Avenue',
        'Inventory'
      ]
      for ( var i = 0; i < dts.length; i++ ) {
        let dt = dts[i]
        await dt.bring()
        await global.updateProgress((i + 1) / dts.length)
        await global.wait(100)
      }
    } finally {
      global.stopProgress()
      global.prfiGotDashboardBulkServerData = true
    }
  }

  componentDidMount() {
    this.mounted = true
    this.refreshState()
  }

  componentWillUnmount() {
    this.mounted = false
    if ( gCurrentDashboard === this ) {
      gCurrentDashboard = null
      global.stCurrentDashboard = null
    }
  }

  refreshTileOperations() {
    this.tiles().forAll(t => {
      t.operation = new global.prfiOperationClass(t.attributes.specname, "view", this.specname(), false)
    })
  }

  a() {
    return this.props.attributes
  }

  specname() {
    return this.a().specname
  }

  refreshState(aOptions) {
    if ( ! this.mounted ) return
    let op = global.copyObj(this.operation()) 
    this.setState({ 
      version: this.version, 
      refreshNo: this.refreshNo, 
      operation: op,
      showSpinner: global.prfiShowSpinner
    })
  }

  navigateToReferrer() {
    this.backToReferrer()
  }

  backToReferrer() {
    global.gApp.backToFirstDifferentSpec()
  }

  cancelFunction() {
    return async () => {
      global.gApp.backToFirstDifferentSpec({promptIfUnsaved: true})
    }
  }

  widthPx() {
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
  }

  leftPx() {
    //let wpMenuWidth = global.elementWidth('adminmenuwrap')
    //let res = wpMenuWidth
    let res = 0
    if ( global.runningInsideWordpress )
      res = -20
    return res
  }

  heightPx() {
    let res
    let h = global.elementHeight
    let menuHeight = h('stMenu')
    if ( global.runningInsideWordpress ) {
      let wpbodyHeight = global.elementHeight("wpbody-content")
      if ( wpbodyHeight ) {
        res = wpbodyHeight - this.stLevel3Height
        return res
      }
    }
    let stLevel2Height = h('stLevel2')
    res = stLevel2Height - this.stLevel3Height - menuHeight - 2
    return res
  }

  topPx() {
    let menuHeight = global.elementHeight('stMenu')
    let res
    if ( global.runningInsideWordpress ) {
      res = this.stLevel3Height
      return res
    }
    let stLevel2Top = global.elementTop('stLevel2')
    res = stLevel2Top + this.stLevel3Height + menuHeight + 2
    return res
  }

  arrangeTiles() {
    let tiles = this.tiles()
    let maxHeight = 0
    let left = this.leftPx()
    let top = this.topPx()
    let dashboardWidth = this.widthPx()
    let dashboardHeight = this.heightPx()
    this.dashboardWidth = dashboardWidth
    this.dashboardHeight = dashboardHeight
    for ( var i = 0; i < tiles.length; i++ ) {
      let t = tiles[i]
      let a = t.attributes
      let widthPct = a.widthPct ? a.widthPct : 100
      let heightPct = a.heightPct ? a.heightPct : 100
      t.widthPx = Math.round((widthPct * dashboardWidth) / 100)
      t.heightPx = Math.round((heightPct * dashboardHeight) / 100)
      let right = left + t.widthPx - 1
      if ( global.withinXPct(5, right, dashboardWidth - 1) ) {
        t.widthPx = dashboardWidth - left
        right = left + t.widthPx - 1
      }
      if ( right > dashboardWidth && (left > 0) ) {
        top += maxHeight
        left = this.leftPx()
        maxHeight = t.heightPx
      }
      let bottom = top + t.heightPx - 1
      if ( global.withinXPct(5, bottom, dashboardHeight - 1) ) {
        t.heightPx = dashboardHeight - top
      }
      t.leftPx = left
      t.topPx = top
      left += t.widthPx
      maxHeight = Math.max(t.heightPx, maxHeight)
    }
  }

  getTileElements() {
    let res = []
    if ( this.initialising )
      return res
    this.arrangeTiles()
    this.refreshTileOperations()
    let tiles = this.tiles()
    for ( var i = 0; i < tiles.length; i++ ) {
      let t = tiles[i]
      let el = this.tileToTileElement(t)
      res.push(el)
    }
    return res
  }

  tileToTileElement(aTile) {
    let name = aTile.name
    let a = aTile.attributes
    let res = (
      <TileElement 
        leftPx={aTile.leftPx}
        topPx={aTile.topPx}
        widthPx={aTile.widthPx}
        heightPx={aTile.heightPx}
        key={name} 
        operation={aTile.operation}
        dashboard={this}
        tileName={name} 
        attributes={a} 
        forward={this.props.forward}
        showMessage={this.props.showMessage}
        containerSpecname={this.specname()}
        containerVersion={this.version}
        containerRefreshNo={this.refreshNo}
        containerSituation={this.props.situation}
        title={name}
      />
    )
    return res
  }

  headerButtons() {
    let actions = this.props.attributes.actions
    if ( actions.length === 0 ) return null
    let res = []
    for ( var i = 0; i < actions.length; i++ ) {
      let a = actions[i]
      if ( a.place !== "header" ) continue
      let awf = a.availableWhen
      if ( awf ) {
        let available = awf(this.cast)
        if ( ! available )
          continue
      }
      let f = this.headerActionToFunction(a)
      let key = this.nameToId(a.name)
      let btn = (
        <Button key={key} onClick={f} className="stHeaderButton mr-1">{a.name}</Button>
      )
      res.push(btn)
    }
    return res
  }

  nameToId(aName) {
    return aName.toId()
  }

  tiles() {
    return this.a().tiles
  }

  headerActionToFunction(aAction) {
    let tof = aAction.tagOrFunction
    let specname = aAction.specname
    if ( tof === "cancel" )
      return this.cancelFunction()
    if ( ! tof )
      return this.segue.bind(this, "view", specname)
    let f = tof.bind(this, this, this.cast)
    return f
  }

  async segue(aAct, aSpecname) {
    this.forward(
      new global.prfiOperationClass(aSpecname, aAct, this.props.attributes.specname, false), 
      null, 
      null,
      null
    )
  }

  getTitle() {
    let a = this.props.attributes
    let res = a.title
    let dtf = a.dynamicTitle
    if ( dtf ) {
      let t = dtf(this)
      if ( t )
        res = t
    }
    return res
  }

  spinner() {
    return (
      <Spinner animation="border" style={{float: "left", display: "none", marginTop: "3px"}} className="mr-2" id="stSpinner"/>
    )
  }

  logo() {
    return (
      <img src={logo} alt="logo" style={{width: "29px", height: "34px", float: "left", marginTop: "3px"}} className="prfiLogo"/>
    )
  }

  sunder() {
    let url = window.location + ''
    url = url.stripAfterLast('#')
    url = url + '&sunder=true'
    window.open(url, global.prfiSoftwareName + ' ' + 'Dashboard'.translate(), 'scrollbars=yes,menubar=no,resizable=yes,status=no,toolbar=no')
  }

  async customDashboardTitleToName(aTitle) {
    if ( aTitle === 'Main Dashboard'.translate() )
      return 'MainDashboard.js'
    let customDashboard = await 'CustomDashboard'.bringFirst({title: aTitle}); if ( ! customDashboard ) return ''
    return customDashboard.name
  }

  async onCustomDashboardChange(aValue) {
    //this.setState({customDashboardTitle: aValue})
    let customDashboardName = await this.customDashboardTitleToName(aValue); if ( ! customDashboardName ) return
    let Operation = global.gApp.getOperationClass()
    let op = new Operation(customDashboardName, "view")
    global.gApp.forward(op)
  }

  configure() {
    let Operation = global.gApp.getOperationClass()
    let op = new Operation('DashboardConfig.js', "edit")
    global.gApp.forward(op)
  }

  switchCombo() {
    if ( ! this.state.switching ) return null
    let customDashboardTitle = this.a().title
    if ( customDashboardTitle === "Dashboard" )
      customDashboardTitle = "Main Dashboard".translate()
    let handleClick = event => {
      let el = event.target
      if ( ! global.isFunction(el.select) ) return null
      el.select()
    }
    let Combobox = global.gApp.getComboboxClass()
    return (
      <Combobox 
        data={this.customDashboardTitles}
        className="stDashboardCombo"
        id="Dashboard"
        name="stDashboardCombo"
        value={customDashboardTitle}
        onChange={this.onCustomDashboardChange.bind(this)}
        onClick={handleClick}>
      </Combobox>
    )
  }

  startSwitching() {
    this.setState({switching: true})
  }

  cancelSwitching() {
    this.setState({switching: false})
  }

  switchButton() {
    if ( this.state.switching ) return null
    return (
      <Button key="stDashboardSwitch" onClick={this.startSwitching.bind(this)} className="stHeaderButton mr-1">
        {"Switch Dashboard".translate()}
      </Button>
    )
  }

  cancelSwitchButton() {
    if ( ! this.state.switching ) return null
    return (
      <Button key="stDashboardCancelSwitch" onClick={this.cancelSwitching.bind(this)} className="stHeaderButton mr-1">
        {"Cancel".translate()}
      </Button>
    )
  }

  configureButton() {
    if ( this.state.switching ) return null
    return (
      <Button key="stDashboardConfigure" onClick={this.configure.bind(this)} className="stHeaderButton mr-1">{"Configure".translate()}</Button>
    )
  }

  refreshButton() {
    if ( this.state.switching ) return null
    return (
      <Button key="stDashboardRefresh" id="stDashboardRefreshButton" onClick={this.refresh.bind(this, {manual: true})} 
        className="stHeaderButton mr-1">{"Refresh".translate()}</Button>
    )
  }

  refresh(options) {
    if ( ! this.mounted ) return
    let manual = options && options.manual
    global.prfiExecDeferred(
      async () => {
        await global.gApp.harmonize()
        this.version++
        this.refreshNo++
        this.setState(
          {
            version: this.version,
            refreshNo: this.refreshNo
          }
        )
        if ( ! manual )
          setTimeout(this.refresh.bind(this), this.autoRefreshMs)
      }, 
      {forceSpinner: true, ms: 200}
    )
  }

  headerControls() {
    if ( global.willSunder() ) return null
    let res = (
      <div className="stDashboardHeaderControls">
        {this.refreshButton()}
        {this.switchCombo()}
        {this.switchButton()}
        {this.cancelSwitchButton()}
        {this.configureButton()}
      </div>
    )
    return res
  }

  parseErrorElement() {
    let msg = this.a().parseError; if ( ! msg ) return null
    msg = 'This dashboard is invalid and cannot be viewed until it is reviewed and fixed.'.translate() +
      ' ' + 'Javascript error:'.translate() + ' "' + msg + '"'
    let id = 'stParseError'
    return (
      <div>
        <div>
          <Label for={id} className="stLabel">
            {'Error'.translate()}
          </Label>
        </div>
        <div>
          <Label className="stTextField" name="stParseError" value={msg} id={id}>
            {msg}
          </Label>
        </div>
      </div>
    )
  }

  render() {
    let op = this.operation()
    if ( (! this.initialising) && ((op !== this.lastOperation) || (this.version === 1) )) {
      this.initialising = true
      global.prfiExecDeferred(this.initialise.bind(this))
    }
    let title = this.getTitle()
    let hb = this.headerButtons()
    let hbr = null
    if ( hb )
      hbr = 
        <Row className="headerButtons">
          {hb}
        </Row>
    let sunderLink = null
    if ( (! global.prfiSunder) && (! global.willSunder()) )
      sunderLink = (
        <Link 
          className="stSunderLink"
          onClick={this.sunder.bind(this)}
        >
          {"Show in Separate Window".translate()}
        </Link>
      )
    let style = {height: this.stLevel3Height + "px"}
    if ( global.prfiSunder ) 
      style = {height: (this.stLevel3Height + 32) + "px", marginTop: "-32px"}
    let main
    if ( global.willSunder() ) {
      let text = 'Dashboard is starting in a separate browser window.'.translate() + ' ' +
        'To keep the dashboard in this window in future, alter Settings > "Keep dashboard in main window".'.translate() + ' ' + 
        "If the dashboard doesn't appear, check your browser settings and allow popups from this site.".translate()
      main = (
        <FormGroup className="stDashboardLevel5" key={this.specname()}>
          <div style={{margin: "20px"}}>
            <Label className="stTextField" name="stSunderMsg" value={text} id="stSunderMsg">
              {text}
            </Label>
          </div>
        </FormGroup>
      )
    } else {
      let parseError = this.parseErrorElement()
      main = (
        <FormGroup className="stDashboardLevel5" key={this.specname()}>
          {parseError}
          {this.getTileElements()}
        </FormGroup>
      )
    }
    let res = (
      <FormGroup id="prfiDashboard" className="stLevel3 prfiDashboard" style={style}>
        <div className="pageTitle" id="dashboardPageTitle">
          {this.spinner()}
          {this.logo()}
          <div className="stPageTitleText" id="stPageTitleText">{title}</div>
          {this.headerControls()}
          <div id="stLinks">
            {sunderLink}
          </div>
        </div>
        {hbr}
        <FormGroup className="stDashboardLevel4" id="stLevel4"> 
          {main}
        </FormGroup>
      </FormGroup>
    )
    return res
  }

}

class TileElement extends React.Component {

  constructor(aProps) {
    super(aProps)
    this.state = {}
    this.version = 0
    this.zoom = gDefaultZoom
  }

  refreshState() {
    if ( ! this.mounted ) return
    this.setState({version: this.version})
  }

  componentDidMount() {
    this.mounted = true
  }

  componentWillUnmount() {
    this.mounted = false
  }

  toElement() {
    return document.getElementById(this.getId())
  }

  getId() {
    return this.props.title.toId()
  }

  onScroll() {
    if ( this.anyFillinIsShowing() )
      this.forceUpdate()
  }

  anyFillinIsShowing() {
    return this.idIsShowing("stFillin0") || this.idIsShowing("stFillin19")
  }

  idIsShowing(aId) {
    let div = this.toElement(); if ( ! div ) return true
    let row = document.getElementById(aId); if ( ! row ) return false
    let rowRect = row.getBoundingClientRect()
    let divRect = div.getBoundingClientRect()
    return (
      rowRect.bottom > divRect.top &&
      rowRect.top < divRect.bottom
    )
  }

  render() {
    let id = this.getId()
    let p = this.props
    let widthPx = global.roundToXDecimals(p.widthPx / this.zoom, 4)
    let heightPx = global.roundToXDecimals(p.heightPx / this.zoom, 4)
    let res = (
      <div 
        id={id}
        className="prfiTile" 
        onScroll={this.onScroll.bind(this)}
        style={
          {
            overflowY: "scroll", 
            left: p.leftPx + "px", 
            top: p.topPx + "px", 
            width: widthPx + "px", 
            height: heightPx + "px", 
            transform: "scale(" + this.zoom + ")",
            transformOrigin: "0 0",
            paddingLeft: "14px",
            position: "absolute"
          }
        }
      >
        <Feature 
          operation={p.operation} 
          hideCancelButton={true}
          hideLogo={true}
          hideContactSupport={true}
          forward={p.forward} 
          showMessage={p.showMessage} 
          title={p.title}
          scroller={this}
          tileWidthPx={widthPx}
          inTile={true}
          containerSituation={p.containerSituation}
          provideVeil={true}
          containerRefreshNo={p.containerRefreshNo}
        />
      </div>
    )
    return res
  }

}
