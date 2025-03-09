import React from 'react'
import { Container, Row, Col } from 'reactstrap'

'SetAllToLotControlled'.page()
'Set All Products to Lot Controlled'.title()
'Back'.action({act: 'cancel'})
'Start'.action()
'blurb'.field({snippet: true})

/* eslint-disable no-cond-assign */

'Start'.act(async (page) => {

  let doProduct = async p => {
    let inv = await p.toInventory({allowCreate: true})
    inv.lotTracking = 'Lot'
    inv.trackExpiryDates = 'Yes'
  }

  let doIt = async () => {
    let ps = await 'products'.bring()
    let knt = ps.length
    for ( var i = 0; i < ps.length; i++ ) {
      let p = ps[i]
      await doProduct(p)
      global.updateProgress(0.5 * (i / knt))
    }
  }

  global.startProgress({message: "Updating products"})
  try {
    await doIt()
    global.updateProgress(0.5)
    await global.foreman.doSave()
  } finally {
    global.stopProgress()
  }
  page.showMessage('Process Complete')

})

'blurb'.content(
  <Container key="migrate" className="mt-5">
    <Row>
      <Col className="text-center mt-3">
        Click "Start" to commence.  
      </Col>
    </Row>
    <Row>
      <Col className="text-center mt-3">
        This will set all products to use lot numbers with expiry dates.
      </Col>
    </Row>
  </Container>
)
