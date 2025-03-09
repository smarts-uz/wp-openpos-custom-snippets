import React from 'react'
import { Container, Row, Col } from 'reactstrap'
import Link from '../Link.js'
import HomePageLink from '../HomePageLink.js'
import prologo from './images/probanner.png'
'Home'.page({noTitle: true, expose: true})
'blurb'.field({snippet: true})

'blurb'.visibleWhen(() => {
  return global.gApp.specConfigsReady
})

'blurb'.content(
  <Container key="logo" className="mt-5">
    <Row>
      <Col className="text-center mt-3">
        <img className="prfi_pro_logo" src={prologo} alt="prologo"/>
      </Col>
    </Row>
    <Row>
      <Col className="text-center mt-5 mb-3" style={{fontSize: "24px"}}>
        {"The E-Commerce ERP".translate()}
      </Col>
    </Row>
    <Row>
      <Col className="text-center mt-3">
        {
          global.gApp.atumIsActive ? 
            <div>
              <Link menuCaption="Copy ATUM Data" style={{fontSize: "16px", marginTop: "6px"}}>{"Import data from ATUM".translate()}</Link><br/>
              <br/>
            </div>
          :
            null
        }
        <HomePageLink menuCaption="View Short Stock" icon="Eye" menuText="Assess your inventory requirements" />
        <HomePageLink menuCaption="Purchase Orders" icon="PeopleArrows" menuText="Enter purchase orders" />
        <HomePageLink menuCaption="Receive Purchases" icon="Boxes" menuText="Track orders and receive goods" />
        <HomePageLink menuCaption="Work Orders" icon="Tools" menuText="Manage assembly / manufacturing" />
        <HomePageLink menuCaption="Inventory" icon="Warehouse" menuText="Adjust inventory levels and costs" />
        <HomePageLink menuCaption="Location Inventory" icon="Cubes" menuText="Manage inventory by location" />
        <HomePageLink menuCaption="Fulfillment" icon="Truck" menuText="Manage order fulfillment" />
        <HomePageLink menuCaption="Sales and Invoices" icon="FileInvoiceDollar" menuText="View sales and produce invoice PDFs" />
        <HomePageLink menuCaption="View Profits" icon="DollarSign" menuText="View profits" />
        <HomePageLink menuCaption="General Ledger" icon="BalanceScale" menuText="Manage financial accounts" />
        <HomePageLink menuCaption="Dashboard" icon="ChartLine" menuText="Monitor your business with the dashboard" />
        <HomePageLink menuCaption="Stocktake" icon="HandPointRight" menuText="Do stocktakes" />
        <HomePageLink menuCaption="Suppliers" icon="UserFriends" menuText="Enter supplier details" />
        <HomePageLink menuCaption="Customers" icon="ShoppingBasket" menuText="View customer details" />
        <HomePageLink menuCaption="Locations" icon="Cubes" menuText="Manage multiple inventory locations" />
        <HomePageLink menuCaption="Currencies" icon="Coins" menuText="Manage multiple currencies" />
        <HomePageLink menuCaption="Reports" icon="ThList" menuText="View reports" />
        <HomePageLink menuCaption="Search" icon="Search" menuText="Search across all data" />
        <HomePageLink menuCaption="Settings" icon="Cog" menuText="Settings" />
        <HomePageLink menuCaption="Modify Profitori" icon="LightBulb" menuText="Customize Profitori to suit your business" />
        <br/>
        {"and more".translate()}
      </Col>
    </Row>
  </Container>
)

